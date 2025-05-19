<?php
/**
 * Invoice Dashboard Class
 * 
 * Handles the admin dashboard for managing manual invoices
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoices_Dashboard {
    
    /**
     * Display the main dashboard
     */
    public static function display_dashboard() {
        // Check user permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'wc-manual-invoices'));
        }
        
        // Handle actions
        self::handle_actions();
        
        // Get current tab
        $current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'invoices';
        
        // Display dashboard
        include WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/admin-dashboard.php';
    }
    
    /**
     * Handle dashboard actions
     */
    private static function handle_actions() {
        if (!isset($_POST['action']) || !wp_verify_nonce($_POST['_wpnonce'], 'wc_manual_invoices_nonce')) {
            return;
        }
        
        $action = sanitize_text_field($_POST['action']);
        
        switch ($action) {
            case 'create_invoice':
                self::handle_create_invoice();
                break;
                
            case 'send_invoice':
                self::handle_send_invoice();
                break;
                
            case 'clone_invoice':
                self::handle_clone_invoice();
                break;
                
            case 'delete_invoice':
                self::handle_delete_invoice();
                break;
        }
    }
    
    /**
     * Handle invoice creation
     */
    private static function handle_create_invoice() {
        // Validate form data
        $invoice_data = self::validate_invoice_form_data($_POST);
        
        if (is_wp_error($invoice_data)) {
            add_action('admin_notices', function() use ($invoice_data) {
                echo '<div class="error"><p>' . $invoice_data->get_error_message() . '</p></div>';
            });
            return;
        }
        
        // Create invoice
        $result = WC_Manual_Invoice_Generator::create_invoice($invoice_data);
        
        if (is_wp_error($result)) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="error"><p>' . $result->get_error_message() . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="updated"><p>' . 
                     sprintf(__('Invoice #%d created successfully!', 'wc-manual-invoices'), $result) . 
                     '</p></div>';
            });
            
            // Send email if requested
            if (!empty($_POST['send_email'])) {
                self::send_invoice_email($result);
            }
        }
    }
    
    /**
     * Handle sending invoice email
     */
    private static function handle_send_invoice() {
        $order_id = intval($_POST['order_id']);
        
        if (self::send_invoice_email($order_id)) {
            add_action('admin_notices', function() {
                echo '<div class="updated"><p>' . __('Invoice email sent successfully!', 'wc-manual-invoices') . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() {
                echo '<div class="error"><p>' . __('Failed to send invoice email.', 'wc-manual-invoices') . '</p></div>';
            });
        }
    }
    
    /**
     * Handle invoice cloning
     */
    private static function handle_clone_invoice() {
        $order_id = intval($_POST['order_id']);
        $result = WC_Manual_Invoice_Generator::clone_invoice($order_id);
        
        if (is_wp_error($result)) {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="error"><p>' . $result->get_error_message() . '</p></div>';
            });
        } else {
            add_action('admin_notices', function() use ($result) {
                echo '<div class="updated"><p>' . 
                     sprintf(__('Invoice cloned successfully! New invoice #%d created.', 'wc-manual-invoices'), $result) . 
                     '</p></div>';
            });
        }
    }
    
    /**
     * Handle invoice deletion
     */
    private static function handle_delete_invoice() {
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if ($order && $order->get_meta('_is_manual_invoice')) {
            if ($order->get_status() === 'pending' || $order->get_status() === 'manual-invoice') {
                wp_delete_post($order_id, true);
                add_action('admin_notices', function() {
                    echo '<div class="updated"><p>' . __('Invoice deleted successfully!', 'wc-manual-invoices') . '</p></div>';
                });
            } else {
                add_action('admin_notices', function() {
                    echo '<div class="error"><p>' . __('Cannot delete paid invoices.', 'wc-manual-invoices') . '</p></div>';
                });
            }
        }
    }
    
    /**
     * Validate invoice form data
     * 
     * @param array $data Form data
     * @return array|WP_Error Validated data or error
     */
    private static function validate_invoice_form_data($data) {
        $validated = array();
        
        // Customer validation
        if (!empty($data['customer_id'])) {
            $validated['customer_id'] = intval($data['customer_id']);
        } elseif (!empty($data['customer_email'])) {
            if (!is_email($data['customer_email'])) {
                return new WP_Error('invalid_email', __('Please enter a valid email address.', 'wc-manual-invoices'));
            }
            $validated['customer_email'] = sanitize_email($data['customer_email']);
            
            // Additional billing info for new customers
            $validated['billing_first_name'] = sanitize_text_field($data['billing_first_name'] ?? '');
            $validated['billing_last_name'] = sanitize_text_field($data['billing_last_name'] ?? '');
            $validated['billing_phone'] = sanitize_text_field($data['billing_phone'] ?? '');
            $validated['billing_address_1'] = sanitize_text_field($data['billing_address_1'] ?? '');
            $validated['billing_address_2'] = sanitize_text_field($data['billing_address_2'] ?? '');
            $validated['billing_city'] = sanitize_text_field($data['billing_city'] ?? '');
            $validated['billing_state'] = sanitize_text_field($data['billing_state'] ?? '');
            $validated['billing_postcode'] = sanitize_text_field($data['billing_postcode'] ?? '');
            $validated['billing_country'] = sanitize_text_field($data['billing_country'] ?? 'US');
        } else {
            return new WP_Error('missing_customer', __('Customer information is required.', 'wc-manual-invoices'));
        }
        
        // Items validation
        $validated['items'] = array();
        $validated['custom_items'] = array();
        
        // Regular products
        if (!empty($data['product_ids'])) {
            for ($i = 0; $i < count($data['product_ids']); $i++) {
                if (!empty($data['product_ids'][$i])) {
                    $validated['items'][] = array(
                        'product_id' => intval($data['product_ids'][$i]),
                        'quantity' => floatval($data['product_quantities'][$i] ?? 1),
                        'total' => floatval($data['product_totals'][$i] ?? 0),
                    );
                }
            }
        }
        
        // Custom items
        if (!empty($data['custom_item_names'])) {
            for ($i = 0; $i < count($data['custom_item_names']); $i++) {
                if (!empty($data['custom_item_names'][$i])) {
                    $validated['custom_items'][] = array(
                        'name' => sanitize_text_field($data['custom_item_names'][$i]),
                        'description' => sanitize_textarea_field($data['custom_item_descriptions'][$i] ?? ''),
                        'quantity' => floatval($data['custom_item_quantities'][$i] ?? 1),
                        'total' => floatval($data['custom_item_totals'][$i] ?? 0),
                    );
                }
            }
        }
        
        // Check if we have at least one item
        if (empty($validated['items']) && empty($validated['custom_items'])) {
            return new WP_Error('missing_items', __('At least one item is required.', 'wc-manual-invoices'));
        }
        
        // Fees
        $validated['fees'] = array();
        if (!empty($data['fee_names'])) {
            for ($i = 0; $i < count($data['fee_names']); $i++) {
                if (!empty($data['fee_names'][$i])) {
                    $validated['fees'][] = array(
                        'name' => sanitize_text_field($data['fee_names'][$i]),
                        'amount' => floatval($data['fee_amounts'][$i] ?? 0),
                    );
                }
            }
        }
        
        // Shipping
        if (!empty($data['shipping_total'])) {
            $validated['shipping'] = array(
                'method_title' => sanitize_text_field($data['shipping_method'] ?? __('Shipping', 'wc-manual-invoices')),
                'method_id' => 'manual_shipping',
                'total' => floatval($data['shipping_total']),
            );
        }
        
        // Tax
        if (!empty($data['tax_total'])) {
            $validated['tax'] = array(
                'name' => sanitize_text_field($data['tax_name'] ?? __('Tax', 'wc-manual-invoices')),
                'total' => floatval($data['tax_total']),
            );
        }
        
        // Notes and terms
        $validated['notes'] = sanitize_textarea_field($data['notes'] ?? '');
        $validated['terms'] = sanitize_textarea_field($data['terms'] ?? '');
        $validated['due_date'] = sanitize_text_field($data['due_date'] ?? '');
        
        return $validated;
    }
    
    /**
     * Send invoice email
     * 
     * @param int $order_id Order ID
     * @return bool Success
     */
    private static function send_invoice_email($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            return false;
        }
        
        // Send email
        if (WC() && WC()->mailer() && isset(WC()->mailer()->emails['WC_Manual_Invoice_Email'])) {
            WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
            
            // Update last sent date
            $order->update_meta_data('_invoice_last_sent', current_time('mysql'));
            $order->save();
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Get manual invoices
     * 
     * @param array $args Query arguments
     * @return array Invoices
     */
    public static function get_manual_invoices($args = array()) {
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'status' => 'any',
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query_args = array(
            'type' => 'shop_order',
            'limit' => $args['limit'],
            'offset' => $args['offset'],
            'orderby' => $args['orderby'],
            'order' => $args['order'],
            'meta_query' => array(
                array(
                    'key' => '_is_manual_invoice',
                    'value' => true,
                    'compare' => '=',
                ),
            ),
        );
        
        if ($args['status'] !== 'any') {
            $query_args['status'] = $args['status'];
        }
        
        return wc_get_orders($query_args);
    }
    
    /**
     * Get invoice statistics
     * 
     * @return array Statistics
     */
    public static function get_invoice_statistics() {
        global $wpdb;
        
        $stats = array();
        
        // Total invoices
        $stats['total'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND pm.meta_key = '_is_manual_invoice'
            AND pm.meta_value = '1'
        "));
        
        // Pending invoices
        $stats['pending'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-pending', 'wc-manual-invoice')
            AND pm.meta_key = '_is_manual_invoice'
            AND pm.meta_value = '1'
        "));
        
        // Paid invoices
        $stats['paid'] = $wpdb->get_var($wpdb->prepare("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-processing', 'wc-completed')
            AND pm.meta_key = '_is_manual_invoice'
            AND pm.meta_value = '1'
        "));
        
        // Total amount
        $stats['total_amount'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(pm_total.meta_value)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id
            WHERE p.post_type = 'shop_order'
            AND pm.meta_key = '_is_manual_invoice'
            AND pm.meta_value = '1'
            AND pm_total.meta_key = '_order_total'
        "));
        
        // Pending amount
        $stats['pending_amount'] = $wpdb->get_var($wpdb->prepare("
            SELECT SUM(pm_total.meta_value)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            INNER JOIN {$wpdb->postmeta} pm_total ON p.ID = pm_total.post_id
            WHERE p.post_type = 'shop_order'
            AND p.post_status IN ('wc-pending', 'wc-manual-invoice')
            AND pm.meta_key = '_is_manual_invoice'
            AND pm.meta_value = '1'
            AND pm_total.meta_key = '_order_total'
        "));
        
        return $stats;
    }
}