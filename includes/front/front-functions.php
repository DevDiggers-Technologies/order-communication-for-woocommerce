<?php
/**
 * This file contains all front-end action callback functions for the plugin.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Front;

use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;
use DDWCOrderCommunication\Includes\Common\DDWCOC_Chat_Assets;
use DDWCOrderCommunication\Templates\DDWCOC_Order_Communication_Template;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Front_Functions' ) ) {
	/**
	 * Front functions handler
	 */
	class DDWCOC_Front_Functions {
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
		 * Front scripts enqueue
		 *
		 * @return void
		 */
		public function ddwcoc_front_scripts() {
			if ( is_wc_endpoint_url( 'view-order' ) ) {
				DDWCOC_Chat_Assets::ddwcoc_enqueue( $this->ddwcoc_configuration );
			} elseif ( is_account_page() ) {
				DDWCOC_Chat_Assets::ddwcoc_enqueue_style();
			}
		}

		/**
		 * Add the conversation on the view order page.
		 *
		 * @param \WC_Order $order Order.
		 * @return void
		 */
		public function ddwcoc_add_content_at_view_order_page( $order ) {
			if ( ! is_wc_endpoint_url( 'view-order' ) ) {
				return;
			}
			?>
			<h2 class="ddwcoc-chat-heading"><?php esc_html_e( 'Questions about this order?', 'devdiggers-order-communication-for-woocommerce' ); ?></h2>
			<?php
			new DDWCOC_Order_Communication_Template( $order->get_id() );
		}

		/**
		 * No-JavaScript fallback: the chat form posts to the same page.
		 *
		 * @return void
		 */
		public function ddwcoc_handle_message_save() {
			if ( empty( $_POST['ddwcoc_send_message'] ) || empty( $_POST['ddwcoc-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ddwcoc-nonce'] ) ), 'ddwcoc-nonce-action' ) ) {
				return;
			}

			$order_id = isset( $_POST['ddwcoc_order_id'] ) ? absint( $_POST['ddwcoc_order_id'] ) : 0;

			if ( ! current_user_can( 'view_order', $order_id ) ) {
				wc_add_notice( esc_html__( 'You are not allowed to do this.', 'devdiggers-order-communication-for-woocommerce' ), 'error' );
				return;
			}

			$helper = new DDWCOC_Communication_Helper();
			$access = $helper->ddwcoc_get_access( $order_id );

			if ( is_wp_error( $access ) ) {
				wc_add_notice( $access->get_error_message(), 'error' );
				return;
			}

			$order  = $access[0];
			$result = $helper->ddwcoc_add_message( $order, isset( $_POST['ddwcoc_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['ddwcoc_message'] ) ) : '' );

			if ( is_wp_error( $result ) ) {
				wc_add_notice( $result->get_error_message(), 'error' );
			} else {
				wc_add_notice( esc_html__( 'Message sent successfully.', 'devdiggers-order-communication-for-woocommerce' ) );
			}

			wp_safe_redirect( $order->get_view_order_url() . '#ddwcoc-chat' );
			exit();
		}

		/**
		 * Add a Messages column to the My Account orders table.
		 *
		 * @param array $columns Columns.
		 * @return array
		 */
		public function ddwcoc_add_my_orders_column( $columns ) {
			$position = array_search( 'order-actions', array_keys( $columns ), true );
			$column   = [ 'ddwcoc-messages' => esc_html__( 'Messages', 'devdiggers-order-communication-for-woocommerce' ) ];

			return false === $position ? $columns + $column : array_slice( $columns, 0, $position, true ) + $column + array_slice( $columns, $position, null, true );
		}

		/**
		 * Render the Messages column.
		 *
		 * @param \WC_Order $order Order.
		 * @return void
		 */
		public function ddwcoc_render_my_orders_column( $order ) {
			$unread = ( new DDWCOC_Communication_Helper() )->ddwcoc_get_unread_count( $order, 'customer' );
			$total  = $unread ? 0 : DDWCOC_Communication_Helper::ddwcoc_get_order_counts( $order->get_id(), true )['total'];
			$state  = $unread ? 'has-unread' : ( $total ? 'has-messages' : 'is-empty' );
			?>
			<a class="ddwcoc-my-orders-link <?php echo esc_attr( $state ); ?>" href="<?php echo esc_url( $order->get_view_order_url() . '#ddwcoc-chat' ); ?>">
				<?php if ( ! $unread ) : ?>
					<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				<?php endif; ?>
				<?php
				if ( $unread ) {
					/* translators: %d: number of unread messages. */
					echo esc_html( sprintf( _n( '%d new reply', '%d new replies', $unread, 'devdiggers-order-communication-for-woocommerce' ), $unread ) );
				} elseif ( $total ) {
					/* translators: %d: number of messages. */
					echo esc_html( sprintf( _n( '%d message', '%d messages', $total, 'devdiggers-order-communication-for-woocommerce' ), $total ) );
				} else {
					esc_html_e( 'Message us', 'devdiggers-order-communication-for-woocommerce' );
				}
				?>
			</a>
			<?php
		}
	}
}
