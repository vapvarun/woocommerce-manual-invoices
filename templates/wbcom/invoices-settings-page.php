<?php

/**
 * Provide an admin area view for the genral settings page.
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wbcomdesigns.com/
 * @since      1.0.0
 *
 * @package    Buddyvendor
 * @subpackage Buddyvendor/admin/partials
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wc_invoice_pro_settigns = get_option( 'wc_manual_invoices_settings' );
$logo_url                = $wc_invoice_pro_settigns['company-logo'] ?? '';

?>
<div class="wbcom-tab-content">
	<div class="wbcom-wrapper-admin">
		<div class="wbcom-admin-title-section wbcom-flex">
			<h3 class="wbcom-welcome-title"><?php esc_html_e( 'Invoice Settings', 'buddyvendor' ); ?></h3>
			<a href="<?php echo esc_url( 'https://docs.wbcomdesigns.com/doc_category/buddyvendor/' ); ?>" class="wbcom-docslink" target="_blank"><?php esc_html_e( 'Documentation', 'buddyvendor' ); ?></a>
		</div>
		<div class="wbcom-admin-option-wrap-view">
			<form method="post" action="options.php">
				<div class="wbcom-admin-option-wrap">
					<?php
					settings_fields( 'wc_manual_invoices_settings' );
					do_settings_sections( 'wc_manual_invoices_settings' );
					?>
					<div class="form-table">
						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
									<label for="wcip-company-name"><?php esc_html_e( 'Invoice Settings', 'buddyvendor' ); ?></label>
								</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-default-due-days"><?php esc_html_e( 'Default Due Days', 'buddyvendor' ); ?></label>
									<p class="description"><?php esc_html_e( 'Default number of days until invoice is due', 'buddyvendor' ); ?></p>
								</div>
								<div class="wbcom-settings-section-options">
									<input name="wc_manual_invoices_settings[default-due-days]" type="number" id="wcip-default-due-days"  value="<?php echo esc_attr( $wc_invoice_pro_settigns['default-due-days'] ?? '' ); ?>">
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-auto-send-email"><?php esc_html_e( 'Auto Send Email', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<label class="wb-switch">
									<input name="wc_manual_invoices_settings[send-email]" type="checkbox" id="wcip-auto-send-email" value="yes" <?php checked( $wc_invoice_pro_settigns['send-email'] ?? '', 'yes' ); ?>>
										<div class="wb-slider wb-round"></div>
									</label>
								</div>
								<p class="description"><?php esc_html_e( 'Automatically send email when invoice is created', 'buddyvendor' ); ?></p>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-auto-genrate-pdf"><?php esc_html_e( 'Auto Generate PDF', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<label class="wb-switch">
										<input name="wc_manual_invoices_settings[genrate-pdf]" type="checkbox" id="wcip-auto-genrate-pdf" value="yes" <?php checked( $wc_invoice_pro_settigns['genrate-pdf'] ?? '', 'yes' ); ?>>
										<div class="wb-slider wb-round"></div>
									</label>
								</div>
								<p class="description"><?php esc_html_e( 'Automatically generate PDF when invoice is created', 'buddyvendor' ); ?></p>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-invoice-prefix"><?php esc_html_e( 'Invoice Prefix', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<input name="wc_manual_invoices_settings[invoice-prefix]" type="text" class="regular-text" id="wcip-invoice-prefix" value="<?php echo esc_attr( $wc_invoice_pro_settigns['invoice-prefix'] ?? '' ); ?>">
								</div>
								<p class="description"><?php esc_html_e( 'Prefix for invoice numbers (e.g., INV-)', 'buddyvendor' ); ?></p>
							</div>
						</div>

						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label for="wcip-company-name"><?php esc_html_e( 'Company Information', 'buddyvendor' ); ?></label>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-company-name"><?php esc_html_e( 'Company Name', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<input name="wc_manual_invoices_settings[company-name]" type="text" class="regular-text" id="wcip-company-name" value="<?php echo esc_attr( $wc_invoice_pro_settigns['company-name'] ?? '' ); ?>">
									<p class="description"><?php esc_html_e( 'Leave empty to automatically use your site title', 'buddyvendor' ); ?></p>
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-company-address"><?php esc_html_e( 'Company Address', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<textarea name="wc_manual_invoices_settings[company-address]" id="wcip-company-address" rows="4"><?php echo esc_textarea( $wc_invoice_pro_settigns['company-address'] ?? '' ); ?></textarea>
									<p class="description"><?php esc_html_e( 'Leave empty to automatically use your WooCommerce store address', 'buddyvendor' ); ?></p>
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-company-phone"><?php esc_html_e( 'Company Phone', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<input type="tel" name="wc_manual_invoices_settings[company-phone]" id="wcip-company-phone" value="<?php echo esc_attr( $wc_invoice_pro_settigns['company-phone'] ?? '' ); ?>">
									<p class="description"><?php esc_html_e( 'Leave empty to automatically use your store phone number', 'buddyvendor' ); ?></p>
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-company-email"><?php esc_html_e( 'Company Email', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<input type="email" name="wc_manual_invoices_settings[company-email]" id="wcip-company-email" value="<?php echo esc_attr( $wc_invoice_pro_settigns['company-email'] ?? '' ); ?>">	
									<p class="description"><?php esc_html_e( 'Leave empty to automatically use your store phone number', 'buddyvendor' ); ?></p>
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-company-logo"><?php esc_html_e( 'Company Logo', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<input type="hidden" id="wcip-company-logo" name="wc_manual_invoices_settings[company-logo]" value="<?php echo esc_url( $logo_url ); ?>">

									<!-- Upload Button -->
									<button type="button" class="button" id="wcip-company-logo-button"
										data-target-input="wcip-company-logo"
										data-target-preview="wcip-company-logo-preview"
										data-target-remove="wcip-remove-logo">
										<?php esc_html_e( 'Upload / Select Logo', 'buddyvendor' ); ?>
									</button>

									<!-- Remove Button -->
									<button type="button" class="button" id="wcip-remove-logo" style="<?php echo empty( $logo_url ) ? 'display:none;' : ''; ?>">
										<?php esc_html_e( 'Remove Logo', 'buddyvendor' ); ?>
									</button>

									<!-- Preview -->
									<div id="wcip-company-logo-preview" style="margin-top: 10px;">
										<?php if ( $logo_url ) : ?>
											<img src="<?php echo esc_url( $logo_url ); ?>" style="max-height: 100px;">
										<?php endif; ?>
									</div>
								</div>
								
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-invoice-footer"><?php esc_html_e( 'Invoice Footer', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<textarea name="wc_manual_invoices_settings[invoice-footer]" id="wcip-invoice-footer" rows="4"><?php echo esc_textarea( $wc_invoice_pro_settigns['invoice-footer'] ?? '' ); ?></textarea>
									<p class="description">
										<?php esc_html_e( 'Custom text to appear at the bottom of invoices (leave empty for automatic footer)', 'buddyvendor' ); ?>
									</p>
								</div>
							</div>
						</div>

						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label><?php esc_html_e( 'Payment Reminders', 'buddyvendor' ); ?></label>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-enable-reminders"><?php esc_html_e( 'Enable Reminders', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<label class="wb-switch">
										<input name="wc_manual_invoices_settings[enable-reminder]" type="checkbox" id="wcip-enable-reminders" value="yes" <?php checked( $wc_invoice_pro_settigns['enable-reminder'] ?? '', 'yes' ); ?>>
										<div class="wb-slider wb-round"></div>
									</label>
									<p class="description">
										<?php esc_html_e( 'Send automatic reminders for unpaid invoices', 'buddyvendor' ); ?>
									</p>
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-reminder-days"><?php esc_html_e( 'Reminder Days', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<label class="wb-switch">
										<input name="wc_manual_invoices_settings[reminder-days]" type="checkbox" id="wcip-reminder-days" value="yes" <?php checked( $wc_invoice_pro_settigns['reminder-days'] ?? '', 'yes' ); ?>>
										<div class="wb-slider wb-round"></div>
									</label>
									<p class="description">
										<?php esc_html_e( 'Days after due date to send reminders (comma-separated, e.g., 7,14,30)', 'buddyvendor' ); ?>
									</p>
								</div>
							</div>
						</div>

						<div class="wbcom-settings-section-wrap">
							<div class="wbcom-settings-section-options-heading">
								<label><?php esc_html_e( 'Late Fees', 'buddyvendor' ); ?></label>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-enable-late-fees"><?php esc_html_e( 'Enable Late Fees', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<label class="wb-switch">
										<input name="wc_manual_invoices_settings[enable-late-fees]" type="checkbox" id="wcip-enable-late-fees" value="yes" <?php checked( $wc_invoice_pro_settigns['enable-late-fees'] ?? '', 'yes' ); ?>>
										<div class="wb-slider wb-round"></div>
									</label>
									<p class="description">
										<?php esc_html_e( 'Add late fees to overdue invoices', 'buddyvendor' ); ?>
									</p>
								</div>
							</div>
							<div class="wbcom-settings-section-wrap">
								<div class="wbcom-settings-section-options-heading">
									<label for="wcip-late-fee-amount"><?php esc_html_e( 'Late Fee Amount', 'buddyvendor' ); ?></label>
								</div>
								<div class="wbcom-settings-section-options">
									<input name="wc_manual_invoices_settings[late-fee-amount]" class="regular-text" type="number" id="wcip-late-fee-amount" value="<?php echo esc_attr( $wc_invoice_pro_settigns['late-fee-amount'] ?? '' ); ?>">
									<select name="wc_manual_invoices_settings[late-fee-type]">
										<option value="fixed"><?php esc_html_e( 'Fixed Amount', 'buddyvendor' ); ?></option>
										<option value="percentage"><?php esc_html_e( 'Percentage', 'buddyvendor' ); ?></option>
									</select>
									<p class="description">
										<?php esc_html_e( 'Late fee amount (fixed amount or percentage of invoice total)', 'buddyvendor' ); ?>
									</p>
								</div>
							</div>
						</div>
					</div>
					<?php submit_button(); ?>
				</div>
			</form>
		</div>
	</div>
</div>