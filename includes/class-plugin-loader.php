<?php


namespace WC_Manual_Invoices;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Plugin_Loader {


	private static $instances = array();

	/**
	 * Plugin_Loader should not be cloneable.
	 */
	protected function __clone() {}


	/**
	 * Plugin_Loader should not be restorable from strings.
	 */
	public function __wakeup() {
		throw new \Exception( 'Cannot unserialize a Plugin_Loader.' );
	}


	public static function getInstance(): Plugin_Loader {
		$cls = static::class;
		if ( ! isset( self::$instances[ $cls ] ) ) {
			self::$instances[ $cls ] = new static();
		}

		return self::$instances[ $cls ];
	}


	protected function __construct() {
		// Check WooCommerce compatibility
		add_action( 'before_woocommerce_init', array( $this, 'declare_compatibility' ) );

		add_action( 'woocommerce_loaded', array( $this, 'init_plugin' ) );

		register_activation_hook( WC_MANUAL_INVOICES_PLUGIN_PATH . 'wc-manual-invoices.php', array( $this, 'activate' ) );
		register_deactivation_hook( WC_MANUAL_INVOICES_PLUGIN_PATH . 'wc-manual-invoices.php', array( $this, 'deactivate' ) );

		add_action( 'plugins_loaded', array( $this, 'woocommerce_not_loaded' ), 11 );
	}


	/**
	 * Declare compatibility with WooCommerce features
	 */
	public function declare_compatibility() {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'custom_order_tables',
				__FILE__,
				true
			);

			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
				'orders_cache',
				__FILE__,
				true
			);
		}
	}

	/**
	 * Load the plugin after WP User Frontend is loaded
	 *
	 * @return void
	 */
	public function init_plugin() {

		/**
		 * Loading Auto loader
		 */
		spl_autoload_register( array( __CLASS__, 'loader' ) );

		$this->init_classes();

		$this->includes();

		$this->init_hooks();

		do_action( 'wc_manual_invoices_init' );
	}


	public function init_classes() {
		\WC_Manual_Invoices\WC_Manual_Invoices_Admin_Options::getInstance();
	}


	public function init_hooks() {
		add_action( 'init', array( $this, 'localization_setup' ) );
	}


	/**
	 * Check plugin requirements
	 */
	private function check_requirements() {
		global $wp_version;

		// Check WordPress version
		if ( version_compare( $wp_version, '6.0', '<' ) ) {
			return false;
		}

		// Check PHP version
		if ( version_compare( PHP_VERSION, '8.0', '<' ) ) {
			return false;
		}

		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return false;
		}

		// Check WooCommerce version
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '8.0', '<' ) ) {
			return false;
		}

		return true;
	}


	/**
	 * Initialize plugin for localization
	 *
	 * @uses load_plugin_textdomain()
	 */
	public function localization_setup() {
		load_plugin_textdomain( 'wc-manual-invoices', false, dirname( plugin_basename( WC_MANUAL_INVOICES_PLUGIN_BASENAME ) ) . '/languages/' );
	}


	/**
	 * Handles scenerios when WooCommerce is not active
	 *
	 * @since 2.9.27
	 *
	 * @return void
	 */
	public function woocommerce_not_loaded() {
		// Check if WooCommerce is active and loaded
		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( $this, 'woocommerce_missing_notice' ) );
			return;
		}
	}


	/**
	 * Show WooCommerce missing notice
	 */
	public function woocommerce_missing_notice() {
		$screen = get_current_screen();
		if ( $screen && $screen->id === 'plugins' ) {
			echo '<div class="error"><p>';
			printf(
				__( '%s requires WooCommerce to be installed and active. This plugin is fully compatible with High-Performance Order Storage (HPOS).', 'wc-manual-invoices' ),
				'<strong>WooCommerce Manual Invoices Pro</strong>'
			);
			echo '</p></div>';
		}
	}

	/**
	 * Auto Load class and the files
	 *
	 * @since 1.0.0
	 *
	 * @param string $class_name class name to load.
	 *
	 * @return void
	 */
	private static function loader( $class_name ) {
		// Only handle classes that start with our plugin namespace prefix
		if ( strpos( $class_name, 'WC_Manual_Invoices' ) !== 0 ) {
			return;
		}

		$relative_class = str_replace( 'WC_Manual_Invoices\\', '', $class_name );
		$file_name      = 'class-' . strtolower( str_replace( '_', '-', $relative_class ) ) . '.php';
		$full_path      = WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/' . $file_name;

		if ( file_exists( $full_path ) ) {
			require_once $full_path;
		}
	}


	private function includes() {
		require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/wbcom/wbcom-admin-settings.php';
		require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-settings.php';
	}

	/**
	 * Plugin activation
	 */
	public function activate() {
		// Check minimum requirements
		if ( ! $this->check_requirements() ) {
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( __( 'WooCommerce Manual Invoices Pro by Wbcom Designs requires WordPress 6.0+, WooCommerce 8.0+, and PHP 8.0+', 'wc-manual-invoices' ) );
		}

		// Create database tables if needed
		$this->create_tables();

		// Flush rewrite rules
		flush_rewrite_rules();

		// Set activation flag
		update_option( 'wc_manual_invoices_activated', current_time( 'mysql' ) );

		// Set default settings
		$default_settings = array(
			'default_due_days'  => 30,
			'auto_send_email'   => 'yes',
			'auto_generate_pdf' => 'yes',
			'invoice_prefix'    => 'INV-',
			'company_name'      => '',
			'company_address'   => '',
			'company_phone'     => '',
			'company_email'     => '',
			'company_logo'      => '',
			'invoice_footer'    => '',
			'reminder_enabled'  => 'no',
			'reminder_days'     => array( 7, 14, 30 ),
			'late_fee_enabled'  => 'no',
			'late_fee_amount'   => 0,
			'late_fee_type'     => 'fixed',
		);

		// Only set defaults if no settings exist
		if ( ! get_option( 'wc_manual_invoices_settings' ) ) {
			update_option( 'wc_manual_invoices_settings', $default_settings );
		}
	}


	/**
	 * Create custom database tables
	 */
	private function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Invoice reminders table
		$table_name = $wpdb->prefix . 'wc_manual_invoice_reminders';

		$sql = "CREATE TABLE $table_name (
            id int(11) NOT NULL AUTO_INCREMENT,
            order_id int(11) NOT NULL,
            reminder_date datetime DEFAULT CURRENT_TIMESTAMP,
            reminder_type varchar(50) NOT NULL,
            sent tinyint(1) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY order_id (order_id)
        ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Plugin deactivation
	 */
	public function deactivate() {
		// Clean up if needed
		flush_rewrite_rules();

		// Remove activation flag
		delete_option( 'wc_manual_invoices_activated' );
	}
}
