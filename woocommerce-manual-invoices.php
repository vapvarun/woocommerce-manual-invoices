<?php
/**
 * Plugin Name: WooCommerce Manual Invoices Pro
 * Plugin URI: https://wbcomdesigns.com/woocommerce-manual-invoices
 * Description: Create manual invoices and send "Pay Now" links to customers using WooCommerce's checkout and payment infrastructure. Fully compatible with High-Performance Order Storage (HPOS).
 * Version: 1.0.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com
 * Text Domain: wc-manual-invoices
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * Requires PHP: 8.0
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Network: false
 * Update URI: false
 * 
 * @package WooCommerce_Manual_Invoices
 * @version 1.0.0
 * @author Wbcom Designs
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('WC_MANUAL_INVOICES_VERSION', '1.0.0');
define('WC_MANUAL_INVOICES_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WC_MANUAL_INVOICES_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WC_MANUAL_INVOICES_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main plugin class
 */
class WC_Manual_Invoices_Plugin {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Check WooCommerce compatibility
        add_action('before_woocommerce_init', array($this, 'declare_compatibility'));
        
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Declare compatibility with WooCommerce features
     */
    public function declare_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
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
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active and loaded
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Wait for WooCommerce to fully initialize
        if (!did_action('woocommerce_loaded')) {
            add_action('woocommerce_loaded', array($this, 'init'));
            return;
        }
        
        // Load text domain
        load_plugin_textdomain('wc-manual-invoices', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Include required files
        $this->includes();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Include required files
     */
    private function includes() {
    // Core classes
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-generator.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-dashboard.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-pdf.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-ajax.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-settings.php';
        
        // ADD THIS LINE - PDF Installer class
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-pdf-installer.php';
        
        // Load email class after WooCommerce emails are initialized
        add_action('woocommerce_init', array($this, 'load_email_class'));
    }
    
    /**
     * Load email class after WooCommerce is initialized
     */
    public function load_email_class() {
        if (class_exists('WC_Email')) {
            require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-email.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize AJAX handler
        new WC_Manual_Invoice_AJAX();
        
        // Initialize settings
        WC_Manual_Invoices_Settings::init();
        
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Add custom order status
        add_action('init', array($this, 'register_custom_order_status'));
        add_filter('wc_order_statuses', array($this, 'add_custom_order_status'));
        
        // Custom email template (delayed to ensure WooCommerce is ready)
        add_action('woocommerce_init', array($this, 'add_custom_email_class'));
        
        // Order actions
        add_action('woocommerce_order_status_pending', array($this, 'send_invoice_email'), 10, 1);
        
        // Add pay now link to orders
        add_action('woocommerce_admin_order_data_after_order_details', array($this, 'display_pay_now_link'));
        
        // Handle pay link security
        add_filter('woocommerce_valid_order_statuses_for_payment', array($this, 'add_manual_invoice_status_for_payment'), 10, 2);
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            __('Manual Invoices', 'wc-manual-invoices'),
            __('Invoices', 'wc-manual-invoices'),
            'manage_woocommerce',
            'wc-manual-invoices',
            array('WC_Manual_Invoices_Dashboard', 'display_dashboard')
        );
        
        add_submenu_page(
            'woocommerce',
            __('Invoice Settings', 'wc-manual-invoices'),
            __('Invoice Settings', 'wc-manual-invoices'),
            'manage_woocommerce',
            'wc-manual-invoices-settings',
            array('WC_Manual_Invoices_Settings', 'display_settings')
        );
        
        // ADD THIS - PDF Settings page
        add_submenu_page(
            'woocommerce',
            __('PDF Settings', 'wc-manual-invoices'),
            __('PDF Settings', 'wc-manual-invoices'),
            'manage_woocommerce',
            'wc-manual-invoices-pdf-settings',
            array($this, 'display_pdf_settings_page')
        );
    }
    
    /**
     * Display PDF settings page
     */
    public function display_pdf_settings_page() {
        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-manual-invoices'));
        }
        
        // Include the PDF settings template
        include WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/admin-pdf-settings.php';
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'wc-manual-invoices') !== false) {
            // Enqueue JavaScript - Correct assets path
            wp_enqueue_script(
                'wc-manual-invoices-admin',
                WC_MANUAL_INVOICES_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wc-enhanced-select'),
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
            wp_localize_script('wc-manual-invoices-admin', 'wc_manual_invoices', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_manual_invoices_nonce'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'currency_position' => get_option('woocommerce_currency_pos'),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'price_decimals' => wc_get_price_decimals(),
                // Add i18n strings for JavaScript
                'i18n_select_customer' => __('Select a customer...', 'wc-manual-invoices'),
                'i18n_search_customers' => __('Type at least 2 characters to search customers', 'wc-manual-invoices'),
                'i18n_searching' => __('Searching...', 'wc-manual-invoices'),
                'i18n_no_customers' => __('No customers found', 'wc-manual-invoices'),
                'i18n_loading_more' => __('Loading more results...', 'wc-manual-invoices'),
                'i18n_select_product' => __('Search for a product...', 'wc-manual-invoices'),
                'i18n_search_products' => __('Type at least 2 characters to search products', 'wc-manual-invoices'),
                'i18n_no_products' => __('No products found', 'wc-manual-invoices'),
                'i18n_customer_required' => __('Please select a customer or enter email address.', 'wc-manual-invoices'),
                'i18n_items_required' => __('Please add at least one item to the invoice.', 'wc-manual-invoices'),
                'i18n_confirm_clone' => __('Are you sure you want to clone this invoice?', 'wc-manual-invoices'),
                'i18n_confirm_delete' => __('Are you sure you want to delete this invoice? This action cannot be undone.', 'wc-manual-invoices'),
                'i18n_ajax_error' => __('An error occurred. Please try again.', 'wc-manual-invoices'),
            ));
            
            // Also enqueue Select2 CSS if not already loaded
            if (!wp_style_is('select2', 'enqueued')) {
                wp_enqueue_style('select2', WC()->plugin_url() . '/assets/css/select2.css', array(), WC_VERSION);
            }
        }
    }
    
    /**
     * Register custom order status
     */
    public function register_custom_order_status() {
        register_post_status('wc-manual-invoice', array(
            'label' => __('Manual Invoice', 'wc-manual-invoices'),
            'public' => true,
            'show_in_admin_status_list' => true,
            'show_in_admin_all_list' => true,
            'exclude_from_search' => false,
            'label_count' => _n_noop(
                'Manual Invoice <span class="count">(%s)</span>',
                'Manual Invoices <span class="count">(%s)</span>',
                'wc-manual-invoices'
            ),
        ));
    }
    
    /**
     * Add custom order status to WC order statuses
     */
    public function add_custom_order_status($order_statuses) {
        $order_statuses['wc-manual-invoice'] = __('Manual Invoice', 'wc-manual-invoices');
        return $order_statuses;
    }
    
    /**
     * Add custom email class
     */
    public function add_custom_email_class() {
        add_filter('woocommerce_email_classes', array($this, 'register_email_class'));
    }
    
    /**
     * Register email class with WooCommerce
     */
    public function register_email_class($email_classes) {
        if (class_exists('WC_Manual_Invoice_Email')) {
            $email_classes['WC_Manual_Invoice_Email'] = new WC_Manual_Invoice_Email();
        }
        return $email_classes;
    }
    
    /**
     * Send invoice email automatically
     */
    public function send_invoice_email($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_meta('_is_manual_invoice')) {
            // Ensure email class is loaded
            if (WC() && WC()->mailer() && isset(WC()->mailer()->emails['WC_Manual_Invoice_Email'])) {
                WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
            }
        }
    }
    
    /**
     * Display pay now link in admin order details
     */
    public function display_pay_now_link($order) {
        if ($order->get_meta('_is_manual_invoice') && $order->needs_payment()) {
            $pay_url = $order->get_checkout_payment_url();
            echo '<div class="manual-invoice-pay-link" style="margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #96588a;">';
            echo '<h3 style="margin-top: 0; color: #96588a;">' . __('Pay Now Link', 'wc-manual-invoices') . '</h3>';
            echo '<p><strong>' . __('Customer Pay URL:', 'wc-manual-invoices') . '</strong></p>';
            echo '<input type="text" value="' . esc_attr($pay_url) . '" readonly style="width: 100%; max-width: 500px; padding: 8px; border: 1px solid #ddd; border-radius: 4px; font-family: monospace; font-size: 12px;">';
            echo '<p><small style="color: #666;">' . __('Send this link to the customer to complete payment.', 'wc-manual-invoices') . '</small></p>';
            echo '<p><a href="' . esc_url($pay_url) . '" target="_blank" class="button button-secondary">' . __('Test Payment Link', 'wc-manual-invoices') . '</a></p>';
            echo '</div>';
        }
    }
    
    /**
     * Allow manual invoice status for payment
     */
    public function add_manual_invoice_status_for_payment($statuses, $order) {
        if ($order && $order->get_meta('_is_manual_invoice')) {
            $statuses[] = 'manual-invoice';
        }
        return $statuses;
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Check minimum requirements
        if (!$this->check_requirements()) {
            deactivate_plugins(plugin_basename(__FILE__));
            wp_die(__('WooCommerce Manual Invoices Pro by Wbcom Designs requires WordPress 6.0+, WooCommerce 8.0+, and PHP 8.0+', 'wc-manual-invoices'));
        }
        
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation flag
        update_option('wc_manual_invoices_activated', current_time('mysql'));
        
        // Set default settings
        $default_settings = array(
            'default_due_days' => 30,
            'auto_send_email' => 'yes',
            'auto_generate_pdf' => 'yes',
            'invoice_prefix' => 'INV-',
            'company_name' => '',
            'company_address' => '',
            'company_phone' => '',
            'company_email' => '',
            'company_logo' => '',
            'invoice_footer' => '',
            'reminder_enabled' => 'no',
            'reminder_days' => array(7, 14, 30),
            'late_fee_enabled' => 'no',
            'late_fee_amount' => 0,
            'late_fee_type' => 'fixed',
        );
        
        // Only set defaults if no settings exist
        if (!get_option('wc_manual_invoices_settings')) {
            update_option('wc_manual_invoices_settings', $default_settings);
        }
    }
    
    /**
     * Check plugin requirements
     */
    private function check_requirements() {
        global $wp_version;
        
        // Check WordPress version
        if (version_compare($wp_version, '6.0', '<')) {
            return false;
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '8.0', '<')) {
            return false;
        }
        
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return false;
        }
        
        // Check WooCommerce version
        if (defined('WC_VERSION') && version_compare(WC_VERSION, '8.0', '<')) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
        
        // Remove activation flag
        delete_option('wc_manual_invoices_activated');
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
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Show WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins') {
            echo '<div class="error"><p>';
            echo sprintf(
                __('%s requires WooCommerce to be installed and active. This plugin is fully compatible with High-Performance Order Storage (HPOS).', 'wc-manual-invoices'),
                '<strong>WooCommerce Manual Invoices Pro</strong>'
            );
            echo '</p></div>';
        }
    }
}

// Initialize the plugin
WC_Manual_Invoices_Plugin::get_instance();