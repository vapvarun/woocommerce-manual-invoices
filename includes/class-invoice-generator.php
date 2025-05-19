<?php
/**
 * Invoice Generator Class
 * 
 * Handles the creation and management of manual invoices
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
            
            // Set order status
            $order->set_status('pending');
            
            // Add meta data to identify as manual invoice
            $order->add_meta_data('_is_manual_invoice', true);
            $order->add_meta_data('_manual_invoice_notes', $invoice_data['notes'] ?? '');
            $order->add_meta_data('_manual_invoice_terms', $invoice_data['terms'] ?? '');
            $order->add_meta_data('_manual_invoice_due_date', $invoice_data['due_date'] ?? '');
            
            // Calculate totals
            $order->calculate_totals();
            
            // Save order
            $order->save();
            
            // Add order note
            $order->add_order_note(
                __('Manual invoice created via WooCommerce Manual Invoices Pro', 'wc-manual-invoices'),
                false
            );
            
            return $order->get_id();
            
        } catch (Exception $e) {
            return new WP_Error('invoice_creation_failed', $e->getMessage());
        }
    }
    
    /**
     * Set customer for the order
     * 
     * @param WC_Order $order
     * @param array $invoice_data
     */
    private static function set_customer($order, $invoice_data) {
        if (!empty($invoice_data['customer_id'])) {
            // Existing customer
            $customer = new WC_Customer($invoice_data['customer_id']);
            $order->set_customer_id($invoice_data['customer_id']);
            
            // Set billing address from customer
            $order->set_billing_first_name($customer->get_billing_first_name());
            $order->set_billing_last_name($customer->get_billing_last_name());
            $order->set_billing_email($customer->get_billing_email());
            $order->set_billing_phone($customer->get_billing_phone());
            $order->set_billing_address_1($customer->get_billing_address_1());
            $order->set_billing_address_2($customer->get_billing_address_2());
            $order->set_billing_city($customer->get_billing_city());
            $order->set_billing_state($customer->get_billing_state());
            $order->set_billing_postcode($customer->get_billing_postcode());
            $order->set_billing_country($customer->get_billing_country());
            
        } else {
            // New customer or guest
            $order->set_billing_email($invoice_data['customer_email']);
            $order->set_billing_first_name($invoice_data['billing_first_name'] ?? '');
            $order->set_billing_last_name($invoice_data['billing_last_name'] ?? '');
            $order->set_billing_phone($invoice_data['billing_phone'] ?? '');
            $order->set_billing_address_1($invoice_data['billing_address_1'] ?? '');
            $order->set_billing_address_2($invoice_data['billing_address_2'] ?? '');
            $order->set_billing_city($invoice_data['billing_city'] ?? '');
            $order->set_billing_state($invoice_data['billing_state'] ?? '');
            $order->set_billing_postcode($invoice_data['billing_postcode'] ?? '');
            $order->set_billing_country($invoice_data['billing_country'] ?? 'US');
        }
    }
    
    /**
     * Add items to order
     * 
     * @param WC_Order $order
     * @param array $invoice_data
     */
    private static function add_items_to_order($order, $invoice_data) {
        // Add regular products
        if (!empty($invoice_data['items'])) {
            foreach ($invoice_data['items'] as $item) {
                $product = wc_get_product($item['product_id']);
                if ($product) {
                    $order_item = new WC_Order_Item_Product();
                    $order_item->set_product($product);
                    $order_item->set_name($product->get_name());
                    $order_item->set_quantity($item['quantity']);
                    $order_item->set_subtotal($item['total']);
                    $order_item->set_total($item['total']);
                    
                    $order->add_item($order_item);
                }
            }
        }
        
        // Add custom items
        if (!empty($invoice_data['custom_items'])) {
            foreach ($invoice_data['custom_items'] as $item) {
                $order_item = new WC_Order_Item_Product();
                $order_item->set_name($item['name']);
                $order_item->set_quantity($item['quantity']);
                $order_item->set_subtotal($item['total']);
                $order_item->set_total($item['total']);
                
                // Add meta data for custom items
                $order_item->add_meta_data('_is_custom_item', true);
                $order_item->add_meta_data('_custom_item_description', $item['description'] ?? '');
                
                $order->add_item($order_item);
            }
        }
    }
    
    /**
     * Add fees to order
     * 
     * @param WC_Order $order
     * @param array $fees
     */
    private static function add_fees_to_order($order, $fees) {
        foreach ($fees as $fee) {
            $fee_item = new WC_Order_Item_Fee();
            $fee_item->set_name($fee['name']);
            $fee_item->set_amount($fee['amount']);
            $fee_item->set_total($fee['amount']);
            
            $order->add_item($fee_item);
        }
    }
    
    /**
     * Add shipping to order
     * 
     * @param WC_Order $order
     * @param array $shipping
     */
    private static function add_shipping_to_order($order, $shipping) {
        $shipping_item = new WC_Order_Item_Shipping();
        $shipping_item->set_method_title($shipping['method_title'] ?? __('Shipping', 'wc-manual-invoices'));
        $shipping_item->set_method_id($shipping['method_id'] ?? 'manual_shipping');
        $shipping_item->set_total($shipping['total']);
        
        $order->add_item($shipping_item);
    }
    
    /**
     * Add tax to order
     * 
     * @param WC_Order $order
     * @param array $tax
     */
    private static function add_tax_to_order($order, $tax) {
        $tax_item = new WC_Order_Item_Tax();
        $tax_item->set_name($tax['name'] ?? __('Tax', 'wc-manual-invoices'));
        $tax_item->set_rate_code($tax['rate_code'] ?? 'TAX');
        $tax_item->set_label($tax['label'] ?? __('Tax', 'wc-manual-invoices'));
        $tax_item->set_compound($tax['compound'] ?? false);
        $tax_item->set_tax_total($tax['total']);
        $tax_item->set_shipping_tax_total($tax['shipping_tax'] ?? 0);
        
        $order->add_item($tax_item);
    }
    
    /**
     * Generate pay now link
     * 
     * @param int $order_id
     * @return string Pay now URL
     */
    public static function generate_pay_link($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return '';
        }
        
        return $order->get_checkout_payment_url();
    }
    
    /**
     * Clone an existing invoice
     * 
     * @param int $order_id Original order ID
     * @return int|WP_Error New order ID or error
     */
    public static function clone_invoice($order_id) {
        $original_order = wc_get_order($order_id);
        
        if (!$original_order || !$original_order->get_meta('_is_manual_invoice')) {
            return new WP_Error('invalid_invoice', __('Invalid invoice to clone.', 'wc-manual-invoices'));
        }
        
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
        );
        
        // Copy items
        foreach ($original_order->get_items() as $item) {
            if ($item->get_meta('_is_custom_item')) {
                $invoice_data['custom_items'][] = array(
                    'name' => $item->get_name(),
                    'description' => $item->get_meta('_custom_item_description'),
                    'quantity' => $item->get_quantity(),
                    'total' => $item->get_total(),
                );
            } else {
                $product = $item->get_product();
                if ($product) {
                    $invoice_data['items'][] = array(
                        'product_id' => $product->get_id(),
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
            break; // Only one shipping method for now
        }
        
        // Copy tax
        foreach ($original_order->get_tax_totals() as $tax) {
            $invoice_data['tax'] = array(
                'name' => $tax->label,
                'total' => $tax->amount,
            );
            break; // Simplified tax handling
        }
        
        // Create new invoice
        return self::create_invoice($invoice_data);
    }
    
    /**
     * Update invoice
     * 
     * @param int $order_id Order ID
     * @param array $invoice_data Updated invoice data
     * @return bool Success or failure
     */
    public static function update_invoice($order_id, $invoice_data) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            return false;
        }
        
        // Only allow updates if order is pending payment
        if (!$order->needs_payment()) {
            return false;
        }
        
        try {
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
            $order->update_meta_data('_manual_invoice_due_date', $invoice_data['due_date'] ?? '');
            
            // Recalculate totals
            $order->calculate_totals();
            
            // Save order
            $order->save();
            
            // Add order note
            $order->add_order_note(
                __('Manual invoice updated via WooCommerce Manual Invoices Pro', 'wc-manual-invoices'),
                false
            );
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
}