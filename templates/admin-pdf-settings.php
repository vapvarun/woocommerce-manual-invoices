<?php
/**
 * Simplified PDF Settings Page Template - DomPDF Only
 * 
 * Simplified to only handle bundled DomPDF with fallback text generation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get PDF library status
$pdf_status = WC_Manual_Invoice_PDF_Installer::get_library_status();
$dompdf_available = WC_Manual_Invoice_PDF_Installer::is_library_available('dompdf');
$diagnostics = WC_Manual_Invoice_PDF_Installer::get_diagnostics();

// Handle test PDF generation
if (isset($_POST['test_pdf']) && wp_verify_nonce($_POST['_wpnonce'], 'test_pdf_generation')) {
    $test_result = WC_Manual_Invoice_PDF_Installer::test_pdf_generation();
    if (is_wp_error($test_result)) {
        echo '<div class="notice notice-error"><p><strong>Test Result:</strong> ' . esc_html($test_result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Test Successful!</strong> PDF generated successfully. <a href="' . esc_url($test_result['download_url']) . '" target="_blank">Download Test PDF</a></p></div>';
    }
}
?>

<div class="wrap wc-manual-invoices-wrap">
    <!-- Navigation -->
    <div style="margin-bottom: 20px;">
        <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices'); ?>" class="button">
            <span class="dashicons dashicons-arrow-left-alt" style="margin-right: 5px;"></span>
            <?php _e('Back to Invoices', 'wc-manual-invoices'); ?>
        </a>
        <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices-settings'); ?>" class="button button-secondary" style="margin-left: 10px;">
            <span class="dashicons dashicons-admin-generic" style="margin-right: 5px;"></span>
            <?php _e('General Settings', 'wc-manual-invoices'); ?>
        </a>
    </div>

    <!-- Header Section -->
    <div class="wc-manual-invoices-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-title">
                    <span class="dashicons dashicons-pdf" style="margin-right: 10px;"></span>
                    <?php _e('PDF Generation Status', 'wc-manual-invoices'); ?>
                </h1>
                <p class="header-subtitle">
                    <?php _e('Professional PDF invoice generation powered by DomPDF', 'wc-manual-invoices'); ?>
                </p>
            </div>
            <div class="header-actions">
                <?php if ($dompdf_available): ?>
                    <form method="post" style="display: inline;">
                        <?php wp_nonce_field('test_pdf_generation'); ?>
                        <button type="submit" name="test_pdf" class="btn-header">
                            <span class="dashicons dashicons-media-document" style="margin-right: 6px;"></span>
                            <?php _e('Test PDF', 'wc-manual-invoices'); ?>
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Current Status -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-info"></span>
            <?php _e('Current PDF Status', 'wc-manual-invoices'); ?>
        </h2>
        
        <?php if ($dompdf_available): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 24px;"></span>
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 18px;">
                            <?php _e('✅ PDF Generation Ready', 'wc-manual-invoices'); ?>
                        </h3>
                        <p style="margin: 0; font-size: 14px;">
                            <?php _e('DomPDF is bundled and working correctly. Professional PDF invoices will be generated automatically.', 'wc-manual-invoices'); ?>
                        </p>
                        <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.8;">
                            <?php printf(__('Version: %s', 'wc-manual-invoices'), esc_html($pdf_status['dompdf']['version'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="dashicons dashicons-warning" style="font-size: 24px;"></span>
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 18px;">
                            <?php _e('⚠️ PDF Library Issue', 'wc-manual-invoices'); ?>
                        </h3>
                        <p style="margin: 0; font-size: 14px;">
                            <?php _e('DomPDF library files are missing or corrupted. Invoices will be generated as formatted text files.', 'wc-manual-invoices'); ?>
                        </p>
                        <p style="margin: 10px 0 0 0; font-size: 13px;">
                            <strong><?php _e('Solution:', 'wc-manual-invoices'); ?></strong>
                            <?php _e('Re-download and reinstall the plugin to restore DomPDF files.', 'wc-manual-invoices'); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- DomPDF Information -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('DomPDF Information', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="border: 2px solid <?php echo $dompdf_available ? '#28a745' : '#dc3545'; ?>; border-radius: 8px; padding: 20px; position: relative;">
            <?php if ($dompdf_available): ?>
                <div style="position: absolute; top: -10px; right: 15px; background: #28a745; color: white; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                    ✓ WORKING
                </div>
            <?php endif; ?>
            
            <h3 style="margin-top: 0; color: #333; display: flex; align-items: center; gap: 10px;">
                <span class="dashicons dashicons-star-filled" style="color: #ffd700;"></span>
                DomPDF (Bundled)
                <span style="background: #96588a; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">INCLUDED</span>
            </h3>
            
            <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                <?php echo esc_html($pdf_status['dompdf']['description']); ?>
            </p>
            
            <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                <h4 style="margin-top: 0; margin-bottom: 10px; color: #96588a; font-size: 14px;">Features:</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555;">
                    <li>✅ No installation required - bundled with plugin</li>
                    <li>✅ Excellent HTML/CSS support for beautiful invoices</li>
                    <li>✅ Professional PDF output with proper formatting</li>
                    <li>✅ Handles complex layouts and styling</li>
                    <li>✅ Automatic font rendering and text formatting</li>
                    <li>✅ Secure - no remote resources required</li>
                </ul>
            </div>
            
            <?php if (!$dompdf_available): ?>
                <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #856404; font-size: 14px;">Missing Files Detected:</h4>
                    <?php 
                    $missing_files = $pdf_status['dompdf']['missing_files'] ?? array();
                    if (!empty($missing_files)): 
                    ?>
                        <?php foreach ($missing_files as $file): ?>
                            <div style="font-family: monospace; font-size: 12px; color: #856404; margin-bottom: 5px;">
                                ❌ <?php echo esc_html($file); ?>
                            </div>
                        <?php endforeach; ?>
                        <div style="margin-top: 10px; padding: 10px; background: #fff; border-radius: 3px; font-size: 12px;">
                            <strong>Expected location:</strong> 
                            <code><?php echo esc_html(WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/'); ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <strong style="color: <?php echo $dompdf_available ? '#28a745' : '#dc3545'; ?>;">
                        Status: <?php echo $dompdf_available ? 'Ready' : 'Missing Files'; ?>
                    </strong>
                    <?php if ($dompdf_available): ?>
                        <br><small style="color: #666;">Version: <?php echo esc_html($pdf_status['dompdf']['version']); ?></small>
                    <?php endif; ?>
                </div>
                
                <?php if ($dompdf_available): ?>
                    <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Working', 'wc-manual-invoices'); ?>
                    </span>
                <?php else: ?>
                    <div style="color: #dc3545; font-size: 12px; text-align: right;">
                        <strong>Action Required:</strong><br>
                        <small>Reinstall plugin to restore DomPDF</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if (!$dompdf_available): ?>
    <!-- Troubleshooting Guide -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px; border-left: 4px solid #dc3545;">
        <h2 style="margin-top: 0; color: #dc3545; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-sos"></span>
            <?php _e('Troubleshooting Guide', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #dc3545;">📋 How to Fix DomPDF Issues:</h3>
            
            <div style="counter-reset: step-counter; padding-left: 0;">
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #dc3545; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">1</div>
                    <div>
                        <strong>Re-download the Plugin</strong><br>
                        <span style="color: #666;">Download a fresh copy of the plugin from your original source</span>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #dc3545; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">2</div>
                    <div>
                        <strong>Deactivate & Delete Current Plugin</strong><br>
                        <span style="color: #666;">Go to Plugins → Deactivate → Delete (your settings will be preserved)</span>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #dc3545; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">3</div>
                    <div>
                        <strong>Install Fresh Copy</strong><br>
                        <span style="color: #666;">Upload and activate the new plugin files</span>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #dc3545; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">4</div>
                    <div>
                        <strong>Verify File Permissions</strong><br>
                        <span style="color: #666;">Ensure lib/dompdf/ folder has 755 permissions</span>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 0; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #28a745; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">✓</div>
                    <div>
                        <strong>Test PDF Generation</strong><br>
                        <span style="color: #666;">Return to this page and click "Test PDF" button</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Alternative Solution -->
        <div style="background: #e7f3ff; border: 1px solid #bee5eb; padding: 15px; border-radius: 6px;">
            <h4 style="margin-top: 0; color: #0c5460;">🔧 Still Having Issues?</h4>
            <p style="margin-bottom: 10px; color: #0c5460; font-size: 14px;">
                Don't worry! Even without DomPDF, your invoices will still work perfectly:
            </p>
            <ul style="margin: 0; padding-left: 20px; color: #0c5460; font-size: 13px;">
                <li>Invoices will be generated as professionally formatted text files</li>
                <li>All invoice data, payment links, and customer details included</li>
                <li>Customers can still pay online normally</li>
                <li>Text format is actually preferred by some accounting systems</li>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Quick Actions -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-performance"></span>
            <?php _e('Quick Actions', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <!-- Test PDF Generation -->
            <?php if ($dompdf_available): ?>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('test_pdf_generation'); ?>
                    <button type="submit" name="test_pdf" class="button button-large" style="background: #0073aa; color: white; border: none; padding: 12px 20px;">
                        <span class="dashicons dashicons-media-document" style="margin-right: 8px;"></span>
                        <?php _e('Test PDF Generation', 'wc-manual-invoices'); ?>
                    </button>
                </form>
            <?php endif; ?>
            
            <!-- Create Test Invoice -->
            <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices&tab=create'); ?>" class="button button-large" style="padding: 12px 20px;">
                <span class="dashicons dashicons-plus-alt" style="margin-right: 8px;"></span>
                <?php _e('Create Test Invoice', 'wc-manual-invoices'); ?>
            </a>
            
            <!-- Refresh Status -->
            <button onclick="location.reload()" class="button button-large" style="padding: 12px 20px;">
                <span class="dashicons dashicons-update" style="margin-right: 8px;"></span>
                <?php _e('Refresh Status', 'wc-manual-invoices'); ?>
            </button>
            
            <!-- View Diagnostics -->
            <button onclick="document.getElementById('diagnostics-details').style.display = document.getElementById('diagnostics-details').style.display === 'none' ? 'block' : 'none';" class="button button-large" style="padding: 12px 20px;">
                <span class="dashicons dashicons-admin-tools" style="margin-right: 8px;"></span>
                <?php _e('View Diagnostics', 'wc-manual-invoices'); ?>
            </button>
        </div>
    </div>
    
    <!-- System Diagnostics (Hidden by default) -->
    <div id="diagnostics-details" style="display: none; background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('System Diagnostics', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h4 style="margin-top: 0; color: #333;">Server Environment</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li><strong>PHP Version:</strong> <?php echo esc_html($diagnostics['php_version']); ?></li>
                    <li><strong>WordPress:</strong> <?php echo esc_html($diagnostics['wordpress_version']); ?></li>
                    <li><strong>WooCommerce:</strong> <?php echo esc_html($diagnostics['woocommerce_version']); ?></li>
                    <li><strong>Memory Limit:</strong> <?php echo esc_html($diagnostics['memory_limit']); ?></li>
                    <li><strong>Max Execution:</strong> <?php echo esc_html($diagnostics['max_execution_time']); ?>s</li>
                    <li><strong>Upload Dir Writable:</strong> <?php echo $diagnostics['upload_dir_writable'] ? '✅ Yes' : '❌ No'; ?></li>
                </ul>
            </div>
            
            <div>
                <h4 style="margin-top: 0; color: #333;">DomPDF File Status</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <?php foreach ($diagnostics['dompdf_files'] as $file => $info): ?>
                        <li style="margin-bottom: 5px;">
                            <strong><?php echo esc_html(basename($file)); ?>:</strong>
                            <?php if ($info['exists']): ?>
                                <span style="color: #28a745;">✅ Found (<?php echo size_format($info['size']); ?>)</span>
                            <?php else: ?>
                                <span style="color: #dc3545;">❌ Missing</span>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px; font-family: monospace; font-size: 12px;">
            <strong>Plugin Path:</strong> <?php echo esc_html($diagnostics['plugin_path']); ?><br>
            <strong>Expected DomPDF:</strong> <?php echo esc_html($diagnostics['plugin_path'] . 'lib/dompdf/'); ?>
        </div>
    </div>
    
    <!-- Help & Support -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-sos"></span>
            <?php _e('Need Help?', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- Quick Tips -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #28a745;">
                <h3 style="margin-top: 0; color: #28a745; font-size: 16px;">
                    <span class="dashicons dashicons-lightbulb" style="margin-right: 5px;"></span>
                    Quick Tips
                </h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li>DomPDF is pre-installed and should work immediately</li>
                    <li>Text fallback ensures invoices always work</li>
                    <li>No external dependencies or composer required</li>
                    <li>All invoice features work regardless of PDF status</li>
                    <li>Customer payment links always function normally</li>
                </ul>
            </div>
            
            <!-- Common Issues -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #ffc107;">
                <h3 style="margin-top: 0; color: #856404; font-size: 16px;">
                    <span class="dashicons dashicons-warning" style="margin-right: 5px;"></span>
                    Common Issues
                </h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li>Incomplete plugin upload during installation</li>
                    <li>File permission issues on shared hosting</li>
                    <li>Old cached plugin files conflicting</li>
                    <li>Server-side file extraction problems</li>
                    <li>WordPress file modification restrictions</li>
                </ul>
            </div>
            
            <!-- Support -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #96588a;">
                <h3 style="margin-top: 0; color: #96588a; font-size: 16px;">
                    <span class="dashicons dashicons-phone" style="margin-right: 5px;"></span>
                    Get Support
                </h3>
                <p style="margin: 0 0 15px 0; font-size: 14px; color: #555;">
                    Professional support for WooCommerce Manual Invoices Pro
                </p>
                <div style="font-size: 13px;">
                    <p style="margin: 0 0 10px 0;">
                        <strong>Email Support:</strong><br>
                        <a href="mailto:support@wbcomdesigns.com" style="color: #96588a; text-decoration: none;">
                            📧 support@wbcomdesigns.com
                        </a>
                    </p>
                    <p style="margin: 0;">
                        <strong>Include in your message:</strong><br>
                        <span style="font-size: 12px; color: #666;">
                            • PHP version: <?php echo esc_html($diagnostics['php_version']); ?><br>
                            • WordPress: <?php echo esc_html($diagnostics['wordpress_version']); ?><br>
                            • Plugin version: <?php echo WC_MANUAL_INVOICES_VERSION; ?>
                        </span>
                    </p>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Plugin Info Footer -->
    <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #96588a, #7e4874); color: white; border-radius: 8px; text-align: center;">
        <h3 style="margin-top: 0; color: white;">
            <span class="dashicons dashicons-heart" style="margin-right: 5px;"></span>
            WooCommerce Manual Invoices Pro
        </h3>
        <p style="margin-bottom: 10px; opacity: 0.9; font-size: 14px;">
            Professional invoice management with reliable PDF generation
        </p>
        <p style="margin-bottom: 0; font-size: 12px; opacity: 0.8;">
            Made with ❤️ by Wbcom Designs | Version <?php echo WC_MANUAL_INVOICES_VERSION; ?> | 
            DomPDF: <?php echo $dompdf_available ? '✅ Ready' : '⚠️ Issue'; ?> | 
            Fallback: ✅ Always Available
        </p>
    </div>
</div>

<style>
.button:hover {
    transform: translateY(-1px);
    transition: all 0.2s ease;
}

.button:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

@media (max-width: 768px) {
    .wrap > div {
        margin-left: 0;
        margin-right: 0;
    }
    
    div[style*="grid-template-columns"] {
        grid-template-columns: 1fr !important;
    }
    
    .header-content {
        flex-direction: column;
        text-align: center;
        gap: 20px;
    }
    
    .header-actions {
        width: 100%;
        justify-content: center;
    }
}

/* Loading animation */
@keyframes rotation {
    from { transform: rotate(0deg); }
    to { transform: rotate(359deg); }
}

.button .dashicons.spin {
    animation: rotation 1s infinite linear;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Add loading states to buttons
    $('button[type="submit"]').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        var buttonText = $button.text().trim();
        
        // Don't show loading for refresh button
        if (buttonText.indexOf('Refresh') !== -1) {
            return;
        }
        
        $button.prop('disabled', true);
        
        // Find the dashicon and make it spin
        var $icon = $button.find('.dashicons');
        if ($icon.length) {
            $icon.addClass('spin');
        }
        
        // Add "Processing..." text
        if (buttonText.indexOf('Test') !== -1) {
            $button.find('span:not(.dashicons)').text('Generating...');
        }
        
        // Re-enable after form submission (backup)
        setTimeout(function() {
            $button.prop('disabled', false).html(originalText);
        }, 15000); // 15 seconds timeout
    });
    
    // Highlight important paths in diagnostics
    $('code').each(function() {
        var text = $(this).text();
        if (text.indexOf('lib/dompdf') !== -1) {
            $(this).css({
                'background': '#fff3cd',
                'border': '1px solid #ffeaa7',
                'font-weight': 'bold'
            });
        }
    });
});
</script>