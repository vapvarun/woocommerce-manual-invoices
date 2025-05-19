<?php
/**
 * AJAX Handler for Manual Invoices
 * 
 * Handles AJAX requests for invoice management
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_AJAX {
    
    /**
     * Constructor
     */
    public function __construct() {
        // Initialize AJAX handlers
        add_action('init', array($this, 'init_ajax_handlers'));
    }
    
    /**
     * Initialize AJAX handlers
     */
    public function init_ajax_handlers() {
        // AJAX actions for logged-in users
        add_action('wp_ajax_wc_manual_invoice_search_customers', array($this, 'search_customers'));
        add_action('wp_ajax_wc_manual_invoice_search_products', array($this, 'search_products'));
        add_action('wp_ajax_wc_manual_invoice_get_customer_details', array($this, 'get_customer_details'));
        add_action('wp_ajax_wc_manual_invoice_get_product_details', array($this, 'get_product_details'));
        add_action('wp_ajax_wc_manual_invoice_send_email', array($this, 'send_invoice_email'));
        add_action('wp_ajax_wc_manual_invoice_generate_pdf', array($this, 'generate_pdf'));
        add_action('wp_ajax_wc_manual_invoice_delete', array($this, 'delete_invoice'));
        add_action('wp_ajax_wc_manual_invoice_clone', array($this, 'clone_invoice'));
        add_action('wp_ajax_wc_manual_invoice_update_status', array($this, 'update_invoice_status'));
    }
    
    /**
     * Search customers via AJAX
     */
    public function search_customers() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $term = sanitize_text_field($_POST['term']);
        $customers = array();
        
        if (strlen($term) >= 2) {
            // Search users
            $user_query = new WP_User_Query(array(
                'search' => '*' . $term . '*',
                'search_columns' => array('user_login', 'user_email', 'display_name'),
                'meta_query' => array(
                    'relation' => 'OR',
                    array(
                        'key' => 'first_name',
                        'value' => $term,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'last_name',
                        'value' => $term,
                        'compare' => 'LIKE'
                    ),
                ),
                'number' => 20,
            ));
            
            foreach ($user_query->get_results() as $user) {
                $customer = new WC_Customer($user->ID);
                $customers[] = array(
                    'id' => $user->ID,
                    'text' => sprintf(
                        '%s (%s)',
                        $customer->get_display_name(),
                        $customer->get_email()
                    ),
                    'email' => $customer->get_email(),
                    'first_name' => $customer->get_first_name(),
                    'last_name' => $customer->get_last_name(),
                );
            }
        }
        
        wp_send_json($customers);
    }
    
    /**
     * Search products via AJAX
     */
    public function search_products() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $term = sanitize_text_field($_POST['term']);
        $products = array();
        
        if (strlen($term) >= 2) {
            $product_query = new WP_Query(array(
                'post_type' => 'product',
                'post_status' => 'publish',
                's' => $term,
                'posts_per_page' => 20,
            ));
            
            foreach ($product_query->posts as $post) {
                $product = wc_get_product($post->ID);
                $products[] = array(
                    'id' => $product->get_id(),
                    'text' => sprintf(
                        '%s - %s',
                        $product->get_name(),
                        wc_price($product->get_price())
                    ),
                    'price' => $product->get_price(),
                    'name' => $product->get_name(),
                );
            }
        }
        
        wp_send_json($products);
    }
    
    /**
     * Get customer details via AJAX
     */
    public function get_customer_details() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $customer_id = intval($_POST['customer_id']);
        $customer = new WC_Customer($customer_id);
        
        $details = array(
            'id' => $customer_id,
            'email' => $customer->get_email(),
            'first_name' => $customer->get_first_name(),
            'last_name' => $customer->get_last_name(),
            'phone' => $customer->get_billing_phone(),
            'address_1' => $customer->get_billing_address_1(),
            'address_2' => $customer->get_billing_address_2(),
            'city' => $customer->get_billing_city(),
            'state' => $customer->get_billing_state(),
            'postcode' => $customer->get_billing_postcode(),
            'country' => $customer->get_billing_country(),
        );
        
        wp_send_json_success($details);
    }
    
    /**
     * Get product details via AJAX
     */
    public function get_product_details() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $product_id = intval($_POST['product_id']);
        $product = wc_get_product($product_id);
        
        if (!$product) {
            wp_send_json_error('Product not found');
            return;
        }
        
        $details = array(
            'id' => $product->get_id(),
            'name' => $product->get_name(),
            'price' => $product->get_price(),
            'sku' => $product->get_sku(),
            'description' => $product->get_short_description(),
        );
        
        wp_send_json_success($details);
    }
    
    /**
     * Send invoice email via AJAX
     */
    public function send_invoice_email() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            wp_send_json_error('Invalid invoice');
            return;
        }
        
        // Send email
        if (WC() && WC()->mailer() && isset(WC()->mailer()->emails['WC_Manual_Invoice_Email'])) {
            WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
            
            // Update last sent date
            $order->update_meta_data('_invoice_last_sent', current_time('mysql'));
            $order->save();
            
            wp_send_json_success('Email sent successfully');
        } else {
            wp_send_json_error('Email system not available');
        }
    }
    
    /**
     * Generate PDF via AJAX
     */
    public function generate_pdf() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id']);
        $force_regenerate = !empty($_POST['force_regenerate']);
        
        $pdf_path = WC_Manual_Invoice_PDF::generate_pdf($order_id, $force_regenerate);
        
        if ($pdf_path) {
            $download_url = WC_Manual_Invoice_PDF::get_pdf_download_url($order_id);
            wp_send_json_success(array(
                'download_url' => $download_url,
                'message' => 'PDF generated successfully',
            ));
        } else {
            wp_send_json_error('Failed to generate PDF');
        }
    }
    
    /**
     * Delete invoice via AJAX
     */
    public function delete_invoice() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id']);
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            wp_send_json_error('Invalid invoice');
            return;
        }
        
        // Only allow deletion of pending invoices
        if ($order->get_status() !== 'pending' && $order->get_status() !== 'manual-invoice') {
            wp_send_json_error('Cannot delete paid invoices');
            return;
        }
        
        // Delete PDF file
        WC_Manual_Invoice_PDF::delete_pdf($order_id);
        
        // Delete order
        wp_delete_post($order_id, true);
        
        wp_send_json_success('Invoice deleted successfully');
    }
    
    /**
     * Clone invoice via AJAX
     */
    public function clone_invoice() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id']);
        $result = WC_Manual_Invoice_Generator::clone_invoice($order_id);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success(array(
                'new_order_id' => $result,
                'message' => sprintf('Invoice cloned successfully! New invoice #%d created.', $result),
                'redirect_url' => admin_url('admin.php?page=wc-manual-invoices&tab=edit&order_id=' . $result),
            ));
        }
    }
    
    /**
     * Update invoice status via AJAX
     */
    public function update_invoice_status() {
        // Check nonce
        if (!wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_die('Insufficient permissions');
        }
        
        $order_id = intval($_POST['order_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            wp_send_json_error('Invalid invoice');
            return;
        }
        
        // Update status
        $order->set_status($new_status);
        $order->save();
        
        // Add order note
        $order->add_order_note(
            sprintf('Status changed to %s via manual invoice management', $new_status),
            false
        );
        
        wp_send_json_success('Status updated successfully');
    }
}