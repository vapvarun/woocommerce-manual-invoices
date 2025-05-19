<?php
/**
 * Enhanced Manual Invoice Email Template (HTML) with Smart Defaults
 * 
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/manual-invoice.php
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Executes the header of the email
 */
do_action('woocommerce_email_header', $email_heading, $email);

// Get company info with smart defaults
$company_info = WC_Manual_Invoices_Settings::get_company_info();
$pay_link = $order->get_checkout_payment_url();
$due_date = $order->get_meta('_manual_invoice_due_date');
$notes = $order->get_meta('_manual_invoice_notes');
$terms = $order->get_meta('_manual_invoice_terms');
$invoice_number = WC_Manual_Invoices_Settings::get_invoice_number($order->get_id());

// Ensure we have fallback values for critical fields
$company_name = !empty($company_info['name']) ? $company_info['name'] : get_bloginfo('name');
$company_email = !empty($company_info['email']) ? $company_info['email'] : get_option('admin_email');

// Email styles with improved layout
$email_styles = '
<style type="text/css">
    .invoice-container {
        max-width: 600px;
        margin: 0 auto;
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #333;
        background-color: #ffffff;
    }
    .company-header {
        text-align: center;
        margin-bottom: 30px;
        padding: 20px;
        background: linear-gradient(135deg, #96588a 0%, #7e4874 100%);
        color: white;
        border-radius: 8px 8px 0 0;
    }
    .company-logo {
        margin-bottom: 15px;
    }
    .company-logo img {
        max-width: 180px;
        height: auto;
        display: block;
        margin: 0 auto;
        border-radius: 4px;
    }
    .company-name {
        font-size: 26px;
        font-weight: bold;
        color: white;
        margin: 10px 0;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    }
    .company-tagline {
        font-size: 14px;
        opacity: 0.9;
        margin-top: 5px;
    }
    .invoice-title {
        background-color: #f8f9fa;
        color: #96588a;
        padding: 20px;
        text-align: center;
        margin-bottom: 30px;
        border: 2px solid #96588a;
        border-radius: 8px;
    }
    .invoice-title h2 {
        margin: 0;
        font-size: 24px;
        font-weight: bold;
    }
    .invoice-meta {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 6px;
        margin-bottom: 25px;
        border-left: 4px solid #96588a;
    }
    .invoice-meta-row {
        display: table;
        width: 100%;
        margin-bottom: 10px;
    }
    .invoice-meta-row:last-child {
        margin-bottom: 0;
    }
    .meta-label {
        display: table-cell;
        font-weight: bold;
        width: 35%;
        color: #555;
        padding-right: 10px;
    }
    .meta-value {
        display: table-cell;
        color: #333;
    }
    .addresses-container {
        display: table;
        width: 100%;
        margin-bottom: 30px;
        background-color: #ffffff;
        border: 1px solid #e1e1e1;
        border-radius: 6px;
        overflow: hidden;
    }
    .address-block {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        padding: 20px;
    }
    .address-block:first-child {
        border-right: 1px solid #e1e1e1;
        background-color: #f8f9fa;
    }
    .address-title {
        font-size: 16px;
        font-weight: bold;
        color: #96588a;
        margin-bottom: 12px;
        padding-bottom: 5px;
        border-bottom: 2px solid #96588a;
    }
    .address-content {
        font-size: 14px;
        line-height: 1.6;
    }
    .address-content strong {
        color: #333;
        font-size: 15px;
    }
    .address-content .company-detail {
        margin-bottom: 6px;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 25px;
        background-color: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        border-radius: 6px;
        overflow: hidden;
    }
    .items-table th {
        background: linear-gradient(135deg, #96588a 0%, #7e4874 100%);
        color: white;
        padding: 15px 12px;
        text-align: left;
        font-weight: bold;
        font-size: 14px;
    }
    .items-table th.text-center {
        text-align: center;
    }
    .items-table th.text-right {
        text-align: right;
    }
    .items-table td {
        padding: 12px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 13px;
    }
    .items-table tr:last-child td {
        border-bottom: none;
    }
    .items-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .items-table .item-name {
        font-weight: bold;
        color: #333;
        font-size: 14px;
    }
    .items-table .item-description {
        color: #666;
        font-size: 12px;
        font-style: italic;
        margin-top: 4px;
    }
    .totals-table {
        width: 320px;
        margin-left: auto;
        margin-bottom: 30px;
        background-color: white;
        border-radius: 6px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .totals-table td {
        padding: 12px 15px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 14px;
    }
    .totals-table tr:last-child td {
        border-bottom: none;
    }
    .totals-table .subtotal-row {
        color: #555;
    }
    .totals-table .total-row {
        background: linear-gradient(135deg, #96588a 0%, #7e4874 100%);
        color: white;
        font-weight: bold;
        font-size: 16px;
    }
    .payment-section {
        background: linear-gradient(135deg, #96588a 0%, #7e4874 100%);
        color: white;
        padding: 30px;
        text-align: center;
        border-radius: 10px;
        margin: 30px 0;
        box-shadow: 0 4px 15px rgba(150, 88, 138, 0.3);
    }
    .payment-section h3 {
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 22px;
        font-weight: normal;
    }
    .payment-section p {
        margin-bottom: 25px;
        font-size: 16px;
        opacity: 0.9;
    }
    .pay-button {
        display: inline-block;
        background-color: white;
        color: #96588a;
        padding: 15px 35px;
        text-decoration: none;
        border-radius: 50px;
        font-weight: bold;
        font-size: 18px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
        border: 2px solid white;
    }
    .footer-section {
        background-color: #f8f9fa;
        padding: 20px;
        text-align: center;
        margin-top: 40px;
        border-top: 3px solid #96588a;
        border-radius: 0 0 8px 8px;
    }
    .footer-company-info {
        font-size: 12px;
        color: #666;
        margin-bottom: 10px;
    }
    .footer-note {
        font-size: 11px;
        color: #999;
        font-style: italic;
    }
    @media (max-width: 600px) {
        .invoice-container {
            margin: 0;
            border-radius: 0;
        }
        .addresses-container,
        .address-block {
            display: block;
            width: 100%;
        }
        .address-block {
            border-right: none !important;
            border-bottom: 1px solid #e1e1e1;
        }
        .address-block:last-child {
            border-bottom: none;
        }
        .totals-table {
            width: 100%;
        }
        .pay-button {
            padding: 12px 25px;
            font-size: 16px;
        }
    }
</style>';

echo $email_styles;
?>

<div class="invoice-container">
    <!-- Company Header -->
    <div class="company-header">
        <?php if (!empty($company_info['logo'])) : ?>
            <div class="company-logo">
                <img src="<?php echo esc_url($company_info['logo']); ?>" 
                     alt="<?php echo esc_attr($company_name); ?>" />
            </div>
        <?php endif; ?>
        
        <div class="company-name">
            <?php echo esc_html($company_name); ?>
        </div>
        
        <?php if (!empty($company_info['address']) || !empty($company_info['phone'])) : ?>
            <div class="company-tagline">
                <?php 
                $tagline_parts = array();
                if (!empty($company_info['phone'])) {
                    $tagline_parts[] = $company_info['phone'];
                }
                if (!empty($company_info['email'])) {
                    $tagline_parts[] = $company_info['email'];
                }
                echo esc_html(implode(' • ', $tagline_parts));
                ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Invoice Title -->
    <div class="invoice-title">
        <h2><?php printf(__('Invoice %s', 'wc-manual-invoices'), $invoice_number); ?></h2>
    </div>

    <!-- Invoice Meta Information -->
    <div class="invoice-meta">
        <div class="invoice-meta-row">
            <div class="meta-label"><?php _e('Invoice Date:', 'wc-manual-invoices'); ?></div>
            <div class="meta-value"><?php echo esc_html(wc_format_datetime($order->get_date_created())); ?></div>
        </div>
        
        <?php if ($due_date) : ?>
            <div class="invoice-meta-row">
                <div class="meta-label"><?php _e('Due Date:', 'wc-manual-invoices'); ?></div>
                <div class="meta-value">
                    <?php echo esc_html(date_i18n(get_option('date_format'), strtotime($due_date))); ?>
                    <?php if (strtotime($due_date) < current_time('timestamp') && $order->needs_payment()) : ?>
                        <span style="color: #dc3545; font-weight: bold; margin-left: 10px;">
                            (<?php _e('OVERDUE', 'wc-manual-invoices'); ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="invoice-meta-row">
            <div class="meta-label"><?php _e('Amount Due:', 'wc-manual-invoices'); ?></div>
            <div class="meta-value">
                <strong style="color: #96588a; font-size: 16px;">
                    <?php echo $order->get_formatted_order_total(); ?>
                </strong>
            </div>
        </div>
        
        <div class="invoice-meta-row">
            <div class="meta-label"><?php _e('Order ID:', 'wc-manual-invoices'); ?></div>
            <div class="meta-value"><?php echo esc_html($order->get_order_number()); ?></div>
        </div>
    </div>

    <!-- Billing Addresses -->
    <div class="addresses-container">
        <div class="address-block">
            <div class="address-title"><?php _e('From', 'wc-manual-invoices'); ?></div>
            <div class="address-content">
                <div class="company-detail">
                    <strong><?php echo esc_html($company_name); ?></strong>
                </div>
                
                <?php if (!empty($company_info['address'])) : ?>
                    <div class="company-detail">
                        <?php echo nl2br(esc_html($company_info['address'])); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($company_info['phone'])) : ?>
                    <div class="company-detail">
                        <strong><?php _e('Phone:', 'wc-manual-invoices'); ?></strong> 
                        <?php echo esc_html($company_info['phone']); ?>
                    </div>
                <?php endif; ?>
                
                <div class="company-detail">
                    <strong><?php _e('Email:', 'wc-manual-invoices'); ?></strong> 
                    <a href="mailto:<?php echo esc_attr($company_email); ?>" 
                       style="color: #96588a; text-decoration: none;">
                        <?php echo esc_html($company_email); ?>
                    </a>
                </div>
            </div>
        </div>

        <div class="address-block">
            <div class="address-title"><?php _e('To', 'wc-manual-invoices'); ?></div>
            <div class="address-content">
                <div class="company-detail">
                    <strong><?php echo esc_html($order->get_formatted_billing_full_name()); ?></strong>
                </div>
                
                <div class="company-detail">
                    <a href="mailto:<?php echo esc_attr($order->get_billing_email()); ?>" 
                       style="color: #96588a; text-decoration: none;">
                        <?php echo esc_html($order->get_billing_email()); ?>
                    </a>
                </div>
                
                <?php if ($order->get_billing_phone()) : ?>
                    <div class="company-detail">
                        <strong><?php _e('Phone:', 'wc-manual-invoices'); ?></strong> 
                        <?php echo esc_html($order->get_billing_phone()); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($order->get_formatted_billing_address()) : ?>
                    <div class="company-detail" style="margin-top: 10px;">
                        <?php echo $order->get_formatted_billing_address(); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Order Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;"><?php _e('Item', 'wc-manual-invoices'); ?></th>
                <th class="text-center" style="width: 15%;"><?php _e('Qty', 'wc-manual-invoices'); ?></th>
                <th class="text-right" style="width: 15%;"><?php _e('Price', 'wc-manual-invoices'); ?></th>
                <th class="text-right" style="width: 20%;"><?php _e('Total', 'wc-manual-invoices'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->get_items() as $item_id => $item) : 
                $product = $item->get_product();
                $item_total = $item->get_total();
                $item_quantity = $item->get_quantity();
                $item_price = $item_quantity > 0 ? $item_total / $item_quantity : 0;
            ?>
                <tr>
                    <td>
                        <div class="item-name"><?php echo esc_html($item->get_name()); ?></div>
                        
                        <?php if ($product && $product->get_sku()) : ?>
                            <div class="item-description">
                                <?php printf(__('SKU: %s', 'wc-manual-invoices'), esc_html($product->get_sku())); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($item->get_meta('_custom_item_description')) : ?>
                            <div class="item-description">
                                <?php echo esc_html($item->get_meta('_custom_item_description')); ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo esc_html($item_quantity); ?></td>
                    <td class="text-right"><?php echo wc_price($item_price); ?></td>
                    <td class="text-right"><?php echo wc_price($item_total); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Order Totals -->
    <table class="totals-table">
        <tr class="subtotal-row">
            <td style="width: 70%;"><?php _e('Subtotal:', 'wc-manual-invoices'); ?></td>
            <td class="text-right" style="width: 30%;"><?php echo wc_price($order->get_subtotal()); ?></td>
        </tr>

        <?php foreach ($order->get_fees() as $fee) : ?>
            <tr class="subtotal-row">
                <td><?php echo esc_html($fee->get_name()); ?>:</td>
                <td class="text-right"><?php echo wc_price($fee->get_total()); ?></td>
            </tr>
        <?php endforeach; ?>

        <?php if ($order->get_total_shipping() > 0) : ?>
            <tr class="subtotal-row">
                <td><?php _e('Shipping:', 'wc-manual-invoices'); ?></td>
                <td class="text-right"><?php echo wc_price($order->get_total_shipping()); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($order->get_total_tax() > 0) : ?>
            <tr class="subtotal-row">
                <td><?php _e('Tax:', 'wc-manual-invoices'); ?></td>
                <td class="text-right"><?php echo wc_price($order->get_total_tax()); ?></td>
            </tr>
        <?php endif; ?>

        <tr class="total-row">
            <td><strong><?php _e('Total:', 'wc-manual-invoices'); ?></strong></td>
            <td class="text-right"><strong><?php echo $order->get_formatted_order_total(); ?></strong></td>
        </tr>
    </table>

    <!-- Payment Section -->
    <?php if ($order->needs_payment()) : ?>
        <div class="payment-section">
            <h3><?php _e('Payment Required', 'wc-manual-invoices'); ?></h3>
            <p><?php _e('Click the button below to securely pay your invoice online.', 'wc-manual-invoices'); ?></p>
            
            <a href="<?php echo esc_url($pay_link); ?>" class="pay-button">
                <?php printf(__('Pay %s Now', 'wc-manual-invoices'), $order->get_formatted_order_total()); ?>
            </a>
            
            <div style="margin-top: 20px; font-size: 14px; opacity: 0.8;">
                <?php _e('Secure payment powered by WooCommerce', 'wc-manual-invoices'); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Notes and Terms sections remain the same -->
    <?php if ($notes) : ?>
        <div style="margin: 25px 0; padding: 20px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #96588a;">
            <h4 style="margin-top: 0; margin-bottom: 12px; color: #96588a; font-size: 16px; font-weight: bold;">
                <?php _e('Notes', 'wc-manual-invoices'); ?>
            </h4>
            <p style="margin-bottom: 0; color: #555; line-height: 1.6; font-size: 14px;">
                <?php echo nl2br(esc_html($notes)); ?>
            </p>
        </div>
    <?php endif; ?>

    <?php if ($terms) : ?>
        <div style="margin: 25px 0; padding: 20px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #96588a;">
            <h4 style="margin-top: 0; margin-bottom: 12px; color: #96588a; font-size: 16px; font-weight: bold;">
                <?php _e('Terms & Conditions', 'wc-manual-invoices'); ?>
            </h4>
            <p style="margin-bottom: 0; color: #555; line-height: 1.6; font-size: 14px;">
                <?php echo nl2br(esc_html($terms)); ?>
            </p>
        </div>
    <?php endif; ?>

    <!-- Enhanced Footer -->
    <div class="footer-section">
        <div class="footer-company-info">
            <strong><?php echo esc_html($company_name); ?></strong>
            <?php if (!empty($company_info['address']) || !empty($company_info['phone'])) : ?>
                <br>
                <?php 
                $footer_parts = array();
                if (!empty($company_info['phone'])) {
                    $footer_parts[] = $company_info['phone'];
                }
                if (!empty($company_email)) {
                    $footer_parts[] = $company_email;
                }
                echo esc_html(implode(' • ', $footer_parts));
                ?>
            <?php endif; ?>
        </div>
        
        <div class="footer-note">
            <?php 
            if (!empty($company_info['footer'])) {
                echo nl2br(esc_html($company_info['footer']));
            } else {
                printf(
                    __('Invoice generated on %s • Powered by WooCommerce Manual Invoices Pro', 'wc-manual-invoices'),
                    esc_html(current_time(get_option('date_format')))
                );
            }
            ?>
        </div>
    </div>
</div>

<?php
/**
 * Show user-defined additional content
 */
if ($additional_content = $email->get_additional_content()) {
    echo '<div style="margin: 20px 0; padding: 15px; background-color: #f9f9f9; border-radius: 5px;">';
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
    echo '</div>';
}

/**
 * Executes the footer of the email
 */
do_action('woocommerce_email_footer', $email);