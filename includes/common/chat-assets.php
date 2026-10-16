<?php
/**
 * Chat assets shared by the order edit screen and My Account.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Common;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Chat_Assets' ) ) {
	/**
	 * Chat assets class
	 */
	class DDWCOC_Chat_Assets {
		/**
		 * Enqueue the chat stylesheet.
		 *
		 * @return void
		 */
		public static function ddwcoc_enqueue_style() {
			wp_enqueue_style( 'dashicons' );
			wp_enqueue_style( 'ddwcoc-front-style', DDWCOC_PLUGIN_URL . 'assets/css/front.css', [], filemtime( DDWCOC_PLUGIN_FILE . 'assets/css/front.css' ) );
		}

		/**
		 * Enqueue the chat stylesheet + script.
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 * @return void
		 */
		public static function ddwcoc_enqueue( $ddwcoc_configuration ) {
			self::ddwcoc_enqueue_style();

			wp_enqueue_script( 'ddwcoc-front-script', DDWCOC_PLUGIN_URL . 'assets/js/front.js', [], filemtime( DDWCOC_PLUGIN_FILE . 'assets/js/front.js' ), true );

			wp_localize_script(
				'ddwcoc-front-script',
				'ddwcocChatObj',
				[
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'ddwcoc-chat' ),
					'pollInterval'  => absint( $ddwcoc_configuration['poll_interval'] ) * 1000,
					'i18n'          => [
						'sending'      => esc_html__( 'Sending...', 'devdiggers-order-communication-for-woocommerce' ),
						'send'         => esc_html__( 'Send', 'devdiggers-order-communication-for-woocommerce' ),
						'error'        => esc_html__( 'Message could not be sent. Please try again.', 'devdiggers-order-communication-for-woocommerce' ),
						'open'         => esc_html__( 'Open', 'devdiggers-order-communication-for-woocommerce' ),
						'closed'       => esc_html__( 'Closed', 'devdiggers-order-communication-for-woocommerce' ),
						'close'        => esc_html__( 'Close Conversation', 'devdiggers-order-communication-for-woocommerce' ),
						'reopen'       => esc_html__( 'Reopen Conversation', 'devdiggers-order-communication-for-woocommerce' ),
						'confirmClose' => esc_html__( 'Close this conversation? The customer will not be able to reply until you reopen it.', 'devdiggers-order-communication-for-woocommerce' ),
					],
				]
			);
		}
	}
}
