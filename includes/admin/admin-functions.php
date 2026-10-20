<?php
/**
 * This file contains all admin-side action callback functions for the plugin.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Admin;

use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;
use DDWCOrderCommunication\Includes\Common\DDWCOC_Chat_Assets;
use DDWCOrderCommunication\Templates\DDWCOC_Order_Communication_Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Admin_Functions' ) ) {
	/**
	 * Admin Functions Class
	 */
	class DDWCOC_Admin_Functions {
		/**
		 * Configuration Variable
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
		 * Register settings function
		 *
		 * @return void
		 */
		public function ddwcoc_register_settings() {
			$toggle = [ 'sanitize_callback' => [ $this, 'ddwcoc_sanitize_toggle' ] ];
			$number = [ 'sanitize_callback' => 'absint' ];

			$groups = [
				'ddwcoc-general-configuration-fields' => [
					'_ddwcoc_enabled'              => $toggle,
					'_ddwcoc_allowed_order_status' => [ 'sanitize_callback' => [ $this, 'ddwcoc_sanitize_order_statuses' ] ],
				],
				'ddwcoc-emails-configuration-fields'  => [
					'_ddwcoc_enabled_admin_mail'    => $toggle,
					'_ddwcoc_enabled_customer_mail' => $toggle,
					'_ddwcoc_email_settings'        => [ 'sanitize_callback' => [ $this, 'ddwcoc_sanitize_email_settings' ] ],
				],
				'ddwcoc-chat-configuration-fields'    => [
					'_ddwcoc_sender_font_color'         => [ 'sanitize_callback' => 'sanitize_hex_color' ],
					'_ddwcoc_sender_background_color'   => [ 'sanitize_callback' => 'sanitize_hex_color' ],
					'_ddwcoc_receiver_font_color'       => [ 'sanitize_callback' => 'sanitize_hex_color' ],
					'_ddwcoc_receiver_background_color' => [ 'sanitize_callback' => 'sanitize_hex_color' ],
					'_ddwcoc_poll_interval'             => $number,
				],
			];

			foreach ( $groups as $group => $options ) {
				foreach ( $options as $option => $args ) {
					register_setting( $group, $option, $args );
				}
			}
		}

		/**
		 * Sanitize a checkbox: 'yes' or empty.
		 *
		 * @param mixed $input Input.
		 * @return string
		 */
		public function ddwcoc_sanitize_toggle( $input ) {
			return 'yes' === $input ? 'yes' : '';
		}

		/**
		 * Sanitize the allowed order statuses: only statuses WooCommerce knows.
		 *
		 * @param mixed $input Input.
		 * @return array
		 */
		public function ddwcoc_sanitize_order_statuses( $input ) {
			return array_values( array_intersect( array_map( 'sanitize_key', (array) $input ), array_keys( wc_get_order_statuses() ) ) );
		}

		/**
		 * Sanitize email settings.
		 *
		 * @param mixed $input type => fields.
		 * @return array
		 */
		public function ddwcoc_sanitize_email_settings( $input ) {
			$settings = [];

			foreach ( [ 'store', 'customer' ] as $type ) {
				$fields = isset( $input[ $type ] ) ? (array) $input[ $type ] : [];

				foreach ( [ 'subject', 'heading' ] as $key ) {
					$settings[ $type ][ $key ] = isset( $fields[ $key ] ) ? sanitize_text_field( $fields[ $key ] ) : '';
				}

				$settings[ $type ]['message'] = isset( $fields['message'] ) ? wp_kses_post( $fields['message'] ) : '';
			}

			$recipients = isset( $input['store']['recipient'] ) ? array_filter( array_map( 'sanitize_email', array_map( 'trim', explode( ',', (string) $input['store']['recipient'] ) ) ) ) : [];

			$settings['store']['recipient'] = implode( ', ', array_filter( $recipients, 'is_email' ) );

			return $settings;
		}

		/**
		 * Add custom meta box function
		 *
		 * @param string $screen_id Screen ID.
		 * @return void
		 */
		public function ddwcoc_add_custom_meta_box( $screen_id ) {
			if ( 'woocommerce_page_wc-orders' === $screen_id || 'shop_order' === $screen_id ) {
				add_meta_box( 'ddwcoc-meta-box', esc_html__( 'Order Communication', 'devdiggers-order-communication-for-woocommerce' ), [ $this, 'ddwcoc_add_content_in_order_communication_meta_box' ], $screen_id, 'normal', 'high' );
			}
		}

		/**
		 * Render the conversation in the order meta box.
		 *
		 * @param \WC_Order|\WP_Post $order Order or post.
		 * @return void
		 */
		public function ddwcoc_add_content_in_order_communication_meta_box( $order ) {
			DDWCOC_Chat_Assets::ddwcoc_enqueue( $this->ddwcoc_configuration );
			new DDWCOC_Order_Communication_Template( is_a( $order, 'WC_Order' ) ? $order->get_id() : $order->ID, 'admin' );
		}

		/**
		 * Show the unread count next to the plugin menu.
		 *
		 * @return void
		 */
		public function ddwcoc_add_unread_menu_bubble() {
			global $submenu;

			$unread = DDWCOC_Communication_Helper::ddwcoc_get_total_unread();

			if ( ! $unread || empty( $submenu ) ) {
				return;
			}

			foreach ( $submenu as $parent => $items ) {
				foreach ( $items as $index => $item ) {
					if ( 'ddwcoc-dashboard' === $item[2] ) {
						// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Standard way to add a count bubble.
						$submenu[ $parent ][ $index ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . absint( $unread ) . '</span></span>';
						return;
					}
				}
			}
		}

		/**
		 * Add the Messages column to the orders list.
		 *
		 * @param array $columns Columns.
		 * @return array
		 */
		public function ddwcoc_add_orders_column( $columns ) {
			$new = [];

			foreach ( $columns as $key => $label ) {
				$new[ $key ] = $label;
				if ( 'order_status' === $key ) {
					$new['ddwcoc_messages'] = esc_html__( 'Messages', 'devdiggers-order-communication-for-woocommerce' );
				}
			}

			return isset( $new['ddwcoc_messages'] ) ? $new : $new + [ 'ddwcoc_messages' => esc_html__( 'Messages', 'devdiggers-order-communication-for-woocommerce' ) ];
		}

		/**
		 * Render the Messages column.
		 *
		 * @param string             $column Column.
		 * @param \WC_Order|int|null $order  Order (HPOS) or post ID (legacy).
		 * @return void
		 */
		public function ddwcoc_render_orders_column( $column, $order ) {
			if ( 'ddwcoc_messages' !== $column ) {
				return;
			}

			$order = is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order );
			if ( ! $order ) {
				return;
			}

			// ponytail: one small query per row (20 rows a page); batch it if pages ever get large.
			$counts = DDWCOC_Communication_Helper::ddwcoc_get_order_counts( $order->get_id() );

			if ( ! $counts['total'] ) {
				echo '<span class="ddwcoc-orders-column-empty" aria-hidden="true">&ndash;</span>';
				return;
			}

			printf(
				'<a href="%1$s" class="ddwcoc-orders-column%2$s" title="%3$s"><span class="dashicons dashicons-format-chat"></span>%4$s</a>',
				esc_url( DDWCOC_Communication_Helper::ddwcoc_get_inbox_url( $order->get_id() ) ),
				$counts['unread'] ? ' has-unread' : '',
				/* translators: 1: total messages, 2: unread messages. */
				esc_attr( sprintf( __( '%1$d messages, %2$d unread', 'devdiggers-order-communication-for-woocommerce' ), $counts['total'], $counts['unread'] ) ),
				$counts['unread'] ? '<span class="ddwcoc-unread-count">' . absint( $counts['unread'] ) . '</span>' : absint( $counts['total'] )
			);
		}
	}
}
