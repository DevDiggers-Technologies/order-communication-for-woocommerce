<?php
/**
 * This file contains all front-end action hooks for the plugin.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Front;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Front_Hooks' ) ) {
	/**
	 * Front end hooks class
	 */
	class DDWCOC_Front_Hooks extends DDWCOC_Front_Functions {

		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			parent::__construct( $ddwcoc_configuration );

			if ( 'yes' === $this->ddwcoc_configuration['enabled'] ) {
				add_action( 'wp_enqueue_scripts', [ $this, 'ddwcoc_front_scripts' ] );
				add_action( 'woocommerce_order_details_after_order_table', [ $this, 'ddwcoc_add_content_at_view_order_page' ] );
				add_action( 'wp_loaded', [ $this, 'ddwcoc_handle_message_save' ] );

				// Unread replies in the My Account orders list.
				add_filter( 'woocommerce_account_orders_columns', [ $this, 'ddwcoc_add_my_orders_column' ] );
				add_action( 'woocommerce_my_account_my_orders_column_ddwcoc-messages', [ $this, 'ddwcoc_render_my_orders_column' ] );
			}
		}
	}
}
