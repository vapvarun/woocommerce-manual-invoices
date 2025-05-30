<?php
/**
 * Enhanced Invoice Generator Class
 * 
 * Handles the creation and management of manual invoices with HPOS compatibility
 * and comprehensive error handling
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_Generator {
    
    /**
     * Create a new manual invoice
     * 
     * @param array $invoice_data Invoice data
     * @return int|WP_Error Order ID or error
     */
    public static function create_invoice($invoice_data) {
        try {
            // Validate required fields
            if (empty($invoice_data['customer_id']) && empty($invoice_data['customer_email'])) {
                return new WP_Error('missing_customer', __('Customer information is required.', 'wc-manual-invoices'));
            }
            
            if (empty($invoice_data['items']) && empty($invoice_data['custom_items'])) {
                return new WP_Error('missing_items', __('At least one item is required.', 'wc-manual-invoices'));
            }
            
            // Create WooCommerce order
            $order = wc_create_order();
            
            if (is_wp_error($order)) {
                return $order;
            }
            
            // Set customer
            self::set_customer($order, $invoice_data);
            
            // Add items to order
            self::add_items_to_order($order, $invoice_data);
            
            // Add fees
            if (!empty($invoice_data['fees'])) {
                self::add_fees_to_order($order, $invoice_data['fees']);
            }
            
            // Add shipping
            if (!empty($invoice_data['shipping'])) {
                self::add_shipping_to_order($order, $invoice_data['shipping']);
            }
            
            // Add tax
            if (!empty($invoice_data['tax'])) {
                self::add_tax_to_order($order, $invoice_data['tax']);
            }
            
            // Set order status - use manual-invoice status if available, otherwise pending
            $order->set_status('manual-invoice');
            
            // Add meta data to identify as manual invoice
            $order->add_meta_data('_is_manual_invoice', true);
            $order->add_meta_data('_manual_invoice_notes', $invoice_data['notes'] ?? '');
            $order->add_meta_data('_manual_invoice_terms', $invoice_data['terms'] ?? '');
            
            // Set due date - either provided or calculated from settings
            if (!empty($invoice_data['due_date'])) {
                $order->add_meta_data('_manual_invoice_due_date', sanitize_text_field($invoice_data['due_date']));
            } else {
                $due_date = WC_Manual_Invoices_Settings::calculate_due_date($order->get_date_created()->format('Y-m-d H:i:s'));
                $order->add_meta_data('_manual_invoice_due_date', $due_date);
            }
            
            // Add creation timestamp
            $order->add_meta_data('_manual_invoice_created', current_time('mysql'));
            $order->add_meta_data('_manual_invoice_version', WC_MANUAL_INVOICES_VERSION);
            
            // Calculate totals
            $order->calculate_totals();
            
            // Save order
            $order->save();
            
            // Add order note
            $order->add_order_note(
                sprintf(
                    __('Manual invoice created via WooCommerce Manual Invoices Pro v%s', 'wc-manual-invoices'),
                    WC_MANUAL_INVOICES_VERSION
                ),
                false
            );
            
            // Auto-generate PDF if enabled
            $auto_pdf = WC_Manual_Invoices_Settings::get_setting('auto_generate_pdf', 'yes');
            if ($auto_pdf === 'yes') {
                // Generate PDF in background to avoid blocking the UI
                wp_schedule_single_event(time() + 5, 'wc_manual_invoice_generate_pdf', array($order->get_id()));
            }
            
            // Auto-send email if enabled and requested
            $auto_email = WC_Manual_Invoices_Settings::get_setting('auto_send_email', 'yes');
            $send_email = !empty($invoice_data['send_email']) || $auto_email === 'yes';
            
            if ($send_email) {
                // Send email in background to avoid blocking the UI
                wp_schedule_single_event(time() + 10, 'wc_manual_invoice_send_email', array($order->get_id()));
            }
            
            return $order->get_id();
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Invoice creation failed - ' . $e->getMessage());
            return new WP_Error('invoice_creation_failed', 
                sprintf(__('Failed to create invoice: %s', 'wc-manual-invoices'), $e->getMessage())
            );
        }
    }
    
    /**
     * Set customer for the order with enhanced data handling
     * 
     * @param WC_Order $order
     * @param array $invoice_data
     */
    private static function set_customer($order, $invoice_data) {
        if (!empty($invoice_data['customer_id'])) {
            // Existing customer
            $customer_id = intval($invoice_data['customer_id']);
            $customer = new WC_Customer($customer_id);
            
            if ($customer->get_id()) {
                $order->set_customer_id($customer_id);
                
                // Set billing address from customer with fallbacks
                $order->set_billing_first_name($customer->get_billing_first_name() ?: $customer->get_first_name());
                $order->set_billing_last_name($customer->get_billing_last_name() ?: $customer->get_last_name());
                $order->set_billing_email($customer->get_email());
                $order->set_billing_phone($customer->get_billing_phone());
                $order->set_billing_address_1($customer->get_billing_address_1());
                $order->set_billing_address_2($customer->get_billing_address_2());
                $order->set_billing_city($customer->get_billing_city());
                $order->set_billing_state($customer->get_billing_state());
                $order->set_billing_postcode($customer->get_billing_postcode());
                $order->set_billing_country($customer->get_billing_country() ?: 'US');
                
                // Set shipping address same as billing if not set
                if (!$customer->get_shipping_address_1()) {
                    $order->set_shipping_first_name($order->get_billing_first_name());
                    $order->set_shipping_last_name($order->get_billing_last_name());
                    $order->set_shipping_address_1($order->get_billing_address_1());
                    $order->set_shipping_address_2($order->get_billing_address_2());
                    $order->set_shipping_city($order->get_billing_city());
                    $order->set_shipping_state($order->get_billing_state());
                    $order->set_shipping_postcode($order->get_billing_postcode());
                    $order->set_shipping_country($order->get_billing_country());
                } else {
                    $order->set_shipping_first_name($customer->get_shipping_first_name());
                    $order->set_shipping_last_name($customer->get_shipping_last_name());
                    $order->set_shipping_address_1($customer->get_shipping_address_1());
                    $order->set_shipping_address_2($customer->get_shipping_address_2());
                    $order->set_shipping_city($customer->get_shipping_city());
                    $order->set_shipping_state($customer->get_shipping_state());
                    $order->set_shipping_postcode($customer->get_shipping_postcode());
                    $order->set_shipping_country($customer->get_shipping_country());
                }
            }
        } else {
            // New customer or guest checkout
            $email = sanitize_email($invoice_data['customer_email']);
            
            // Validate email
            if (!is_email($email)) {
                throw new Exception(__('Invalid email address provided.', 'wc-manual-invoices'));
            }
            
            // Check if user exists with this email
            $existing_user = get_user_by('email', $email);
            if ($existing_user) {
                $order->set_customer_id($existing_user->ID);
            }
            
            // Set billing details
            $order->set_billing_email($email);
            $order->set_billing_first_name(sanitize_text_field($invoice_data['billing_first_name'] ?? ''));
            $order->set_billing_last_name(sanitize_text_field($invoice_data['billing_last_name'] ?? ''));
            $order->set_billing_phone(sanitize_text_field($invoice_data['billing_phone'] ?? ''));
            $order->set_billing_address_1(sanitize_text_field($invoice_data['billing_address_1'] ?? ''));
            $order->set_billing_address_2(sanitize_text_field($invoice_data['billing_address_2'] ?? ''));
            $order->set_billing_city(sanitize_text_field($invoice_data['billing_city'] ?? ''));
            $order->set_billing_state(sanitize_text_field($invoice_data['billing_state'] ?? ''));
            $order->set_billing_postcode(sanitize_text_field($invoice_data['billing_postcode'] ?? ''));
            $order->set_billing_country(sanitize_text_field($invoice_data['billing_country'] ?? 'US'));
            
            // Set shipping same as billing for new customers
            $order->set_shipping_first_name($order->get_billing_first_name());
            $order->set_shipping_last_name($order->get_billing_last_name());
            $order->set_shipping_address_1($order->get_billing_address_1());
            $order->set_shipping_address_2($order->get_billing_address_2());
            $order->set_shipping_city($order->get_billing_city());
            $order->set_shipping_state($order->get_billing_state());
            $order->set_shipping_postcode($order->get_billing_postcode());
            $order->set_shipping_country($order->get_billing_country());
        }
    }
    
    /**
     * Add items to order with enhanced validation
     * 
     * @param WC_Order $order
     * @param array $invoice_data
     */
    private static function add_items_to_order($order, $invoice_data) {
        $items_added = 0;
        
        // Add regular products
        if (!empty($invoice_data['items'])) {
            foreach ($invoice_data['items'] as $item_data) {
                if (empty($item_data['product_id'])) {
                    continue;
                }
                
                $product_id = intval($item_data['product_id']);
                $product = wc_get_product($product_id);
                
                if (!$product || !$product->exists()) {
                    error_log("WC Manual Invoices: Product ID $product_id not found or invalid");
                    continue;
                }
                
                $quantity = max(1, floatval($item_data['quantity'] ?? 1));
                $total = floatval($item_data['total'] ?? 0);
                $unit_price = $quantity > 0 ? $total / $quantity : 0;
                
                // Create order item
                $order_item = new WC_Order_Item_Product();
                $order_item->set_product($product);
                $order_item->set_name($product->get_name());
                $order_item->set_quantity($quantity);
                $order_item->set_subtotal($total);
                $order_item->set_total($total);
                
                // Add product meta data
                if ($product->get_sku()) {
                    $order_item->add_meta_data('_sku', $product->get_sku());
                }
                
                // Add variation data if applicable
                if ($product->is_type('variation')) {
                    $variation_attributes = $product->get_variation_attributes();
                    foreach ($variation_attributes as $key => $value) {
                        $order_item->add_meta_data($key, $value);
                    }
                }
                
                // Add custom pricing note if different from product price
                $product_price = floatval($product->get_price());
                if ($product_price > 0 && abs($unit_price - $product_price) > 0.01) {
                    $order_item->add_meta_data('_custom_price', 'yes');
                    $order_item->add_meta_data('_original_price', $product_price);
                }
                
                $order->add_item($order_item);
                $items_added++;
            }
        }
        
        // Add custom items
        if (!empty($invoice_data['custom_items'])) {
            foreach ($invoice_data['custom_items'] as $item_data) {
                if (empty($item_data['name'])) {
                    continue;
                }
                
                $name = sanitize_text_field($item_data['name']);
                $description = sanitize_textarea_field($item_data['description'] ?? '');
                $quantity = max(1, floatval($item_data['quantity'] ?? 1));
                $total = floatval($item_data['total'] ?? 0);
                
                // Create custom order item
                $order_item = new WC_Order_Item_Product();
                $order_item->set_name($name);
                $order_item->set_quantity($quantity);
                $order_item->set_subtotal($total);
                $order_item->set_total($total);
                
                // Mark as custom item with description
                $order_item->add_meta_data('_is_custom_item', true);
                $order_item->add_meta_data('_custom_item_description', $description);
                $order_item->add_meta_data('_custom_item_created', current_time('mysql'));
                
                $order->add_item($order_item);
                $items_added++;
            }
        }
        
        if ($items_added === 0) {
            throw new Exception(__('No valid items could be added to the invoice.', 'wc-manual-invoices'));
        }
    }
    
    /**
     * Add fees to order with validation
     * 
     * @param WC_Order $order
     * @param array $fees
     */
    private static function add_fees_to_order($order, $fees) {
        foreach ($fees as $fee_data) {
            if (empty($fee_data['name']) || !isset($fee_data['amount'])) {
                continue;
            }
            
            $name = sanitize_text_field($fee_data['name']);
            $amount = floatval($fee_data['amount']);
            
            if ($amount == 0) {
                continue; // Skip zero amount fees
            }
            
            $fee_item = new WC_Order_Item_Fee();
            $fee_item->set_name($name);
            $fee_item->set_amount($amount);
            $fee_item->set_total($amount);
            
            // Add meta for manual fee tracking
            $fee_item->add_meta_data('_manual_fee', true);
            $fee_item->add_meta_data('_fee_created', current_time('mysql'));
            
            // Handle tax if applicable
            if (!empty($fee_data['tax_amount'])) {
                $tax_amount = floatval($fee_data['tax_amount']);
                $fee_item->set_total_tax($tax_amount);
            }
            
            $order->add_item($fee_item);
        }
    }
    
    /**
     * Add shipping to order with validation
     * 
     * @param WC_Order $order
     * @param array $shipping
     */
    private static function add_shipping_to_order($order, $shipping) {
        $method_title = sanitize_text_field($shipping['method_title'] ?? __('Shipping', 'wc-manual-invoices'));
        $method_id = sanitize_text_field($shipping['method_id'] ?? 'manual_shipping');
        $total = floatval($shipping['total'] ?? 0);
        
        if ($total <= 0) {
            return; // Skip zero or negative shipping
        }
        
        $shipping_item = new WC_Order_Item_Shipping();
        $shipping_item->set_method_title($method_title);
        $shipping_item->set_method_id($method_id);
        $shipping_item->set_total($total);
        
        // Add shipping meta
        $shipping_item->add_meta_data('_manual_shipping', true);
        $shipping_item->add_meta_data('_shipping_created', current_time('mysql'));
        
        // Handle shipping tax if applicable
        if (!empty($shipping['tax_amount'])) {
            $tax_amount = floatval($shipping['tax_amount']);
            $shipping_item->set_total_tax($tax_amount);
        }
        
        $order->add_item($shipping_item);
    }
    
    /**
     * Add tax to order with enhanced handling
     * 
     * @param WC_Order $order
     * @param array $tax
     */
    private static function add_tax_to_order($order, $tax) {
        $name = sanitize_text_field($tax['name'] ?? __('Tax', 'wc-manual-invoices'));
        $total = floatval($tax['total'] ?? 0);
        
        if ($total <= 0) {
            return; // Skip zero or negative tax
        }
        
        $tax_item = new WC_Order_Item_Tax();
        $tax_item->set_name($name);
        $tax_item->set_rate_code($tax['rate_code'] ?? 'MANUAL-TAX');
        $tax_item->set_label($tax['label'] ?? $name);
        $tax_item->set_compound($tax['compound'] ?? false);
        $tax_item->set_tax_total($total);
        $tax_item->set_shipping_tax_total($tax['shipping_tax'] ?? 0);
        
        // Add manual tax meta
        $tax_item->add_meta_data('_manual_tax', true);
        $tax_item->add_meta_data('_tax_created', current_time('mysql'));
        
        // Set tax rate if provided
        if (!empty($tax['rate'])) {
            $tax_item->add_meta_data('_tax_rate', floatval($tax['rate']));
        }
        
        $order->add_item($tax_item);
    }
    
    /**
     * Generate pay now link with enhanced security
     * 
     * @param int $order_id
     * @return string Pay now URL
     */
    public static function generate_pay_link($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            return '';
        }
        
        // Generate secure payment URL
        $pay_url = $order->get_checkout_payment_url();
        
        // Add tracking parameters
        $pay_url = add_query_arg(array(
            'invoice_id' => $order_id,
            'key' => $order->get_order_key(),
            'utm_source' => 'manual_invoice',
            'utm_medium' => 'email'
        ), $pay_url);
        
        return $pay_url;
    }
    
    /**
     * Clone an existing invoice with enhanced data copying
     * 
     * @param int $order_id Original order ID
     * @return int|WP_Error New order ID or error
     */
    public static function clone_invoice($order_id) {
        $original_order = wc_get_order($order_id);
        
        if (!$original_order || !$original_order->get_meta('_is_manual_invoice')) {
            return new WP_Error('invalid_invoice', __('Invalid invoice to clone.', 'wc-manual-invoices'));
        }
        
        try {
            // Prepare invoice data from original order
            $invoice_data = array(
                'customer_id' => $original_order->get_customer_id(),
                'customer_email' => $original_order->get_billing_email(),
                'billing_first_name' => $original_order->get_billing_first_name(),
                'billing_last_name' => $original_order->get_billing_last_name(),
                'billing_phone' => $original_order->get_billing_phone(),
                'billing_address_1' => $original_order->get_billing_address_1(),
                'billing_address_2' => $original_order->get_billing_address_2(),
                'billing_city' => $original_order->get_billing_city(),
                'billing_state' => $original_order->get_billing_state(),
                'billing_postcode' => $original_order->get_billing_postcode(),
                'billing_country' => $original_order->get_billing_country(),
                'items' => array(),
                'custom_items' => array(),
                'fees' => array(),
                'shipping' => array(),
                'tax' => array(),
                'notes' => $original_order->get_meta('_manual_invoice_notes'),
                'terms' => $original_order->get_meta('_manual_invoice_terms'),
                'send_email' => false // Don't auto-send for cloned invoices
            );
            
            // Copy items with proper categorization
            foreach ($original_order->get_items() as $item) {
                if ($item->get_meta('_is_custom_item')) {
                    // Custom item
                    $invoice_data['custom_items'][] = array(
                        'name' => $item->get_name(),
                        'description' => $item->get_meta('_custom_item_description'),
                        'quantity' => $item->get_quantity(),
                        'total' => $item->get_total(),
                    );
                } else {
                    // Regular product
                    $product = $item->get_product();
                    if ($product && $product->exists()) {
                        $invoice_data['items'][] = array(
                            'product_id' => $product->get_id(),
                            'quantity' => $item->get_quantity(),
                            'total' => $item->get_total(),
                        );
                    } else {
                        // Product no longer exists, convert to custom item
                        $invoice_data['custom_items'][] = array(
                            'name' => $item->get_name(),
                            'description' => __('Original product no longer available', 'wc-manual-invoices'),
                            'quantity' => $item->get_quantity(),
                            'total' => $item->get_total(),
                        );
                    }
                }
            }
            
            // Copy fees
            foreach ($original_order->get_fees() as $fee) {
                $invoice_data['fees'][] = array(
                    'name' => $fee->get_name(),
                    'amount' => $fee->get_total(),
                );
            }
            
            // Copy shipping
            foreach ($original_order->get_shipping_methods() as $shipping) {
                $invoice_data['shipping'] = array(
                    'method_title' => $shipping->get_method_title(),
                    'method_id' => $shipping->get_method_id(),
                    'total' => $shipping->get_total(),
                );
                break; // Only one shipping method supported for now
            }
            
            // Copy tax (simplified)
            $total_tax = $original_order->get_total_tax();
            if ($total_tax > 0) {
                $tax_items = $original_order->get_taxes();
                if (!empty($tax_items)) {
                    $first_tax = reset($tax_items);
                    $invoice_data['tax'] = array(
                        'name' => $first_tax->get_name(),
                        'total' => $total_tax,
                    );
                }
            }
            
            // Create new invoice
            $new_order_id = self::create_invoice($invoice_data);
            
            if (!is_wp_error($new_order_id)) {
                // Add cloning meta to both orders
                $original_order->add_meta_data('_cloned_to', $new_order_id);
                $original_order->add_meta_data('_clone_date', current_time('mysql'));
                $original_order->save();
                
                $new_order = wc_get_order($new_order_id);
                $new_order->add_meta_data('_cloned_from', $order_id);
                $new_order->add_meta_data('_clone_date', current_time('mysql'));
                $new_order->save();
                
                // Add order notes
                $original_order->add_order_note(
                    sprintf(__('Invoice cloned to order #%d', 'wc-manual-invoices'), $new_order_id),
                    false
                );
                
                $new_order->add_order_note(
                    sprintf(__('Cloned from invoice #%d', 'wc-manual-invoices'), $order_id),
                    false
                );
            }
            
            return $new_order_id;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Clone failed - ' . $e->getMessage());
            return new WP_Error('clone_failed', 
                sprintf(__('Failed to clone invoice: %s', 'wc-manual-invoices'), $e->getMessage())
            );
        }
    }
    
    /**
     * Update invoice with validation and change tracking
     * 
     * @param int $order_id Order ID
     * @param array $invoice_data Updated invoice data
     * @return bool|WP_Error Success or error
     */
    public static function update_invoice($order_id, $invoice_data) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            return new WP_Error('invalid_invoice', __('Invalid invoice to update.', 'wc-manual-invoices'));
        }
        
        // Only allow updates if order is pending payment
        if (!$order->needs_payment()) {
            return new WP_Error('invoice_paid', __('Cannot update paid invoices.', 'wc-manual-invoices'));
        }
        
        try {
            // Store original data for change tracking
            $original_total = $order->get_total();
            $original_items_count = count($order->get_items());
            
            // Remove existing items
            foreach ($order->get_items() as $item_id => $item) {
                $order->remove_item($item_id);
            }
            
            // Remove existing fees
            foreach ($order->get_fees() as $item_id => $fee) {
                $order->remove_item($item_id);
            }
            
            // Remove existing shipping
            foreach ($order->get_shipping_methods() as $item_id => $shipping) {
                $order->remove_item($item_id);
            }
            
            // Remove existing tax
            foreach ($order->get_taxes() as $item_id => $tax) {
                $order->remove_item($item_id);
            }
            
            // Update customer information
            self::set_customer($order, $invoice_data);
            
            // Add updated items
            self::add_items_to_order($order, $invoice_data);
            
            // Add updated fees
            if (!empty($invoice_data['fees'])) {
                self::add_fees_to_order($order, $invoice_data['fees']);
            }
            
            // Add updated shipping
            if (!empty($invoice_data['shipping'])) {
                self::add_shipping_to_order($order, $invoice_data['shipping']);
            }
            
            // Add updated tax
            if (!empty($invoice_data['tax'])) {
                self::add_tax_to_order($order, $invoice_data['tax']);
            }
            
            // Update meta data
            $order->update_meta_data('_manual_invoice_notes', $invoice_data['notes'] ?? '');
            $order->update_meta_data('_manual_invoice_terms', $invoice_data['terms'] ?? '');
            
            // Update due date if provided
            if (!empty($invoice_data['due_date'])) {
                $order->update_meta_data('_manual_invoice_due_date', sanitize_text_field($invoice_data['due_date']));
            }
            
            // Add update tracking
            $order->update_meta_data('_manual_invoice_updated', current_time('mysql'));
            $order->update_meta_data('_manual_invoice_update_count', 
                intval($order->get_meta('_manual_invoice_update_count')) + 1
            );
            
            // Recalculate totals
            $order->calculate_totals();
            
            // Save order
            $order->save();
            
            // Track changes in order note
            $new_total = $order->get_total();
            $new_items_count = count($order->get_items());
            $changes = array();
            
            if (abs($new_total - $original_total) > 0.01) {
                $changes[] = sprintf(__('Total changed from %s to %s', 'wc-manual-invoices'), 
                    wc_price($original_total), wc_price($new_total));
            }
            
            if ($new_items_count !== $original_items_count) {
                $changes[] = sprintf(__('Items count changed from %d to %d', 'wc-manual-invoices'), 
                    $original_items_count, $new_items_count);
            }
            
            if (!empty($changes)) {
                $order->add_order_note(
                    sprintf(__('Invoice updated: %s', 'wc-manual-invoices'), implode(', ', $changes)),
                    false
                );
            } else {
                $order->add_order_note(
                    __('Invoice details updated', 'wc-manual-invoices'),
                    false
                );
            }
            
            // Clear any cached PDF since content changed
            if ($order->get_meta('_invoice_pdf_path')) {
                WC_Manual_Invoice_PDF::delete_pdf($order_id);
            }
            
            return true;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Update failed - ' . $e->getMessage());
            return new WP_Error('update_failed', 
                sprintf(__('Failed to update invoice: %s', 'wc-manual-invoices'), $e->getMessage())
            );
        }
    }
    
    /**
     * Get invoice statistics for reporting
     * 
     * @return array Statistics
     */
    public static function get_invoice_statistics() {
        $stats = array(
            'total_invoices' => 0,
            'pending_invoices' => 0,
            'paid_invoices' => 0,
            'overdue_invoices' => 0,
            'total_value' => 0,
            'pending_value' => 0,
            'paid_value' => 0,
            'average_value' => 0,
            'this_month_count' => 0,
            'this_month_value' => 0,
        );
        
        try {
            // Use WooCommerce OrderUtil if available (HPOS compatibility)
            if (class_exists('\Automattic\WooCommerce\Utilities\OrderUtil') && 
                \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
                // HPOS is enabled
                $all_invoices = wc_get_orders(array(
                    'meta_key' => '_is_manual_invoice',
                    'meta_value' => '1',
                    'limit' => -1,
                    'return' => 'objects'
                ));
            } else {
                // Legacy posts table
                $all_invoices = wc_get_orders(array(
                    'meta_key' => '_is_manual_invoice',
                    'meta_value' => '1',
                    'limit' => -1,
                    'return' => 'objects'
                ));
            }
            
            $current_month = current_time('Y-m');
            $current_time = current_time('timestamp');
            
            foreach ($all_invoices as $order) {
                $order_total = $order->get_total();
                $order_status = $order->get_status();
                $due_date = $order->get_meta('_manual_invoice_due_date');
                $order_month = $order->get_date_created()->format('Y-m');
                
                // Total counts and values
                $stats['total_invoices']++;
                $stats['total_value'] += $order_total;
                
                // Status-based stats
                if (in_array($order_status, array('pending', 'manual-invoice', 'on-hold'))) {
                    $stats['pending_invoices']++;
                    $stats['pending_value'] += $order_total;
                    
                    // Check if overdue
                    if ($due_date && strtotime($due_date) < $current_time) {
                        $stats['overdue_invoices']++;
                    }
                } elseif (in_array($order_status, array('processing', 'completed'))) {
                    $stats['paid_invoices']++;
                    $stats['paid_value'] += $order_total;
                }
                
                // This month stats
                if ($order_month === $current_month) {
                    $stats['this_month_count']++;
                    $stats['this_month_value'] += $order_total;
                }
            }
            
            // Calculate average
            if ($stats['total_invoices'] > 0) {
                $stats['average_value'] = $stats['total_value'] / $stats['total_invoices'];
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Statistics calculation failed - ' . $e->getMessage());
        }
        
        return $stats;
    }
    
    /**
     * Validate invoice data before processing
     * 
     * @param array $data Raw form data
     * @return array|WP_Error Validated data or error
     */
    public static function validate_invoice_data($data) {
        $validated = array();
        $errors = array();
        
        // Customer validation
        if (!empty($data['customer_id'])) {
            $customer_id = intval($data['customer_id']);
            $customer = new WC_Customer($customer_id);
            if (!$customer->get_id()) {
                $errors[] = __('Selected customer does not exist.', 'wc-manual-invoices');
            } else {
                $validated['customer_id'] = $customer_id;
            }
        } elseif (!empty($data['customer_email'])) {
            $email = sanitize_email($data['customer_email']);
            if (!is_email($email)) {
                $errors[] = __('Please enter a valid email address.', 'wc-manual-invoices');
            } else {
                $validated['customer_email'] = $email;
                
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
            }
        } else {
            $errors[] = __('Customer information is required.', 'wc-manual-invoices');
        }
        
        // Items validation
        $validated['items'] = array();
        $validated['custom_items'] = array();
        $items_count = 0;
        
        // Regular products
        if (!empty($data['product_ids'])) {
            for ($i = 0; $i < count($data['product_ids']); $i++) {
                if (!empty($data['product_ids'][$i])) {
                    $product_id = intval($data['product_ids'][$i]);
                    $product = wc_get_product($product_id);
                    
                    if (!$product || !$product->exists()) {
                        $errors[] = sprintf(__('Product with ID %d does not exist.', 'wc-manual-invoices'), $product_id);
                        continue;
                    }
                    
                    $quantity = max(1, floatval($data['product_quantities'][$i] ?? 1));
                    $total = max(0, floatval($data['product_totals'][$i] ?? 0));
                    
                    $validated['items'][] = array(
                        'product_id' => $product_id,
                        'quantity' => $quantity,
                        'total' => $total,
                    );
                    $items_count++;
                }
            }
        }
        
        // Custom items
        if (!empty($data['custom_item_names'])) {
            for ($i = 0; $i < count($data['custom_item_names']); $i++) {
                if (!empty($data['custom_item_names'][$i])) {
                    $name = sanitize_text_field($data['custom_item_names'][$i]);
                    $description = sanitize_textarea_field($data['custom_item_descriptions'][$i] ?? '');
                    $quantity = max(1, floatval($data['custom_item_quantities'][$i] ?? 1));
                    $total = max(0, floatval($data['custom_item_totals'][$i] ?? 0));
                    
                    $validated['custom_items'][] = array(
                        'name' => $name,
                        'description' => $description,
                        'quantity' => $quantity,
                        'total' => $total,
                    );
                    $items_count++;
                }
            }
        }
        
        // Check if we have at least one item
        if ($items_count === 0) {
            $errors[] = __('At least one item is required.', 'wc-manual-invoices');
        }
        
        // Fees validation
        $validated['fees'] = array();
        if (!empty($data['fee_names'])) {
            for ($i = 0; $i < count($data['fee_names']); $i++) {
                if (!empty($data['fee_names'][$i])) {
                    $name = sanitize_text_field($data['fee_names'][$i]);
                    $amount = floatval($data['fee_amounts'][$i] ?? 0);
                    
                    if ($amount != 0) { // Allow negative fees (discounts)
                        $validated['fees'][] = array(
                            'name' => $name,
                            'amount' => $amount,
                        );
                    }
                }
            }
        }
        
        // Shipping validation
        if (!empty($data['shipping_total']) && floatval($data['shipping_total']) > 0) {
            $validated['shipping'] = array(
                'method_title' => sanitize_text_field($data['shipping_method'] ?? __('Shipping', 'wc-manual-invoices')),
                'method_id' => 'manual_shipping',
                'total' => floatval($data['shipping_total']),
            );
        }
        
        // Tax validation
        if (!empty($data['tax_total']) && floatval($data['tax_total']) > 0) {
            $validated['tax'] = array(
                'name' => sanitize_text_field($data['tax_name'] ?? __('Tax', 'wc-manual-invoices')),
                'total' => floatval($data['tax_total']),
            );
        }
        
        // Notes and terms
        $validated['notes'] = sanitize_textarea_field($data['notes'] ?? '');
        $validated['terms'] = sanitize_textarea_field($data['terms'] ?? '');
        
        // Due date validation
        if (!empty($data['due_date'])) {
            $due_date = sanitize_text_field($data['due_date']);
            if (strtotime($due_date) === false) {
                $errors[] = __('Invalid due date format.', 'wc-manual-invoices');
            } else {
                $validated['due_date'] = $due_date;
            }
        }
        
        // Email option
        $validated['send_email'] = !empty($data['send_email']);
        
        // Return errors if any
        if (!empty($errors)) {
            return new WP_Error('validation_failed', implode(' ', $errors));
        }
        
        return $validated;
    }
}

// Register scheduled actions for background processing
add_action('wc_manual_invoice_generate_pdf', function($order_id) {
    WC_Manual_Invoice_PDF::generate_pdf($order_id);
});

add_action('wc_manual_invoice_send_email', function($order_id) {
    $order = wc_get_order($order_id);
    if ($order && $order->get_meta('_is_manual_invoice')) {
        if (WC() && WC()->mailer() && isset(WC()->mailer()->emails['WC_Manual_Invoice_Email'])) {
            WC()->mailer()->emails['WC_Manual_Invoice_Email']->trigger($order_id);
            
            // Update last sent date
            $order->update_meta_data('_invoice_last_sent', current_time('mysql'));
            $order->save();
        }
    }
});