<?php
/**
 * Dashboard template class
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Templates\Admin\Dashboard;

use DDWCOrderCommunication\Helper\Dashboard\DDWCOC_Dashboard_Helper;
use DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper;
use DevDiggers\Framework\Includes\DDFW_Dashboard;
use DevDiggers\Framework\Includes\DDFW_Dashboard_Data;
use DevDiggers\Framework\Includes\DDFW_SVG;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Dashboard_Template' ) ) {
	/**
	 * Dashboard template class
	 */
	class DDWCOC_Dashboard_Template {
		/**
		 * Dashboard helper
		 *
		 * @var DDWCOC_Dashboard_Helper
		 */
		protected $helper;

		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.Found -- Shared template signature.
			if ( ! class_exists( DDFW_Dashboard::class ) ) {
				return;
			}

			$this->helper = new DDWCOC_Dashboard_Helper();
			$range        = DDFW_Dashboard_Data::get_date_range();
			$data         = $this->helper->ddwcoc_get_dashboard_data( $range );
			$current      = $data['current'];
			$previous     = $data['previous'];
			$changes      = $data['changes'];

			// A card whose figure did not move still gets a line under it.
			$no_change = function ( $change, $value ) {
				if ( 0.0 !== (float) $change || null === $change ) {
					return '';
				}

				return empty( $value )
					? esc_html__( 'None in either period', 'devdiggers-order-communication-for-woocommerce' )
					: esc_html__( 'No change vs previous period', 'devdiggers-order-communication-for-woocommerce' );
			};

			new DDFW_Dashboard(
				[
					'columns'       => 4,
					'header'        => [
						/* translators: %s: current user display name. */
						'welcome'  => esc_html__( 'Welcome back, %s! 👋🏻', 'devdiggers-order-communication-for-woocommerce' ),
						'subtitle' => esc_html__( 'Here is how your team is keeping up with customer conversations', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'summary_cards' => [
						[
							'title'       => esc_html__( 'Messages Received', 'devdiggers-order-communication-for-woocommerce' ),
							'value'       => $current['customer'],
							'change'      => $changes['customer'],
							'previous'    => number_format_i18n( $previous['customer'] ),
							'note'        => $no_change( $changes['customer'], $current['customer'] ),
							'is_positive' => $changes['customer'] >= 0,
							'icon'        => DDFW_SVG::get_svg_icon( 'inbox', true ),
						],
						[
							'title'       => esc_html__( 'Store Replies', 'devdiggers-order-communication-for-woocommerce' ),
							'value'       => $current['staff'],
							'change'      => $changes['staff'],
							'previous'    => number_format_i18n( $previous['staff'] ),
							'note'        => $no_change( $changes['staff'], $current['staff'] ),
							'is_positive' => $changes['staff'] >= 0,
							'icon'        => DDFW_SVG::get_svg_icon( 'users', true ),
						],
						[
							'title'       => esc_html__( 'Active Conversations', 'devdiggers-order-communication-for-woocommerce' ),
							'value'       => count( $current['conversations'] ),
							'change'      => $changes['conversations'],
							'previous'    => number_format_i18n( count( $previous['conversations'] ) ),
							'note'        => $no_change( $changes['conversations'], count( $current['conversations'] ) ),
							'is_positive' => $changes['conversations'] >= 0,
							'icon'        => DDFW_SVG::get_svg_icon( 'chat', true ),
						],
						[
							'title' => esc_html__( 'Awaiting Reply Now', 'devdiggers-order-communication-for-woocommerce' ),
							'value' => count( $this->helper->ddwcoc_get_awaiting_order_ids() ),
							'note'  => esc_html__( 'Unread conversations, right now', 'devdiggers-order-communication-for-woocommerce' ),
							'icon'  => DDFW_SVG::get_svg_icon( 'alert', true ),
						],
					],
					'charts'        => [
						[
							'id'         => 'message-activity',
							'title'      => esc_html__( 'Message Activity', 'devdiggers-order-communication-for-woocommerce' ),
							'date_label' => $range['label'],
							'full_width' => true,
							'type'       => 'line',
							'data'       => $data['activity'],
							'x_key'      => 'date',
							'series'     => [
								[
									'key'   => 'customer',
									'label' => esc_html__( 'Customer Messages', 'devdiggers-order-communication-for-woocommerce' ),
									'color' => '#0256ff',
								],
								[
									'key'   => 'staff',
									'label' => esc_html__( 'Store Replies', 'devdiggers-order-communication-for-woocommerce' ),
									'color' => '#10b981',
								],
							],
							'empty'      => [
								'title' => esc_html__( 'No messages in this period', 'devdiggers-order-communication-for-woocommerce' ),
								'desc'  => esc_html__( 'Customer messages and your replies will be charted here.', 'devdiggers-order-communication-for-woocommerce' ),
							],
						],
					],
					'widgets'       => [
						[
							'title'  => esc_html__( 'Awaiting Your Reply', 'devdiggers-order-communication-for-woocommerce' ),
							'render' => [ $this, 'ddwcoc_render_awaiting' ],
						],
						[
							'title'  => esc_html__( 'Latest Customer Messages', 'devdiggers-order-communication-for-woocommerce' ),
							'render' => [ $this, 'ddwcoc_render_recent_messages' ],
						],
						[
							'width'  => 'full',
							'render' => [ $this, 'ddwcoc_render_pro_card' ],
						],
					],
				]
			);
		}

		/**
		 * Oldest unanswered conversations.
		 *
		 * @return void
		 */
		public function ddwcoc_render_awaiting() {
			$items = [];

			foreach ( $this->helper->ddwcoc_get_awaiting_order_ids( 6, true ) as $row ) {
				$order = wc_get_order( $row->order_id );

				if ( ! $order ) {
					continue;
				}

				$items[] = [
					'url'         => DDWCOC_Communication_Helper::ddwcoc_get_inbox_url( $order->get_id() ),
					'title'       => '#' . $order->get_order_number() . ' ' . $order->get_formatted_billing_full_name(),
					/* translators: %d: unread messages. */
					'meta'        => sprintf( _n( '%d unread message', '%d unread messages', (int) $row->unread, 'devdiggers-order-communication-for-woocommerce' ), (int) $row->unread ),
					'image'       => get_avatar_url( $order->get_billing_email(), [ 'size' => 72 ] ),
					'shape'       => 'round',
					'value'       => esc_html( DDWCOC_Dashboard_Helper::ddwcoc_format_duration( time() - strtotime( $row->waiting_since . ' UTC' ) ) ),
					'value_class' => 'negative',
				];
			}

			DDFW_Dashboard::render_list( $items, __( 'Inbox zero. Every customer has an answer.', 'devdiggers-order-communication-for-woocommerce' ) );
		}

		/**
		 * Upgrade card under the figures.
		 *
		 * @return void
		 */
		public function ddwcoc_render_pro_card() {
			ddfw_upgrade_to_pro_section(
				[
					'heading'       => esc_html__( 'See how fast your team really answers', 'devdiggers-order-communication-for-woocommerce' ),
					'description'   => esc_html__( 'Pro measures your response speed and shows where every conversation stands, so nothing slips through.', 'devdiggers-order-communication-for-woocommerce' ),
					'list_features' => [
						esc_html__( 'Average first response time, with the trend against the previous period', 'devdiggers-order-communication-for-woocommerce' ),
						esc_html__( 'Reply rate: the share of conversations your team answered', 'devdiggers-order-communication-for-woocommerce' ),
						esc_html__( 'Daily response time chart', 'devdiggers-order-communication-for-woocommerce' ),
						esc_html__( 'Awaiting, answered and closed split of all conversations', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'upgrade_url'   => 'https://devdiggers.com/product/woocommerce-order-communication/',
				]
			);
		}

		/**
		 * Latest customer messages.
		 *
		 * @return void
		 */
		public function ddwcoc_render_recent_messages() {
			$items = [];

			foreach ( $this->helper->ddwcoc_get_recent_customer_messages() as $message ) {
				$order = wc_get_order( $message->comment_post_ID );

				$items[] = [
					'url'   => $order ? DDWCOC_Communication_Helper::ddwcoc_get_inbox_url( $order->get_id() ) : '',
					'title' => $order ? $message->comment_author . ' #' . $order->get_order_number() : $message->comment_author,
					'meta'  => wp_trim_words( wp_strip_all_tags( $message->comment_content ), 12 ),
					'image' => get_avatar_url( $message->comment_author_email, [ 'size' => 72 ] ),
					'shape' => 'round',
					/* translators: %s: human time difference. */
					'value' => esc_html( sprintf( __( '%s ago', 'devdiggers-order-communication-for-woocommerce' ), human_time_diff( strtotime( $message->comment_date_gmt . ' UTC' ) ) ) ),
				];
			}

			DDFW_Dashboard::render_list( $items, __( 'No customer messages yet', 'devdiggers-order-communication-for-woocommerce' ) );
		}
	}
}
