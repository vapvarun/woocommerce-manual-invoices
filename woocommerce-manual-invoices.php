<?php
/**
 * Plugin Name: WooCommerce Manual Invoices Pro
 * Plugin URI: https://yoursite.com/woocommerce-manual-invoices
 * Description: Create manual invoices and send "Pay Now" links to customers using WooCommerce's checkout and payment infrastructure.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yoursite.com
 * Text Domain: wc-manual-invoices
 * Domain Path: /languages
 * Requires at least: 6.0
 * Tested up to: 6.4
 * WC requires at least: 8.0
 * WC tested up to: 8.5
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
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
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'init'));
        
        // Activation hook
        register_activation_hook(__FILE__, array($this, 'activate'));
        
        // Deactivation hook
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
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
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-email.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-pdf.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-ajax.php';
        require_once WC_MANUAL_INVOICES_PLUGIN_PATH . 'includes/class-invoice-settings.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        
        // Add custom order status
        add_action('init', array($this, 'register_custom_order_status'));
        add_filter('wc_order_statuses', array($this, 'add_custom_order_status'));
        
        // Custom email template
        add_filter('woocommerce_email_classes', array($this, 'add_custom_email_class'));
        
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
    }
    
    /**
     * Enqueue admin scripts and styles
     */
    public function admin_enqueue_scripts($hook) {
        if (strpos($hook, 'wc-manual-invoices') !== false) {
            wp_enqueue_script(
                'wc-manual-invoices-admin',
                WC_MANUAL_INVOICES_PLUGIN_URL . 'assets/js/admin.js',
                array('jquery', 'wc-enhanced-select'),
                WC_MANUAL_INVOICES_VERSION,
                true
            );
            
            wp_enqueue_style(
                'wc-manual-invoices-admin',
                WC_MANUAL_INVOICES_PLUGIN_URL . 'assets/css/admin.css',
                array(),
                WC_MANUAL_INVOICES_VERSION
            );
            
            wp_localize_script('wc-manual-invoices-admin', 'wc_manual_invoices', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wc_manual_invoices_nonce'),
                'currency_symbol' => get_woocommerce_currency_symbol(),
                'currency_position' => get_option('woocommerce_currency_pos'),
                'thousand_separator' => wc_get_price_thousand_separator(),
                'decimal_separator' => wc_get_price_decimal_separator(),
                'price_decimals' => wc_get_price_decimals(),
            ));
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
    public function add_custom_email_class($email_classes) {
        $email_classes['WC_Manual_Invoice_Email'] = new WC_Manual_Invoice_Email();
        return $email_classes;
    }
    
    /**
     * Send invoice email automatically
     */
    public function send_invoice_email($order_id) {
        $order = wc_get_order($order_id);
        if ($order && $order->get_meta('_is_manual_invoice')) {
            WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
        }
    }
    
    /**
     * Display pay now link in admin order details
     */
    public function display_pay_now_link($order) {
        if ($order->get_meta('_is_manual_invoice') && $order->needs_payment()) {
            $pay_url = $order->get_checkout_payment_url();
            echo '<div class="manual-invoice-pay-link">';
            echo '<h3>' . __('Pay Now Link', 'wc-manual-invoices') . '</h3>';
            echo '<p><strong>' . __('Customer Pay URL:', 'wc-manual-invoices') . '</strong></p>';
            echo '<input type="text" value="' . esc_attr($pay_url) . '" readonly style="width: 100%; max-width: 500px;">';
            echo '<p><small>' . __('Send this link to the customer to complete payment.', 'wc-manual-invoices') . '</small></p>';
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
        // Create database tables if needed
        $this->create_tables();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Clean up if needed
        flush_rewrite_rules();
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
        echo '<div class="error"><p>';
        echo sprintf(
            __('%s requires WooCommerce to be installed and active.', 'wc-manual-invoices'),
            '<strong>WooCommerce Manual Invoices Pro</strong>'
        );
        echo '</p></div>';
    }
}

// Initialize the plugin
WC_Manual_Invoices_Plugin::get_instance();