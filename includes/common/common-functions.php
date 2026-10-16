<?php
/**
 * This file contains all common functions for the plugin.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Common;

use DDWCOrderCommunication\Includes\DDWCOC_Email_Notification_Handler;
use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;
use DDWCOrderCommunication\Templates\DDWCOC_Order_Communication_Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Common_Functions' ) ) {
	/**
	 * Common functions
	 */
	class DDWCOC_Common_Functions {
		/**
		 * Configuration
		 *
		 * @var array
		 */
		protected $ddwcoc_configuration;

		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			$this->ddwcoc_configuration = $ddwcoc_configuration;
		}

		/**
		 * Add Email Notification function
		 *
		 * @param array $email_classes Email classes.
		 * @return array
		 */
		public function ddwcoc_add_new_email_notification( $email_classes ) {
			$email_classes['WC_Email_DDWCOC_Notification'] = new DDWCOC_Email_Notification_Handler();
			return $email_classes;
		}

		/**
		 * Add Email Notification Action function
		 *
		 * @param array $actions Actions.
		 * @return array
		 */
		public function ddwcoc_add_notification_actions( $actions ) {
			$actions[] = 'ddwcoc_mail';
			return $actions;
		}

		/**
		 * Exclude messages from comment queries that do not ask for them.
		 *
		 * @param array $clauses Query clauses.
		 * @return array
		 */
		public function ddwcoc_exclude_message_comments( $clauses ) {
			if ( false === strpos( $clauses['where'], DDWCOC_Communication_Helper::TYPE ) ) {
				$clauses['where'] .= ( $clauses['where'] ? ' AND ' : '' ) . "comment_type != '" . DDWCOC_Communication_Helper::TYPE . "'";
			}

			return $clauses;
		}

		/**
		 * Exclude messages from comment feeds.
		 *
		 * @param string $where Where clause.
		 * @return string
		 */
		public function ddwcoc_exclude_message_comments_from_feed( $where ) {
			return $where . ( $where ? ' AND ' : '' ) . "comment_type != '" . DDWCOC_Communication_Helper::TYPE . "'";
		}

		/**
		 * Resolve the order and role for a chat request, or send a JSON error.
		 *
		 * Every caller verifies the chat nonce first.
		 *
		 * @param int    $order_id Order ID.
		 * @param string $context  'admin' or 'front'.
		 * @return array [ helper, order, role ]
		 */
		protected function ddwcoc_get_chat_access( $order_id, $context ) {
			$helper = new DDWCOC_Communication_Helper();
			$access = $helper->ddwcoc_get_access( $order_id, $context );

			if ( is_wp_error( $access ) ) {
				wp_send_json_error( [ 'message' => $access->get_error_message() ], 403 );
			}

			return [ $helper, $access[0], $access[1] ];
		}

		/**
		 * Payload with the messages the client has not rendered yet.
		 *
		 * @param DDWCOC_Communication_Helper $helper   Helper.
		 * @param \WC_Order                   $order    Order.
		 * @param string                      $role     Role.
		 * @param int                         $after_id Last message ID the client has.
		 * @return array
		 */
		protected function ddwcoc_get_chat_payload( $helper, $order, $role, $after_id ) {
			$messages = $helper->ddwcoc_get_messages( $order->get_id(), $role, $after_id );
			$html     = '';

			foreach ( $messages as $message ) {
				$html .= DDWCOC_Order_Communication_Template::ddwcoc_get_message_html( $message, $role, $helper );
			}

			if ( $messages ) {
				$helper->ddwcoc_mark_seen( $order, $role );
			}

			return [
				'html'      => $html,
				'lastId'    => $messages ? (int) end( $messages )->comment_ID : $after_id,
				'seenUntil' => $helper->ddwcoc_get_seen_until( $order, $role ),
				'blocked'   => $helper->ddwcoc_get_reply_block_reason( $order, $role ),
				'closed'    => $helper->ddwcoc_is_closed( $order ),
			];
		}

		/**
		 * AJAX: send a message.
		 *
		 * @return void
		 */
		public function ddwcoc_ajax_send_message() {
			check_ajax_referer( 'ddwcoc-chat', 'nonce' );

			$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
			$context  = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'front';
			$after_id = isset( $_POST['after_id'] ) ? absint( wp_unslash( $_POST['after_id'] ) ) : 0;
			$content  = isset( $_POST['message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['message'] ) ) : '';

			list( $helper, $order, $role ) = $this->ddwcoc_get_chat_access( $order_id, $context );

			$result = $helper->ddwcoc_add_message( $order, $content, [ 'sender' => $role ] );

			if ( is_wp_error( $result ) ) {
				wp_send_json_error( [ 'message' => $result->get_error_message() ] );
			}

			wp_send_json_success( $this->ddwcoc_get_chat_payload( $helper, $order, $role, $after_id ) );
		}

		/**
		 * AJAX: fetch new messages (polling).
		 *
		 * @return void
		 */
		public function ddwcoc_ajax_fetch_messages() {
			check_ajax_referer( 'ddwcoc-chat', 'nonce' );

			$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
			$context  = isset( $_POST['context'] ) ? sanitize_key( wp_unslash( $_POST['context'] ) ) : 'front';
			$after_id = isset( $_POST['after_id'] ) ? absint( wp_unslash( $_POST['after_id'] ) ) : 0;

			list( $helper, $order, $role ) = $this->ddwcoc_get_chat_access( $order_id, $context );

			wp_send_json_success( $this->ddwcoc_get_chat_payload( $helper, $order, $role, $after_id ) );
		}

		/**
		 * AJAX: close or reopen a conversation (staff only).
		 *
		 * @return void
		 */
		public function ddwcoc_ajax_set_conversation_status() {
			check_ajax_referer( 'ddwcoc-chat', 'nonce' );

			if ( ! DDWCOC_Communication_Helper::ddwcoc_is_staff() ) {
				wp_send_json_error( [ 'message' => esc_html__( 'You are not allowed to do this.', 'devdiggers-order-communication-for-woocommerce' ) ], 403 );
			}

			$order_id = isset( $_POST['order_id'] ) ? absint( wp_unslash( $_POST['order_id'] ) ) : 0;
			$after_id = isset( $_POST['after_id'] ) ? absint( wp_unslash( $_POST['after_id'] ) ) : 0;
			$closed   = ! empty( $_POST['closed'] );

			list( $helper, $order, $role ) = $this->ddwcoc_get_chat_access( $order_id, 'admin' );

			$helper->ddwcoc_set_closed( $order, $closed );

			wp_send_json_success( $this->ddwcoc_get_chat_payload( $helper, $order, $role, $after_id ) );
		}
	}
}
