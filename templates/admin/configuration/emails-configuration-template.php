<?php
/**
 * Emails configuration template.
 *
 * @package WooCommerce Order Communication
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
			$emails       = $ddwcoc_configuration['email_settings'];
			$placeholders = sprintf(
				/* translators: %s: placeholder list. */
				esc_html__( 'Available placeholders: %s', 'devdiggers-order-communication-for-woocommerce' ),
				'<strong>{message}</strong>, <strong>{reply_button}</strong>, <strong>{reply_url}</strong>, <strong>{customer_name}</strong>, <strong>{order_number}</strong>, <strong>{order_date}</strong>, <strong>{order_status}</strong>, <strong>{order_total}</strong>, <strong>{site_name}</strong>'
			);

			$message_help = esc_html__( 'The full body of the email. {message} is replaced with the chat message in a quote box and {reply_button} with a button that opens the conversation. If you remove {message}, the chat message is added at the end so it is never lost.', 'devdiggers-order-communication-for-woocommerce' );

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
						[
							'type'        => 'text',
							'label'       => esc_html__( 'Subject', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Subject line of the email. {site_title} is also supported.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-email-store-subject',
							'name'        => '_ddwcoc_email_settings[store][subject]',
							'value'       => $emails['store']['subject'],
						],
						[
							'type'        => 'text',
							'label'       => esc_html__( 'Heading', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Main title shown at the top of the email.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-email-store-heading',
							'name'        => '_ddwcoc_email_settings[store][heading]',
							'value'       => $emails['store']['heading'],
						],
						[
							'type'          => 'editor',
							'label'         => esc_html__( 'Email Message', 'devdiggers-order-communication-for-woocommerce' ),
							'description'   => $message_help . '<br />' . $placeholders,
							'id'            => 'ddwcoc-email-store-message',
							'name'          => '_ddwcoc_email_settings[store][message]',
							'value'         => $emails['store']['message'],
							'textarea_rows' => 10,
						],
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
						[
							'type'        => 'text',
							'label'       => esc_html__( 'Subject', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Subject line of the email. {site_title} is also supported.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-email-customer-subject',
							'name'        => '_ddwcoc_email_settings[customer][subject]',
							'value'       => $emails['customer']['subject'],
						],
						[
							'type'        => 'text',
							'label'       => esc_html__( 'Heading', 'devdiggers-order-communication-for-woocommerce' ),
							'description' => esc_html__( 'Main title shown at the top of the email.', 'devdiggers-order-communication-for-woocommerce' ),
							'id'          => 'ddwcoc-email-customer-heading',
							'name'        => '_ddwcoc_email_settings[customer][heading]',
							'value'       => $emails['customer']['heading'],
						],
						[
							'type'          => 'editor',
							'label'         => esc_html__( 'Email Message', 'devdiggers-order-communication-for-woocommerce' ),
							'description'   => $message_help . '<br />' . $placeholders,
							'id'            => 'ddwcoc-email-customer-message',
							'name'          => '_ddwcoc_email_settings[customer][message]',
							'value'         => $emails['customer']['message'],
							'textarea_rows' => 10,
						],
					],
				],
			];

			( new DDFW_Layout() )->get_form_section_layout( $args, 'ddwcoc-emails-configuration-fields', [] );
		}
	}
}
