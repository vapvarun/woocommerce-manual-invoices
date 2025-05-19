<?php
/**
 * Admin Settings Template
 * 
 * Template for plugin settings page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = WC_Manual_Invoices_Settings::get_settings();
?>

<div class="wrap">
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
                                   value="<?php echo esc_attr($settings['default_due_days']); ?>" 
                                   min="1" step="1" class="small-text">
                            <p class="description"><?php _e('Default number of days until invoice is due', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Auto Send Email', 'wc-manual-invoices'); ?></th>
                        <td>
                            <label>
                                <input type="radio" name="auto_send_email" value="yes" 
                                       <?php checked($settings['auto_send_email'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="auto_send_email" value="no" 
                                       <?php checked($settings['auto_send_email'], 'no'); ?>>
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
                                       <?php checked($settings['auto_generate_pdf'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="auto_generate_pdf" value="no" 
                                       <?php checked($settings['auto_generate_pdf'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Automatically generate PDF when invoice is created', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Invoice Prefix', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="invoice_prefix" 
                                   value="<?php echo esc_attr($settings['invoice_prefix']); ?>" 
                                   class="regular-text">
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
                                   value="<?php echo esc_attr($settings['company_name']); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Leave empty to use site title', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Address', 'wc-manual-invoices'); ?></th>
                        <td>
                            <textarea name="company_address" rows="4" 
                                      class="large-text"><?php echo esc_textarea($settings['company_address']); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Phone', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="company_phone" 
                                   value="<?php echo esc_attr($settings['company_phone']); ?>" 
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Email', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="email" name="company_email" 
                                   value="<?php echo esc_attr($settings['company_email']); ?>" 
                                   class="regular-text">
                            <p class="description"><?php _e('Leave empty to use admin email', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Company Logo', 'wc-manual-invoices'); ?></th>
                        <td>
                            <?php if (!empty($settings['company_logo'])) : ?>
                                <div class="current-logo">
                                    <img src="<?php echo esc_url($settings['company_logo']); ?>" 
                                         style="max-width: 200px; height: auto; display: block; margin-bottom: 10px;">
                                    <input type="hidden" name="existing_company_logo" 
                                           value="<?php echo esc_attr($settings['company_logo']); ?>">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="company_logo" accept="image/*">
                            <p class="description"><?php _e('Upload a logo for invoices (JPG, PNG, GIF)', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Invoice Footer', 'wc-manual-invoices'); ?></th>
                        <td>
                            <textarea name="invoice_footer" rows="4" 
                                      class="large-text"><?php echo esc_textarea($settings['invoice_footer']); ?></textarea>
                            <p class="description"><?php _e('Text to appear at the bottom of invoices', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                </table>
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
                                       <?php checked($settings['reminder_enabled'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="reminder_enabled" value="no" 
                                       <?php checked($settings['reminder_enabled'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Send automatic reminders for unpaid invoices', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Reminder Days', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="text" name="reminder_days" 
                                   value="<?php echo esc_attr(implode(',', $settings['reminder_days'])); ?>" 
                                   class="regular-text">
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
                                       <?php checked($settings['late_fee_enabled'], 'yes'); ?>>
                                <?php _e('Yes', 'wc-manual-invoices'); ?>
                            </label>
                            <label>
                                <input type="radio" name="late_fee_enabled" value="no" 
                                       <?php checked($settings['late_fee_enabled'], 'no'); ?>>
                                <?php _e('No', 'wc-manual-invoices'); ?>
                            </label>
                            <p class="description"><?php _e('Add late fees to overdue invoices', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Late Fee Amount', 'wc-manual-invoices'); ?></th>
                        <td>
                            <input type="number" name="late_fee_amount" 
                                   value="<?php echo esc_attr($settings['late_fee_amount']); ?>" 
                                   step="0.01" min="0" class="small-text">
                            <select name="late_fee_type">
                                <option value="fixed" <?php selected($settings['late_fee_type'], 'fixed'); ?>>
                                    <?php _e('Fixed Amount', 'wc-manual-invoices'); ?>
                                </option>
                                <option value="percentage" <?php selected($settings['late_fee_type'], 'percentage'); ?>>
                                    <?php _e('Percentage', 'wc-manual-invoices'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Late fee amount (fixed amount or percentage of invoice total)', 'wc-manual-invoices'); ?></p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        
        <?php submit_button(__('Save Settings', 'wc-manual-invoices')); ?>
    </form>
</div>