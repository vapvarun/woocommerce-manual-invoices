<?php
/**
 * Manual Invoice Email Template (HTML)
 * 
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/manual-invoice.php
 * 
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce_Manual_Invoices/Templates/Emails
 * @version 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Executes the header of the email
 *
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action('woocommerce_email_header', $email_heading, $email);

// Get company info and invoice details
$company_info = WC_Manual_Invoices_Settings::get_company_info();
$pay_link = $order->get_checkout_payment_url();
$due_date = $order->get_meta('_manual_invoice_due_date');
$notes = $order->get_meta('_manual_invoice_notes');
$terms = $order->get_meta('_manual_invoice_terms');
$invoice_number = WC_Manual_Invoices_Settings::get_invoice_number($order->get_id());

// Email styles
$email_styles = '
<style type="text/css">
    .invoice-container {
        max-width: 600px;
        margin: 0 auto;
        font-family: Arial, sans-serif;
        line-height: 1.6;
        color: #333;
    }
    .company-header {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 2px solid #96588a;
    }
    .company-logo {
        margin-bottom: 15px;
    }
    .company-logo img {
        max-width: 200px;
        height: auto;
        display: block;
        margin: 0 auto;
    }
    .company-name {
        font-size: 24px;
        font-weight: bold;
        color: #96588a;
        margin: 10px 0;
    }
    .invoice-title {
        background-color: #96588a;
        color: white;
        padding: 15px;
        text-align: center;
        margin-bottom: 30px;
        border-radius: 5px;
    }
    .invoice-title h2 {
        margin: 0;
        font-size: 22px;
        font-weight: normal;
    }
    .invoice-meta {
        background-color: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 25px;
    }
    .invoice-meta-row {
        display: table;
        width: 100%;
        margin-bottom: 8px;
    }
    .invoice-meta-row:last-child {
        margin-bottom: 0;
    }
    .meta-label {
        display: table-cell;
        font-weight: bold;
        width: 30%;
        color: #555;
    }
    .meta-value {
        display: table-cell;
        color: #333;
    }
    .addresses-container {
        display: table;
        width: 100%;
        margin-bottom: 30px;
    }
    .address-block {
        display: table-cell;
        width: 50%;
        vertical-align: top;
        padding-right: 20px;
    }
    .address-block:last-child {
        padding-right: 0;
        padding-left: 20px;
    }
    .address-title {
        font-size: 16px;
        font-weight: bold;
        color: #96588a;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #ddd;
    }
    .address-content {
        font-size: 14px;
        line-height: 1.5;
    }
    .address-content strong {
        color: #333;
        font-size: 15px;
    }
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 25px;
        background-color: white;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        border-radius: 5px;
        overflow: hidden;
    }
    .items-table th {
        background-color: #96588a;
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
        border-bottom: 1px solid #eee;
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
    .items-table .text-center {
        text-align: center;
    }
    .items-table .text-right {
        text-align: right;
    }
    .totals-table {
        width: 300px;
        margin-left: auto;
        margin-bottom: 30px;
        background-color: white;
        border-radius: 5px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    .totals-table td {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        font-size: 14px;
    }
    .totals-table tr:last-child td {
        border-bottom: none;
    }
    .totals-table .subtotal-row {
        color: #555;
    }
    .totals-table .total-row {
        background-color: #96588a;
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
    .pay-button:hover {
        background-color: transparent;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0,0,0,0.15);
    }
    .payment-note {
        margin-top: 20px;
        font-size: 14px;
        opacity: 0.8;
    }
    .payment-details {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 5px;
        margin: 20px 0;
        border-left: 4px solid #96588a;
    }
    .payment-details h4 {
        margin-top: 0;
        color: #96588a;
        font-size: 16px;
    }
    .payment-link {
        font-family: monospace;
        font-size: 12px;
        word-break: break-all;
        background-color: white;
        padding: 10px;
        border-radius: 3px;
        border: 1px solid #ddd;
        color: #333;
    }
    .info-section {
        margin: 25px 0;
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 5px;
        border-left: 4px solid #96588a;
    }
    .info-section h4 {
        margin-top: 0;
        margin-bottom: 12px;
        color: #96588a;
        font-size: 16px;
        font-weight: bold;
    }
    .info-section p {
        margin-bottom: 0;
        color: #555;
        line-height: 1.6;
        font-size: 14px;
    }
    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .status-pending {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeaa7;
    }
    .status-processing {
        background-color: #d1ecf1;
        color: #0c5460;
        border: 1px solid #b8daff;
    }
    .status-completed {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .footer-note {
        text-align: center;
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        color: #666;
        font-size: 12px;
    }
    .social-links {
        text-align: center;
        margin: 20px 0;
    }
    .social-links a {
        display: inline-block;
        margin: 0 10px;
        color: #96588a;
        text-decoration: none;
        font-size: 14px;
    }
    @media (max-width: 600px) {
        .invoice-container {
            padding: 10px;
        }
        .addresses-container,
        .address-block {
            display: block;
            width: 100%;
        }
        .address-block {
            margin-bottom: 25px;
            padding-right: 0;
            padding-left: 0;
        }
        .totals-table {
            width: 100%;
        }
        .items-table th,
        .items-table td {
            padding: 8px 6px;
            font-size: 12px;
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
                     alt="<?php echo esc_attr($company_info['name']); ?>" />
            </div>
        <?php endif; ?>
        
        <div class="company-name">
            <?php echo esc_html($company_info['name']); ?>
        </div>
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
            <div class="meta-label"><?php _e('Order Status:', 'wc-manual-invoices'); ?></div>
            <div class="meta-value">
                <span class="status-badge status-<?php echo esc_attr($order->get_status()); ?>">
                    <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                </span>
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
                <strong><?php echo esc_html($company_info['name']); ?></strong><br>
                
                <?php if ($company_info['address']) : ?>
                    <?php echo nl2br(esc_html($company_info['address'])); ?><br>
                <?php endif; ?>
                
                <?php if ($company_info['phone']) : ?>
                    <strong><?php _e('Phone:', 'wc-manual-invoices'); ?></strong> 
                    <?php echo esc_html($company_info['phone']); ?><br>
                <?php endif; ?>
                
                <?php if ($company_info['email']) : ?>
                    <strong><?php _e('Email:', 'wc-manual-invoices'); ?></strong> 
                    <a href="mailto:<?php echo esc_attr($company_info['email']); ?>" 
                       style="color: #96588a; text-decoration: none;">
                        <?php echo esc_html($company_info['email']); ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="address-block">
            <div class="address-title"><?php _e('To', 'wc-manual-invoices'); ?></div>
            <div class="address-content">
                <strong><?php echo esc_html($order->get_formatted_billing_full_name()); ?></strong><br>
                
                <a href="mailto:<?php echo esc_attr($order->get_billing_email()); ?>" 
                   style="color: #96588a; text-decoration: none;">
                    <?php echo esc_html($order->get_billing_email()); ?>
                </a><br>
                
                <?php if ($order->get_billing_phone()) : ?>
                    <strong><?php _e('Phone:', 'wc-manual-invoices'); ?></strong> 
                    <?php echo esc_html($order->get_billing_phone()); ?><br>
                <?php endif; ?>
                
                <?php if ($order->get_formatted_billing_address()) : ?>
                    <br><?php echo $order->get_formatted_billing_address(); ?>
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
                        
                        <?php 
                        // Display product variation details
                        if ($product && $product->is_type('variation')) {
                            $attributes = $product->get_variation_attributes();
                            if (!empty($attributes)) {
                                echo '<div class="item-description">';
                                foreach ($attributes as $name => $value) {
                                    $taxonomy = str_replace('attribute_', '', $name);
                                    $term = get_term_by('slug', $value, $taxonomy);
                                    $attribute_name = wc_attribute_label($taxonomy);
                                    $attribute_value = $term ? $term->name : $value;
                                    echo esc_html($attribute_name) . ': ' . esc_html($attribute_value) . '<br>';
                                }
                                echo '</div>';
                            }
                        }
                        ?>
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
                <td>
                    <?php _e('Shipping:', 'wc-manual-invoices'); ?>
                    <?php 
                    // Display shipping method if available
                    $shipping_methods = $order->get_shipping_methods();
                    if (!empty($shipping_methods)) {
                        $shipping_method = reset($shipping_methods);
                        echo '<br><small style="font-style: italic;">(' . esc_html($shipping_method->get_method_title()) . ')</small>';
                    }
                    ?>
                </td>
                <td class="text-right"><?php echo wc_price($order->get_total_shipping()); ?></td>
            </tr>
        <?php endif; ?>

        <?php if ($order->get_total_tax() > 0) : ?>
            <tr class="subtotal-row">
                <td>
                    <?php _e('Tax:', 'wc-manual-invoices'); ?>
                    <?php
                    // Display tax details if available
                    $tax_totals = $order->get_tax_totals();
                    if (!empty($tax_totals)) {
                        echo '<br>';
                        foreach ($tax_totals as $tax_total) {
                            echo '<small style="font-style: italic;">(' . esc_html($tax_total->label) . ')</small><br>';
                        }
                    }
                    ?>
                </td>
                <td class="text-right"><?php echo wc_price($order->get_total_tax()); ?></td>
            </tr>
        <?php endif; ?>

        <?php
        // Display discounts if any
        if ($order->get_total_discount() > 0) :
        ?>
            <tr class="subtotal-row">
                <td>
                    <?php _e('Discount:', 'wc-manual-invoices'); ?>
                    <?php
                    // Show coupon codes if used
                    $coupons = $order->get_coupon_codes();
                    if (!empty($coupons)) {
                        echo '<br><small style="font-style: italic;">(' . implode(', ', $coupons) . ')</small>';
                    }
                    ?>
                </td>
                <td class="text-right">-<?php echo wc_price($order->get_total_discount()); ?></td>
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
            
            <div class="payment-note">
                <?php _e('You will be redirected to our secure payment page.', 'wc-manual-invoices'); ?>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="payment-details">
            <h4><?php _e('Payment Link', 'wc-manual-invoices'); ?></h4>
            <p><?php _e('If the button above doesn\'t work, copy and paste this link into your browser:', 'wc-manual-invoices'); ?></p>
            <div class="payment-link"><?php echo esc_url($pay_link); ?></div>
        </div>
    <?php else : ?>
        <div class="payment-section" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <h3><?php _e('Payment Received', 'wc-manual-invoices'); ?></h3>
            <p><?php _e('Thank you! This invoice has been marked as paid.', 'wc-manual-invoices'); ?></p>
            <?php if ($order->get_date_paid()) : ?>
                <div class="payment-note">
                    <?php printf(
                        __('Paid on: %s', 'wc-manual-invoices'), 
                        esc_html(wc_format_datetime($order->get_date_paid()))
                    ); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Invoice Notes -->
    <?php if ($notes) : ?>
        <div class="info-section">
            <h4><?php _e('Notes', 'wc-manual-invoices'); ?></h4>
            <p><?php echo nl2br(esc_html($notes)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Terms and Conditions -->
    <?php if ($terms) : ?>
        <div class="info-section">
            <h4><?php _e('Terms & Conditions', 'wc-manual-invoices'); ?></h4>
            <p><?php echo nl2br(esc_html($terms)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Additional Content -->
    <?php if ($additional_content = $email->get_additional_content()) : ?>
        <div class="info-section">
            <p><?php echo nl2br(esc_html($additional_content)); ?></p>
        </div>
    <?php endif; ?>

    <!-- Social Links (if company has social media) -->
    <?php 
    $company_website = get_option('woocommerce_store_url', home_url());
    $company_social = apply_filters('wc_manual_invoice_social_links', array());
    
    if (!empty($company_social) || $company_website) :
    ?>
        <div class="social-links">
            <?php if ($company_website) : ?>
                <a href="<?php echo esc_url($company_website); ?>" target="_blank">
                    <?php _e('Visit our website', 'wc-manual-invoices'); ?>
                </a>
            <?php endif; ?>
            
            <?php foreach ($company_social as $platform => $url) : ?>
                <a href="<?php echo esc_url($url); ?>" target="_blank">
                    <?php echo esc_html(ucfirst($platform)); ?>
                </a>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Footer Note -->
    <div class="footer-note">
        <?php 
        $footer_text = WC_Manual_Invoices_Settings::get_setting('invoice_footer');
        if ($footer_text) {
            echo nl2br(esc_html($footer_text));
        } else {
            printf(
                __('This invoice was generated by %s on %s', 'wc-manual-invoices'),
                esc_html($company_info['name']),
                esc_html(current_time(get_option('date_format') . ' ' . get_option('time_format')))
            );
        }
        ?>
    </div>
</div>

<?php
/**
 * Show user-defined additional content - this is set in each email's settings.
 */
if ($additional_content) {
    echo wp_kses_post(wpautop(wptexturize($additional_content)));
}

/**
 * Executes the footer of the email
 *
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action('woocommerce_email_footer', $email);