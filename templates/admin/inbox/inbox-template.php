<?php
/**
 * Inbox: every conversation in one screen, without loading the full order editor.
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Templates\Admin\Inbox;

use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;
use DDWCOrderCommunication\Helper\DDWCOC_Inbox_Helper;
use DDWCOrderCommunication\Templates\DDWCOC_Order_Communication_Template;
use DevDiggers\Framework\Includes\DDFW_SVG;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Inbox_Template' ) ) {
	/**
	 * Inbox template class
	 */
	class DDWCOC_Inbox_Template {
		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only navigation.
			$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$filter   = isset( $_GET['status_filter'] ) ? sanitize_key( wp_unslash( $_GET['status_filter'] ) ) : '';
			$order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0;
			// phpcs:enable

			$filters = [
				''       => esc_html__( 'All', 'devdiggers-order-communication-for-woocommerce' ),
				'unread' => esc_html__( 'Awaiting Reply', 'devdiggers-order-communication-for-woocommerce' ),
				'closed' => esc_html__( 'Closed', 'devdiggers-order-communication-for-woocommerce' ),
			];

			if ( ! isset( $filters[ $filter ] ) ) {
				$filter = '';
			}

			$inbox  = new DDWCOC_Inbox_Helper( $search );
			$rows   = $inbox->ddwcoc_get_rows( $filter );
			$counts = $inbox->ddwcoc_get_status_counts( array_keys( $filters ) );

			// One query loads every row's latest message and its meta, instead of one per row.
			if ( $rows ) {
				get_comments(
					[
						'comment__in' => array_map( 'absint', wp_list_pluck( $rows, 'last_id' ) ),
						'type'        => DDWCOC_Communication_Helper::TYPE,
						'number'      => count( $rows ),
					]
				);
			}

			if ( ! $order_id && $rows ) {
				$order_id = (int) $rows[0]->order_id;
			}

			$order = $rows && $order_id ? wc_get_order( $order_id ) : false;
			$keep  = array_filter(
				[
					's'             => $search,
					'status_filter' => $filter,
				]
			);
			?>
			<div class="ddwcoc-inbox<?php echo $order ? '' : ' is-empty'; ?>">
				<aside class="ddwcoc-inbox-list">
					<div class="ddwcoc-inbox-list-head">
						<h2><?php esc_html_e( 'Conversations', 'devdiggers-order-communication-for-woocommerce' ); ?></h2>
						<form class="ddwcoc-inbox-search" method="get" role="search">
							<input type="hidden" name="page" value="ddwcoc-dashboard" />
							<input type="hidden" name="menu" value="inbox" />
							<?php if ( $filter ) : ?>
								<input type="hidden" name="status_filter" value="<?php echo esc_attr( $filter ); ?>" />
							<?php endif; ?>
							<?php DDFW_SVG::get_svg_icon( 'search', false, [ 'size' => 16 ] ); ?>
							<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search name, email, order or message', 'devdiggers-order-communication-for-woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Search conversations', 'devdiggers-order-communication-for-woocommerce' ); ?>" />
						</form>
						<ul class="subsubsub">
							<?php foreach ( $filters as $key => $label ) : ?>
								<li>
									<a class="<?php echo $key === $filter ? 'current' : ''; ?>" href="
									<?php
									echo esc_url(
										add_query_arg(
											array_filter(
												[
													's' => $search,
													'status_filter' => $key,
												]
											),
											DDWCOC_Communication_Helper::ddwcoc_get_inbox_url()
										)
									);
									?>
												">
										<?php echo esc_html( $label ); ?>
										<span class="count">(<?php echo absint( $counts[ $key ] ); ?>)</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</div>

					<div class="ddwcoc-inbox-items">
						<?php if ( ! $rows ) : ?>
							<div class="ddwcoc-inbox-none">
								<?php
								DDFW_SVG::get_svg_icon(
									$search ? 'search' : 'inbox',
									false,
									[
										'size'         => 32,
										'stroke_width' => '1.5',
									]
								);
								?>
								<h3>
									<?php
									if ( $search ) {
										esc_html_e( 'No matching conversations', 'devdiggers-order-communication-for-woocommerce' );
									} elseif ( 'unread' === $filter ) {
										esc_html_e( 'You are all caught up', 'devdiggers-order-communication-for-woocommerce' );
									} elseif ( 'closed' === $filter ) {
										esc_html_e( 'No closed conversations', 'devdiggers-order-communication-for-woocommerce' );
									} else {
										esc_html_e( 'No conversations yet', 'devdiggers-order-communication-for-woocommerce' );
									}
									?>
								</h3>
								<p>
									<?php
									if ( $search ) {
										esc_html_e( 'Try a different name, email address, order number or word from the message.', 'devdiggers-order-communication-for-woocommerce' );
									} elseif ( 'unread' === $filter ) {
										esc_html_e( 'Every customer message has been read. New ones will appear here.', 'devdiggers-order-communication-for-woocommerce' );
									} elseif ( 'closed' === $filter ) {
										esc_html_e( 'Conversations you close from the chat header are kept here.', 'devdiggers-order-communication-for-woocommerce' );
									} else {
										esc_html_e( 'When a customer writes about an order, or your team sends the first message, the conversation shows up here.', 'devdiggers-order-communication-for-woocommerce' );
									}
									?>
								</p>
							</div>
						<?php endif; ?>

						<?php
						foreach ( $rows as $row ) :
							$row_order = wc_get_order( $row->order_id );
							$last      = get_comment( $row->last_id );

							if ( ! $row_order || ! $last ) {
								continue;
							}

							$name    = trim( $row_order->get_formatted_billing_full_name() );
							$sender  = get_comment_meta( $last->comment_ID, '_ddwcoc_sender', true );
							$unread  = (int) $row->unread;
							$classes = [ 'ddwcoc-inbox-item' ];

							if ( (int) $row->order_id === $order_id ) {
								$classes[] = 'is-active';
							}

							if ( $unread ) {
								$classes[] = 'has-unread';
							}
							?>
							<a class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" href="<?php echo esc_url( add_query_arg( $keep, DDWCOC_Communication_Helper::ddwcoc_get_inbox_url( $row->order_id ) ) ); ?>">
								<span class="ddwcoc-inbox-avatar" data-initial="<?php echo esc_attr( self::ddwcoc_initial( $name ? $name : $row_order->get_billing_email() ) ); ?>"><?php echo wp_kses_post( get_avatar( $row_order->get_billing_email(), 80, 'blank' ) ); ?></span>
								<span class="ddwcoc-inbox-item-body">
									<span class="ddwcoc-inbox-item-top">
										<strong><?php echo esc_html( $name ? $name : $row_order->get_billing_email() ); ?></strong>
										<time datetime="<?php echo esc_attr( gmdate( 'c', strtotime( $row->recent_date_gmt . ' UTC' ) ) ); ?>"><?php echo esc_html( human_time_diff( strtotime( $row->recent_date_gmt . ' UTC' ) ) ); ?></time>
									</span>
									<span class="ddwcoc-inbox-item-preview">
										<?php if ( 'customer' !== $sender ) : ?>
											<em><?php echo 'system' === $sender ? esc_html__( 'Auto:', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'You:', 'devdiggers-order-communication-for-woocommerce' ); ?></em>
										<?php endif; ?>
										<?php echo esc_html( wp_trim_words( wp_strip_all_tags( $last->comment_content ), 12 ) ); ?>
									</span>
									<span class="ddwcoc-inbox-item-meta">
										<span>#<?php echo esc_html( $row_order->get_order_number() ); ?></span>
										<mark class="order-status status-<?php echo esc_attr( $row_order->get_status() ); ?>"><span><?php echo esc_html( wc_get_order_status_name( $row_order->get_status() ) ); ?></span></mark>
										<?php if ( $unread ) : ?>
											<span class="ddwcoc-inbox-badge">
												<?php
												/* translators: %d: unread messages. */
												echo esc_html( sprintf( _n( '%d new', '%d new', $unread, 'devdiggers-order-communication-for-woocommerce' ), $unread ) );
												?>
											</span>
										<?php endif; ?>
									</span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
				</aside>

				<?php if ( $order ) : ?>
					<section class="ddwcoc-inbox-thread">
						<?php new DDWCOC_Order_Communication_Template( $order->get_id(), 'admin' ); ?>
					</section>

					<aside class="ddwcoc-inbox-order">
						<?php $this->ddwcoc_render_order_panel( $order ); ?>
					</aside>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * First letter for the avatar fallback.
		 *
		 * @param string $name Name or email.
		 * @return string
		 */
		protected static function ddwcoc_initial( $name ) {
			return strtoupper( mb_substr( $name, 0, 1 ) );
		}

		/**
		 * Customer and order context so staff rarely need the full editor.
		 *
		 * @param \WC_Order $order Order.
		 * @return void
		 */
		protected function ddwcoc_render_order_panel( $order ) {
			$customer_id     = $order->get_customer_id();
			$customer_orders = $customer_id ? wc_get_customer_order_count( $customer_id ) : 0;
			$customer_spent  = $customer_id ? wc_get_customer_total_spent( $customer_id ) : 0;
			$name            = trim( $order->get_formatted_billing_full_name() );
			$address         = $order->has_shipping_address() ? $order->get_formatted_shipping_address() : $order->get_formatted_billing_address();
			?>
			<section class="ddwcoc-inbox-section ddwcoc-inbox-customer">
				<div class="ddwcoc-inbox-customer-head">
					<span class="ddwcoc-inbox-avatar" data-initial="<?php echo esc_attr( self::ddwcoc_initial( $name ? $name : $order->get_billing_email() ) ); ?>"><?php echo wp_kses_post( get_avatar( $order->get_billing_email(), 96, 'blank' ) ); ?></span>
					<div>
						<strong><?php echo esc_html( $name ? $name : $order->get_billing_email() ); ?></strong>
						<span><?php echo $customer_id ? esc_html__( 'Registered customer', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'Guest customer', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
					</div>
				</div>

				<?php if ( $customer_id ) : ?>
					<div class="ddwcoc-inbox-stats">
						<div>
							<strong><?php echo absint( $customer_orders ); ?></strong>
							<span><?php esc_html_e( 'Orders', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
						</div>
						<div>
							<strong><?php echo wp_kses_post( wc_price( $customer_spent ) ); ?></strong>
							<span><?php esc_html_e( 'Total spent', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
						</div>
					</div>
				<?php endif; ?>

				<dl class="ddwcoc-inbox-contact">
					<dt><?php esc_html_e( 'Email', 'devdiggers-order-communication-for-woocommerce' ); ?></dt>
					<dd><a href="mailto:<?php echo esc_attr( $order->get_billing_email() ); ?>"><?php echo esc_html( $order->get_billing_email() ); ?></a></dd>
					<?php if ( $order->get_billing_phone() ) : ?>
						<dt><?php esc_html_e( 'Phone', 'devdiggers-order-communication-for-woocommerce' ); ?></dt>
						<dd><a href="tel:<?php echo esc_attr( $order->get_billing_phone() ); ?>"><?php echo esc_html( $order->get_billing_phone() ); ?></a></dd>
					<?php endif; ?>
					<?php if ( $address ) : ?>
						<dt><?php echo $order->has_shipping_address() ? esc_html__( 'Ships to', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'Address', 'devdiggers-order-communication-for-woocommerce' ); ?></dt>
						<dd><address><?php echo wp_kses_post( $address ); ?></address></dd>
					<?php endif; ?>
				</dl>
			</section>

			<section class="ddwcoc-inbox-section">
				<div class="ddwcoc-inbox-section-head">
					<h3>
						<?php
						/* translators: %s: order number. */
						echo esc_html( sprintf( __( 'Order #%s', 'devdiggers-order-communication-for-woocommerce' ), $order->get_order_number() ) );
						?>
					</h3>
					<mark class="order-status status-<?php echo esc_attr( $order->get_status() ); ?>"><span><?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?></span></mark>
				</div>
				<dl class="ddwcoc-inbox-facts">
					<dt><?php esc_html_e( 'Placed', 'devdiggers-order-communication-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $order->get_date_created() ? $order->get_date_created()->date_i18n( get_option( 'date_format' ) ) : '-' ); ?></dd>
					<dt><?php esc_html_e( 'Payment', 'devdiggers-order-communication-for-woocommerce' ); ?></dt>
					<dd><?php echo esc_html( $order->get_payment_method_title() ? $order->get_payment_method_title() : '-' ); ?></dd>
				</dl>
				<ul class="ddwcoc-inbox-lines">
					<?php
					foreach ( $order->get_items() as $item ) :
						$product = $item->get_product();
						?>
						<li>
							<span class="ddwcoc-inbox-thumb"><?php echo $product ? wp_kses_post( $product->get_image( [ 36, 36 ] ) ) : ''; ?></span>
							<span class="ddwcoc-inbox-line-name"><?php echo esc_html( $item->get_name() ); ?><small>&times; <?php echo esc_html( $item->get_quantity() ); ?></small></span>
							<span><?php echo wp_kses_post( wc_price( $item->get_total(), [ 'currency' => $order->get_currency() ] ) ); ?></span>
						</li>
					<?php endforeach; ?>
				</ul>
				<div class="ddwcoc-inbox-total">
					<span><?php esc_html_e( 'Total', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
					<strong><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
				</div>
				<a class="button ddwcoc-inbox-edit" href="<?php echo esc_url( $order->get_edit_order_url() ); ?>"><?php esc_html_e( 'Open Full Order', 'devdiggers-order-communication-for-woocommerce' ); ?></a>
			</section>
			<?php
		}
	}
}
