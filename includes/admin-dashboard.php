<?php
/**
 * This file handles all admin dashboard functionalities.
 *
 * @package DevDiggers Order Communication for WooCommerce
 * @version 1.3.0
 */

namespace DDWCOrderCommunication\Includes;

use DevDiggers\Framework\Includes\DDFW_Plugin_Dashboard;
use DevDiggers\Framework\Includes\DDFW_Assets;
use DevDiggers\Framework\Includes\DDFW_Layout;
use DevDiggers\Framework\Includes\DDFW_SVG;
use DDWCOrderCommunication\Templates\Admin;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'DDWCOC_Admin_Dashboard' ) ) {
	/**
	 * Admin Dashboard Class
	 */
	class DDWCOC_Admin_Dashboard {
		/**
		 * Configuration Variable
		 *
		 * @var array
		 */
		protected $ddwcoc_configuration;

		/**
		 * Dashboard Variable
		 *
		 * @var DDFW_Plugin_Dashboard
		 */
		protected $dashboard;

		/**
		 * Construct
		 *
		 * @param array $ddwcoc_configuration Configuration.
		 */
		public function __construct( $ddwcoc_configuration ) {
			$this->ddwcoc_configuration = $ddwcoc_configuration;
			add_action( 'admin_enqueue_scripts', [ $this, 'ddwcoc_enqueue_admin_scripts' ] );
			add_filter( 'ddfw_modify_svg_icons', [ $this, 'ddwcoc_modify_svg_icons' ], 10, 2 );
			add_filter( 'admin_footer_text', [ $this, 'ddwcoc_set_admin_footer_text' ], 99 );
			$this->ddwcoc_add_dashboard_menu();
		}

		/**
		 * Add Admin menu function
		 *
		 * @return void
		 */
		public function ddwcoc_add_dashboard_menu() {
			ob_start();
			?>
			<?php echo wp_kses( \DDWCOrderCommunication\Includes\Admin\DDWCOC_Setup_Wizard::ddwcoc_get_logo(), ddfw_kses_allowed_svg_tags() ); ?>
			<?php esc_html_e( 'Order Communication', 'devdiggers-order-communication-for-woocommerce' ); ?>
			<?php
			$plugin_name = ob_get_clean();

			$args = [
				'page_title'              => esc_html__( 'Order Communication', 'devdiggers-order-communication-for-woocommerce' ),
				'menu_title'              => esc_html__( 'Order Communication', 'devdiggers-order-communication-for-woocommerce' ),
				'slug'                    => 'ddwcoc-dashboard',
				'plugin_name'             => $plugin_name,
				'upgrade_url'             => 'https://devdiggers.com/product/woocommerce-order-communication/',
				'menus'                   => [
					'dashboard'      => [
						'label'    => esc_html__( 'Dashboard', 'devdiggers-order-communication-for-woocommerce' ),
						'callback' => [ $this, 'ddwcoc_get_dashboard_template' ],
						'layout'   => 'full-width',
					],
					'inbox'          => [
						'label'    => esc_html__( 'Inbox', 'devdiggers-order-communication-for-woocommerce' ),
						'callback' => [ $this, 'ddwcoc_get_inbox_template' ],
						'layout'   => 'full-width',
					],
					'communications' => [
						'label'    => esc_html__( 'Communications', 'devdiggers-order-communication-for-woocommerce' ),
						'callback' => [ $this, 'ddwcoc_get_communications_template' ],
						'layout'   => 'full-width',
					],
					'templates'      => [
						'label'    => esc_html__( 'Templates', 'devdiggers-order-communication-for-woocommerce' ),
						'callback' => [ $this, 'ddwcoc_get_templates_template' ],
						'layout'   => 'full-width',
					],
					'configuration'  => [
						'label'  => esc_html__( 'Configuration', 'devdiggers-order-communication-for-woocommerce' ),
						'layout' => 'sidebar',
						'tabs'   => [
							'general'        => [
								'label'    => esc_html__( 'General', 'devdiggers-order-communication-for-woocommerce' ),
								'icon'     => DDFW_SVG::get_svg_icon(
									'general',
									true,
									[ 'size' => 18 ]
								),
								'callback' => [ $this, 'ddwcoc_get_general_configuration_template' ],
							],
							'chat'           => [
								'label'    => esc_html__( 'Chat', 'devdiggers-order-communication-for-woocommerce' ),
								'icon'     => DDFW_SVG::get_svg_icon(
									'chat',
									true,
									[ 'size' => 18 ]
								),
								'callback' => [ $this, 'ddwcoc_get_chat_configuration_template' ],
							],
							'automation'     => [
								'label'    => esc_html__( 'Automation', 'devdiggers-order-communication-for-woocommerce' ),
								'icon'     => DDFW_SVG::get_svg_icon(
									'automation',
									true,
									[ 'size' => 18 ]
								),
								'callback' => [ $this, 'ddwcoc_get_automation_configuration_template' ],
							],
							'emails'         => [
								'label'    => esc_html__( 'Emails', 'devdiggers-order-communication-for-woocommerce' ),
								'icon'     => DDFW_SVG::get_svg_icon(
									'mail',
									true,
									[ 'size' => 18 ]
								),
								'callback' => [ $this, 'ddwcoc_get_emails_configuration_template' ],
							],
							'business-hours' => [
								'label'    => esc_html__( 'Business Hours', 'devdiggers-order-communication-for-woocommerce' ),
								'icon'     => DDFW_SVG::get_svg_icon(
									'clock',
									true,
									[ 'size' => 18 ]
								),
								'callback' => [ $this, 'ddwcoc_get_business_hours_configuration_template' ],
							],
							'limits'         => [
								'label'    => esc_html__( 'Message Limits', 'devdiggers-order-communication-for-woocommerce' ),
								'icon'     => DDFW_SVG::get_svg_icon(
									'shield',
									true,
									[ 'size' => 18 ]
								),
								'callback' => [ $this, 'ddwcoc_get_limits_configuration_template' ],
							],
						],
					],
				],
			];

			$this->dashboard = new DDFW_Plugin_Dashboard( $args );
		}

		/**
		 * Get Dashboard Template
		 *
		 * @return void
		 */
		public function ddwcoc_get_dashboard_template() {
			new Admin\Dashboard\DDWCOC_Dashboard_Template( $this->ddwcoc_configuration );
		}

		/**
		 * Templates page: a Pro feature.
		 *
		 * @return void
		 */
		public function ddwcoc_get_templates_template() {
			$this->ddwcoc_upgrade_card(
				'templates',
				esc_html__( 'Answer common questions in one click with saved replies', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Write your usual answers once and insert them into any conversation from the chat box. Order details fill themselves in.', 'devdiggers-order-communication-for-woocommerce' ),
				[
					esc_html__( 'Unlimited saved replies, grouped by category', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Search and insert a reply from the chat box', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Customer name, order number, total and status filled in for you', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'See which replies your team uses most', 'devdiggers-order-communication-for-woocommerce' ),
				]
			);
		}

		/**
		 * Get Inbox Template
		 *
		 * @return void
		 */
		public function ddwcoc_get_inbox_template() {
			new Admin\Inbox\DDWCOC_Inbox_Template( $this->ddwcoc_configuration );
		}

		/**
		 * Communications page: a Pro feature.
		 *
		 * @return void
		 */
		public function ddwcoc_get_communications_template() {
			$this->ddwcoc_upgrade_card(
				'communications',
				esc_html__( 'Manage every conversation from one table', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'A sortable list of all order conversations with filters and bulk actions, plus exports for your records.', 'devdiggers-order-communication-for-woocommerce' ),
				[
					esc_html__( 'Filter by unread, read, replied or closed', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Mark many conversations read, unread or closed at once', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Export conversations to CSV', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Print a clean transcript of any order conversation', 'devdiggers-order-communication-for-woocommerce' ),
				]
			);
		}

		/**
		 * Screenshot for an upgrade card, or an empty string when none is bundled.
		 *
		 * An empty string makes the card render on its own, so a screenshot can be dropped
		 * into assets/images/pro/ later without a code change.
		 *
		 * @param string $name File name without the extension.
		 * @return string
		 */
		public static function ddwcoc_get_pro_image( $name ) {
			$file = 'assets/images/pro/' . sanitize_file_name( $name ) . '.webp';

			return file_exists( DDWCOC_PLUGIN_FILE . $file ) ? DDWCOC_PLUGIN_URL . $file : '';
		}

		/**
		 * One full-page upgrade card.
		 *
		 * @param string $image    Screenshot name.
		 * @param string $heading  Heading.
		 * @param string $text     Description.
		 * @param array  $features Feature bullets.
		 * @return void
		 */
		protected function ddwcoc_upgrade_card( $image, $heading, $text, $features ) {
			ddfw_upgrade_to_pro_section(
				[
					'image_url'     => self::ddwcoc_get_pro_image( $image ),
					'heading'       => $heading,
					'description'   => $text,
					'list_features' => $features,
					'upgrade_url'   => 'https://devdiggers.com/product/woocommerce-order-communication/',
				]
			);
		}

		/**
		 * A Pro-only configuration tab: a section header with the upgrade card under it.
		 *
		 * @param string $image    Screenshot name.
		 * @param string $title    Section heading.
		 * @param string $intro    Section description.
		 * @param string $heading  Card heading.
		 * @param string $text     Card description.
		 * @param array  $features Feature bullets.
		 * @return void
		 */
		protected function ddwcoc_pro_tab_card( $image, $title, $intro, $heading, $text, $features ) {
			$sections = [
				[
					'header'            => [
						'heading'     => $title,
						'description' => $intro,
					],
					'after_header_html' => ddfw_get_upgrade_to_pro_section(
						[
							'image_url'     => self::ddwcoc_get_pro_image( $image ),
							'heading'       => $heading,
							'description'   => $text,
							'list_features' => $features,
							'upgrade_url'   => 'https://devdiggers.com/product/woocommerce-order-communication/',
						]
					),
				],
			];

			( new DDFW_Layout() )->get_form_section_layout( $sections );
		}

		/**
		 * Footer text on the plugin pages.
		 *
		 * @param string $text Footer text.
		 * @return string
		 */
		public function ddwcoc_set_admin_footer_text( $text ) {
			if ( ! $this->dashboard->is_a_plugin_page() ) {
				return $text;
			}

			return sprintf(
				/* translators: 1: plugin name, 2: five-star rating link. */
				esc_html__( 'Thank you for using %1$s. If you like it, please leave us a %2$s rating. It means a lot to us!', 'devdiggers-order-communication-for-woocommerce' ),
				'<strong>' . esc_html__( 'DevDiggers Order Communication for WooCommerce', 'devdiggers-order-communication-for-woocommerce' ) . '</strong>',
				'<a href="https://wordpress.org/support/plugin/devdiggers-order-communication-for-woocommerce/reviews/#new-post" target="_blank" aria-label="' . esc_attr__( 'five star', 'devdiggers-order-communication-for-woocommerce' ) . '"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 192 32" height="10"><path d="M16 26.534L6.111 32 8 20.422l-8-8.2 11.056-1.688L16 0l4.944 10.534L32 12.223l-8 8.2L25.889 32zm40 0L46.111 32 48 20.422l-8-8.2 11.056-1.688L56 0l4.944 10.534L72 12.223l-8 8.2L65.889 32zm40 0L86.111 32 88 20.422l-8-8.2 11.056-1.688L96 0l4.944 10.534L112 12.223l-8 8.2L105.889 32zm40 0L126.111 32 128 20.422l-8-8.2 11.056-1.688L136 0l4.944 10.534L152 12.223l-8 8.2L145.889 32zm40 0L166.111 32 168 20.422l-8-8.2 11.056-1.688L176 0l4.944 10.534L192 12.223l-8 8.2L185.889 32z" fill="#F5A623" fill-rule="evenodd"/></svg></a>'
			);
		}

		/**
		 * Get General Configuration Template
		 *
		 * @return void
		 */
		public function ddwcoc_get_general_configuration_template() {
			new Admin\Configuration\DDWCOC_Configuration_Template( $this->ddwcoc_configuration );
		}

		/**
		 * Get Chat Configuration Template
		 *
		 * @return void
		 */
		public function ddwcoc_get_chat_configuration_template() {
			new Admin\Configuration\DDWCOC_Chat_Configuration_Template( $this->ddwcoc_configuration );
		}

		/**
		 * Automation tab: a Pro feature.
		 *
		 * @return void
		 */
		public function ddwcoc_get_automation_configuration_template() {
			$this->ddwcoc_pro_tab_card(
				'automation',
				esc_html__( 'Automation', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Keep customers informed and acknowledged without typing a word.', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Reply instantly and post order updates by themselves', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Pro answers every customer message right away and writes into the conversation each time an order changes status.', 'devdiggers-order-communication-for-woocommerce' ),
				[
					esc_html__( 'Instant auto-reply, with an optional delay so a person can answer first', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Your own message for each order status, posted and emailed automatically', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Placeholders for customer name, order number, date, status and total', 'devdiggers-order-communication-for-woocommerce' ),
				]
			);
		}

		/**
		 * Get Emails Configuration Template
		 *
		 * @return void
		 */
		public function ddwcoc_get_emails_configuration_template() {
			new Admin\Configuration\DDWCOC_Emails_Configuration_Template( $this->ddwcoc_configuration );
		}

		/**
		 * Business Hours tab: a Pro feature.
		 *
		 * @return void
		 */
		public function ddwcoc_get_business_hours_configuration_template() {
			$this->ddwcoc_pro_tab_card(
				'business-hours',
				esc_html__( 'Business Hours', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Tell customers when your team is around, and what happens when it is not.', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Set expectations with business hours', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Pro shows customers whether your team is online and handles messages sent while you are closed.', 'devdiggers-order-communication-for-woocommerce' ),
				[
					esc_html__( 'Opening hours and working days in your store timezone', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Online or away status in the chat header', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Block messages after hours, or accept them with an automatic notice', 'devdiggers-order-communication-for-woocommerce' ),
				]
			);
		}

		/**
		 * Message Limits tab: a Pro feature.
		 *
		 * @return void
		 */
		public function ddwcoc_get_limits_configuration_template() {
			$this->ddwcoc_pro_tab_card(
				'limits',
				esc_html__( 'Message Limits', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Protect your team from spam and floods of messages.', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Stop message floods before they reach your team', 'devdiggers-order-communication-for-woocommerce' ),
				esc_html__( 'Pro caps how many messages one customer can send per day. Staff replies are never limited.', 'devdiggers-order-communication-for-woocommerce' ),
				[
					esc_html__( 'Daily message limit per customer', 'devdiggers-order-communication-for-woocommerce' ),
					esc_html__( 'Your own message when the limit is reached', 'devdiggers-order-communication-for-woocommerce' ),
				]
			);
		}

		/**
		 * Enqueue admin scripts function
		 *
		 * @return void
		 */
		public function ddwcoc_enqueue_admin_scripts() {
			$screen = get_current_screen();

			// Orders list Messages column.
			if ( $screen && in_array( $screen->id, [ 'woocommerce_page_wc-orders', 'edit-shop_order', 'shop_order' ], true ) ) {
				\DDWCOrderCommunication\Includes\Common\DDWCOC_Chat_Assets::ddwcoc_enqueue_style();
			}

			if ( $this->dashboard->is_a_plugin_page() ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only screen check.
				if ( isset( $_GET['menu'] ) && 'inbox' === sanitize_key( wp_unslash( $_GET['menu'] ) ) ) {
					\DDWCOrderCommunication\Includes\Common\DDWCOC_Chat_Assets::ddwcoc_enqueue( $this->ddwcoc_configuration );
				}

				wp_enqueue_style( 'ddwcoc-admin-style', DDWCOC_PLUGIN_URL . 'assets/css/admin.css', [ DDFW_Assets::$framework_css_handle ], filemtime( DDWCOC_PLUGIN_FILE . 'assets/css/admin.css' ) );
				wp_enqueue_script( 'ddwcoc-admin-script', DDWCOC_PLUGIN_URL . 'assets/js/admin.js', [ DDFW_Assets::$framework_js_handle ], filemtime( DDWCOC_PLUGIN_FILE . 'assets/js/admin.js' ), true );

				wp_localize_script(
					'ddwcoc-admin-script',
					'ddwcocAdminObj',
					[
						'awaiting' => \DDWCOrderCommunication\Helper\DDWCOC_Communication_Helper::ddwcoc_get_total_unread(),
					]
				);
			}
		}

		/**
		 * Add the plugin's own icons to the framework SVG set.
		 *
		 * @param array $svg_icons Registered icons.
		 * @param array $args      Icon arguments: size, stroke_color, stroke_width, fill.
		 * @return array
		 */
		public function ddwcoc_modify_svg_icons( $svg_icons, $args ) {
			$size         = ! empty( $args['size'] ) ? $args['size'] : '24';
			$size_attr    = 'width="' . $size . '" height="' . $size . '"';
			$stroke_color = ! empty( $args['stroke_color'] ) ? $args['stroke_color'] : 'currentColor';
			$stroke_width = isset( $args['stroke_width'] ) ? $args['stroke_width'] : '2';
			$fill         = ! empty( $args['fill'] ) ? $args['fill'] : 'none';

			$svg_icons['chat'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>';

			$svg_icons['users'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>';

			$svg_icons['clock'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16.5 12"></polyline></svg>';

			$svg_icons['automation'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>';

			$svg_icons['inbox'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><polyline points="22 12 16 12 14 15 10 15 8 12 2 12"></polyline><path d="M5.45 5.11 2 12v6a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-6l-3.45-6.89A2 2 0 0 0 16.76 4H7.24a2 2 0 0 0-1.79 1.11z"></path></svg>';

			$svg_icons['alert'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';

			$svg_icons['download'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>';

			$svg_icons['search'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><path d="m21 21-4.3-4.3"></path></svg>';

			$svg_icons['mail'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"></rect><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path></svg>';

			$svg_icons['shield'] = '<svg xmlns="http://www.w3.org/2000/svg" ' . $size_attr . ' viewBox="0 0 24 24" fill="' . $fill . '" stroke="' . $stroke_color . '" stroke-width="' . $stroke_width . '" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"></path></svg>';

			return $svg_icons;
		}
	}
}
