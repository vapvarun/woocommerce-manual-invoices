<?php
/**
 * Enhanced AJAX Handler for Manual Invoices - DomPDF Focus
 * 
 * Handles AJAX requests for invoice management with simplified PDF generation
 * FIXED: Focus only on DomPDF with text fallback
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
        
        // Simplified PDF actions - DomPDF only
        add_action('wp_ajax_wc_manual_invoice_test_pdf_generation', array($this, 'test_pdf_generation'));
    }
    
    /**
     * Enhanced customer search with pagination for large databases
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
        $page = intval($_POST['page'] ?? 1);
        $per_page = 20; // Results per page
        $offset = ($page - 1) * $per_page;
        
        $customers = array();
        $more_results = false;
        
        if (strlen($term) >= 2) {
            // Use WP_User_Query for better performance with large datasets
            $args = array(
                'search' => '*' . $term . '*',
                'search_columns' => array('user_login', 'user_email', 'display_name'),
                'number' => $per_page + 1, // Get one extra to check if there are more results
                'offset' => $offset,
                'orderby' => 'display_name',
                'order' => 'ASC',
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
                    array(
                        'key' => 'billing_first_name',
                        'value' => $term,
                        'compare' => 'LIKE'
                    ),
                    array(
                        'key' => 'billing_last_name',
                        'value' => $term,
                        'compare' => 'LIKE'
                    ),
                ),
            );
            
            $user_query = new WP_User_Query($args);
            $users = $user_query->get_results();
            
            // Check if there are more results
            if (count($users) > $per_page) {
                $more_results = true;
                array_pop($users); // Remove the extra user we queried
            }
            
            foreach ($users as $user) {
                try {
                    $customer = new WC_Customer($user->ID);
                    
                    // Get customer order count for context
                    $order_count = wc_get_customer_order_count($user->ID);
                    
                    // Build display name
                    $display_name = $customer->get_display_name();
                    if (empty($display_name)) {
                        $display_name = $customer->get_first_name() . ' ' . $customer->get_last_name();
                        $display_name = trim($display_name);
                        if (empty($display_name)) {
                            $display_name = $customer->get_email();
                        }
                    }
                    
                    $customers[] = array(
                        'id' => $user->ID,
                        'text' => sprintf('%s (%s)', $display_name, $customer->get_email()),
                        'name' => $display_name,
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
                        'orders_count' => $order_count,
                    );
                } catch (Exception $e) {
                    // Skip customers that can't be loaded
                    continue;
                }
            }
            
            // Also search by email directly for exact matches
            if (is_email($term)) {
                $user = get_user_by('email', $term);
                if ($user && !in_array($user->ID, array_column($customers, 'id'))) {
                    try {
                        $customer = new WC_Customer($user->ID);
                        $order_count = wc_get_customer_order_count($user->ID);
                        
                        $display_name = $customer->get_display_name();
                        if (empty($display_name)) {
                            $display_name = $customer->get_first_name() . ' ' . $customer->get_last_name();
                            $display_name = trim($display_name);
                            if (empty($display_name)) {
                                $display_name = $customer->get_email();
                            }
                        }
                        
                        // Add exact email match at the beginning
                        array_unshift($customers, array(
                            'id' => $user->ID,
                            'text' => sprintf('%s (%s)', $display_name, $customer->get_email()),
                            'name' => $display_name,
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
                            'orders_count' => $order_count,
                        ));
                    } catch (Exception $e) {
                        // Skip if customer can't be loaded
                    }
                }
            }
        }
        
        wp_send_json(array(
            'results' => $customers,
            'pagination' => array(
                'more' => $more_results
            )
        ));
    }
    
    /**
     * FIXED: Enhanced product search with pagination and stock status
     */
    public function search_products() {
        // Verify nonce - FIXED: Check if nonce exists first
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        // FIXED: Get search term safely
        $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
        $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
        $per_page = 20;
        $offset = ($page - 1) * $per_page;
        
        $products = array();
        $more_results = false;
        
        // FIXED: Reduce minimum search length to 1 character for better UX
        if (strlen($term) >= 1) {
            try {
                // FIXED: Better product search query
                $args = array(
                    'post_type' => 'product',
                    'post_status' => 'publish',
                    'posts_per_page' => $per_page + 1, // Get one extra to check for more results
                    'offset' => $offset,
                    'orderby' => 'title',
                    'order' => 'ASC',
                    'meta_query' => array(
                        'relation' => 'OR',
                        array(
                            'key' => '_sku',
                            'value' => $term,
                            'compare' => 'LIKE'
                        ),
                    ),
                );
                
                // FIXED: Add title search separately to avoid conflicts
                if (!empty($term)) {
                    $args['s'] = $term;
                }
                
                $product_query = new WP_Query($args);
                $found_products = $product_query->posts;
                
                // Check if there are more results
                if (count($found_products) > $per_page) {
                    $more_results = true;
                    array_pop($found_products); // Remove the extra product
                }
                
                foreach ($found_products as $post) {
                    try {
                        $product = wc_get_product($post->ID);
                        
                        // FIXED: Better product validation
                        if (!$product || !is_object($product)) {
                            continue;
                        }
                        
                        // Skip variable products, only allow simple and variations
                        if ($product->is_type('variable')) {
                            continue;
                        }
                        
                        // Get product price
                        $price = $product->get_price();
                        if ($price === '' || $price === null) {
                            $price = 0;
                        }
                        
                        // Format price for display
                        $price_formatted = $price > 0 ? wc_price($price) : __('Free', 'wc-manual-invoices');
                        
                        // Get stock status
                        $stock_status = $product->get_stock_status();
                        $stock_text = '';
                        
                        if ($product->managing_stock()) {
                            $stock_quantity = $product->get_stock_quantity();
                            if ($stock_quantity !== null && $stock_quantity > 0) {
                                $stock_text = sprintf(__('%d in stock', 'wc-manual-invoices'), $stock_quantity);
                            } else {
                                $stock_text = __('Out of stock', 'wc-manual-invoices');
                                $stock_status = 'outofstock';
                            }
                        } else {
                            $stock_text = $stock_status === 'instock' ? __('In stock', 'wc-manual-invoices') : __('Out of stock', 'wc-manual-invoices');
                        }
                        
                        // Get product SKU
                        $sku = $product->get_sku();
                        
                        // FIXED: Build proper product data array
                        $product_data = array(
                            'id' => $product->get_id(),
                            'text' => sprintf('%s - %s', $product->get_name(), $price_formatted),
                            'name' => $product->get_name(),
                            'price' => (float) $price,
                            'price_formatted' => $price_formatted,
                            'sku' => $sku,
                            'description' => wp_strip_all_tags($product->get_short_description()),
                            'stock_status' => $stock_status,
                            'stock_text' => $stock_text,
                            'type' => $product->get_type(),
                        );
                        
                        $products[] = $product_data;
                        
                    } catch (Exception $e) {
                        // Log error but continue
                        error_log('WC Manual Invoices: Product search error for product ID ' . $post->ID . ': ' . $e->getMessage());
                        continue;
                    }
                }
                
                // FIXED: Also search by SKU exactly if no results found by title
                if (empty($products)) {
                    $sku_product_id = wc_get_product_id_by_sku($term);
                    if ($sku_product_id) {
                        try {
                            $product = wc_get_product($sku_product_id);
                            if ($product && is_object($product) && !$product->is_type('variable')) {
                                $price = $product->get_price();
                                if ($price === '' || $price === null) {
                                    $price = 0;
                                }
                                
                                $price_formatted = $price > 0 ? wc_price($price) : __('Free', 'wc-manual-invoices');
                                $stock_status = $product->get_stock_status();
                                $stock_text = $stock_status === 'instock' ? __('In stock', 'wc-manual-invoices') : __('Out of stock', 'wc-manual-invoices');
                                
                                if ($product->managing_stock()) {
                                    $stock_quantity = $product->get_stock_quantity();
                                    if ($stock_quantity !== null && $stock_quantity > 0) {
                                        $stock_text = sprintf(__('%d in stock', 'wc-manual-invoices'), $stock_quantity);
                                    } else {
                                        $stock_text = __('Out of stock', 'wc-manual-invoices');
                                        $stock_status = 'outofstock';
                                    }
                                }
                                
                                // Add SKU match at the beginning
                                array_unshift($products, array(
                                    'id' => $product->get_id(),
                                    'text' => sprintf('%s - %s (SKU: %s)', $product->get_name(), $price_formatted, $product->get_sku()),
                                    'name' => $product->get_name(),
                                    'price' => (float) $price,
                                    'price_formatted' => $price_formatted,
                                    'sku' => $product->get_sku(),
                                    'description' => wp_strip_all_tags($product->get_short_description()),
                                    'stock_status' => $stock_status,
                                    'stock_text' => $stock_text,
                                    'type' => $product->get_type(),
                                ));
                            }
                        } catch (Exception $e) {
                            error_log('WC Manual Invoices: SKU search error: ' . $e->getMessage());
                        }
                    }
                }
                
            } catch (Exception $e) {
                error_log('WC Manual Invoices: Product search query error: ' . $e->getMessage());
                wp_send_json_error(array(
                    'message' => 'Product search failed. Please try again.',
                    'error' => $e->getMessage()
                ));
                return;
            }
        }
        
        // FIXED: Always send proper JSON response
        wp_send_json_success(array(
            'results' => $products,
            'pagination' => array(
                'more' => $more_results
            )
        ));
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
        
        try {
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
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to load customer details.', 'wc-manual-invoices')
            ));
        }
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
        
        try {
            $product = wc_get_product($product_id);
            
            if (!$product) {
                wp_send_json_error(array(
                    'message' => __('Product not found.', 'wc-manual-invoices')
                ));
                return;
            }
            
            $details = array(
                'id' => $product->get_id(),
                'name' => $product->get_name(),
                'price' => floatval($product->get_price()),
                'sku' => $product->get_sku(),
                'description' => $product->get_short_description(),
                'stock_status' => $product->get_stock_status(),
                'stock_quantity' => $product->get_stock_quantity(),
            );
            
            wp_send_json_success($details);
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to load product details.', 'wc-manual-invoices')
            ));
        }
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
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order || !$order->get_meta('_is_manual_invoice')) {
                wp_send_json_error(array(
                    'message' => __('Invalid invoice.', 'wc-manual-invoices')
                ));
                return;
            }
            
            // Send email
            if (WC() && WC()->mailer() && isset(WC()->mailer()->emails['WC_Manual_Invoice_Email'])) {
                $result = WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
                
                // Update last sent date
                $order->update_meta_data('_invoice_last_sent', current_time('mysql'));
                $order->save();
                
                wp_send_json_success(array(
                    'message' => sprintf(__('Invoice email sent successfully to %s', 'wc-manual-invoices'), $order->get_billing_email())
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Email system not available.', 'wc-manual-invoices')
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to send email. Please try again.', 'wc-manual-invoices')
            ));
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
        
        try {
            $pdf_path = WC_Manual_Invoice_PDF::generate_pdf($order_id, $force_regenerate);
            
            if ($pdf_path) {
                $download_url = WC_Manual_Invoice_PDF::get_pdf_download_url($order_id);
                
                // Determine file type for response
                $file_extension = pathinfo($pdf_path, PATHINFO_EXTENSION);
                $file_type = $file_extension === 'pdf' ? 'PDF' : 'Text';
                
                wp_send_json_success(array(
                    'download_url' => $download_url,
                    'message' => sprintf(__('%s generated successfully', 'wc-manual-invoices'), $file_type),
                    'file_type' => $file_type
                ));
            } else {
                wp_send_json_error(array(
                    'message' => __('Failed to generate invoice file. Please check server requirements.', 'wc-manual-invoices')
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Invoice generation failed. Please try again.', 'wc-manual-invoices')
            ));
        }
    }
    
    /**
     * Test PDF generation via AJAX
     */
    public function test_pdf_generation() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wc_manual_invoices_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }
        
        // Check permissions
        if (!current_user_can('manage_woocommerce')) {
            wp_send_json_error(array('message' => 'Insufficient permissions'));
            return;
        }
        
        try {
            $result = WC_Manual_Invoice_PDF_Installer::test_pdf_generation();
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message()
                ));
            } else {
                wp_send_json_success($result);
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Test failed: ' . $e->getMessage()
            ));
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
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order || !$order->get_meta('_is_manual_invoice')) {
                wp_send_json_error(array(
                    'message' => __('Invalid invoice.', 'wc-manual-invoices')
                ));
                return;
            }
            
            // Only allow deletion of pending invoices
            if (!in_array($order->get_status(), array('pending', 'manual-invoice'))) {
                wp_send_json_error(array(
                    'message' => __('Cannot delete paid invoices.', 'wc-manual-invoices')
                ));
                return;
            }
            
            // Delete PDF file
            WC_Manual_Invoice_PDF::delete_pdf($order_id);
            
            // Use HPOS-compatible method for deletion
            if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
                // HPOS is enabled - use WooCommerce's order deletion
                $order->delete(true);
            } else {
                // Legacy - delete post
                wp_delete_post($order_id, true);
            }
            
            wp_send_json_success(array(
                'message' => sprintf(__('Invoice #%s deleted successfully', 'wc-manual-invoices'), $order->get_order_number())
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to delete invoice. Please try again.', 'wc-manual-invoices')
            ));
        }
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
        
        try {
            $result = WC_Manual_Invoice_Generator::clone_invoice($order_id);
            
            if (is_wp_error($result)) {
                wp_send_json_error(array(
                    'message' => $result->get_error_message()
                ));
            } else {
                wp_send_json_success(array(
                    'new_order_id' => $result,
                    'message' => sprintf(__('Invoice cloned successfully! New invoice #%d created.', 'wc-manual-invoices'), $result),
                    'redirect_url' => admin_url('admin.php?page=wc-manual-invoices&tab=create&order_id=' . $result),
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to clone invoice. Please try again.', 'wc-manual-invoices')
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
        
        try {
            $order = wc_get_order($order_id);
            
            if (!$order || !$order->get_meta('_is_manual_invoice')) {
                wp_send_json_error(array(
                    'message' => __('Invalid invoice.', 'wc-manual-invoices')
                ));
                return;
            }
            
            // Update status
            $order->set_status($new_status);
            $order->save();
            
            // Add order note
            $order->add_order_note(
                sprintf(__('Status changed to %s via manual invoice management', 'wc-manual-invoices'), $new_status),
                false
            );
            
            wp_send_json_success(array(
                'message' => sprintf(__('Status updated to %s successfully', 'wc-manual-invoices'), wc_get_order_status_name($new_status))
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => __('Failed to update status. Please try again.', 'wc-manual-invoices')
            ));
        }
    }
}