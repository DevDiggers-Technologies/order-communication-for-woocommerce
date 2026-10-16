<?php
/**
 * Chat Configuration template class
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 1.3.0
 */

namespace DDWCOrderCommunication\Templates\Admin\Configuration;

use DevDiggers\Framework\Includes\DDFW_Layout;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Chat_Configuration_Template' ) ) {
	/**
	 * Chat Settings Configuration template class
	 */
	class DDWCOC_Chat_Configuration_Template {

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
			$this->ddwcoc_get_chat_configuration_template();
		}

		/**
		 * Get Chat Configuration Template function
		 *
		 * @return void
		 */
		public function ddwcoc_get_chat_configuration_template() {
			$args = [
				[
					'header' => [
						'heading'     => esc_html__( 'Chat Colors', 'devdiggers-order-communication-for-woocommerce' ),
						'description' => esc_html__( 'Match the chat bubbles to your brand. "Own" bubbles are the messages of whoever is viewing the chat (your team in admin, the customer in My Account).', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'fields' => [
						[
							'type'        => 'colorpicker',
							'label'       => esc_html__( 'Own Message Text', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Text color of your own messages.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-sender-font-color',
							'name'        => '_ddwcoc_sender_font_color',
							'value'       => $this->ddwcoc_configuration['sender_font_color'],
						],
						[
							'type'        => 'colorpicker',
							'label'       => esc_html__( 'Own Message Bubble', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Bubble color of your own messages. Also used for the Send button.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-sender-background-color',
							'name'        => '_ddwcoc_sender_background_color',
							'value'       => $this->ddwcoc_configuration['sender_background_color'],
						],
						[
							'type'        => 'colorpicker',
							'label'       => esc_html__( 'Other Party Text', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Text color of messages from the other side.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-receiver-font-color',
							'name'        => '_ddwcoc_receiver_font_color',
							'value'       => $this->ddwcoc_configuration['receiver_font_color'],
						],
						[
							'type'        => 'colorpicker',
							'label'       => esc_html__( 'Other Party Bubble', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Bubble color of messages from the other side.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-receiver-background-color',
							'name'        => '_ddwcoc_receiver_background_color',
							'value'       => $this->ddwcoc_configuration['receiver_background_color'],
						],
					],
				],
				[
					'header' => [
						'heading'     => esc_html__( 'Live Updates', 'devdiggers-order-communication-for-woocommerce' ),
						'description' => esc_html__( 'New messages appear automatically while the conversation is open, with no page reload.', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'fields' => [
						[
							'type'              => 'number',
							'label'             => esc_html__( 'Refresh Interval (seconds)', 'devdiggers-order-communication-for-woocommerce' ),
							'description'       => esc_html__( 'How often an open conversation checks for new messages. Lower is more real time, higher is lighter on the server. Minimum 5.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'                => 'ddwcoc-poll-interval',
							'name'              => '_ddwcoc_poll_interval',
							'value'             => $this->ddwcoc_configuration['poll_interval'],
							'custom_attributes' => [
								'min' => 5,
							],
						],
					],
				],
			];

			$layout = new DDFW_Layout();
			$layout->get_form_section_layout( $args, 'ddwcoc-chat-configuration-fields', [] );
		}
	}
}
