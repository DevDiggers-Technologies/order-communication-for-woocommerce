<?php
/**
 * Order conversation (chat) template, shared by the order edit screen and My Account.
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Templates;

use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Order_Communication_Template' ) ) {
	/**
	 * Order Communication Template Class
	 */
	class DDWCOC_Order_Communication_Template {
		/**
		 * Construct
		 *
		 * @param int    $order_id Order ID.
		 * @param string $context  'admin' or 'front'.
		 */
		public function __construct( $order_id, $context = 'front' ) {
			$helper = new DDWCOC_Communication_Helper();
			$access = $helper->ddwcoc_get_access( $order_id, $context );

			if ( ! is_wp_error( $access ) ) {
				$this->ddwcoc_render( $helper, $access[0], $access[1] );
			}
		}

		/**
		 * Render the conversation.
		 *
		 * @param DDWCOC_Communication_Helper $helper Helper.
		 * @param \WC_Order                   $order  Order.
		 * @param string                      $role   Viewer role.
		 * @return void
		 */
		protected function ddwcoc_render( $helper, $order, $role ) {
			global $ddwcoc_configuration;

			$messages   = $helper->ddwcoc_get_messages( $order->get_id(), $role );
			$is_staff   = 'staff' === $role;
			$closed     = $helper->ddwcoc_is_closed( $order );
			$blocked    = $helper->ddwcoc_get_reply_block_reason( $order, $role );
			$last_id    = $messages ? (int) end( $messages )->comment_ID : 0;
			$seen_until = $helper->ddwcoc_get_seen_until( $order, $role );
			$style      = sprintf(
				'--ddwcoc-own-color:%s;--ddwcoc-own-bg:%s;--ddwcoc-other-color:%s;--ddwcoc-other-bg:%s;',
				$ddwcoc_configuration['sender_font_color'],
				$ddwcoc_configuration['sender_background_color'],
				$ddwcoc_configuration['receiver_font_color'],
				$ddwcoc_configuration['receiver_background_color']
			);

			// Opening the thread counts as reading it.
			$helper->ddwcoc_mark_seen( $order, $role );
			?>
			<div class="ddwcoc-chat<?php echo $closed ? ' is-closed' : ''; ?>" id="ddwcoc-chat" data-order-id="<?php echo esc_attr( $order->get_id() ); ?>" data-context="<?php echo esc_attr( $is_staff ? 'admin' : 'front' ); ?>" data-last-id="<?php echo esc_attr( $last_id ); ?>" data-seen-until="<?php echo esc_attr( $seen_until ); ?>" style="<?php echo esc_attr( $style ); ?>">
				<div class="ddwcoc-chat-header">
					<div class="ddwcoc-chat-title">
						<?php if ( $is_staff ) : ?>
							<strong><?php echo esc_html( trim( $order->get_formatted_billing_full_name() ) ? $order->get_formatted_billing_full_name() : $order->get_billing_email() ); ?></strong>
						<?php else : ?>
							<?php $site_icon = get_site_icon_url( 64 ); ?>
							<span class="ddwcoc-chat-store-avatar" aria-hidden="true">
								<?php if ( $site_icon ) : ?>
									<img src="<?php echo esc_url( $site_icon ); ?>" alt="" />
								<?php else : ?>
									<?php echo esc_html( strtoupper( mb_substr( get_bloginfo( 'name' ), 0, 1 ) ) ); ?>
								<?php endif; ?>
							</span>
							<span class="ddwcoc-chat-store">
								<strong><?php echo esc_html( get_bloginfo( 'name' ) ); ?></strong>
								<small class="ddwcoc-chat-presence">
									<?php
									/* translators: %s: order number. */
									echo esc_html( sprintf( __( 'Order #%s', 'devdiggers-order-communication-for-woocommerce' ), $order->get_order_number() ) );
									?>
								</small>
							</span>
						<?php endif; ?>
						<?php if ( $is_staff ) : ?>
							<span class="ddwcoc-chat-state"><?php echo $closed ? esc_html__( 'Closed', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'Open', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
						<?php endif; ?>
					</div>
					<?php if ( ! $is_staff ) : ?>
						<span class="ddwcoc-chat-state"><?php echo $closed ? esc_html__( 'Closed', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'Open', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
					<?php endif; ?>
					<?php if ( $is_staff ) : ?>
						<div class="ddwcoc-chat-actions">
							<button type="button" class="button ddwcoc-toggle-conversation" data-closed="<?php echo esc_attr( $closed ? '0' : '1' ); ?>">
								<?php echo $closed ? esc_html__( 'Reopen Conversation', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'Close Conversation', 'devdiggers-order-communication-for-woocommerce' ); ?>
							</button>
						</div>
					<?php endif; ?>
				</div>

				<div class="ddwcoc-chat-messages" role="log" aria-live="polite">
					<?php
					foreach ( $messages as $message ) {
						self::ddwcoc_render_message( $message, $role, $helper );
					}
					?>
					<div class="ddwcoc-chat-empty"<?php echo $messages ? ' hidden' : ''; ?>>
						<svg class="ddwcoc-icon" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
						<p><?php echo $is_staff ? esc_html__( 'No messages yet. Start the conversation with your customer.', 'devdiggers-order-communication-for-woocommerce' ) : esc_html__( 'Have a question about this order? Send us a message and we will get back to you.', 'devdiggers-order-communication-for-woocommerce' ); ?></p>
					</div>
				</div>

				<div class="ddwcoc-chat-notice"<?php echo $blocked ? '' : ' hidden'; ?>><?php echo esc_html( $blocked ); ?></div>

				<?php
				// Staff write inside the order edit <form>, so a nested <form> would be dropped by the parser.
				$tag = $is_staff ? 'div' : 'form';
				?>
				<<?php echo esc_attr( $tag ); ?> class="ddwcoc-chat-form"<?php echo $is_staff ? '' : ' method="post" enctype="multipart/form-data"'; ?><?php echo $blocked ? ' hidden' : ''; ?>>

					<div class="ddwcoc-chat-composer">
						<textarea name="ddwcoc_message" class="ddwcoc-chat-input" rows="2" placeholder="<?php esc_attr_e( 'Write a message... (Enter to send, Shift+Enter for a new line)', 'devdiggers-order-communication-for-woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Message', 'devdiggers-order-communication-for-woocommerce' ); ?>"></textarea>
						<div class="ddwcoc-chat-toolbar">
							<?php $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null; ?>
							<?php if ( $is_staff && $screen && '_page_ddwcoc-dashboard' === substr( $screen->id, -22 ) ) : ?>
								<?php // Pro composer tools, inert. Inbox only: the framework styles the PRO tag on its own screens. ?>
								<span class="ddwcoc-chat-tool ddfw-upgrade-to-pro-tag-wrapper" aria-disabled="true" title="<?php esc_attr_e( 'Share photos and documents with Pro', 'devdiggers-order-communication-for-woocommerce' ); ?>">
									<svg class="ddwcoc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.44 11.05-9.19 9.19a6 6 0 0 1-8.49-8.49l8.57-8.57A4 4 0 1 1 18 8.84l-8.59 8.57a2 2 0 0 1-2.83-2.83l8.49-8.48"/></svg><?php esc_html_e( 'Attach', 'devdiggers-order-communication-for-woocommerce' ); ?>
									<?php echo wp_kses_post( ddfw_get_pro_tag() ); ?>
								</span>
								<span class="ddwcoc-chat-tool ddfw-upgrade-to-pro-tag-wrapper" aria-disabled="true" title="<?php esc_attr_e( 'Insert saved replies with Pro', 'devdiggers-order-communication-for-woocommerce' ); ?>">
									<svg class="ddwcoc-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M8 13h8M8 17h5"/></svg><?php esc_html_e( 'Templates', 'devdiggers-order-communication-for-woocommerce' ); ?>
									<?php echo wp_kses_post( ddfw_get_pro_tag() ); ?>
								</span>
								<span class="ddwcoc-chat-tool ddfw-upgrade-to-pro-tag-wrapper" aria-disabled="true" title="<?php esc_attr_e( 'Write notes only your team can see with Pro', 'devdiggers-order-communication-for-woocommerce' ); ?>">
									<?php esc_html_e( 'Private note', 'devdiggers-order-communication-for-woocommerce' ); ?>
									<?php echo wp_kses_post( ddfw_get_pro_tag() ); ?>
								</span>
							<?php endif; ?>
							<?php if ( ! $is_staff ) : ?>
								<input type="hidden" name="ddwcoc_order_id" value="<?php echo esc_attr( $order->get_id() ); ?>" />
								<?php wp_nonce_field( 'ddwcoc-nonce-action', 'ddwcoc-nonce' ); ?>
							<?php endif; ?>
							<button type="<?php echo $is_staff ? 'button' : 'submit'; ?>" name="ddwcoc_send_message" value="1" class="ddwcoc-chat-send"><span class="ddwcoc-chat-send-label"><?php esc_html_e( 'Send', 'devdiggers-order-communication-for-woocommerce' ); ?></span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 2-7 20-4-9-9-4z"/><path d="M22 2 11 13"/></svg></button>
						</div>
					</div>
				</<?php echo esc_attr( $tag ); ?>>
			</div>
			<?php
		}

		/**
		 * HTML for one message, for the AJAX payload.
		 *
		 * @param \WP_Comment                 $message Message.
		 * @param string                      $role    Viewer role.
		 * @param DDWCOC_Communication_Helper $helper  Helper.
		 * @return string
		 */
		public static function ddwcoc_get_message_html( $message, $role, $helper ) {
			ob_start();
			self::ddwcoc_render_message( $message, $role, $helper );
			return ob_get_clean();
		}

		/**
		 * Print one message.
		 *
		 * @param \WP_Comment                 $message Message.
		 * @param string                      $role    Viewer role.
		 * @param DDWCOC_Communication_Helper $helper  Helper.
		 * @return void
		 */
		public static function ddwcoc_render_message( $message, $role, $helper ) {
			$sender     = $helper->ddwcoc_get_sender( $message );
			$is_private = (bool) get_comment_meta( $message->comment_ID, '_ddwcoc_private', true );
			$is_own     = $sender === $role;
			$timestamp  = strtotime( $message->comment_date_gmt . ' UTC' );
			$classes    = [ 'ddwcoc-message', 'ddwcoc-message-' . $sender, $is_own ? 'ddwcoc-message-own' : 'ddwcoc-message-other' ];

			if ( $is_private ) {
				$classes[] = 'is-private';
			}

			if ( 'system' === $sender ) {
				$author = get_bloginfo( 'name' );
			} elseif ( $is_own && get_current_user_id() === (int) $message->user_id ) {
				$author = esc_html__( 'You', 'devdiggers-order-communication-for-woocommerce' );
			} else {
				$author = $message->comment_author;
			}

			?>
			<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" data-id="<?php echo esc_attr( $message->comment_ID ); ?>" data-time="<?php echo esc_attr( $timestamp ); ?>">
				<span class="ddwcoc-message-avatar" aria-hidden="true"><?php echo esc_html( 'system' === $sender ? '•' : strtoupper( mb_substr( $message->comment_author, 0, 1 ) ) ); ?><?php echo 'system' === $sender ? '' : wp_kses_post( get_avatar( $message->user_id ? (int) $message->user_id : $message->comment_author_email, 60, 'blank', '' ) ); ?></span>
				<div class="ddwcoc-message-main">
					<div class="ddwcoc-message-bubble">
						<?php if ( $is_private ) : ?>
							<span class="ddwcoc-message-tag"><?php esc_html_e( 'Private note', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
						<?php elseif ( 'system' === $sender ) : ?>
							<span class="ddwcoc-message-tag"><?php esc_html_e( 'Automated', 'devdiggers-order-communication-for-woocommerce' ); ?></span>
						<?php endif; ?>
						<div class="ddwcoc-message-text"><?php echo wp_kses_post( make_clickable( wpautop( $message->comment_content ) ) ); ?></div>
					</div>
					<div class="ddwcoc-message-meta">
						<span class="ddwcoc-message-author"><?php echo esc_html( $author ); ?></span>
						<time datetime="<?php echo esc_attr( gmdate( 'c', $timestamp ) ); ?>"><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $message->comment_date ) ) ); ?></time>
						<?php if ( $is_own && ! $is_private ) : ?>
							<span class="ddwcoc-message-receipt" title="<?php esc_attr_e( 'Seen', 'devdiggers-order-communication-for-woocommerce' ); ?>" aria-label="<?php esc_attr_e( 'Seen', 'devdiggers-order-communication-for-woocommerce' ); ?>"></span>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
