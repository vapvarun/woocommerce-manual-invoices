<?php
/**
 * Admin Dashboard Template
 * 
 * Template for the main invoice management dashboard
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current tab
$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'invoices';

// Get statistics
$stats = WC_Manual_Invoices_Dashboard::get_invoice_statistics();

// Get filter parameters
$status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : 'any';
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
$paged = isset($_GET['paged']) ? intval($_GET['paged']) : 1;
$offset = ($paged - 1) * $limit;

// Get invoices
$invoices = WC_Manual_Invoices_Dashboard::get_manual_invoices(array(
    'status' => $status_filter,
    'limit' => $limit,
    'offset' => $offset,
));

// Tab URLs
$tab_urls = array(
    'invoices' => admin_url('admin.php?page=wc-manual-invoices&tab=invoices'),
    'create' => admin_url('admin.php?page=wc-manual-invoices&tab=create'),
    'reports' => admin_url('admin.php?page=wc-manual-invoices&tab=reports'),
);
?>

<div class="wrap">
    <h1><?php _e('Manual Invoices', 'wc-manual-invoices'); ?></h1>
    
    <!-- Statistics -->
    <div class="wc-manual-invoices-stats">
        <ul class="wc-tabs">
            <li>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
                    <span class="stat-label"><?php _e('Total Invoices', 'wc-manual-invoices'); ?></span>
                </div>
            </li>
            <li>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['pending']); ?></span>
                    <span class="stat-label"><?php _e('Pending', 'wc-manual-invoices'); ?></span>
                </div>
            </li>
            <li>
                <div class="stat-box">
                    <span class="stat-number"><?php echo esc_html($stats['paid']); ?></span>
                    <span class="stat-label"><?php _e('Paid', 'wc-manual-invoices'); ?></span>
                </div>
            </li>
            <li>
                <div class="stat-box">
                    <span class="stat-number"><?php echo wc_price($stats['total_amount']); ?></span>
                    <span class="stat-label"><?php _e('Total Value', 'wc-manual-invoices'); ?></span>
                </div>
            </li>
        </ul>
    </div>
    
    <!-- Tabs -->
    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo esc_url($tab_urls['invoices']); ?>" 
           class="nav-tab <?php echo $current_tab === 'invoices' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Invoices', 'wc-manual-invoices'); ?>
        </a>
        <a href="<?php echo esc_url($tab_urls['create']); ?>" 
           class="nav-tab <?php echo $current_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Create Invoice', 'wc-manual-invoices'); ?>
        </a>
        <a href="<?php echo esc_url($tab_urls['reports']); ?>" 
           class="nav-tab <?php echo $current_tab === 'reports' ? 'nav-tab-active' : ''; ?>">
            <?php _e('Reports', 'wc-manual-invoices'); ?>
        </a>
    </nav>
    
    <!-- Tab Content -->
    <div class="tab-content">
        <?php if ($current_tab === 'invoices') : ?>
            
            <!-- Filters -->
            <div class="tablenav top">
                <form method="get">
                    <input type="hidden" name="page" value="wc-manual-invoices">
                    <input type="hidden" name="tab" value="invoices">
                    
                    <select name="status">
                        <option value="any" <?php selected($status_filter, 'any'); ?>><?php _e('All Statuses', 'wc-manual-invoices'); ?></option>
                        <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending', 'wc-manual-invoices'); ?></option>
                        <option value="manual-invoice" <?php selected($status_filter, 'manual-invoice'); ?>><?php _e('Manual Invoice', 'wc-manual-invoices'); ?></option>
                        <option value="processing" <?php selected($status_filter, 'processing'); ?>><?php _e('Processing', 'wc-manual-invoices'); ?></option>
                        <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'wc-manual-invoices'); ?></option>
                    </select>
                    
                    <?php submit_button(__('Filter', 'wc-manual-invoices'), 'button', 'filter', false); ?>
                </form>
            </div>
            
            <!-- Invoices Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Invoice #', 'wc-manual-invoices'); ?></th>
                        <th><?php _e('Customer', 'wc-manual-invoices'); ?></th>
                        <th><?php _e('Date', 'wc-manual-invoices'); ?></th>
                        <th><?php _e('Status', 'wc-manual-invoices'); ?></th>
                        <th><?php _e('Total', 'wc-manual-invoices'); ?></th>
                        <th><?php _e('Actions', 'wc-manual-invoices'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($invoices) : ?>
                        <?php foreach ($invoices as $order) : ?>
                            <tr>
                                <td>
                                    <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                                        #<?php echo esc_html($order->get_order_number()); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php echo esc_html($order->get_formatted_billing_full_name()); ?>
                                    <br>
                                    <small><?php echo esc_html($order->get_billing_email()); ?></small>
                                </td>
                                <td><?php echo esc_html($order->get_date_created()->format('Y-m-d H:i')); ?></td>
                                <td>
                                    <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                        <?php echo esc_html(ucfirst($order->get_status())); ?>
                                    </span>
                                </td>
                                <td><?php echo $order->get_formatted_order_total(); ?></td>
                                <td>
                                    <div class="row-actions">
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>">
                                            <?php _e('View', 'wc-manual-invoices'); ?>
                                        </a>
                                        
                                        <?php if ($order->needs_payment()) : ?>
                                            | <a href="#" class="send-invoice-email" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                                <?php _e('Send Email', 'wc-manual-invoices'); ?>
                                            </a>
                                            
                                            | <a href="#" class="clone-invoice" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                                <?php _e('Clone', 'wc-manual-invoices'); ?>
                                            </a>
                                        <?php endif; ?>
                                        
                                        | <a href="#" class="generate-pdf" data-order-id="<?php echo esc_attr($order->get_id()); ?>">
                                            <?php _e('PDF', 'wc-manual-invoices'); ?>
                                        </a>
                                        
                                        <?php if ($order->get_status() === 'pending' || $order->get_status() === 'manual-invoice') : ?>
                                            | <a href="#" class="delete-invoice" data-order-id="<?php echo esc_attr($order->get_id()); ?>" 
                                                 onclick="return confirm('<?php _e('Are you sure you want to delete this invoice?', 'wc-manual-invoices'); ?>')">
                                                <?php _e('Delete', 'wc-manual-invoices'); ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="6"><?php _e('No invoices found.', 'wc-manual-invoices'); ?></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
        <?php elseif ($current_tab === 'create') : ?>
            
            <!-- Create Invoice Form -->
            <form method="post" id="wc-manual-invoice-form">
                <?php wp_nonce_field('wc_manual_invoices_nonce'); ?>
                <input type="hidden" name="action" value="create_invoice">
                
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Customer Information', 'wc-manual-invoices'); ?></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Customer', 'wc-manual-invoices'); ?></th>
                                <td>
                                    <select name="customer_id" id="customer_select" style="width: 100%; max-width: 400px;">
                                        <option value=""><?php _e('Select existing customer...', 'wc-manual-invoices'); ?></option>
                                    </select>
                                    <p class="description"><?php _e('Start typing to search for customers', 'wc-manual-invoices'); ?></p>
                                </td>
                            </tr>
                            <tr class="customer-details" style="display: none;">
                                <th><?php _e('Or create new customer', 'wc-manual-invoices'); ?></th>
                                <td>
                                    <p>
                                        <label><?php _e('Email', 'wc-manual-invoices'); ?></label>
                                        <input type="email" name="customer_email" id="customer_email" style="width: 100%; max-width: 400px;">
                                    </p>
                                    <p>
                                        <label><?php _e('First Name', 'wc-manual-invoices'); ?></label>
                                        <input type="text" name="billing_first_name" id="billing_first_name" style="width: 100%; max-width: 400px;">
                                    </p>
                                    <p>
                                        <label><?php _e('Last Name', 'wc-manual-invoices'); ?></label>
                                        <input type="text" name="billing_last_name" id="billing_last_name" style="width: 100%; max-width: 400px;">
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Invoice Items', 'wc-manual-invoices'); ?></h3>
                    <div class="inside">
                        <!-- Products -->
                        <h4><?php _e('Products', 'wc-manual-invoices'); ?></h4>
                        <div id="invoice-products">
                            <div class="invoice-product-row">
                                <select name="product_ids[]" class="product-select" style="width: 40%;">
                                    <option value=""><?php _e('Select product...', 'wc-manual-invoices'); ?></option>
                                </select>
                                <input type="number" name="product_quantities[]" placeholder="<?php _e('Qty', 'wc-manual-invoices'); ?>" 
                                       style="width: 15%;" min="1" step="1" value="1">
                                <input type="number" name="product_totals[]" placeholder="<?php _e('Total', 'wc-manual-invoices'); ?>" 
                                       style="width: 20%;" min="0" step="0.01">
                                <button type="button" class="button remove-product-row"><?php _e('Remove', 'wc-manual-invoices'); ?></button>
                            </div>
                        </div>
                        <button type="button" id="add-product-row" class="button"><?php _e('Add Product', 'wc-manual-invoices'); ?></button>
                        
                        <!-- Custom Items -->
                        <h4><?php _e('Custom Items', 'wc-manual-invoices'); ?></h4>
                        <div id="invoice-custom-items">
                            <div class="invoice-custom-item-row">
                                <input type="text" name="custom_item_names[]" placeholder="<?php _e('Item Name', 'wc-manual-invoices'); ?>" 
                                       style="width: 25%;">
                                <input type="text" name="custom_item_descriptions[]" placeholder="<?php _e('Description', 'wc-manual-invoices'); ?>" 
                                       style="width: 25%;">
                                <input type="number" name="custom_item_quantities[]" placeholder="<?php _e('Qty', 'wc-manual-invoices'); ?>" 
                                       style="width: 15%;" min="1" step="1" value="1">
                                <input type="number" name="custom_item_totals[]" placeholder="<?php _e('Total', 'wc-manual-invoices'); ?>" 
                                       style="width: 20%;" min="0" step="0.01">
                                <button type="button" class="button remove-custom-item-row"><?php _e('Remove', 'wc-manual-invoices'); ?></button>
                            </div>
                        </div>
                        <button type="button" id="add-custom-item-row" class="button"><?php _e('Add Custom Item', 'wc-manual-invoices'); ?></button>
                    </div>
                </div>
                
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Additional Charges', 'wc-manual-invoices'); ?></h3>
                    <div class="inside">
                        <!-- Fees -->
                        <h4><?php _e('Fees', 'wc-manual-invoices'); ?></h4>
                        <div id="invoice-fees">
                            <div class="invoice-fee-row">
                                <input type="text" name="fee_names[]" placeholder="<?php _e('Fee Name', 'wc-manual-invoices'); ?>" 
                                       style="width: 40%;">
                                <input type="number" name="fee_amounts[]" placeholder="<?php _e('Amount', 'wc-manual-invoices'); ?>" 
                                       style="width: 30%;" step="0.01">
                                <button type="button" class="button remove-fee-row"><?php _e('Remove', 'wc-manual-invoices'); ?></button>
                            </div>
                        </div>
                        <button type="button" id="add-fee-row" class="button"><?php _e('Add Fee', 'wc-manual-invoices'); ?></button>
                        
                        <!-- Shipping -->
                        <h4><?php _e('Shipping', 'wc-manual-invoices'); ?></h4>
                        <p>
                            <input type="text" name="shipping_method" placeholder="<?php _e('Shipping Method', 'wc-manual-invoices'); ?>" 
                                   style="width: 40%;" value="<?php _e('Shipping', 'wc-manual-invoices'); ?>">
                            <input type="number" name="shipping_total" placeholder="<?php _e('Shipping Total', 'wc-manual-invoices'); ?>" 
                                   style="width: 30%;" step="0.01">
                        </p>
                        
                        <!-- Tax -->
                        <h4><?php _e('Tax', 'wc-manual-invoices'); ?></h4>
                        <p>
                            <input type="text" name="tax_name" placeholder="<?php _e('Tax Name', 'wc-manual-invoices'); ?>" 
                                   style="width: 40%;" value="<?php _e('Tax', 'wc-manual-invoices'); ?>">
                            <input type="number" name="tax_total" placeholder="<?php _e('Tax Total', 'wc-manual-invoices'); ?>" 
                                   style="width: 30%;" step="0.01">
                        </p>
                    </div>
                </div>
                
                <div class="postbox">
                    <h3 class="hndle"><?php _e('Invoice Details', 'wc-manual-invoices'); ?></h3>
                    <div class="inside">
                        <table class="form-table">
                            <tr>
                                <th><?php _e('Due Date', 'wc-manual-invoices'); ?></th>
                                <td>
                                    <input type="date" name="due_date" id="due_date" 
                                           value="<?php echo esc_attr(date('Y-m-d', strtotime('+30 days'))); ?>">
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Notes', 'wc-manual-invoices'); ?></th>
                                <td>
                                    <textarea name="notes" rows="4" style="width: 100%; max-width: 600px;"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Terms & Conditions', 'wc-manual-invoices'); ?></th>
                                <td>
                                    <textarea name="terms" rows="4" style="width: 100%; max-width: 600px;"></textarea>
                                </td>
                            </tr>
                            <tr>
                                <th><?php _e('Send Email', 'wc-manual-invoices'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox" name="send_email" value="1" checked>
                                        <?php _e('Send invoice email to customer immediately', 'wc-manual-invoices'); ?>
                                    </label>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <?php submit_button(__('Create Invoice', 'wc-manual-invoices')); ?>
            </form>
            
        <?php elseif ($current_tab === 'reports') : ?>
            
            <!-- Reports Content -->
            <div class="postbox">
                <h3 class="hndle"><?php _e('Invoice Reports', 'wc-manual-invoices'); ?></h3>
                <div class="inside">
                    <p><?php _e('Reports feature coming soon...', 'wc-manual-invoices'); ?></p>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<!-- Loading overlay -->
<div id="wc-manual-invoices-loading" style="display: none;">
    <div class="loading-overlay">
        <div class="loading-spinner"></div>
    </div>
</div>