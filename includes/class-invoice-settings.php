<?php
/**
 * Invoice Settings Class
 * 
 * Handles plugin settings and configuration
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoices_Settings {
    
    /**
     * Settings option name
     */
    const OPTION_NAME = 'wc_manual_invoices_settings';
    
    /**
     * Default settings
     */
    private static $defaults = array(
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
    
    /**
     * Display settings page
     */
    public static function display_settings() {
        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-manual-invoices'));
        }
        
        // Handle form submission
        if (isset($_POST['submit']) && wp_verify_nonce($_POST['_wpnonce'], 'wc_manual_invoices_settings')) {
            self::save_settings($_POST);
            echo '<div class="updated"><p>' . __('Settings saved successfully!', 'wc-manual-invoices') . '</p></div>';
        }
        
        // Get current settings
        $settings = self::get_settings();
        
        // Display settings form
        include WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/admin-settings.php';
    }
    
    /**
     * Get plugin settings
     * 
     * @return array Settings
     */
    public static function get_settings() {
        $settings = get_option(self::OPTION_NAME, array());
        return wp_parse_args($settings, self::$defaults);
    }
    
    /**
     * Get single setting
     * 
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public static function get_setting($key, $default = null) {
        $settings = self::get_settings();
        
        if (isset($settings[$key])) {
            return $settings[$key];
        }
        
        if ($default !== null) {
            return $default;
        }
        
        return isset(self::$defaults[$key]) ? self::$defaults[$key] : null;
    }
    
    /**
     * Save settings
     * 
     * @param array $data Form data
     */
    private static function save_settings($data) {
        $settings = array();
        
        // General settings
        $settings['default_due_days'] = intval($data['default_due_days']);
        $settings['auto_send_email'] = sanitize_text_field($data['auto_send_email']);
        $settings['auto_generate_pdf'] = sanitize_text_field($data['auto_generate_pdf']);
        $settings['invoice_prefix'] = sanitize_text_field($data['invoice_prefix']);
        
        // Company information
        $settings['company_name'] = sanitize_text_field($data['company_name']);
        $settings['company_address'] = sanitize_textarea_field($data['company_address']);
        $settings['company_phone'] = sanitize_text_field($data['company_phone']);
        $settings['company_email'] = sanitize_email($data['company_email']);
        $settings['invoice_footer'] = sanitize_textarea_field($data['invoice_footer']);
        
        // Handle logo upload
        if (!empty($_FILES['company_logo']['tmp_name'])) {
            $uploaded_logo = self::handle_logo_upload($_FILES['company_logo']);
            if ($uploaded_logo) {
                $settings['company_logo'] = $uploaded_logo;
            }
        } elseif (!empty($data['existing_company_logo'])) {
            $settings['company_logo'] = sanitize_text_field($data['existing_company_logo']);
        }
        
        // Reminders
        $settings['reminder_enabled'] = sanitize_text_field($data['reminder_enabled']);
        $settings['reminder_days'] = array_map('intval', explode(',', $data['reminder_days']));
        
        // Late fees
        $settings['late_fee_enabled'] = sanitize_text_field($data['late_fee_enabled']);
        $settings['late_fee_amount'] = floatval($data['late_fee_amount']);
        $settings['late_fee_type'] = sanitize_text_field($data['late_fee_type']);
        
        // Save settings
        update_option(self::OPTION_NAME, $settings);
    }
    
    /**
     * Handle logo upload
     * 
     * @param array $file Uploaded file data
     * @return string|false Logo URL or false on failure
     */
    private static function handle_logo_upload($file) {
        if (!function_exists('wp_handle_upload')) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
        }
        
        $upload_overrides = array('test_form' => false);
        $movefile = wp_handle_upload($file, $upload_overrides);
        
        if ($movefile && !isset($movefile['error'])) {
            return $movefile['url'];
        }
        
        return false;
    }
    
    /**
     * Get company information for invoices
     * 
     * @return array Company information
     */
    public static function get_company_info() {
        $settings = self::get_settings();
        
        return array(
            'name' => !empty($settings['company_name']) ? $settings['company_name'] : get_bloginfo('name'),
            'address' => $settings['company_address'],
            'phone' => $settings['company_phone'],
            'email' => !empty($settings['company_email']) ? $settings['company_email'] : get_option('admin_email'),
            'logo' => $settings['company_logo'],
        );
    }
    
    /**
     * Get invoice number with prefix
     * 
     * @param int $order_id Order ID
     * @return string Formatted invoice number
     */
    public static function get_invoice_number($order_id) {
        $prefix = self::get_setting('invoice_prefix', 'INV-');
        return $prefix . $order_id;
    }
    
    /**
     * Calculate due date
     * 
     * @param string $created_date Order creation date
     * @return string Due date
     */
    public static function calculate_due_date($created_date = null) {
        $due_days = self::get_setting('default_due_days', 30);
        
        if (!$created_date) {
            $created_date = current_time('mysql');
        }
        
        $due_date = date('Y-m-d', strtotime($created_date . ' + ' . $due_days . ' days'));
        
        return $due_date;
    }
    
    /**
     * Check if reminders are enabled
     * 
     * @return bool Reminders enabled
     */
    public static function are_reminders_enabled() {
        return self::get_setting('reminder_enabled', 'no') === 'yes';
    }
    
    /**
     * Get reminder days
     * 
     * @return array Reminder days
     */
    public static function get_reminder_days() {
        return self::get_setting('reminder_days', array(7, 14, 30));
    }
    
    /**
     * Check if late fees are enabled
     * 
     * @return bool Late fees enabled
     */
    public static function are_late_fees_enabled() {
        return self::get_setting('late_fee_enabled', 'no') === 'yes';
    }
    
    /**
     * Calculate late fee
     * 
     * @param float $order_total Order total
     * @return float Late fee amount
     */
    public static function calculate_late_fee($order_total) {
        if (!self::are_late_fees_enabled()) {
            return 0;
        }
        
        $fee_amount = self::get_setting('late_fee_amount', 0);
        $fee_type = self::get_setting('late_fee_type', 'fixed');
        
        if ($fee_type === 'percentage') {
            return ($order_total * $fee_amount) / 100;
        }
        
        return $fee_amount;
    }
    
    /**
     * Register settings with WordPress
     */
    public static function register_settings() {
        register_setting('wc_manual_invoices_settings', self::OPTION_NAME);
    }
    
    /**
     * Initialize settings
     */
    public static function init() {
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }
}