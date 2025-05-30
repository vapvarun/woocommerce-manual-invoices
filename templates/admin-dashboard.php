<?php
/**
 * Modern Admin Dashboard Template with Enhanced UI
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

// Calculate additional stats
$overdue_count = 0;
$this_month_total = 0;
$conversion_rate = $stats['total'] > 0 ? round(($stats['paid'] / $stats['total']) * 100, 1) : 0;

// Get overdue invoices for current month
foreach ($invoices as $order) {
    $due_date = $order->get_meta('_manual_invoice_due_date');
    if ($due_date && strtotime($due_date) < current_time('timestamp') && $order->needs_payment()) {
        $overdue_count++;
    }
    if ($order->get_date_created()->format('Y-m') === current_time('Y-m')) {
        $this_month_total += $order->get_total();
    }
}
?>

<div class="wc-manual-invoices-wrap">
    <!-- Header Section -->
    <div class="wc-manual-invoices-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-title">
                    <span class="dashicons dashicons-media-text" style="margin-right: 10px;"></span>
                    <?php _e('Invoice Management', 'wc-manual-invoices'); ?>
                </h1>
                <p class="header-subtitle">
                    <?php _e('Create, manage, and track your manual invoices with ease', 'wc-manual-invoices'); ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="<?php echo esc_url($tab_urls['create']); ?>" class="btn-header">
                    <span class="dashicons dashicons-plus-alt" style="margin-right: 6px;"></span>
                    <?php _e('New Invoice', 'wc-manual-invoices'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-manual-invoices-settings')); ?>" class="btn-header">
                    <span class="dashicons dashicons-admin-generic" style="margin-right: 6px;"></span>
                    <?php _e('Settings', 'wc-manual-invoices'); ?>
                </a>
                <!-- ADD THIS - PDF Settings link -->
                <a href="<?php echo esc_url(admin_url('admin.php?page=wc-manual-invoices-pdf-settings')); ?>" class="btn-header">
                    <span class="dashicons dashicons-pdf" style="margin-right: 6px;"></span>
                    <?php _e('PDF Settings', 'wc-manual-invoices'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Statistics Cards -->
    <div class="wc-manual-invoices-stats">
        <div class="stat-card">
            <div class="stat-icon total">
                <span class="dashicons dashicons-portfolio" style="color: white;"></span>
            </div>
            <span class="stat-number"><?php echo esc_html($stats['total']); ?></span>
            <span class="stat-label"><?php _e('Total Invoices', 'wc-manual-invoices'); ?></span>
            <div class="stat-trend">
                <span class="dashicons dashicons-arrow-up-alt"></span>
                <span><?php echo date('M Y'); ?></span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon pending">
                <span class="dashicons dashicons-clock" style="color: white;"></span>
            </div>
            <span class="stat-number"><?php echo esc_html($stats['pending']); ?></span>
            <span class="stat-label"><?php _e('Pending Payment', 'wc-manual-invoices'); ?></span>
            <?php if ($overdue_count > 0) : ?>
                <div class="stat-trend" style="color: #e74c3c;">
                    <span class="dashicons dashicons-warning"></span>
                    <span><?php printf(__('%d Overdue', 'wc-manual-invoices'), $overdue_count); ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon paid">
                <span class="dashicons dashicons-yes-alt" style="color: white;"></span>
            </div>
            <span class="stat-number"><?php echo esc_html($stats['paid']); ?></span>
            <span class="stat-label"><?php _e('Paid Invoices', 'wc-manual-invoices'); ?></span>
            <div class="stat-trend">
                <span class="dashicons dashicons-chart-line"></span>
                <span><?php echo $conversion_rate; ?>% <?php _e('Rate', 'wc-manual-invoices'); ?></span>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon value">
                <span class="dashicons dashicons-money-alt" style="color: white;"></span>
            </div>
            <span class="stat-number"><?php echo wc_price($stats['total_amount']); ?></span>
            <span class="stat-label"><?php _e('Total Value', 'wc-manual-invoices'); ?></span>
            <div class="stat-trend">
                <span class="dashicons dashicons-calendar-alt"></span>
                <span><?php _e('All Time', 'wc-manual-invoices'); ?></span>
            </div>
        </div>
    </div>
    
    <!-- Navigation Tabs -->
    <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        <a href="<?php echo esc_url($tab_urls['invoices']); ?>" 
        class="nav-tab <?php echo $current_tab === 'invoices' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-list-view"></span>
            <?php _e('All Invoices', 'wc-manual-invoices'); ?>
        </a>
        <a href="<?php echo esc_url($tab_urls['create']); ?>" 
        class="nav-tab <?php echo $current_tab === 'create' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-plus-alt"></span>
            <?php _e('Create Invoice', 'wc-manual-invoices'); ?>
        </a>
        <a href="<?php echo esc_url($tab_urls['reports']); ?>" 
        class="nav-tab <?php echo $current_tab === 'reports' ? 'nav-tab-active' : ''; ?>">
            <span class="dashicons dashicons-chart-bar"></span>
            <?php _e('Reports & Analytics', 'wc-manual-invoices'); ?>
        </a>
        <!-- ADD THIS - PDF Settings tab -->
        <a href="<?php echo esc_url(admin_url('admin.php?page=wc-manual-invoices-pdf-settings')); ?>" 
        class="nav-tab">
            <span class="dashicons dashicons-pdf"></span>
            <?php _e('PDF Settings', 'wc-manual-invoices'); ?>
        </a>
    </nav>
    
    <!-- Tab Content -->
    <div class="tab-content">
        <?php if ($current_tab === 'invoices') : ?>
            
            <!-- Enhanced Filters -->
            <div class="tablenav top">
                <div class="filter-section">
                    <form method="get" style="display: flex; gap: 12px; align-items: center;">
                        <input type="hidden" name="page" value="wc-manual-invoices">
                        <input type="hidden" name="tab" value="invoices">
                        
                        <label for="status-filter" style="font-weight: 600;"><?php _e('Status:', 'wc-manual-invoices'); ?></label>
                        <select name="status" id="status-filter" class="filter-select">
                            <option value="any" <?php selected($status_filter, 'any'); ?>><?php _e('All Statuses', 'wc-manual-invoices'); ?></option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>><?php _e('Pending Payment', 'wc-manual-invoices'); ?></option>
                            <option value="manual-invoice" <?php selected($status_filter, 'manual-invoice'); ?>><?php _e('Manual Invoice', 'wc-manual-invoices'); ?></option>
                            <option value="processing" <?php selected($status_filter, 'processing'); ?>><?php _e('Processing', 'wc-manual-invoices'); ?></option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>><?php _e('Completed', 'wc-manual-invoices'); ?></option>
                        </select>
                        
                        <?php submit_button(__('Filter', 'wc-manual-invoices'), 'secondary', 'filter', false, array('style' => 'margin-left: 8px;')); ?>
                    </form>
                </div>
                
                <div class="alignright" style="display: flex; gap: 10px; align-items: center;">
                    <span style="color: #666; font-size: 13px;">
                        <?php printf(__('Showing %d of %d invoices', 'wc-manual-invoices'), count($invoices), $stats['total']); ?>
                    </span>
                    <a href="<?php echo esc_url($tab_urls['create']); ?>" class="button button-primary">
                        <span class="dashicons dashicons-plus-alt" style="margin-right: 4px;"></span>
                        <?php _e('Add New', 'wc-manual-invoices'); ?>
                    </a>
                </div>
            </div>
            
            <!-- Enhanced Invoices Table -->
            <?php if ($invoices) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 120px;">
                                <span class="dashicons dashicons-id-alt" style="margin-right: 6px;"></span>
                                <?php _e('Invoice', 'wc-manual-invoices'); ?>
                            </th>
                            <th style="width: 200px;">
                                <span class="dashicons dashicons-businessman" style="margin-right: 6px;"></span>
                                <?php _e('Customer', 'wc-manual-invoices'); ?>
                            </th>
                            <th style="width: 140px;">
                                <span class="dashicons dashicons-calendar-alt" style="margin-right: 6px;"></span>
                                <?php _e('Date', 'wc-manual-invoices'); ?>
                            </th>
                            <th style="width: 120px;">
                                <span class="dashicons dashicons-info" style="margin-right: 6px;"></span>
                                <?php _e('Status', 'wc-manual-invoices'); ?>
                            </th>
                            <th style="width: 120px; text-align: right;">
                                <span class="dashicons dashicons-money-alt" style="margin-right: 6px;"></span>
                                <?php _e('Amount', 'wc-manual-invoices'); ?>
                            </th>
                            <th style="width: 160px;">
                                <span class="dashicons dashicons-admin-tools" style="margin-right: 6px;"></span>
                                <?php _e('Actions', 'wc-manual-invoices'); ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $order) : 
                            $due_date = $order->get_meta('_manual_invoice_due_date');
                            $is_overdue = $due_date && strtotime($due_date) < current_time('timestamp') && $order->needs_payment();
                        ?>
                            <tr <?php echo $is_overdue ? 'style="background: #fef7f7;"' : ''; ?>>
                                <td>
                                    <strong>
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>" 
                                           style="color: #96588a; text-decoration: none; font-weight: 600;">
                                            #<?php echo esc_html($order->get_order_number()); ?>
                                        </a>
                                    </strong>
                                    <?php if ($is_overdue) : ?>
                                        <br><small style="color: #e74c3c; font-weight: 600;">
                                            <span class="dashicons dashicons-warning" style="font-size: 12px;"></span>
                                            <?php _e('OVERDUE', 'wc-manual-invoices'); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <strong style="color: #2c3e50;">
                                            <?php echo esc_html($order->get_formatted_billing_full_name()); ?>
                                        </strong>
                                        <small style="color: #7f8c8d; margin-top: 2px;">
                                            <span class="dashicons dashicons-email" style="font-size: 12px; margin-right: 4px;"></span>
                                            <?php echo esc_html($order->get_billing_email()); ?>
                                        </small>
                                    </div>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column;">
                                        <span style="color: #2c3e50; font-weight: 500;">
                                            <?php echo esc_html($order->get_date_created()->format('M j, Y')); ?>
                                        </span>
                                        <small style="color: #7f8c8d; margin-top: 2px;">
                                            <?php echo esc_html($order->get_date_created()->format('g:i A')); ?>
                                        </small>
                                        <?php if ($due_date) : ?>
                                            <small style="color: #666; margin-top: 2px;">
                                                <span class="dashicons dashicons-clock" style="font-size: 12px; margin-right: 2px;"></span>
                                                Due: <?php echo esc_html(date('M j', strtotime($due_date))); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="order-status status-<?php echo esc_attr($order->get_status()); ?>">
                                        <?php echo esc_html(wc_get_order_status_name($order->get_status())); ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <span style="font-size: 16px; font-weight: 600; color: #2c3e50;">
                                        <?php echo $order->get_formatted_order_total(); ?>
                                    </span>
                                    <?php if ($order->needs_payment()) : ?>
                                        <br><small style="color: #e74c3c;">
                                            <?php _e('Payment Due', 'wc-manual-invoices'); ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="row-actions" style="display: flex; gap: 8px; opacity: 1;">
                                        <a href="<?php echo esc_url(admin_url('post.php?post=' . $order->get_id() . '&action=edit')); ?>"
                                           class="button button-small" title="<?php _e('View Details', 'wc-manual-invoices'); ?>">
                                            <span class="dashicons dashicons-visibility"></span>
                                        </a>
                                        
                                        <?php if ($order->needs_payment()) : ?>
                                            <button class="button button-small send-invoice-email" 
                                                    data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                                    title="<?php _e('Send Email', 'wc-manual-invoices'); ?>">
                                                <span class="dashicons dashicons-email-alt"></span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <button class="button button-small generate-pdf" 
                                                data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                                title="<?php _e('Download PDF', 'wc-manual-invoices'); ?>">
                                            <span class="dashicons dashicons-pdf"></span>
                                        </button>
                                        
                                        <?php if ($order->needs_payment()) : ?>
                                            <button class="button button-small clone-invoice" 
                                                    data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                                    title="<?php _e('Clone Invoice', 'wc-manual-invoices'); ?>">
                                                <span class="dashicons dashicons-admin-page"></span>
                                            </button>
                                        <?php endif; ?>
                                        
                                        <?php if ($order->get_status() === 'pending' || $order->get_status() === 'manual-invoice') : ?>
                                            <button class="button button-small delete-invoice" 
                                                    data-order-id="<?php echo esc_attr($order->get_id()); ?>"
                                                    title="<?php _e('Delete Invoice', 'wc-manual-invoices'); ?>"
                                                    style="background: #dc3545; color: white;">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <!-- Empty State -->
                <div class="wc-invoices-empty-state">
                    <div class="empty-state-icon">
                        <span class="dashicons dashicons-media-text"></span>
                    </div>
                    <h2 class="empty-state-title"><?php _e('No Invoices Found', 'wc-manual-invoices'); ?></h2>
                    <p class="empty-state-description">
                        <?php _e('You haven\'t created any invoices yet. Get started by creating your first invoice!', 'wc-manual-invoices'); ?>
                    </p>
                    <a href="<?php echo esc_url($tab_urls['create']); ?>" class="button button-primary button-large">
                        <span class="dashicons dashicons-plus-alt" style="margin-right: 6px;"></span>
                        <?php _e('Create Your First Invoice', 'wc-manual-invoices'); ?>
                    </a>
                </div>
            <?php endif; ?>
            
        <?php elseif ($current_tab === 'create') : ?>
            
            <!-- Create Invoice Form (keeping existing form but with enhanced styling) -->
            <div style="padding: 30px;">
                <div style="max-width: 900px; margin: 0 auto;">
                    <div style="text-align: center; margin-bottom: 40px;">
                        <h2 style="color: #2c3e50; margin-bottom: 12px;"><?php _e('Create New Invoice', 'wc-manual-invoices'); ?></h2>
                        <p style="color: #7f8c8d; font-size: 16px;">
                            <?php _e('Fill in the details below to create a professional invoice for your customer', 'wc-manual-invoices'); ?>
                        </p>
                    </div>
                    
                    <!-- Create Invoice Form -->
                    <form method="post" id="wc-manual-invoice-form" class="wc-manual-invoice-form">
                        <?php wp_nonce_field('wc_manual_invoices_nonce'); ?>
                        <input type="hidden" name="action" value="create_invoice">
                        
                        <div class="invoice-form-section">
                            <h3>
                                <span class="dashicons dashicons-businessman"></span>
                                <?php _e('Customer Information', 'wc-manual-invoices'); ?>
                            </h3>
                            <div class="section-content">
                                <table class="form-table">
                                    <tr>
                                        <th><?php _e('Select Customer', 'wc-manual-invoices'); ?></th>
                                        <td>
                                            <select name="customer_id" id="customer_select" style="width: 100%; max-width: 400px;">
                                                <option value=""><?php _e('Select existing customer...', 'wc-manual-invoices'); ?></option>
                                            </select>
                                            <p class="description"><?php _e('Start typing to search for existing customers', 'wc-manual-invoices'); ?></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <div class="customer-details" style="margin-top: 20px;">
                                    <h4 style="color: #96588a; margin-bottom: 16px;"><?php _e('Or Create New Customer', 'wc-manual-invoices'); ?></h4>
                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 16px;">
                                        <div>
                                            <label for="customer_email" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Email Address *', 'wc-manual-invoices'); ?></label>
                                            <input type="email" name="customer_email" id="customer_email" 
                                                   style="width: 100%;" placeholder="customer@example.com">
                                        </div>
                                        <div>
                                            <label for="billing_first_name" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('First Name', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="billing_first_name" id="billing_first_name" 
                                                   style="width: 100%;" placeholder="John">
                                        </div>
                                        <div>
                                            <label for="billing_last_name" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Last Name', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="billing_last_name" id="billing_last_name" 
                                                   style="width: 100%;" placeholder="Doe">
                                        </div>
                                        <div>
                                            <label for="billing_phone" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Phone Number', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="billing_phone" id="billing_phone" 
                                                   style="width: 100%;" placeholder="+1 (555) 123-4567">
                                        </div>
                                        <div style="grid-column: span 2;">
                                            <label for="billing_address_1" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Address', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="billing_address_1" id="billing_address_1" 
                                                   style="width: 100%;" placeholder="123 Main Street">
                                        </div>
                                        <div>
                                            <label for="billing_city" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('City', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="billing_city" id="billing_city" 
                                                   style="width: 100%;" placeholder="New York">
                                        </div>
                                        <div>
                                            <label for="billing_postcode" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Postal Code', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="billing_postcode" id="billing_postcode" 
                                                   style="width: 100%;" placeholder="10001">
                                        </div>
                                        <div>
                                            <label for="billing_country" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Country', 'wc-manual-invoices'); ?></label>
                                            <select name="billing_country" id="billing_country" style="width: 100%;">
                                                <option value="US"><?php _e('United States', 'wc-manual-invoices'); ?></option>
                                                <option value="CA"><?php _e('Canada', 'wc-manual-invoices'); ?></option>
                                                <option value="GB"><?php _e('United Kingdom', 'wc-manual-invoices'); ?></option>
                                                <option value="AU"><?php _e('Australia', 'wc-manual-invoices'); ?></option>
                                                <!-- Add more countries as needed -->
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="invoice-form-section">
                            <h3>
                                <span class="dashicons dashicons-products"></span>
                                <?php _e('Invoice Items', 'wc-manual-invoices'); ?>
                            </h3>
                            <div class="section-content">
                                <!-- Products Section -->
                                <div style="margin-bottom: 30px;">
                                    <h4 style="color: #96588a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-cart" style="font-size: 18px;"></span>
                                        <?php _e('Add Products', 'wc-manual-invoices'); ?>
                                    </h4>
                                    <div id="invoice-products">
                                        <div class="invoice-product-row">
                                            <div style="flex: 2;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Product', 'wc-manual-invoices'); ?></label>
                                                <select name="product_ids[]" class="product-select" style="width: 100%;">
                                                    <option value=""><?php _e('Search for a product...', 'wc-manual-invoices'); ?></option>
                                                </select>
                                            </div>
                                            <div style="flex: 0 0 120px;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Quantity', 'wc-manual-invoices'); ?></label>
                                                <input type="number" name="product_quantities[]" 
                                                       style="width: 100%;" min="1" step="1" value="1" placeholder="1">
                                            </div>
                                            <div style="flex: 0 0 140px;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Total Price', 'wc-manual-invoices'); ?></label>
                                                <input type="number" name="product_totals[]" 
                                                       style="width: 100%;" min="0" step="0.01" placeholder="0.00">
                                            </div>
                                            <div style="flex: 0 0 auto; padding-top: 28px;">
                                                <button type="button" class="button remove-product-row">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="add-product-row" class="button button-secondary" style="margin-top: 12px;">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php _e('Add Another Product', 'wc-manual-invoices'); ?>
                                    </button>
                                </div>
                                
                                <!-- Custom Items Section -->
                                <div style="margin-bottom: 30px;">
                                    <h4 style="color: #96588a; margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                                        <span class="dashicons dashicons-edit-page" style="font-size: 18px;"></span>
                                        <?php _e('Custom Line Items', 'wc-manual-invoices'); ?>
                                    </h4>
                                    <div id="invoice-custom-items">
                                        <div class="invoice-custom-item-row">
                                            <div style="flex: 1;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Item Name', 'wc-manual-invoices'); ?></label>
                                                <input type="text" name="custom_item_names[]" 
                                                       style="width: 100%;" placeholder="Consulting Service">
                                            </div>
                                            <div style="flex: 1;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Description', 'wc-manual-invoices'); ?></label>
                                                <input type="text" name="custom_item_descriptions[]" 
                                                       style="width: 100%;" placeholder="One-time consultation">
                                            </div>
                                            <div style="flex: 0 0 100px;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Qty', 'wc-manual-invoices'); ?></label>
                                                <input type="number" name="custom_item_quantities[]" 
                                                       style="width: 100%;" min="1" step="1" value="1">
                                            </div>
                                            <div style="flex: 0 0 140px;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Total', 'wc-manual-invoices'); ?></label>
                                                <input type="number" name="custom_item_totals[]" 
                                                       style="width: 100%;" min="0" step="0.01" placeholder="0.00">
                                            </div>
                                            <div style="flex: 0 0 auto; padding-top: 28px;">
                                                <button type="button" class="button remove-custom-item-row">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="add-custom-item-row" class="button button-secondary" style="margin-top: 12px;">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php _e('Add Custom Item', 'wc-manual-invoices'); ?>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="invoice-form-section">
                            <h3>
                                <span class="dashicons dashicons-money-alt"></span>
                                <?php _e('Additional Charges', 'wc-manual-invoices'); ?>
                            </h3>
                            <div class="section-content">
                                <!-- Fees Section -->
                                <div style="margin-bottom: 24px;">
                                    <h4 style="color: #96588a; margin-bottom: 16px;"><?php _e('Fees & Surcharges', 'wc-manual-invoices'); ?></h4>
                                    <div id="invoice-fees">
                                        <div class="invoice-fee-row">
                                            <div style="flex: 1;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Fee Name', 'wc-manual-invoices'); ?></label>
                                                <input type="text" name="fee_names[]" 
                                                       style="width: 100%;" placeholder="Setup Fee, Processing Fee, etc.">
                                            </div>
                                            <div style="flex: 0 0 160px;">
                                                <label style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Amount', 'wc-manual-invoices'); ?></label>
                                                <input type="number" name="fee_amounts[]" 
                                                       style="width: 100%;" step="0.01" placeholder="0.00">
                                            </div>
                                            <div style="flex: 0 0 auto; padding-top: 28px;">
                                                <button type="button" class="button remove-fee-row">
                                                    <span class="dashicons dashicons-trash"></span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" id="add-fee-row" class="button button-secondary" style="margin-top: 12px;">
                                        <span class="dashicons dashicons-plus-alt"></span>
                                        <?php _e('Add Fee', 'wc-manual-invoices'); ?>
                                    </button>
                                </div>
                                
                                <!-- Shipping & Tax -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                    <div>
                                        <h4 style="color: #96588a; margin-bottom: 16px;"><?php _e('Shipping', 'wc-manual-invoices'); ?></h4>
                                        <div style="margin-bottom: 12px;">
                                            <label for="shipping_method" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Method', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="shipping_method" id="shipping_method"
                                                   style="width: 100%;" placeholder="Standard Shipping" value="Shipping">
                                        </div>
                                        <div>
                                            <label for="shipping_total" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Cost', 'wc-manual-invoices'); ?></label>
                                            <input type="number" name="shipping_total" id="shipping_total"
                                                   style="width: 100%;" step="0.01" placeholder="0.00">
                                        </div>
                                    </div>
                                    
                                    <div>
                                        <h4 style="color: #96588a; margin-bottom: 16px;"><?php _e('Tax', 'wc-manual-invoices'); ?></h4>
                                        <div style="margin-bottom: 12px;">
                                            <label for="tax_name" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Tax Type', 'wc-manual-invoices'); ?></label>
                                            <input type="text" name="tax_name" id="tax_name"
                                                   style="width: 100%;" placeholder="Sales Tax, VAT, etc." value="Tax">
                                        </div>
                                        <div>
                                            <label for="tax_total" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Amount', 'wc-manual-invoices'); ?></label>
                                            <input type="number" name="tax_total" id="tax_total"
                                                   style="width: 100%;" step="0.01" placeholder="0.00">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="invoice-form-section">
                            <h3>
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Invoice Settings', 'wc-manual-invoices'); ?>
                            </h3>
                            <div class="section-content">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                    <div>
                                        <label for="due_date" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Due Date', 'wc-manual-invoices'); ?></label>
                                        <input type="date" name="due_date" id="due_date" 
                                               style="width: 100%;" value="<?php echo esc_attr(date('Y-m-d', strtotime('+30 days'))); ?>">
                                        <p class="description"><?php _e('When payment is due for this invoice', 'wc-manual-invoices'); ?></p>
                                    </div>
                                    <div>
                                        <label style="display: block; font-weight: 600; margin-bottom: 12px;"><?php _e('Email Options', 'wc-manual-invoices'); ?></label>
                                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                                            <input type="checkbox" name="send_email" value="1" checked style="margin: 0;">
                                            <span><?php _e('Send invoice email to customer immediately', 'wc-manual-invoices'); ?></span>
                                        </label>
                                        <p class="description"><?php _e('Customer will receive payment link via email', 'wc-manual-invoices'); ?></p>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 24px;">
                                    <label for="notes" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Notes to Customer', 'wc-manual-invoices'); ?></label>
                                    <textarea name="notes" id="notes" rows="4" style="width: 100%;" 
                                              placeholder="Any additional information or payment instructions for the customer..."></textarea>
                                </div>
                                
                                <div style="margin-top: 16px;">
                                    <label for="terms" style="display: block; font-weight: 600; margin-bottom: 6px;"><?php _e('Terms & Conditions', 'wc-manual-invoices'); ?></label>
                                    <textarea name="terms" id="terms" rows="4" style="width: 100%;" 
                                              placeholder="Payment terms, late fees, return policy, etc."></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div style="text-align: center; padding: 30px;">
                            <button type="submit" class="button button-primary button-large" style="padding: 12px 48px; font-size: 16px;">
                                <span class="dashicons dashicons-media-text" style="margin-right: 8px;"></span>
                                <?php _e('Create Invoice', 'wc-manual-invoices'); ?>
                            </button>
                            <p style="margin-top: 12px; color: #666; font-size: 14px;">
                                <?php _e('Invoice will be created as a WooCommerce order and customer will receive payment instructions', 'wc-manual-invoices'); ?>
                            </p>
                        </div>
                    </form>
                </div>
            </div>
            
        <?php elseif ($current_tab === 'reports') : ?>
            
            <!-- Enhanced Reports Section -->
            <div style="padding: 30px;">
                <div style="text-align: center; margin-bottom: 40px;">
                    <h2 style="color: #2c3e50; margin-bottom: 12px;"><?php _e('Invoice Analytics', 'wc-manual-invoices'); ?></h2>
                    <p style="color: #7f8c8d; font-size: 16px;">
                        <?php _e('Detailed insights into your invoice performance and payment trends', 'wc-manual-invoices'); ?>
                    </p>
                </div>
                
                <!-- Quick Stats Row -->
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px;">
                    <div class="stat-card">
                        <h3 style="margin-top: 0; color: #96588a;"><?php _e('This Month', 'wc-manual-invoices'); ?></h3>
                        <div class="stat-number" style="font-size: 24px;"><?php echo wc_price($this_month_total); ?></div>
                        <p style="color: #666; margin-bottom: 0;"><?php _e('Revenue Generated', 'wc-manual-invoices'); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3 style="margin-top: 0; color: #96588a;"><?php _e('Payment Rate', 'wc-manual-invoices'); ?></h3>
                        <div class="stat-number" style="font-size: 24px;"><?php echo $conversion_rate; ?>%</div>
                        <p style="color: #666; margin-bottom: 0;"><?php _e('Invoices Paid', 'wc-manual-invoices'); ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3 style="margin-top: 0; color: #96588a;"><?php _e('Outstanding', 'wc-manual-invoices'); ?></h3>
                        <div class="stat-number" style="font-size: 24px;"><?php echo wc_price($stats['pending_amount']); ?></div>
                        <p style="color: #666; margin-bottom: 0;"><?php _e('Awaiting Payment', 'wc-manual-invoices'); ?></p>
                    </div>
                </div>
                
                <!-- Coming Soon Section -->
                <div style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 12px; padding: 60px 40px; text-align: center;">
                    <div style="width: 80px; height: 80px; margin: 0 auto 24px; border-radius: 50%; background: linear-gradient(135deg, #96588a, #7e4874); display: flex; align-items: center; justify-content: center;">
                        <span class="dashicons dashicons-chart-bar" style="font-size: 36px; color: white;"></span>
                    </div>
                    <h3 style="color: #2c3e50; margin-bottom: 16px; font-size: 24px;"><?php _e('Advanced Reports Coming Soon', 'wc-manual-invoices'); ?></h3>
                    <p style="color: #7f8c8d; font-size: 16px; margin-bottom: 24px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        <?php _e('We\'re working on detailed analytics including payment trends, customer insights, and revenue forecasting to help you better understand your business.', 'wc-manual-invoices'); ?>
                    </p>
                    <div style="color: #96588a; font-weight: 600;">
                        <span class="dashicons dashicons-clock" style="margin-right: 6px;"></span>
                        <?php _e('Expected in the next update', 'wc-manual-invoices'); ?>
                    </div>
                </div>
            </div>
            
        <?php endif; ?>
    </div>
</div>

<!-- Enhanced Loading overlay -->
<div id="wc-manual-invoices-loading" class="wc-manual-invoices-loading" style="display: none;">
    <div class="loading-content">
        <div class="loading-spinner"></div>
        <div class="loading-text"><?php _e('Processing...', 'wc-manual-invoices'); ?></div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Enhanced tooltips for action buttons
    $('.row-actions button').each(function() {
        const title = $(this).attr('title');
        if (title) {
            $(this).on('mouseenter', function() {
                // Could implement custom tooltips here
            });
        }
    });
    
    // Smooth transitions for hover effects
    $('.stat-card, .wp-list-table tbody tr').on('mouseenter mouseleave', function() {
        // Enhanced by CSS transitions
    });
    
    // Auto-refresh functionality (optional)
    <?php if ($current_tab === 'invoices') : ?>
    setInterval(function() {
        // Could add auto-refresh for real-time updates
        // location.reload();
    }, 30000); // 30 seconds
    <?php endif; ?>
});
</script>