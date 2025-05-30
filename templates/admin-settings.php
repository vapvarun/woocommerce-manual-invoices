<?php
/**
 * Admin Settings Template with Enhanced Default Value Display
 * 
 * Template for plugin settings page showing current effective values
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings with smart defaults applied
$settings = WC_Manual_Invoices_Settings::get_settings_with_defaults();

// Get raw settings to show what's actually saved vs fallbacks
$raw_settings = WC_Manual_Invoices_Settings::get_settings();

// Helper function to show fallback values
function show_fallback_info($setting_key, $current_value, $raw_value) {
    if (empty($raw_value) && !empty($current_value)) {
        echo '<p class="description" style="color: #0073aa; font-style: italic;">';
        echo '<span class="dashicons dashicons-info" style="font-size: 14px; margin-right: 5px;"></span>';
        echo sprintf(__('Currently using: %s (automatic)', 'wc-manual-invoices'), '<strong>' . esc_html($current_value) . '</strong>');
        echo '</p>';
    }
}
?>

<div class="wrap">
    <!-- ADD THIS - Navigation links at top -->
    <div style="margin-bottom: 20px;">
        <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices'); ?>" class="button">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-right: 5px;"></span>
            <?php _e('Back to Invoices', 'wc-manual-invoices'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices-pdf-settings'); ?>" class="button button-secondary" style="margin-left: 10px;">
            <span class="dashicons dashicons-pdf" style="margin-right: 5px;"></span>
            <?php _e('PDF Settings', 'wc-manual-invoices'); ?>
        </a>
    </div>
    
    <h1><?php _e('Invoice Settings', 'wc-manual-invoices'); ?></h1>
    
    <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field('wc_manual_invoices_settings'); ?>
        
        <!-- General Settings -->
        <div class="postbox">
            <h3 class="hndle"><?php _e('General Settings', 'wc-manual-invoices'); ?></h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Default Due Days', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="number" name="default_due_days" 
                                   value="<?php echo esc_attr($raw_settings['default_due_days']); ?>" 
                                   min="1" step="1" class="small-text">
                            <p class="description"><?php _e('Default number of days until invoice is due', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Auto Send Email', 'wc-manual-invoices'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="auto_send_email" value="yes" 
                                       <?php checked($raw_settings['auto_send_email'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="auto_send_email" value="no" 
                                       <?php checked($raw_settings['auto_send_email'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Automatically send email when invoice is created', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Auto Generate PDF', 'wc-manual-invoices'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="auto_generate_pdf" value="yes" 
                                       <?php checked($raw_settings['auto_generate_pdf'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="auto_generate_pdf" value="no" 
                                       <?php checked($raw_settings['auto_generate_pdf'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Automatically generate PDF when invoice is created', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Invoice Prefix', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="invoice_prefix" 
                                   value="<?php echo esc_attr($raw_settings['invoice_prefix']); ?>" 
                                   class="regular-text" placeholder="INV-">
                            <p class="description"><?php _e('Prefix for invoice numbers (e.g., INV-)', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Company Information -->
        <div class="postbox">
            <h3 class="hndle"><?php _e('Company Information', 'wc-manual-invoices'); ?></h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Company Name', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="company_name" 
                                   value="<?php echo esc_attr($raw_settings['company_name']); ?>" 
                                   class="regular-text" 
                                   placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                            <?php show_fallback_info('company_name', $settings['company_name'], $raw_settings['company_name']); ?>
                            <p class="description"><?php _e('Leave empty to automatically use your site title', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Address', 'wc-manual-invoices'); ?></th>
                        <td>
                            <textarea name="company_address" rows="4" 
                                      class="large-text" 
                                      placeholder="<?php echo esc_attr(get_option('woocommerce_store_address', __('Your business address', 'wc-manual-invoices'))); ?>"><?php echo esc_textarea($raw_settings['company_address']); ?></textarea>
                            <?php show_fallback_info('company_address', $settings['company_address'], $raw_settings['company_address']); ?>
                            <p class="description"><?php _e('Leave empty to automatically use your WooCommerce store address', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Phone', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="company_phone" 
                                   value="<?php echo esc_attr($raw_settings['company_phone']); ?>" 
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr(get_option('woocommerce_store_phone', __('Your phone number', 'wc-manual-invoices'))); ?>">
                            <?php show_fallback_info('company_phone', $settings['company_phone'], $raw_settings['company_phone']); ?>
                            <p class="description"><?php _e('Leave empty to automatically use your store phone number', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Email', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="email" name="company_email" 
                                   value="<?php echo esc_attr($raw_settings['company_email']); ?>" 
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                            <?php show_fallback_info('company_email', $settings['company_email'], $raw_settings['company_email']); ?>
                            <p class="description"><?php _e('Leave empty to automatically use your site admin email', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Logo', 'wc-manual-invoices'); ?></th>
                        <td>
                            <?php if (!empty($settings['company_logo'])) : ?>
                                <div class="current-logo">
                                    <img src="<?php echo esc_url($settings['company_logo']); ?>" 
                                         style="max-width: 200px; height: auto; display: block; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px;">
                                    <input type="hidden" name="existing_company_logo" 
                                           value="<?php echo esc_attr($settings['company_logo']); ?>">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="company_logo" accept="image/*">
                            <p class="description"><?php _e('Upload a logo for invoices and emails (JPG, PNG, GIF)', 'wc-manual-invoices'); ?></p>
                            <?php if (empty($settings['company_logo'])) : ?>
                                <p class="description" style="color: #666; font-style: italic;">
                                    <span class="dashicons dashicons-camera" style="font-size: 14px; margin-right: 5px;"></span>
                                    <?php _e('No logo uploaded. Consider adding one for a professional appearance.', 'wc-manual-invoices'); ?>
                                </p>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Invoice Footer', 'wc-manual-invoices'); ?></th>
                        <td>
                            <textarea name="invoice_footer" rows="4" 
                                      class="large-text"><?php echo esc_textarea($raw_settings['invoice_footer']); ?></textarea>
                            <?php if (empty($raw_settings['invoice_footer'])) : ?>
                                <p class="description" style="color: #0073aa; font-style: italic;">
                                    <span class="dashicons dashicons-info" style="font-size: 14px; margin-right: 5px;"></span>
                                    <?php _e('Preview of automatic footer:', 'wc-manual-invoices'); ?>
                                </p>
                                <div style="background: #f9f9f9; padding: 10px; border-left: 4px solid #0073aa; margin-top: 5px; font-style: italic; color: #666;">
                                    <?php echo nl2br(esc_html($settings['invoice_footer'])); ?>
                                </div>
                            <?php endif; ?>
                            <p class="description"><?php _e('Custom text to appear at the bottom of invoices (leave empty for automatic footer)', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Preview Section -->
        <div class="postbox">
            <h3 class="hndle">
                <span class="dashicons dashicons-visibility" style="margin-right: 5px;"></span>
                <?php _e('Current Values Preview', 'wc-manual-invoices'); ?>
            </h3>
            <div class="inside">
                <p class="description" style="margin-bottom: 15px;">
                    <?php _e('This shows how your company information will appear on invoices:', 'wc-manual-invoices'); ?>
                </p>
                <div style="background: #fff; border: 1px solid #ddd; padding: 20px; border-radius: 5px;">
                    <h4 style="margin-top: 0; color: #96588a;"><?php echo esc_html($settings['company_name']); ?></h4>
                    
                    <?php if (!empty($settings['company_address'])) : ?>
                        <div style="margin-bottom: 10px;">
                            <?php echo nl2br(esc_html($settings['company_address'])); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['company_phone'])) : ?>
                        <div><strong><?php _e('Phone:', 'wc-manual-invoices'); ?></strong> <?php echo esc_html($settings['company_phone']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['company_email'])) : ?>
                        <div><strong><?php _e('Email:', 'wc-manual-invoices'); ?></strong> <?php echo esc_html($settings['company_email']); ?></div>
                    <?php endif; ?>
                    
                    <?php if (!empty($settings['invoice_footer'])) : ?>
                        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #eee; font-size: 12px; color: #666;">
                            <?php echo nl2br(esc_html($settings['invoice_footer'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Reminders -->
        <div class="postbox">
            <h3 class="hndle"><?php _e('Payment Reminders', 'wc-manual-invoices'); ?></h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Reminders', 'wc-manual-invoices'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="reminder_enabled" value="yes" 
                                       <?php checked($raw_settings['reminder_enabled'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="reminder_enabled" value="no" 
                                       <?php checked($raw_settings['reminder_enabled'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Send automatic reminders for unpaid invoices', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Reminder Days', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="reminder_days" 
                                   value="<?php echo esc_attr(implode(',', $raw_settings['reminder_days'])); ?>" 
                                   class="regular-text" placeholder="7,14,30">
                            <p class="description"><?php _e('Days after due date to send reminders (comma-separated, e.g., 7,14,30)', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Late Fees -->
        <div class="postbox">
            <h3 class="hndle"><?php _e('Late Fees', 'wc-manual-invoices'); ?></h3>
            <div class="inside">
                <table class="form-table">
                    <tr>
                        <th><?php _e('Enable Late Fees', 'wc-manual-invoices'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="late_fee_enabled" value="yes" 
                                       <?php checked($raw_settings['late_fee_enabled'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="late_fee_enabled" value="no" 
                                       <?php checked($raw_settings['late_fee_enabled'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Add late fees to overdue invoices', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Late Fee Amount', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="number" name="late_fee_amount" 
                                   value="<?php echo esc_attr($raw_settings['late_fee_amount']); ?>" 
                                   step="0.01" min="0" class="small-text" placeholder="0.00">
                            <select name="late_fee_type">
                                <option value="fixed" <?php selected($raw_settings['late_fee_type'], 'fixed'); ?>>
                                    <?php _e('Fixed Amount', 'wc-manual-invoices'); ?>
                                </option>
                                <option value="percentage" <?php selected($raw_settings['late_fee_type'], 'percentage'); ?>>
                                    <?php _e('Percentage', 'wc-manual-invoices'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Late fee amount (fixed amount or percentage of invoice total)', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <!-- Information Box -->
        <div class="postbox" style="border-left: 4px solid #0073aa;">
            <h3 class="hndle">
                <span class="dashicons dashicons-lightbulb" style="margin-right: 5px; color: #0073aa;"></span>
                <?php _e('Smart Defaults', 'wc-manual-invoices'); ?>
            </h3>
            <div class="inside">
                <p><?php _e('This plugin uses smart defaults to ensure your invoices always look professional:', 'wc-manual-invoices'); ?></p>
                <ul style="list-style-type: disc; margin-left: 20px;">
                    <li><?php _e('Company name automatically uses your WordPress site title', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Address pulls from your WooCommerce store settings', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Email defaults to your WordPress admin email', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Phone number uses your WooCommerce store phone if available', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Footer includes your business name and current year automatically', 'wc-manual-invoices'); ?></li>
                </ul>
                <p><?php _e('You can override any of these by filling in the fields above.', 'wc-manual-invoices'); ?></p>
            </div>
        </div>
        
        <!-- PDF Status Information -->
        <div class="postbox" style="border-left: 4px solid #0073aa;">
            <h3 class="hndle">
                <span class="dashicons dashicons-pdf" style="margin-right: 5px; color: #0073aa;"></span>
                <?php _e('PDF Generation Status', 'wc-manual-invoices'); ?>
            </h3>
            <div class="inside">
                <?php
                // Check PDF library status
                $pdf_status = WC_Manual_Invoice_PDF::get_pdf_library_status();
                $has_pdf_library = false;
                foreach ($pdf_status as $library => $info) {
                    if ($info['available']) {
                        $has_pdf_library = true;
                        break;
                    }
                }
                ?>
                
                <?php if ($has_pdf_library): ?>
                    <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="dashicons dashicons-yes-alt" style="font-size: 20px;"></span>
                            <div>
                                <strong><?php _e('PDF Generation Available', 'wc-manual-invoices'); ?></strong><br>
                                <?php _e('Professional PDF invoices will be generated for your customers.', 'wc-manual-invoices'); ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span class="dashicons dashicons-warning" style="font-size: 20px;"></span>
                            <div>
                                <strong><?php _e('PDF Library Required', 'wc-manual-invoices'); ?></strong><br>
                                <?php _e('Invoices will be generated as text files. Install a PDF library for professional formatting.', 'wc-manual-invoices'); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <p style="margin-bottom: 10px;">
                    <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices-pdf-settings'); ?>" class="button button-primary">
                        <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                        <?php _e('Manage PDF Settings', 'wc-manual-invoices'); ?>
                    </a>
                </p>
                
                <p style="color: #666; font-size: 13px;">
                    <?php _e('Configure PDF libraries, test generation, and view system requirements.', 'wc-manual-invoices'); ?>
                </p>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'wc-manual-invoices'), 'primary', 'submit', true, array('style' => 'background: #96588a; border-color: #96588a;')); ?>
    </form>
</div>

<style>
.postbox h3.hndle {
    background: #f7f7f7;
    border-bottom: 1px solid #ddd;
    color: #333;
}

.form-table th {
    width: 200px;
    padding-left: 0;
}

.description {
    font-size: 13px;
    color: #666;
}

.current-logo img {
    border: 1px solid #ddd;
    border-radius: 4px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}

.button-primary:hover {
    background: #7e4874 !important;
    border-color: #7e4874 !important;
}
</style>