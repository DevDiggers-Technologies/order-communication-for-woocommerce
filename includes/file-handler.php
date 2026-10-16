<?php
/**
 * File handler
 *
 * @author DevDiggers
 * @package DevDiggers Order Communication for WooCommerce
 * @version 1.3.0
 */

namespace DDWCOrderCommunication\Includes;

use DDWCOrderCommunication\Includes\Admin;
use DDWCOrderCommunication\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_File_Handler' ) ) {

	/**
	 * File handler class
	 */
	class DDWCOC_File_Handler {

		/**
		 * Email settings: the default wording plus the saved store recipient.
		 *
		 * @return array
		 */
		public static function ddwcoc_get_email_settings() {
			$saved    = (array) get_option( '_ddwcoc_email_settings', [] );
			$defaults = [
				'store'    => [
					'recipient' => get_option( 'admin_email' ),
					'subject'   => esc_html__( '[{site_title}] New message from {customer_name} on order #{order_number}', 'devdiggers-order-communication-for-woocommerce' ),
					'heading'   => esc_html__( 'New customer message', 'devdiggers-order-communication-for-woocommerce' ),
					'message'   => '<p>' . esc_html__( 'Hi there,', 'devdiggers-order-communication-for-woocommerce' ) . '</p><p>' . esc_html__( '{customer_name} sent a new message about order #{order_number}:', 'devdiggers-order-communication-for-woocommerce' ) . '</p>{message}<p>{reply_button}</p>',
				],
				'customer' => [
					'subject' => esc_html__( '[{site_title}] New reply on your order #{order_number}', 'devdiggers-order-communication-for-woocommerce' ),
					'heading' => esc_html__( 'You have a new reply', 'devdiggers-order-communication-for-woocommerce' ),
					'message' => '<p>' . esc_html__( 'Hi {customer_name},', 'devdiggers-order-communication-for-woocommerce' ) . '</p><p>' . esc_html__( 'We have replied to your message about order #{order_number}:', 'devdiggers-order-communication-for-woocommerce' ) . '</p>{message}<p>{reply_button}</p><p>' . esc_html__( 'Thank you for shopping with {site_name}.', 'devdiggers-order-communication-for-woocommerce' ) . '</p>',
				],
			];

			// Free sends this fixed wording; only the store recipient can be changed.
			if ( ! empty( $saved['store']['recipient'] ) && '' !== trim( $saved['store']['recipient'] ) ) {
				$defaults['store']['recipient'] = $saved['store']['recipient'];
			}

			return $defaults;
		}

		/**
		 * Configuration Variable
		 *
		 * @var array
		 */
		protected $ddwcoc_configuration;

		/**
		 * Construct
		 */
		public function __construct() {
			$this->ddwcoc_configuration = $this->ddwcoc_set_globals();

			new Common\DDWCOC_Common_Hooks( $this->ddwcoc_configuration );

			if ( is_admin() ) {
				new DDWCOC_Admin_Dashboard( $this->ddwcoc_configuration );
				new Admin\DDWCOC_Setup_Wizard();
				new Admin\DDWCOC_Admin_Hooks( $this->ddwcoc_configuration );
			} else {
				new Front\DDWCOC_Front_Hooks( $this->ddwcoc_configuration );
			}
		}

		/**
		 * Set globals function
		 *
		 * @return array
		 */
		public function ddwcoc_set_globals() {
			global $ddwcoc_configuration;

			$allowed_order_status      = get_option( '_ddwcoc_allowed_order_status' );
			$sender_font_color         = get_option( '_ddwcoc_sender_font_color' );
			$sender_background_color   = get_option( '_ddwcoc_sender_background_color' );
			$receiver_font_color       = get_option( '_ddwcoc_receiver_font_color' );
			$receiver_background_color = get_option( '_ddwcoc_receiver_background_color' );

			$ddwcoc_configuration = [
				// General settings.
				'enabled'                    => get_option( '_ddwcoc_enabled' ),
				'allowed_order_status'       => ! empty( $allowed_order_status ) ? $allowed_order_status : [],
				'enabled_admin_mail'         => get_option( '_ddwcoc_enabled_admin_mail' ),
				'enabled_customer_mail'      => get_option( '_ddwcoc_enabled_customer_mail' ),

				// Chat settings.
				'sender_font_color'          => ! empty( $sender_font_color ) ? $sender_font_color : '#ffffff',
				'sender_background_color'    => ! empty( $sender_background_color ) ? $sender_background_color : '#0256ff',
				'receiver_font_color'        => ! empty( $receiver_font_color ) ? $receiver_font_color : '#1e293b',
				'receiver_background_color'  => ! empty( $receiver_background_color ) ? $receiver_background_color : '#f1f5f9',
				'poll_interval'              => max( 5, absint( get_option( '_ddwcoc_poll_interval', 15 ) ) ),

				'email_settings'             => self::ddwcoc_get_email_settings(),
			];

			return $ddwcoc_configuration;
		}
	}
}
