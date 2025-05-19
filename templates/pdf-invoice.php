<?php
/**
 * PDF Invoice Template
 * 
 * Template for generating PDF invoices
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get company info
$company_info = WC_Manual_Invoices_Settings::get_company_info();
$due_date = $order->get_meta('_manual_invoice_due_date');
$notes = $order->get_meta('_manual_invoice_notes');
$terms = $order->get_meta('_manual_invoice_terms');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title><?php printf(__('Invoice #%s', 'wc-manual-invoices'), $order->get_order_number()); ?></title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .company-logo {
            display: table-cell;
            vertical-align: top;
            width: 40%;
        }
        
        .company-logo img {
            max-width: 200px;
            height: auto;
        }
        
        .invoice-title {
            display: table-cell;
            vertical-align: top;
            text-align: right;
            width: 60%;
        }
        
        .invoice-title h1 {
            margin: 0;
            font-size: 28px;
            color: #96588a;
        }
        
        .invoice-meta {
            margin-top: 10px;
            font-size: 14px;
        }
        
        .addresses {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .company-address, .customer-address {
            display: table-cell;
            vertical-align: top;
            width: 50%;
            padding-right: 20px;
        }
        
        .address-title {
            font-weight: bold;
            margin-bottom: 10px;
            font-size: 14px;
            color: #96588a;
        }
        
        .address-content {
            font-size: 12px;
            line-height: 1.5;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .items-table th {
            background-color: #f7f7f7;
            padding: 12px;
            text-align: left;
            border-bottom: 2px solid #ddd;
            font-weight: bold;
            color: #333;
        }
        
        .items-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #eee;
        }
        
        .items-table .text-right {
            text-align: right;
        }
        
        .items-table .text-center {
            text-align: center;
        }
        
        .totals-table {
            width: 300px;
            margin-left: auto;
            margin-bottom: 30px;
        }
        
        .totals-table td {
            padding: 6px 12px;
            border: none;
        }
        
        .totals-table .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 16px;
        }
        
        .notes, .terms {
            margin: 30px 0;
            padding: 15px;
            background-color: #f9f9f9;
            border-left: 4px solid #96588a;
        }
        
        .notes-title, .terms-title {
            margin-top: 0;
            margin-bottom: 10px;
            font-weight: bold;
            color: #96588a;
        }
        
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
        
        .payment-info {
            background-color: #f0f0f0;
            border: 2px solid #96588a;
            padding: 20px;
            margin: 30px 0;
            text-align: center;
        }
        
        .payment-link {
            font-size: 14px;
            color: #96588a;
            text-decoration: none;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-logo">
            <?php if (!empty($company_info['logo'])) : ?>
                <img src="<?php echo esc_url($company_info['logo']); ?>" alt="<?php echo esc_attr($company_info['name']); ?>">
            <?php endif; ?>
        </div>
        <div class="invoice-title">
            <h1><?php _e('INVOICE', 'wc-manual-invoices'); ?></h1>
            <div class="invoice-meta">
                <strong>#<?php echo esc_html($order->get_order_number()); ?></strong><br>
                <?php printf(__('Date: %s', 'wc-manual-invoices'), wc_format_datetime($order->get_date_created())); ?><br>
                <?php if ($due_date) : ?>
                    <?php printf(__('Due Date: %s', 'wc-manual-invoices'), date_i18n(get_option('date_format'), strtotime($due_date))); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Addresses -->
    <div class="addresses">
        <div class="company-address">
            <div class="address-title"><?php _e('From:', 'wc-manual-invoices'); ?></div>
            <div class="address-content">
                <strong><?php echo esc_html($company_info['name']); ?></strong><br>
                <?php if ($company_info['address']) : ?>
                    <?php echo nl2br(esc_html($company_info['address'])); ?><br>
                <?php endif; ?>
                <?php if ($company_info['phone']) : ?>
                    <?php printf(__('Phone: %s', 'wc-manual-invoices'), esc_html($company_info['phone'])); ?><br>
                <?php endif; ?>
                <?php if ($company_info['email']) : ?>
                    <?php printf(__('Email: %s', 'wc-manual-invoices'), esc_html($company_info['email'])); ?>
                <?php endif; ?>
            </div>
        </div>
        <div class="customer-address">
            <div class="address-title"><?php _e('To:', 'wc-manual-invoices'); ?></div>
            <div class="address-content">
                <strong><?php echo esc_html($order->get_formatted_billing_full_name()); ?></strong><br>
                <?php echo esc_html($order->get_billing_email()); ?><br>
                <?php if ($order->get_billing_phone()) : ?>
                    <?php echo esc_html($order->get_billing_phone()); ?><br>
                <?php endif; ?>
                <?php if ($order->get_formatted_billing_address()) : ?>
                    <?php echo $order->get_formatted_billing_address(); ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Items Table -->
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 50%;"><?php _e('Description', 'wc-manual-invoices'); ?></th>
                <th style="width: 15%;" class="text-center"><?php _e('Qty', 'wc-manual-invoices'); ?></th>
                <th style="width: 15%;" class="text-right"><?php _e('Price', 'wc-manual-invoices'); ?></th>
                <th style="width: 20%;" class="text-right"><?php _e('Total', 'wc-manual-invoices'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($order->get_items() as $item_id => $item) : ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($item->get_name()); ?></strong>
                        <?php if ($item->get_meta('_custom_item_description')) : ?>
                            <br><small style="color: #666;"><?php echo esc_html($item->get_meta('_custom_item_description')); ?></small>
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?php echo esc_html($item->get_quantity()); ?></td>
                    <td class="text-right"><?php echo wc_price($item->get_total() / $item->get_quantity()); ?></td>
                    <td class="text-right"><?php echo wc_price($item->get_total()); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Totals -->
    <table class="totals-table">
        <tr>
            <td><?php _e('Subtotal:', 'wc-manual-invoices'); ?></td>
            <td class="text-right"><?php echo wc_price($order->get_subtotal()); ?></td>
        </tr>
        
        <?php foreach ($order->get_fees() as $fee) : ?>
            <tr>
                <td><?php echo esc_html($fee->get_name()); ?>:</td>
                <td class="text-right"><?php echo wc_price($fee->get_total()); ?></td>
            </tr>
        <?php endforeach; ?>
        
        <?php if ($order->get_total_shipping() > 0) : ?>
            <tr>
                <td><?php _e('Shipping:', 'wc-manual-invoices'); ?></td>
                <td class="text-right"><?php echo wc_price($order->get_total_shipping()); ?></td>
            </tr>
        <?php endif; ?>
        
        <?php if ($order->get_total_tax() > 0) : ?>
            <tr>
                <td><?php _e('Tax:', 'wc-manual-invoices'); ?></td>
                <td class="text-right"><?php echo wc_price($order->get_total_tax()); ?></td>
            </tr>
        <?php endif; ?>
        
        <tr class="total-row">
            <td><?php _e('Total:', 'wc-manual-invoices'); ?></td>
            <td class="text-right"><?php echo $order->get_formatted_order_total(); ?></td>
        </tr>
    </table>
    
    <!-- Payment Information -->
    <?php if ($order->needs_payment()) : ?>
        <div class="payment-info">
            <h3 style="margin-top: 0; color: #96588a;"><?php _e('Payment Instructions', 'wc-manual-invoices'); ?></h3>
            <p><?php _e('Please pay online using the following link:', 'wc-manual-invoices'); ?></p>
            <a href="<?php echo esc_url($order->get_checkout_payment_url()); ?>" class="payment-link">
                <?php echo esc_url($order->get_checkout_payment_url()); ?>
            </a>
        </div>
    <?php endif; ?>
    
    <!-- Notes -->
    <?php if ($notes) : ?>
        <div class="notes">
            <h4 class="notes-title"><?php _e('Notes:', 'wc-manual-invoices'); ?></h4>
            <p><?php echo nl2br(esc_html($notes)); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Terms -->
    <?php if ($terms) : ?>
        <div class="terms">
            <h4 class="terms-title"><?php _e('Terms & Conditions:', 'wc-manual-invoices'); ?></h4>
            <p><?php echo nl2br(esc_html($terms)); ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Footer -->
    <div class="footer">
        <?php
        $footer_text = WC_Manual_Invoices_Settings::get_setting('invoice_footer');
        if ($footer_text) {
            echo nl2br(esc_html($footer_text));
        } else {
            printf(__('Invoice generated by %s', 'wc-manual-invoices'), esc_html($company_info['name']));
        }
        ?>
    </div>
</body>
</html>