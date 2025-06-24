<?php

namespace WC_Manual_Invoices;

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Manual_Invoices_Admin_Options {


	/**
	 * Plugin settings tabs
	 *
	 * @since    1.0.0
	 * @access   public
	 * @var      array    $plugin_settings_tabs The settings tab.
	 */
	public $plugin_settings_tabs = array();

	/**
	 * The WC_Manual_Invoices_Admin_Options instance is stored in a static field. This field is an
	 * array, because we'll allow our WC_Manual_Invoices_Admin_Options to have subclasses. Each item in
	 * this array will be an instance of a specific WC_Manual_Invoices_Admin_Options subclass. You'll
	 * see how this works in a moment.
	 */
	private static $instances = array();

	/**
	 * The WC_Manual_Invoices_Admin_Options constructor should always be private to prevent direct
	 * construction calls with the `new` operator.
	 */
	protected function __construct() {
		add_action( 'admin_menu', array( $this, 'wc_manual_invoices_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'wc_manual_invoices_init_plugin_options' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wc_manual_invoices_enqueue_assets' ) );
	}


	/**
	 * WC_Manual_Invoices_Admin_Options should not be cloneable.
	 */
	protected function __clone() {
		throw new \Exception( 'Cloning is not allowed.' );
	}


	/**
	 * WC_Manual_Invoices_Admin_Options should not be restorable from strings.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a WC_Manual_Invoices_Admin_Options.' );
	}


	/**
	 * This is the static method that controls the access to the WC_Manual_Invoices_Admin_Options
	 * instance. On the first run, it creates a WC_Manual_Invoices_Admin_Options object and places it
	 * into the static field. On subsequent runs, it returns the client existing
	 * object stored in the static field.
	 *
	 * This implementation lets you subclass the WC_Manual_Invoices_Admin_Options class while keeping
	 * just one instance of each subclass around.
	 */
	public static function getInstance(): WC_Manual_Invoices_Admin_Options {
		$cls = static::class;
		if ( ! isset( self::$instances[ $cls ] ) ) {
			self::$instances[ $cls ] = new static();
		}

		return self::$instances[ $cls ];
	}

	/**
	 * Enqueue admin scripts and styles
	 */
	public function wc_manual_invoices_enqueue_assets( $hook ) {
		if ( strpos( $hook, 'wc-manual-invoices' ) !== false || strpos( $hook, 'wc-manual-invoice-options' ) ) {
			//Enqueue WP Media Scripts
			wp_enqueue_media();

			// Enqueue JavaScript - Correct assets path
			wp_enqueue_script(
				'wc-manual-invoices-admin',
				WC_MANUAL_INVOICES_PLUGIN_URL . 'assets/js/admin.js',
				array( 'jquery', 'wc-enhanced-select' ),
				WC_MANUAL_INVOICES_VERSION,
				true
			);

			// Enqueue CSS - Correct assets path
			wp_enqueue_style(
				'wc-manual-invoices-admin',
				WC_MANUAL_INVOICES_PLUGIN_URL . 'assets/css/admin.css',
				array(),
				WC_MANUAL_INVOICES_VERSION
			);

			// Localize script for AJAX
			wp_localize_script(
				'wc-manual-invoices-admin',
				'wc_manual_invoices',
				array(
					'ajax_url'               => admin_url( 'admin-ajax.php' ),
					'nonce'                  => wp_create_nonce( 'wc_manual_invoices_nonce' ),
					'currency_symbol'        => get_woocommerce_currency_symbol(),
					'currency_position'      => get_option( 'woocommerce_currency_pos' ),
					'thousand_separator'     => wc_get_price_thousand_separator(),
					'decimal_separator'      => wc_get_price_decimal_separator(),
					'price_decimals'         => wc_get_price_decimals(),
					// Add i18n strings for JavaScript
					'i18n_select_customer'   => __( 'Select a customer...', 'wc-manual-invoices' ),
					'i18n_search_customers'  => __( 'Type at least 2 characters to search customers', 'wc-manual-invoices' ),
					'i18n_searching'         => __( 'Searching...', 'wc-manual-invoices' ),
					'i18n_no_customers'      => __( 'No customers found', 'wc-manual-invoices' ),
					'i18n_loading_more'      => __( 'Loading more results...', 'wc-manual-invoices' ),
					'i18n_select_product'    => __( 'Search for a product...', 'wc-manual-invoices' ),
					'i18n_search_products'   => __( 'Type at least 2 characters to search products', 'wc-manual-invoices' ),
					'i18n_no_products'       => __( 'No products found', 'wc-manual-invoices' ),
					'i18n_customer_required' => __( 'Please select a customer or enter email address.', 'wc-manual-invoices' ),
					'i18n_items_required'    => __( 'Please add at least one item to the invoice.', 'wc-manual-invoices' ),
					'i18n_confirm_clone'     => __( 'Are you sure you want to clone this invoice?', 'wc-manual-invoices' ),
					'i18n_confirm_delete'    => __( 'Are you sure you want to delete this invoice? This action cannot be undone.', 'wc-manual-invoices' ),
					'i18n_ajax_error'        => __( 'An error occurred. Please try again.', 'wc-manual-invoices' ),
				)
			);

			// Also enqueue Select2 CSS if not already loaded
			if ( ! wp_style_is( 'select2', 'enqueued' ) ) {
				wp_enqueue_style( 'select2', WC()->plugin_url() . '/assets/css/select2.css', array(), WC_VERSION );
			}
		}
	}


	public function wc_manual_invoices_admin_menu() {

		if ( empty( $GLOBALS['admin_page_hooks']['wbcomplugins'] ) ) {
			add_menu_page(
				esc_html__( 'WB Plugins', 'buddyvendor' ),
				esc_html__( 'WB Plugins', 'buddyvendor' ),
				'manage_options',
				'wbcomplugins',
				array( $this, 'wc_manual_invoices_admin_options_page' ),
				'dashicons-lightbulb',
				59
			);

			add_submenu_page(
				'wbcomplugins',
				esc_html__( 'General', 'buddyvendor' ),
				esc_html__( 'General', 'buddyvendor' ),
				'manage_options',
				'wbcomplugins'
			);
		}

		add_submenu_page(
			'wbcomplugins',
			esc_html__( 'WB Plugins', 'buddyvendor' ),
			esc_html__( 'WB Plugins', 'buddyvendor' ),
			'manage_options',
			'wc-manual-invoice-options',
			array( $this, 'wc_manual_invoices_admin_options_page' ),
		);

		add_submenu_page(
			'woocommerce',
			__( 'Invoice Settings', 'wc-manual-invoices' ),
			__( 'Invoice Settings', 'wc-manual-invoices' ),
			'manage_woocommerce',
			'wc-manual-invoices-settings',
			array( 'WC_Manual_Invoices_Settings', 'display_settings' )
		);


		// ADD THIS - PDF Settings page
		// add_submenu_page(
		// 	'woocommerce',
		// 	__( 'PDF Settings', 'wc-manual-invoices' ),
		// 	__( 'PDF Settings', 'wc-manual-invoices' ),
		// 	'manage_woocommerce',
		// 	'wc-manual-invoices-pdf-settings',
		// 	array( $this, 'display_pdf_settings_page' )
		// );
	}


	/**
	 * Display PDF settings page
	 */
	public function display_pdf_settings_page() {
		// Check user permissions
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', 'wc-manual-invoices' ) );
		}

		echo WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/admin-pdf-settings.php';

		// Include the PDF settings template
		include WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/admin-pdf-settings.php';
	}

	public function wc_manual_invoices_admin_options_page() {
		$tab = filter_input( INPUT_GET, 'tab' ) ? filter_input( INPUT_GET, 'tab' ) : 'wc-manual-invoice-welcome';
		?>
		<div class="wrap">
			<div class="wbcom-bb-plugins-offer-wrapper">
				<div id="wb_admin_logo"></div>
			</div>
			<div class="wbcom-wrap">
				<div class="bupr-header">
					<div class="wbcom_admin_header-wrapper">
						<div id="wb_admin_plugin_name">
							<?php esc_html_e( 'WooCommerce Manual Invoices Pro', 'wc-document-preview' ); ?>
							<span><?php printf( __( 'Version %s', 'wc-document-preview' ), WC_MANUAL_INVOICES_VERSION ); ?></span>
						</div>
						<?php echo do_shortcode( '[wbcom_admin_setting_header]' ); ?>
					</div>
				</div>
				<div class="wbcom-admin-settings-page">
					<?php
					settings_errors();
					$this->wc_manual_invoices_settings_tabs();
					settings_fields( $tab );
					do_settings_sections( $tab );
					?>
				</div>
			</div>
		</div>
		<?php
	}


	/**
	 * Actions performed on loading plugin settings
	 *
	 * @since    1.0.9
	 * @access   public
	 * @author   Wbcom Designs
	 */
	public function wc_manual_invoices_init_plugin_options() {

		$this->plugin_settings_tabs['wc-manual-invoice-welcome'] = esc_html__( 'Welcome', 'wc-document-preview' );
		register_setting( 'wc_manual_invoice_welcome', 'wc_manual_invoice_welcome' );
		add_settings_section( 'wc-manual-invoice-welcome', ' ', array( $this, 'wc_manual_invoice_render_welcome_page' ), 'wc-manual-invoice-welcome' );

		$this->plugin_settings_tabs['wc-manual-invoices-settings'] = esc_html__( 'Invoice Settings', 'wc-document-preview' );
		register_setting( 'wc_manual_invoices_settings', 'wc_manual_invoices_settings' );
		add_settings_section( 'wc-manual-invoices-settings', ' ', array( $this, 'wc_manual_invoice_render_invoices_settitngs_page' ), 'wc-manual-invoices-settings' );

		$this->plugin_settings_tabs['wc-manual-invoice-pdf-settigns'] = esc_html__( 'PDF Settings', 'wc-document-preview' );
		register_setting( 'wc-manual-invoice-pdf-settigns', 'wc_manual_invoice_pdf_settigns' );
		add_settings_section( 'wc-manual-invoice-pdf-settigns', ' ', array( $this, 'wc_manual_invoice_render_pdf_settigns_page' ), 'wc-manual-invoice-pdf-settigns' );
	}




	/**
	 * Actions performed to create tabs on the sub menu page.
	 *
	 * @since    1.0.0
	 */
	public function wc_manual_invoices_settings_tabs() {
		$current_tab = filter_input( INPUT_GET, 'tab' ) ? filter_input( INPUT_GET, 'tab' ) : 'wc-manual-invoice-welcome';
		echo '<div class="wbcom-tabs-section"><div class="nav-tab-wrapper"><div class="wb-responsive-menu"><span>' . esc_html( 'Menu' ) . '</span><input class="wb-toggle-btn" type="checkbox" id="wb-toggle-btn"><label class="wb-toggle-icon" for="wb-toggle-btn"><span class="wb-icon-bars"></span></label></div><ul>';
		foreach ( $this->plugin_settings_tabs as $tab_key => $tab_caption ) {
			$active = $current_tab === $tab_key ? 'nav-tab-active' : '';
			echo '<li><a class="nav-tab ' . esc_attr( $active ) . '" id="' . esc_attr( $tab_key ) . '-tab" href="?page=wc-manual-invoice-options&tab=' . esc_attr( $tab_key ) . '">' . esc_attr( $tab_caption ) . '</a></li>';
		}
		echo '</div></ul></div>';
	}


	public function wc_manual_invoice_render_welcome_page() {
		include plugin_dir_path( __DIR__ ) . 'templates/wbcom/welcome-page.php';
	}

	public function wc_manual_invoice_render_invoices_settitngs_page() {
		include plugin_dir_path( __DIR__ ) . 'templates/wbcom/invoices-settings-page.php';
	}

	public function wc_manual_invoice_render_pdf_settigns_page() {
		include plugin_dir_path( __DIR__ ) . 'templates/wbcom/pdf-settigns-page.php';
	}
}
