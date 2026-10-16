<?php
/**
 * Email Notification Handler
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 2.0.0
 */

namespace DDWCOrderCommunication\Includes;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Email_Notification_Handler' ) ) {
	/**
	 * Email Notification Handler class.
	 */
	class DDWCOC_Email_Notification_Handler extends \WC_Email {
		/**
		 * Message lines.
		 *
		 * @var array
		 */
		public $email_message = [];

		/**
		 * Recipient first name.
		 *
		 * @var string
		 */
		public $recipient_name = '';

		/**
		 * Subject and heading for the current send, from the plugin Emails settings.
		 *
		 * @var array
		 */
		protected $ddwcoc_overrides = [];

		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->id             = 'ddwcoc_notification';
			$this->title          = esc_html__( 'Order Communication: New Message', 'devdiggers-order-communication-for-woocommerce' );
			$this->description    = esc_html__( 'Sent when a customer or the store posts a new message on an order conversation. Store notifications go to the recipient set below; customer notifications go to the billing email.', 'devdiggers-order-communication-for-woocommerce' );
			$this->template_html  = 'emails/email-template.php';
			$this->template_plain = 'emails/plain/email-template.php';
			$this->template_base  = DDWCOC_PLUGIN_FILE . 'templates/';
			$this->placeholders   = [ '{order_number}' => '' ];

			add_action( 'ddwcoc_mail_notification', [ $this, 'trigger' ] );

			parent::__construct();

			$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		}

		/**
		 * Default subject.
		 *
		 * @return string
		 */
		public function get_default_subject() {
			return esc_html__( '[{site_title}] New message on order #{order_number}', 'devdiggers-order-communication-for-woocommerce' );
		}

		/**
		 * Default heading.
		 *
		 * @return string
		 */
		public function get_default_heading() {
			return esc_html__( 'You have a new message', 'devdiggers-order-communication-for-woocommerce' );
		}

		/**
		 * Trigger.
		 *
		 * @param array $data email, name, order, message lines.
		 * @return void
		 */
		public function trigger( $data ) {
			if ( empty( $data ) ) {
				return;
			}

			$this->setup_locale();

			$this->email_message    = (array) $data['message'];
			$this->recipient_name   = ! empty( $data['name'] ) ? $data['name'] : '';
			$this->ddwcoc_overrides = array_filter(
				[
					'subject' => $data['subject'] ?? '',
					'heading' => $data['heading'] ?? '',
				]
			);
			$recipient              = ! empty( $data['email'] ) ? $data['email'] : $this->get_option( 'recipient', get_option( 'admin_email' ) );

			if ( ! empty( $data['order'] ) && is_a( $data['order'], 'WC_Order' ) ) {
				$this->object                         = $data['order'];
				$this->placeholders['{order_number}'] = $data['order']->get_order_number();
			}

			if ( $this->is_enabled() && $recipient ) {
				$this->send( $recipient, $this->get_subject(), $this->get_content(), $this->get_headers(), [] );
			}

			$this->restore_locale();
		}

		/**
		 * Subject, preferring the plugin Emails settings.
		 *
		 * @return string
		 */
		public function get_subject() {
			return empty( $this->ddwcoc_overrides['subject'] ) ? parent::get_subject() : $this->format_string( $this->ddwcoc_overrides['subject'] );
		}

		/**
		 * Heading, preferring the plugin Emails settings.
		 *
		 * @return string
		 */
		public function get_heading() {
			return empty( $this->ddwcoc_overrides['heading'] ) ? parent::get_heading() : $this->format_string( $this->ddwcoc_overrides['heading'] );
		}

		/**
		 * Template arguments.
		 *
		 * @param bool $plain_text Plain text.
		 * @return array
		 */
		protected function ddwcoc_get_template_args( $plain_text ) {
			return [
				'email_heading'      => $this->get_heading(),
				'email_message'      => $this->email_message,
				'recipient_name'     => $this->recipient_name,
				'additional_content' => $this->get_additional_content(),
				'blogname'           => $this->get_blogname(),
				'sent_to_admin'      => false,
				'plain_text'         => $plain_text,
				'email'              => $this,
			];
		}

		/**
		 * Get content html.
		 *
		 * @return string
		 */
		public function get_content_html() {
			return wc_get_template_html( $this->template_html, $this->ddwcoc_get_template_args( false ), '', $this->template_base );
		}

		/**
		 * Get content plain.
		 *
		 * @return string
		 */
		public function get_content_plain() {
			return wc_get_template_html( $this->template_plain, $this->ddwcoc_get_template_args( true ), '', $this->template_base );
		}
	}
}
