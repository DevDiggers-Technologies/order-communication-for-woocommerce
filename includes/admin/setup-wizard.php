<?php
/**
 * Setup wizard: first-run onboarding built on the framework wizard.
 *
 * @author DevDiggers
 * @version 2.0.0
 * @package DevDiggers Order Communication for WooCommerce
 */

namespace DDWCOrderCommunication\Includes\Admin;

use DevDiggers\Framework\Includes\DDFW_Form_Field;
use DevDiggers\Framework\Includes\DDFW_Setup_Wizard;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Setup_Wizard' ) ) {
	/**
	 * Setup wizard class
	 */
	class DDWCOC_Setup_Wizard {
		/**
		 * Plugin slug.
		 */
		const SLUG = 'devdiggers-order-communication-for-woocommerce';

		/**
		 * Construct
		 */
		public function __construct() {
			if ( ! class_exists( DDFW_Setup_Wizard::class ) ) {
				return;
			}

			// Stores that configured the plugin before the wizard existed skip it.
			if ( ! get_option( 'ddfw_setup_wizard_completed_' . self::SLUG ) && false !== get_option( '_ddwcoc_enabled', false ) ) {
				update_option( 'ddfw_setup_wizard_completed_' . self::SLUG, true );
			}

			new DDFW_Setup_Wizard( $this->ddwcoc_get_wizard_config() );
		}

		/**
		 * Logo markup.
		 *
		 * @param int $size Size.
		 * @return string
		 */
		public static function ddwcoc_get_logo( $size = 32 ) {
			// Order bubble (customer) answered by a filled store bubble with a check: a resolved order conversation.
			return '<svg width="' . absint( $size ) . '" height="' . absint( $size ) . '" viewBox="0 0 30 30" fill="none" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg">'
				. '<circle cx="15" cy="15" r="14" fill="var(--ddfw-tab-background-color)"/>'
				. '<path d="M13 7.5A2.5 2.5 0 0 1 15.5 5h7A2.5 2.5 0 0 1 25 7.5v5a2.5 2.5 0 0 1-2.5 2.5H22v2.5L19 15h-3.5A2.5 2.5 0 0 1 13 12.5z" fill="var(--ddfw-primary-color)"/>'
				. '<path d="m16.3 10.1 1.8 1.8 3.6-3.6" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>'
				. '<path d="M5 13.5A2.5 2.5 0 0 1 7.5 11H16a2.5 2.5 0 0 1 2.5 2.5v6A2.5 2.5 0 0 1 16 22h-5l-4 3v-3h0A2.5 2.5 0 0 1 5 19.5z" fill="#fff" stroke="var(--ddfw-primary-color)" stroke-width="1.5" stroke-linejoin="round"/>'
				. '<circle cx="8.8" cy="16.5" r="1" fill="var(--ddfw-primary-color)"/><circle cx="11.75" cy="16.5" r="1" fill="var(--ddfw-primary-color)"/><circle cx="14.7" cy="16.5" r="1" fill="var(--ddfw-primary-color)"/>'
				. '</svg>';
		}

		/**
		 * Wizard configuration.
		 *
		 * @return array
		 */
		public function ddwcoc_get_wizard_config() {
			return [
				'plugin_slug'    => self::SLUG,
				'plugin_file'    => self::SLUG . '/functions.php',
				'dashboard_page' => 'ddwcoc-dashboard',
				'redirect_url'   => admin_url( 'admin.php?page=ddwcoc-dashboard' ),
				'brand'          => [
					'name'        => esc_html__( 'Order Communication', 'devdiggers-order-communication-for-woocommerce' ),
					'description' => esc_html__( 'Welcome to DevDiggers Order Communication for WooCommerce! Follow these quick steps to give every order its own live conversation with your customer.', 'devdiggers-order-communication-for-woocommerce' ),
				],
				'steps'          => [
					'welcome'    => [
						'label'         => esc_html__( 'Welcome', 'devdiggers-order-communication-for-woocommerce' ),
						'view_callback' => [ $this, 'ddwcoc_welcome_view' ],
					],
					'general'    => [
						'label'         => esc_html__( 'General', 'devdiggers-order-communication-for-woocommerce' ),
						'title'         => esc_html__( 'Messaging Basics', 'devdiggers-order-communication-for-woocommerce' ),
						'description'   => esc_html__( 'Turn on order conversations and choose who gets notified.', 'devdiggers-order-communication-for-woocommerce' ),
						'view_callback' => [ $this, 'ddwcoc_general_view' ],
						'save_callback' => [ $this, 'ddwcoc_save_fields' ],
					],
					'ready'      => [
						'label'             => esc_html__( 'Ready!', 'devdiggers-order-communication-for-woocommerce' ),
						'ready_title'       => esc_html__( 'You are all set! Customers can now message you from every order.', 'devdiggers-order-communication-for-woocommerce' ),
						'ready_description' => esc_html__( 'Answer customers from the Inbox or right on the order screen. Match the chat to your brand under Configuration > Chat.', 'devdiggers-order-communication-for-woocommerce' ),
					],
				],
			];
		}

		/**
		 * Welcome step.
		 *
		 * @return void
		 */
		public function ddwcoc_welcome_view() {
			?>
			<div class="ddfw-setup-wizard-ready ddfw-setup-wizard-onboarding">
				<div class="ddfw-success-icon-wrap"><?php echo wp_kses( self::ddwcoc_get_logo( 100 ), ddfw_kses_allowed_svg_tags() ); ?></div>
				<h2 class="ddfw-setup-wizard-ready-title"><?php esc_html_e( 'Welcome to Order Communication!', 'devdiggers-order-communication-for-woocommerce' ); ?></h2>
				<p class="ddfw-setup-wizard-ready-desc"><?php esc_html_e( 'Give every order a private, live chat between your store and the customer, with read receipts, email alerts and one shared Inbox for your team.', 'devdiggers-order-communication-for-woocommerce' ); ?></p>
			</div>
			<?php
		}

		/**
		 * Render wizard fields.
		 *
		 * @param array $fields Fields.
		 * @return void
		 */
		protected function ddwcoc_render_fields( $fields ) {
			?>
			<div class="ddfw-fields-section">
				<table class="form-table">
					<tbody>
						<?php
						foreach ( $fields as $field ) {
							DDFW_Form_Field::display_form_field( $field );
						}
						?>
					</tbody>
				</table>
			</div>
			<?php
		}

		/**
		 * Checkbox field definition.
		 *
		 * @param string $option  Option suffix.
		 * @param string $label   Label.
		 * @param string $description Description.
		 * @param string $default_value Default value.
		 * @return array
		 */
		protected function ddwcoc_checkbox( $option, $label, $description, $default_value = 'yes' ) {
			return [
				'type'           => 'checkbox',
				'label'          => $label,
				'checkbox_label' => $label,
				'description'    => $description,
				'id'             => 'ddwcoc-wizard-' . $option,
				'name'           => '_ddwcoc_' . $option,
				'value'          => get_option( '_ddwcoc_' . $option, $default_value ),
			];
		}

		/**
		 * General step.
		 *
		 * @return void
		 */
		public function ddwcoc_general_view() {
			$this->ddwcoc_render_fields(
				[
					$this->ddwcoc_checkbox( 'enabled', esc_html__( 'Enable Order Communication', 'devdiggers-order-communication-for-woocommerce' ), esc_html__( 'Show the conversation on each order in My Account.', 'devdiggers-order-communication-for-woocommerce' ) ),
					$this->ddwcoc_checkbox( 'enabled_admin_mail', esc_html__( 'Email the Store on New Messages', 'devdiggers-order-communication-for-woocommerce' ), esc_html__( 'Get an email when a customer writes.', 'devdiggers-order-communication-for-woocommerce' ) ),
					$this->ddwcoc_checkbox( 'enabled_customer_mail', esc_html__( 'Email Customers on Replies', 'devdiggers-order-communication-for-woocommerce' ), esc_html__( 'Customers get an email with a link back to the conversation.', 'devdiggers-order-communication-for-woocommerce' ) ),
				]
			);
		}

		/**
		 * Save the submitted plugin options.
		 *
		 * @param array $form_data List of [ name, value ].
		 * @return true
		 */
		public function ddwcoc_save_fields( $form_data ) {
			// Only the options this wizard shows. Anything else posted is ignored.
			$allowed = [ '_ddwcoc_enabled', '_ddwcoc_enabled_admin_mail', '_ddwcoc_enabled_customer_mail' ];

			foreach ( (array) $form_data as $field ) {
				if ( empty( $field['name'] ) || ! in_array( $field['name'], $allowed, true ) ) {
					continue;
				}

				update_option( $field['name'], 'yes' === ( $field['value'] ?? '' ) ? 'yes' : '' );
			}

			return true;
		}
	}
}
