<?php
/**
 * This file contains all admin-side action hooks for the plugin.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Admin;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Admin_Hooks' ) ) {
	/**
	 * Admin end hook handler class
	 */
	class DDWCOC_Admin_Hooks extends DDWCOC_Admin_Functions {

		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			parent::__construct( $ddwcoc_configuration );

			add_action( 'admin_init', [ $this, 'ddwcoc_register_settings' ] );
			add_action( 'add_meta_boxes', [ $this, 'ddwcoc_add_custom_meta_box' ] );
			add_action( 'admin_menu', [ $this, 'ddwcoc_add_unread_menu_bubble' ], 999 );

			// Messages column on the WooCommerce orders list (HPOS + legacy).
			add_filter( 'manage_woocommerce_page_wc-orders_columns', [ $this, 'ddwcoc_add_orders_column' ] );
			add_filter( 'manage_edit-shop_order_columns', [ $this, 'ddwcoc_add_orders_column' ] );
			add_action( 'manage_woocommerce_page_wc-orders_custom_column', [ $this, 'ddwcoc_render_orders_column' ], 10, 2 );
			add_action( 'manage_shop_order_posts_custom_column', [ $this, 'ddwcoc_render_orders_column' ], 10, 2 );
		}
	}
}
