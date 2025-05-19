<?php
/**
 * Create Invoice Form Template
 * 
 * Template for the invoice creation form
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

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
                            <option value="DE"><?php _e('Germany', 'wc-manual-invoices'); ?></option>
                            <option value="FR"><?php _e('France', 'wc-manual-invoices'); ?></option>
                            <option value="ES"><?php _e('Spain', 'wc-manual-invoices'); ?></option>
                            <option value="IT"><?php _e('Italy', 'wc-manual-invoices'); ?></option>
                            <option value="NL"><?php _e('Netherlands', 'wc-manual-invoices'); ?></option>
                            <option value="BR"><?php _e('Brazil', 'wc-manual-invoices'); ?></option>
                            <option value="MX"><?php _e('Mexico', 'wc-manual-invoices'); ?></option>
                            <option value="IN"><?php _e('India', 'wc-manual-invoices'); ?></option>
                            <option value="JP"><?php _e('Japan', 'wc-manual-invoices'); ?></option>
                            <option value="CN"><?php _e('China', 'wc-manual-invoices'); ?></option>
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