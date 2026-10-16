<?php
/**
 * General Configuration template class
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Templates\Admin\Configuration;

use DevDiggers\Framework\Includes\DDFW_Layout;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Configuration_Template' ) ) {
	/**
	 * General Configuration template class
	 */
	class DDWCOC_Configuration_Template {

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
			$this->ddwcoc_get_configuration_template();
		}

		/**
		 * Get General Configuration Template function
		 *
		 * @return void
		 */
		public function ddwcoc_get_configuration_template() {
			$args = [
				[
					'header' => [
						'heading'     => esc_html__( 'General Settings', 'devdiggers-order-communication-for-woocommerce' ),
						'description' => esc_html__( 'Turn on order conversations and choose where customers can use them.', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'fields' => [
						[
							'type'           => 'checkbox',
							'label'          => esc_html__( 'Enable Order Communication', 'devdiggers-order-communication-for-woocommerce' ),
							'checkbox_label' => esc_html__( 'Show the conversation on the customer order page', 'devdiggers-order-communication-for-woocommerce' ),
							'description'    => esc_html__( 'Customers chat with your team from My Account > Orders > View. Your team replies from the Inbox or the order screen.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'             => 'ddwcoc-enable',
							'name'           => '_ddwcoc_enabled',
							'value'          => $this->ddwcoc_configuration['enabled'],
						],
						[
							'type'              => 'select',
							'label'             => esc_html__( 'Allowed Order Statuses', 'devdiggers-order-communication-for-woocommerce' ),
							'description'       => esc_html__( 'Customers can only write on orders in these statuses. Leave empty to allow all. Your team can always reply.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'                => 'ddwcoc-allowed-order-status',
							'name'              => '_ddwcoc_allowed_order_status[]',
							'value'             => $this->ddwcoc_configuration['allowed_order_status'],
							'options'           => wc_get_order_statuses(),
							'placeholder'       => esc_html__( 'All statuses', 'devdiggers-order-communication-for-woocommerce' ),
							'custom_attributes' => [
								'multiple' => true,
							],
						],
					],
				],
				[
					'header'            => [
						'heading'     => esc_html__( 'Attachments', 'devdiggers-order-communication-for-woocommerce' ),
						'description' => esc_html__( 'Let customers send photos of a damaged parcel, a receipt or a size chart right in the order chat.', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'after_header_html' => ddfw_get_upgrade_to_pro_section(
						[
							'image_url'     => \DDWCOrderCommunication\Includes\DDWCOC_Admin_Dashboard::ddwcoc_get_pro_image( 'attachments' ),
							'heading'       => esc_html__( 'Share photos and documents in the chat with Pro', 'devdiggers-order-communication-for-woocommerce' ),
							'description'   => esc_html__( 'Customers and your team attach files by picking, dragging or pasting them. Every file is checked by its real content.', 'devdiggers-order-communication-for-woocommerce' ),
							'list_features' => [
								esc_html__( 'Images, PDF, text and Word documents', 'devdiggers-order-communication-for-woocommerce' ),
								esc_html__( 'Several files per message, with your own size limit', 'devdiggers-order-communication-for-woocommerce' ),
								esc_html__( 'Images shown inline in the conversation', 'devdiggers-order-communication-for-woocommerce' ),
								esc_html__( 'Files attached to the notification emails', 'devdiggers-order-communication-for-woocommerce' ),
							],
							'upgrade_url'   => 'https://devdiggers.com/product/woocommerce-order-communication/',
						]
					),
				],
			];

			$layout = new DDFW_Layout();
			$layout->get_form_section_layout( $args, 'ddwcoc-general-configuration-fields', [] );
		}
	}
}
