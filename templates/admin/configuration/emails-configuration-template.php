<?php
/**
 * Emails configuration template.
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Templates\Admin\Configuration;

use DevDiggers\Framework\Includes\DDFW_Layout;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Emails_Configuration_Template' ) ) {
	/**
	 * Emails configuration template class.
	 */
	class DDWCOC_Emails_Configuration_Template {
		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			$emails = $ddwcoc_configuration['email_settings'];

			// The fixed body, as readable text for the locked preview.
			$plain = function ( $html ) {
				return trim( wp_strip_all_tags( str_replace( '</p>', "</p>\n\n", $html ) ) );
			};

			$args = [
				[
					'header' => [
						'heading'     => esc_html__( 'Store Notification Email', 'devdiggers-order-communication-for-woocommerce' ),
						'description' => esc_html__( 'Sent to your team when a customer writes on an order. It quotes the message and links straight to the conversation in the Inbox.', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'fields' => [
						[
							'type'           => 'checkbox',
							'label'          => esc_html__( 'Enable', 'devdiggers-order-communication-for-woocommerce' ),
							'checkbox_label' => esc_html__( 'Email the store when a customer sends a message', 'devdiggers-order-communication-for-woocommerce' ),
							'description'    => esc_html__( 'Turn off if your team works from the Inbox and does not need email alerts.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'             => 'ddwcoc-enabled-admin-mail',
							'name'           => '_ddwcoc_enabled_admin_mail',
							'value'          => $ddwcoc_configuration['enabled_admin_mail'],
						],
						[
							'type'        => 'text',
							'label'       => esc_html__( 'Recipients', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Who receives store notifications. Separate several addresses with commas. Defaults to the site admin email.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-email-store-recipient',
							'name'        => '_ddwcoc_email_settings[store][recipient]',
							'value'       => $emails['store']['recipient'],
							'placeholder' => get_option( 'admin_email' ),
						],
						ddfw_locked_field(
							[
								'type'        => 'text',
								'label'       => esc_html__( 'Subject', 'devdiggers-order-communication-for-woocommerce' ),
								'description' => esc_html__( 'Subject line of the email. {site_title} is also supported.', 'devdiggers-order-communication-for-woocommerce' ),
								'id'          => 'ddwcoc-email-store-subject',
								'value'       => $emails['store']['subject'],
							],
							'ddwcoc'
						),
						ddfw_locked_field(
							[
								'type'        => 'text',
								'label'       => esc_html__( 'Heading', 'devdiggers-order-communication-for-woocommerce' ),
								'description' => esc_html__( 'Main title shown at the top of the email.', 'devdiggers-order-communication-for-woocommerce' ),
								'id'          => 'ddwcoc-email-store-heading',
								'value'       => $emails['store']['heading'],
							],
							'ddwcoc'
						),
						ddfw_locked_field(
							[
								'type'          => 'textarea',
								'label'         => esc_html__( 'Email Message', 'devdiggers-order-communication-for-woocommerce' ),
								'description'   => esc_html__( 'Free sends this wording. Pro lets you rewrite the whole email with placeholders.', 'devdiggers-order-communication-for-woocommerce' ),
								'id'            => 'ddwcoc-email-store-message',
								'value'         => $plain( $emails['store']['message'] ),
								'rows'          => 6,
							],
							'ddwcoc'
						),
					],
				],
				[
					'header' => [
						'heading'     => esc_html__( 'Customer Notification Email', 'devdiggers-order-communication-for-woocommerce' ),
						'description' => esc_html__( 'Sent to the customer when your team replies. It quotes the reply and has a button that opens the conversation in My Account. Private notes never send an email.', 'devdiggers-order-communication-for-woocommerce' ),
					],
					'fields' => [
						[
							'type'           => 'checkbox',
							'label'          => esc_html__( 'Enable', 'devdiggers-order-communication-for-woocommerce' ),
							'checkbox_label' => esc_html__( 'Email the customer when the store replies', 'devdiggers-order-communication-for-woocommerce' ),
							'description'    => esc_html__( 'Recommended. Most customers do not revisit their account page until they get an email.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'             => 'ddwcoc-enabled-customer-mail',
							'name'           => '_ddwcoc_enabled_customer_mail',
							'value'          => $ddwcoc_configuration['enabled_customer_mail'],
						],
						ddfw_locked_field(
							[
								'type'        => 'text',
								'label'       => esc_html__( 'Subject', 'devdiggers-order-communication-for-woocommerce' ),
								'description' => esc_html__( 'Subject line of the email. {site_title} is also supported.', 'devdiggers-order-communication-for-woocommerce' ),
								'id'          => 'ddwcoc-email-customer-subject',
								'value'       => $emails['customer']['subject'],
							],
							'ddwcoc'
						),
						ddfw_locked_field(
							[
								'type'        => 'text',
								'label'       => esc_html__( 'Heading', 'devdiggers-order-communication-for-woocommerce' ),
								'description' => esc_html__( 'Main title shown at the top of the email.', 'devdiggers-order-communication-for-woocommerce' ),
								'id'          => 'ddwcoc-email-customer-heading',
								'value'       => $emails['customer']['heading'],
							],
							'ddwcoc'
						),
						ddfw_locked_field(
							[
								'type'          => 'textarea',
								'label'         => esc_html__( 'Email Message', 'devdiggers-order-communication-for-woocommerce' ),
								'description'   => esc_html__( 'Free sends this wording. Pro lets you rewrite the whole email with placeholders.', 'devdiggers-order-communication-for-woocommerce' ),
								'id'            => 'ddwcoc-email-customer-message',
								'value'         => $plain( $emails['customer']['message'] ),
								'rows'          => 6,
							],
							'ddwcoc'
						),
					],
				],
			];

			( new DDFW_Layout() )->get_form_section_layout( $args, 'ddwcoc-emails-configuration-fields', [] );
		}
	}
}
