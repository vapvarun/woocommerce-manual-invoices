<?php
/**
 * Manual Invoice Email Template (Plain Text)
 * 
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/manual-invoice.php
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get company info
$company_info = WC_Manual_Invoices_Settings::get_company_info();
$pay_link = $order->get_checkout_payment_url();
$due_date = $order->get_meta('_manual_invoice_due_date');
$notes = $order->get_meta('_manual_invoice_notes');
$terms = $order->get_meta('_manual_invoice_terms');

echo "= " . $email_heading . " =\n\n";

echo sprintf(__('Invoice #%s', 'wc-manual-invoices'), $order->get_order_number()) . "\n";
echo sprintf(__('Date: %s', 'wc-manual-invoices'), wc_format_datetime($order->get_date_created())) . "\n";
if ($due_date) {
    echo sprintf(__('Due Date: %s', 'wc-manual-invoices'), date_i18n(get_option('date_format'), strtotime($due_date))) . "\n";
}
echo "\n";

// Company info
echo "===== " . __('From:', 'wc-manual-invoices') . " =====\n";
echo $company_info['name'] . "\n";
if ($company_info['address']) {
    echo $company_info['address'] . "\n";
}
if ($company_info['phone']) {
    echo __('Phone: ', 'wc-manual-invoices') . $company_info['phone'] . "\n";
}
if ($company_info['email']) {
    echo __('Email: ', 'wc-manual-invoices') . $company_info['email'] . "\n";
}
echo "\n";

// Customer info
echo "===== " . __('To:', 'wc-manual-invoices') . " =====\n";
echo $order->get_formatted_billing_full_name() . "\n";
echo $order->get_billing_email() . "\n";
if ($order->get_billing_phone()) {
    echo $order->get_billing_phone() . "\n";
}
if ($order->get_formatted_billing_address()) {
    echo strip_tags($order->get_formatted_billing_address()) . "\n";
}
echo "\n";

// Items
echo "===== " . __('Invoice Items:', 'wc-manual-invoices') . " =====\n";
foreach ($order->get_items() as $item_id => $item) {
    echo sprintf(
        '%s x %s = %s',
        $item->get_name(),
        $item->get_quantity(),
        wc_price($item->get_total())
    ) . "\n";
    
    if ($item->get_meta('_custom_item_description')) {
        echo "  " . $item->get_meta('_custom_item_description') . "\n";
    }
}
echo "\n";

// Totals
echo "===== " . __('Totals:', 'wc-manual-invoices') . " =====\n";
echo __('Subtotal: ', 'wc-manual-invoices') . wc_price($order->get_subtotal()) . "\n";

foreach ($order->get_fees() as $fee) {
    echo $fee->get_name() . ': ' . wc_price($fee->get_total()) . "\n";
}

if ($order->get_total_shipping() > 0) {
    echo __('Shipping: ', 'wc-manual-invoices') . wc_price($order->get_total_shipping()) . "\n";
}

if ($order->get_total_tax() > 0) {
    echo __('Tax: ', 'wc-manual-invoices') . wc_price($order->get_total_tax()) . "\n";
}

echo __('TOTAL: ', 'wc-manual-invoices') . $order->get_formatted_order_total() . "\n\n";

// Payment instructions
if ($order->needs_payment()) {
    echo "===== " . __('Payment Instructions:', 'wc-manual-invoices') . " =====\n";
    echo __('Please pay online using the following link:', 'wc-manual-invoices') . "\n";
    echo $pay_link . "\n\n";
}

// Notes
if ($notes) {
    echo "===== " . __('Notes:', 'wc-manual-invoices') . " =====\n";
    echo $notes . "\n\n";
}

// Terms
if ($terms) {
    echo "===== " . __('Terms & Conditions:', 'wc-manual-invoices') . " =====\n";
    echo $terms . "\n\n";
}

echo "\n";
echo apply_filters('woocommerce_email_footer_text', get_option('woocommerce_email_footer_text'));