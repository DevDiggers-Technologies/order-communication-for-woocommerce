<?php
/**
 * Handles common action and filter hooks for the plugin.
 *
 * @author DevDiggers
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Includes\Common;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Common_Hooks' ) ) {

	/**
	 * Common hooks class
	 */
	class DDWCOC_Common_Hooks extends DDWCOC_Common_Functions {

		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			parent::__construct( $ddwcoc_configuration );

			add_filter( 'woocommerce_email_classes', [ $this, 'ddwcoc_add_new_email_notification' ] );
			add_filter( 'woocommerce_email_actions', [ $this, 'ddwcoc_add_notification_actions' ] );

			// Keep messages out of the WordPress comments screens, widgets and feeds.
			add_filter( 'comments_clauses', [ $this, 'ddwcoc_exclude_message_comments' ] );
			add_filter( 'comment_feed_where', [ $this, 'ddwcoc_exclude_message_comments_from_feed' ] );

			// Chat endpoints serve both the order edit screen and My Account.
			add_action( 'wp_ajax_ddwcoc_send_message', [ $this, 'ddwcoc_ajax_send_message' ] );
			add_action( 'wp_ajax_ddwcoc_fetch_messages', [ $this, 'ddwcoc_ajax_fetch_messages' ] );
			add_action( 'wp_ajax_ddwcoc_set_conversation_status', [ $this, 'ddwcoc_ajax_set_conversation_status' ] );
		}
	}
}
