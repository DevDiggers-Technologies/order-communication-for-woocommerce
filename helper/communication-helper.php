<?php
/**
 * Core message service: every read and write of an order conversation goes through here.
 *
 * Messages are WordPress comments of type `ddwcoc_order_message` attached to the order ID.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Communication_Helper' ) ) {
	/**
	 * Communication Helper class
	 */
	class DDWCOC_Communication_Helper {
		/**
		 * Comment type used for messages.
		 */
		const TYPE = 'ddwcoc_order_message';

		/**
		 * Plugin Configuration array
		 *
		 * @var array
		 */
		protected $ddwcoc_configuration;

		/**
		 * Construct
		 */
		public function __construct() {
			global $ddwcoc_configuration;
			$this->ddwcoc_configuration = $ddwcoc_configuration;
		}

		/**
		 * Whether the current user may act as store staff.
		 *
		 * @return bool
		 */
		public static function ddwcoc_is_staff() {
			return current_user_can( 'manage_woocommerce' ) || current_user_can( 'edit_shop_orders' );
		}

		/**
		 * Admin Inbox URL, optionally opened on one conversation.
		 *
		 * @param int $order_id Order ID.
		 * @return string
		 */
		public static function ddwcoc_get_inbox_url( $order_id = 0 ) {
			return add_query_arg(
				array_filter(
					[
						'page'     => 'ddwcoc-dashboard',
						'menu'     => 'inbox',
						'order_id' => absint( $order_id ),
					]
				),
				admin_url( 'admin.php' )
			);
		}

		/**
		 * Resolve the order and the role the current user has on its conversation.
		 *
		 * @param int    $order_id Order ID.
		 * @param string $context  'admin' when called from the order edit screen, 'front' otherwise.
		 * @return array|\WP_Error [ WC_Order, 'staff'|'customer' ]
		 */
		public function ddwcoc_get_access( $order_id, $context = 'front' ) {
			$order = wc_get_order( absint( $order_id ) );

			if ( ! $order || is_a( $order, 'WC_Order_Refund' ) ) {
				return new \WP_Error( 'ddwcoc_invalid_order', esc_html__( 'Invalid order.', 'devdiggers-order-communication-for-woocommerce' ) );
			}

			if ( 'admin' === $context && self::ddwcoc_is_staff() ) {
				return [ $order, 'staff' ];
			}

			$user_id = get_current_user_id();

			if ( $user_id && (int) $order->get_customer_id() === $user_id ) {
				return [ $order, 'customer' ];
			}

			return new \WP_Error( 'ddwcoc_invalid_order', esc_html__( 'Invalid order.', 'devdiggers-order-communication-for-woocommerce' ) );
		}

		/**
		 * Whether the conversation is closed.
		 *
		 * @param \WC_Order $order Order.
		 * @return bool
		 */
		public function ddwcoc_is_closed( $order ) {
			return 'closed' === $order->get_meta( '_ddwcoc_conversation_status' );
		}

		/**
		 * Open or close a conversation.
		 *
		 * @param \WC_Order $order  Order.
		 * @param bool      $closed Close it.
		 * @return void
		 */
		public function ddwcoc_set_closed( $order, $closed ) {
			if ( $closed ) {
				$order->update_meta_data( '_ddwcoc_conversation_status', 'closed' );
			} else {
				$order->delete_meta_data( '_ddwcoc_conversation_status' );
			}
			$order->save_meta_data();

			/**
			 * Fires after a conversation is closed or reopened.
			 *
			 * @since 1.0.0
			 *
			 * @param \WC_Order $order  Order.
			 * @param bool      $closed Whether the conversation is now closed.
			 */
			do_action( 'ddwcoc_conversation_status_changed', $order, $closed );
		}

		/**
		 * Why the role cannot write on this order right now, or empty string when it can.
		 *
		 * @param \WC_Order $order Order.
		 * @param string    $role  Role.
		 * @return string
		 */
		public function ddwcoc_get_reply_block_reason( $order, $role ) {
			if ( 'staff' === $role ) {
				return '';
			}

			if ( 'yes' !== $this->ddwcoc_configuration['enabled'] ) {
				return esc_html__( 'Order communication is currently disabled.', 'devdiggers-order-communication-for-woocommerce' );
			}

			if ( $this->ddwcoc_is_closed( $order ) ) {
				return esc_html__( 'This conversation has been closed by the store. Contact us if you still need help.', 'devdiggers-order-communication-for-woocommerce' );
			}

			$allowed = $this->ddwcoc_configuration['allowed_order_status'];
			if ( ! empty( $allowed ) && ! in_array( 'wc-' . $order->get_status(), $allowed, true ) ) {
				return esc_html__( 'Messaging is not available for orders with this status.', 'devdiggers-order-communication-for-woocommerce' );
			}

			return '';
		}

		/**
		 * Add a message to an order conversation.
		 *
		 * @param \WC_Order $order   Order.
		 * @param string    $content Message (plain text, sanitized here).
		 * @param array     $args    sender (customer|staff|system), notify.
		 * @return int|\WP_Error Comment ID.
		 */
		public function ddwcoc_add_message( $order, $content, $args = [] ) {
			$args = wp_parse_args(
				$args,
				[
					'sender' => 'customer',
					'notify' => true,
				]
			);

			$content = trim( sanitize_textarea_field( $content ) );

			if ( '' === $content ) {
				return new \WP_Error( 'ddwcoc_empty', esc_html__( 'Message content should not be empty.', 'devdiggers-order-communication-for-woocommerce' ) );
			}

			if ( 'customer' === $args['sender'] ) {
				$reason = $this->ddwcoc_get_reply_block_reason( $order, 'customer' );
				if ( $reason ) {
					return new \WP_Error( 'ddwcoc_blocked', $reason );
				}
			}

			if ( 'system' === $args['sender'] ) {
				$author_name  = get_bloginfo( 'name' );
				$author_email = get_option( 'admin_email' );
				$user_id      = 0;
			} else {
				$user         = wp_get_current_user();
				$author_name  = $user->display_name;
				$author_email = $user->user_email;
				$user_id      = $user->ID;
			}

			$meta = [ '_ddwcoc_sender' => $args['sender'] ];

			if ( 'customer' === $args['sender'] ) {
				$meta['_ddwcoc_message_status'] = 'unread';
			}

			$comment_id = wp_insert_comment(
				[
					'comment_agent'        => 'WooCommerce',
					'comment_approved'     => 1,
					'comment_author'       => $author_name,
					'comment_author_email' => $author_email,
					'comment_content'      => $content,
					'comment_post_ID'      => $order->get_id(),
					'comment_type'         => self::TYPE,
					'user_id'              => $user_id,
					'comment_meta'         => $meta,
				]
			);

			if ( ! $comment_id ) {
				return new \WP_Error( 'ddwcoc_failed', esc_html__( 'There is some issue in sending message right now.', 'devdiggers-order-communication-for-woocommerce' ) );
			}

			if ( 'staff' === $args['sender'] ) {
				$this->ddwcoc_set_customer_messages_status( $order, 'replied' );
			}

			/**
			 * Fires after a message is saved on an order conversation.
			 *
			 * @since 1.0.0
			 *
			 * @param int       $comment_id Message comment ID.
			 * @param \WC_Order $order      Order.
			 * @param array     $args       Message arguments: sender, notify.
			 */
			do_action( 'ddwcoc_message_added', $comment_id, $order, $args );

			if ( $args['notify'] ) {
				$this->ddwcoc_notify( $order, $content, $args['sender'] );
			}

			return $comment_id;
		}

		/**
		 * Get messages of an order visible to a role.
		 *
		 * @param int    $order_id Order ID.
		 * @param string $role     'staff' sees private notes written in Pro, 'customer' never does.
		 * @param int    $after_id Only messages with a higher ID.
		 * @return \WP_Comment[]
		 */
		public function ddwcoc_get_messages( $order_id, $role = 'staff', $after_id = 0 ) {
			$args = [
				'post_id' => $order_id,
				'type'    => self::TYPE,
				'status'  => 'approve',
				'orderby' => 'comment_ID',
				'order'   => 'ASC',
			];

			// Private notes written with Pro stay hidden from customers if the store moves back to Free.
			if ( 'staff' !== $role ) {
				$args['meta_query'] = [ // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					[
						'key'     => '_ddwcoc_private',
						'compare' => 'NOT EXISTS',
					],
				];
			}

			$messages = get_comments( $args );

			if ( $after_id ) {
				// ponytail: threads are short, filtering in PHP beats a custom WHERE clause.
				$messages = array_values(
					array_filter(
						$messages,
						function ( $message ) use ( $after_id ) {
							return (int) $message->comment_ID > $after_id;
						}
					)
				);
			}

			return $messages;
		}

		/**
		 * Who sent a message: customer, staff or system.
		 *
		 * @param \WP_Comment $message Message.
		 * @return string
		 */
		public function ddwcoc_get_sender( $message ) {
			$sender = get_comment_meta( $message->comment_ID, '_ddwcoc_sender', true );
			return $sender ? $sender : 'staff';
		}

		/**
		 * Mark the conversation as seen by a role (drives read receipts and unread counts).
		 *
		 * @param \WC_Order $order Order.
		 * @param string    $role  Role.
		 * @return void
		 */
		public function ddwcoc_mark_seen( $order, $role ) {
			$order->update_meta_data( '_ddwcoc_' . $role . '_last_seen', time() );
			$order->save_meta_data();

			if ( 'staff' === $role ) {
				$this->ddwcoc_set_customer_messages_status( $order, 'read', 'unread' );
			}
		}

		/**
		 * Timestamp up to which the other party has seen the thread.
		 *
		 * @param \WC_Order $order Order.
		 * @param string    $role  Viewer role.
		 * @return int
		 */
		public function ddwcoc_get_seen_until( $order, $role ) {
			return (int) $order->get_meta( '_ddwcoc_' . ( 'staff' === $role ? 'customer' : 'staff' ) . '_last_seen' );
		}

		/**
		 * Unread messages for a role on one order.
		 *
		 * @param \WC_Order $order Order.
		 * @param string    $role  Role.
		 * @return int
		 */
		public function ddwcoc_get_unread_count( $order, $role ) {
			global $wpdb;

			if ( 'staff' === $role ) {
				return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Live message counts across comment meta; caching would show stale counts.
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->comments} c
					INNER JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = '_ddwcoc_message_status' AND m.meta_value = 'unread'
					WHERE c.comment_post_ID = %d AND c.comment_type = %s",
						$order->get_id(),
						self::TYPE
					)
				);
			}

			return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Live message counts across comment meta; caching would show stale counts.
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->comments} c
				INNER JOIN {$wpdb->commentmeta} s ON s.comment_id = c.comment_ID AND s.meta_key = '_ddwcoc_sender' AND s.meta_value IN ( 'staff', 'system' )
				LEFT JOIN {$wpdb->commentmeta} p ON p.comment_id = c.comment_ID AND p.meta_key = '_ddwcoc_private'
				WHERE c.comment_post_ID = %d AND c.comment_type = %s AND p.meta_id IS NULL AND c.comment_date_gmt > %s",
					$order->get_id(),
					self::TYPE,
					gmdate( 'Y-m-d H:i:s', (int) $order->get_meta( '_ddwcoc_customer_last_seen' ) )
				)
			);
		}

		/**
		 * Unread customer messages across all orders (admin menu bubble).
		 *
		 * @return int
		 */
		public static function ddwcoc_get_total_unread() {
			global $wpdb;

			return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Live message counts across comment meta; caching would show stale counts.
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->comments} c
				INNER JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = '_ddwcoc_message_status' AND m.meta_value = 'unread'
				WHERE c.comment_type = %s",
					self::TYPE
				)
			);
		}

		/**
		 * Message count and staff-unread count for one order (orders list column).
		 *
		 * @param int  $order_id     Order ID.
		 * @param bool $skip_private Leave out private notes (customer-facing counts).
		 * @return array{total:int,unread:int}
		 */
		public static function ddwcoc_get_order_counts( $order_id, $skip_private = false ) {
			global $wpdb;

			// A meta_key that never exists keeps the private-note filter in the prepared query.
			$row = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery -- Live message counts across comment meta; caching would show stale counts.
				$wpdb->prepare(
					"SELECT COUNT(*) AS total, SUM( m.meta_value = 'unread' ) AS unread FROM {$wpdb->comments} c
				LEFT JOIN {$wpdb->commentmeta} m ON m.comment_id = c.comment_ID AND m.meta_key = '_ddwcoc_message_status'
				WHERE c.comment_post_ID = %d AND c.comment_type = %s
				AND NOT EXISTS ( SELECT 1 FROM {$wpdb->commentmeta} p WHERE p.comment_id = c.comment_ID AND p.meta_key = %s )",
					$order_id,
					self::TYPE,
					$skip_private ? '_ddwcoc_private' : '_ddwcoc_none'
				)
			);

			return [
				'total'  => $row ? (int) $row->total : 0,
				'unread' => $row ? (int) $row->unread : 0,
			];
		}

		/**
		 * Set the read status of customer messages on an order.
		 *
		 * @param \WC_Order $order Order.
		 * @param string    $status New status: unread, read, replied.
		 * @param string    $only_from Only change messages currently in this status.
		 * @return void
		 */
		public function ddwcoc_set_customer_messages_status( $order, $status, $only_from = '' ) {
			global $wpdb;

			$sql = $wpdb->prepare(
				"UPDATE {$wpdb->commentmeta} m
				INNER JOIN {$wpdb->comments} c ON c.comment_ID = m.comment_id
				SET m.meta_value = %s
				WHERE c.comment_post_ID = %d AND c.comment_type = %s AND m.meta_key = '_ddwcoc_message_status'",
				$status,
				$order->get_id(),
				self::TYPE
			);

			if ( $only_from ) {
				$sql .= $wpdb->prepare( ' AND m.meta_value = %s', $only_from );
			}

			$wpdb->query( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery -- Prepared above; bulk meta update has no API equivalent.

			foreach ( get_comments(
				[
					'post_id' => $order->get_id(),
					'type'    => self::TYPE,
					'fields'  => 'ids',
				]
			) as $comment_id ) {
				wp_cache_delete( $comment_id, 'comment_meta' );
			}
		}

		/**
		 * Replace {placeholders} in a message.
		 *
		 * @param string    $text  Text.
		 * @param \WC_Order $order Order.
		 * @return string
		 */
		public function ddwcoc_replace_placeholders( $text, $order ) {
			$replacements = [
				'{customer_name}' => trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() ),
				'{order_number}'  => $order->get_order_number(),
				'{order_date}'    => $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '',
				'{order_status}'  => wc_get_order_status_name( $order->get_status() ),
				'{order_total}'   => wp_strip_all_tags( html_entity_decode( wc_price( $order->get_total(), [ 'currency' => $order->get_currency() ] ) ) ),
				'{site_name}'     => get_bloginfo( 'name' ),
			];

			/**
			 * Filters the placeholder replacements used in messages and emails.
			 *
			 * @since 1.0.0
			 *
			 * @param array     $replacements Placeholder => value.
			 * @param \WC_Order $order        Order.
			 */
			return strtr( $text, apply_filters( 'ddwcoc_message_placeholders', $replacements, $order ) );
		}

		/**
		 * Email the other party about a new message.
		 *
		 * @param \WC_Order $order       Order.
		 * @param string    $content     Message.
		 * @param string    $sender      Sender.
		 * @return void
		 */
		protected function ddwcoc_notify( $order, $content, $sender ) {
			$to_staff = 'customer' === $sender;

			if ( $to_staff ? 'yes' !== $this->ddwcoc_configuration['enabled_admin_mail'] : 'yes' !== $this->ddwcoc_configuration['enabled_customer_mail'] ) {
				return;
			}

			$url      = $to_staff ? self::ddwcoc_get_inbox_url( $order->get_id() ) : $order->get_view_order_url() . '#ddwcoc-chat';
			$settings = $this->ddwcoc_configuration['email_settings'][ $to_staff ? 'store' : 'customer' ];

			$quote  = '<blockquote style="margin:16px 0;padding:12px 16px;border-left:4px solid #0256ff;background:#f6f8fb;">' . nl2br( esc_html( $content ) ) . '</blockquote>';
			$button = '<a href="' . esc_url( $url ) . '" style="display:inline-block;padding:10px 18px;background:#0256ff;color:#fff;border-radius:6px;text-decoration:none;">' . esc_html__( 'Reply to this message', 'devdiggers-order-communication-for-woocommerce' ) . '</a>';
			$body   = $settings['message'];

			// The chat message must always reach the reader, even if the placeholder was removed.
			if ( false === strpos( $body, '{message}' ) ) {
				$body .= '{message}';
			}

			$body = strtr(
				$this->ddwcoc_replace_placeholders( $body, $order ),
				[
					'{message}'      => $quote,
					'{reply_button}' => $button,
					'{reply_url}'    => esc_url( $url ),
				]
			);

			/**
			 * Sends the new message notification through the WooCommerce mailer.
			 *
			 * @since 1.0.0
			 *
			 * @param array $data Email data: email, subject, heading, name, order, message.
			 */
			do_action(
				'ddwcoc_mail',
				[
					'email'      => $to_staff ? $settings['recipient'] : $order->get_billing_email(),
					'subject'    => $this->ddwcoc_replace_placeholders( $settings['subject'], $order ),
					'heading'    => $this->ddwcoc_replace_placeholders( $settings['heading'], $order ),
					'name'       => $to_staff ? '' : $order->get_billing_first_name(),
					'order'      => $order,
					'message'    => wpautop( $body ),
				]
			);
		}
	}
}
