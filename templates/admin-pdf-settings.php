<?php
/**
 * Enhanced PDF Settings Page Template with Manual Installation Support
 * 
 * Replaces: templates/admin-pdf-settings.php
 * Provides clear instructions for manual TCPDF installation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get PDF library status
$pdf_status = WC_Manual_Invoice_PDF_Installer::get_library_status();
$available_library = WC_Manual_Invoice_PDF_Installer::get_best_available_library();

// Handle TCPDF installation attempt
if (isset($_POST['install_tcpdf']) && wp_verify_nonce($_POST['_wpnonce'], 'install_tcpdf')) {
    $result = WC_Manual_Invoice_PDF_Installer::install_tcpdf_automatically();
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p><strong>Installation Failed:</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Success!</strong> ' . esc_html($result['message']) . '</p></div>';
        // Refresh status after installation
        $pdf_status = WC_Manual_Invoice_PDF_Installer::get_library_status();
        $available_library = WC_Manual_Invoice_PDF_Installer::get_best_available_library();
    }
}

// Handle test PDF generation
if (isset($_POST['test_pdf']) && wp_verify_nonce($_POST['_wpnonce'], 'test_pdf_generation')) {
    $test_result = WC_Manual_Invoice_PDF_Installer::test_pdf_generation();
    if (is_wp_error($test_result)) {
        echo '<div class="notice notice-error"><p><strong>Test Failed:</strong> ' . esc_html($test_result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Test Successful!</strong> PDF generated using ' . esc_html(strtoupper($test_result['library'])) . '. <a href="' . esc_url($test_result['download_url']) . '" target="_blank">Download Test PDF</a></p></div>';
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
                    <?php _e('PDF Generation Settings', 'wc-manual-invoices'); ?>
                </h1>
                <p class="header-subtitle">
                    <?php _e('Manage PDF libraries for professional invoice generation', 'wc-manual-invoices'); ?>
                </p>
            </div>
            <div class="header-actions">
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('test_pdf_generation'); ?>
                    <button type="submit" name="test_pdf" class="btn-header">
                        <span class="dashicons dashicons-media-document" style="margin-right: 6px;"></span>
                        <?php _e('Test PDF', 'wc-manual-invoices'); ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Current Status -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-info"></span>
            <?php _e('Current PDF Status', 'wc-manual-invoices'); ?>
        </h2>
        
        <?php if ($available_library): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 24px;"></span>
                    <div>
                        <h3 style="margin: 0 0 5px 0; font-size: 18px;">
                            <?php _e('✅ PDF Generation Active', 'wc-manual-invoices'); ?>
                        </h3>
                        <p style="margin: 0; font-size: 14px;">
                            <?php printf(__('Using %s library for professional PDF invoice generation.', 'wc-manual-invoices'), '<strong>' . $pdf_status[$available_library]['name'] . '</strong>'); ?>
                        </p>
                        <p style="margin: 5px 0 0 0; font-size: 13px; opacity: 0.8;">
                            <?php printf(__('Version: %s', 'wc-manual-invoices'), $pdf_status[$available_library]['version']); ?>
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
                            <?php _e('⚠️ No PDF Library Available', 'wc-manual-invoices'); ?>
                        </h3>
                        <p style="margin: 0; font-size: 14px;">
                            <?php _e('Invoices will be generated as formatted text files. Install a PDF library for professional invoices.', 'wc-manual-invoices'); ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- PDF Libraries -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('PDF Libraries', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
            
            <!-- DomPDF (Bundled) -->
            <div style="border: 2px solid <?php echo $pdf_status['dompdf']['available'] ? '#28a745' : '#dc3545'; ?>; border-radius: 8px; padding: 20px; position: relative;">
                <?php if ($pdf_status['dompdf']['available']): ?>
                    <div style="position: absolute; top: -10px; right: 15px; background: #28a745; color: white; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                        ✓ ACTIVE
                    </div>
                <?php endif; ?>
                
                <h3 style="margin-top: 0; color: #333; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-star-filled" style="color: #ffd700;"></span>
                    DomPDF (Bundled)
                    <span style="background: #96588a; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">RECOMMENDED</span>
                </h3>
                
                <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                    <?php echo esc_html($pdf_status['dompdf']['description']); ?>
                </p>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #96588a; font-size: 14px;">Features:</h4>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555;">
                        <li>✅ Bundled with plugin - no installation needed</li>
                        <li>✅ Excellent HTML/CSS support</li>
                        <li>✅ Professional PDF output</li>
                        <li>✅ Handles images and complex layouts</li>
                        <li>✅ Perfect for most use cases</li>
                    </ul>
                </div>
                
                <?php if (!$pdf_status['dompdf']['available']): ?>
                    <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; color: #856404; font-size: 14px;">Missing Files:</h4>
                        <?php foreach ($pdf_status['dompdf']['missing_files'] as $file): ?>
                            <div style="font-family: monospace; font-size: 12px; color: #856404;">❌ <?php echo esc_html($file); ?></div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: <?php echo $pdf_status['dompdf']['available'] ? '#28a745' : '#dc3545'; ?>;">
                            Status: <?php echo $pdf_status['dompdf']['available'] ? 'Ready' : 'Missing Files'; ?>
                        </strong>
                        <?php if ($pdf_status['dompdf']['available']): ?>
                            <br><small style="color: #666;">Version: <?php echo esc_html($pdf_status['dompdf']['version']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($pdf_status['dompdf']['available']): ?>
                        <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Working', 'wc-manual-invoices'); ?>
                        </span>
                    <?php else: ?>
                        <div style="color: #dc3545; font-size: 12px; text-align: right;">
                            <strong>Issue:</strong> DomPDF files missing<br>
                            <small>Contact support for assistance</small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- TCPDF (Manual Installation) -->
            <div style="border: 2px solid <?php echo $pdf_status['tcpdf']['available'] ? '#28a745' : '#6c757d'; ?>; border-radius: 8px; padding: 20px; position: relative;">
                <?php if ($pdf_status['tcpdf']['available']): ?>
                    <div style="position: absolute; top: -10px; right: 15px; background: #28a745; color: white; padding: 5px 10px; border-radius: 12px; font-size: 12px; font-weight: bold;">
                        ✓ INSTALLED
                    </div>
                <?php endif; ?>
                
                <h3 style="margin-top: 0; color: #333; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-admin-tools" style="color: #6c757d;"></span>
                    TCPDF (Alternative)
                    <span style="background: #6c757d; color: white; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: bold;">OPTIONAL</span>
                </h3>
                
                <p style="color: #666; margin-bottom: 15px; font-size: 14px;">
                    <?php echo esc_html($pdf_status['tcpdf']['description']); ?>
                </p>
                
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #6c757d; font-size: 14px;">When to use TCPDF:</h4>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555;">
                        <li>🔧 Need complex Unicode support</li>
                        <li>🔧 Require specific PDF features</li>
                        <li>🔧 DomPDF doesn't meet your needs</li>
                        <li>🔧 Advanced table layouts</li>
                    </ul>
                </div>
                
                <?php if (!$pdf_status['tcpdf']['available']): ?>
                    <div style="background: #e7f3ff; border: 1px solid #bee5eb; padding: 15px; border-radius: 5px; margin-bottom: 15px;">
                        <h4 style="margin-top: 0; margin-bottom: 10px; color: #0c5460; font-size: 14px;">📁 Manual Installation Required:</h4>
                        <div style="font-size: 13px; color: #0c5460;">
                            <strong>Installation Path:</strong><br>
                            <code style="background: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; color: #333;">
                                <?php echo esc_html(WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/'); ?>
                            </code>
                        </div>
                    </div>
                <?php endif; ?>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: <?php echo $pdf_status['tcpdf']['available'] ? '#28a745' : '#6c757d'; ?>;">
                            Status: <?php echo $pdf_status['tcpdf']['available'] ? 'Installed' : 'Not Installed'; ?>
                        </strong>
                        <?php if ($pdf_status['tcpdf']['available']): ?>
                            <br><small style="color: #666;">Version: <?php echo esc_html($pdf_status['tcpdf']['version']); ?></small>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!$pdf_status['tcpdf']['available']): ?>
                        <div style="display: flex; gap: 8px;">
                            <form method="post" style="display: inline;">
                                <?php wp_nonce_field('install_tcpdf'); ?>
                                <button type="submit" name="install_tcpdf" class="button" style="background: #0073aa; color: white; border: none; font-size: 12px;">
                                    <span class="dashicons dashicons-download" style="margin-right: 4px; font-size: 14px;"></span>
                                    <?php _e('Try Auto Install', 'wc-manual-invoices'); ?>
                                </button>
                            </form>
                        </div>
                    <?php else: ?>
                        <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Ready', 'wc-manual-invoices'); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Manual Installation Instructions -->
    <?php if (!$pdf_status['tcpdf']['available']): ?>
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px; border-left: 4px solid #0073aa;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-page"></span>
            <?php _e('Manual TCPDF Installation Guide', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; margin-bottom: 20px;">
            <h3 style="margin-top: 0; color: #0073aa;">📋 Step-by-Step Instructions:</h3>
            
            <div style="counter-reset: step-counter; padding-left: 0;">
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #0073aa; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">1</div>
                    <div>
                        <strong>Download TCPDF</strong><br>
                        <span style="color: #666;">Go to: </span>
                        <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" target="_blank" style="color: #0073aa; text-decoration: none;">
                            https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip
                        </a><br>
                        <small style="color: #666;">This will download a ZIP file named "TCPDF-main.zip"</small>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #0073aa; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">2</div>
                    <div>
                        <strong>Extract the ZIP file</strong><br>
                        <span style="color: #666;">Extract the downloaded ZIP to get the "TCPDF-main" folder</span>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #0073aa; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">3</div>
                    <div>
                        <strong>Rename the folder</strong><br>
                        <span style="color: #666;">Rename "TCPDF-main" to "tcpdf" (all lowercase)</span>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #0073aa; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">4</div>
                    <div>
                        <strong>Upload to your server</strong><br>
                        <span style="color: #666;">Upload the "tcpdf" folder to:</span><br>
                        <code style="background: #fff; padding: 4px 8px; border-radius: 4px; color: #333; font-size: 12px; word-break: break-all;">
                            <?php echo esc_html(WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/'); ?>
                        </code><br>
                        <small style="color: #666;">Use FTP, cPanel File Manager, or your hosting control panel</small>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #0073aa; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">5</div>
                    <div>
                        <strong>Verify the installation</strong><br>
                        <span style="color: #666;">The main file should be located at:</span><br>
                        <code style="background: #fff; padding: 4px 8px; border-radius: 4px; color: #333; font-size: 12px; word-break: break-all;">
                            <?php echo esc_html(WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/tcpdf.php'); ?>
                        </code>
                    </div>
                </div>
                
                <div style="counter-increment: step-counter; margin-bottom: 0; display: flex; align-items: flex-start; gap: 15px;">
                    <div style="background: #28a745; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; font-weight: bold; flex-shrink: 0;">✓</div>
                    <div>
                        <strong>Test the installation</strong><br>
                        <span style="color: #666;">Refresh this page and click "Test PDF" to verify TCPDF is working</span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- File Structure Example -->
        <div style="background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 6px;">
            <h4 style="margin-top: 0; color: #333;">📂 Expected File Structure:</h4>
            <pre style="background: #f8f9fa; padding: 10px; border-radius: 4px; font-size: 12px; color: #333; margin: 0; overflow-x: auto;">lib/
├── dompdf/ (bundled)
│   ├── autoload.inc.php
│   └── ...
└── tcpdf/ (manual install)
    ├── tcpdf.php ← Main file
    ├── config/
    ├── fonts/
    ├── include/
    └── examples/</pre>
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
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('test_pdf_generation'); ?>
                <button type="submit" name="test_pdf" class="button button-large" style="background: #0073aa; color: white; border: none; padding: 12px 20px;">
                    <span class="dashicons dashicons-media-document" style="margin-right: 8px;"></span>
                    <?php _e('Test PDF Generation', 'wc-manual-invoices'); ?>
                </button>
            </form>
            
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
            
            <!-- Download TCPDF -->
            <?php if (!$pdf_status['tcpdf']['available']): ?>
            <a href="https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip" target="_blank" class="button button-large" style="background: #28a745; color: white; border: none; padding: 12px 20px;">
                <span class="dashicons dashicons-download" style="margin-right: 8px;"></span>
                <?php _e('Download TCPDF', 'wc-manual-invoices'); ?>
            </a>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- System Information -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('System Information', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div>
                <h4 style="margin-top: 0; color: #333;">Server Environment</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li><strong>PHP Version:</strong> <?php echo PHP_VERSION; ?></li>
                    <li><strong>WordPress:</strong> <?php echo get_bloginfo('version'); ?></li>
                    <li><strong>WooCommerce:</strong> <?php echo defined('WC_VERSION') ? WC_VERSION : 'Not detected'; ?></li>
                    <li><strong>Memory Limit:</strong> <?php echo ini_get('memory_limit'); ?></li>
                    <li><strong>Max Execution:</strong> <?php echo ini_get('max_execution_time'); ?>s</li>
                </ul>
            </div>
            
            <div>
                <h4 style="margin-top: 0; color: #333;">PHP Extensions</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li><strong>ZipArchive:</strong> <?php echo class_exists('ZipArchive') ? '✅ Available' : '❌ Missing'; ?></li>
                    <li><strong>cURL:</strong> <?php echo extension_loaded('curl') ? '✅ Available' : '❌ Missing'; ?></li>
                    <li><strong>GD:</strong> <?php echo extension_loaded('gd') ? '✅ Available' : '❌ Missing'; ?></li>
                    <li><strong>mbstring:</strong> <?php echo extension_loaded('mbstring') ? '✅ Available' : '❌ Missing'; ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Help & Support -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-sos"></span>
            <?php _e('Need Help?', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- Common Issues -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #ffc107;">
                <h3 style="margin-top: 0; color: #856404; font-size: 16px;">
                    <span class="dashicons dashicons-warning" style="margin-right: 5px;"></span>
                    Common Issues
                </h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li>DomPDF should work out of the box - if not, the plugin files may be incomplete</li>
                    <li>TCPDF requires manual installation - follow the step-by-step guide above</li>
                    <li>Increase PHP memory limit to 256MB+ for large invoices</li>
                    <li>Set max execution time to 120+ seconds for PDF generation</li>
                    <li>Ensure proper file permissions on the lib/ directory</li>
                </ul>
            </div>
            
            <!-- Troubleshooting -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #17a2b8;">
                <h3 style="margin-top: 0; color: #17a2b8; font-size: 16px;">
                    <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                    Troubleshooting
                </h3>
                <div style="font-size: 14px; color: #555;">
                    <p><strong>If automatic TCPDF installation fails:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Server may not allow automatic downloads</li>
                        <li>File permissions may be restricted</li>
                        <li>Use the manual installation method instead</li>
                    </ul>
                    
                    <p><strong>If PDF generation fails:</strong></p>
                    <ul style="margin: 10px 0; padding-left: 20px;">
                        <li>Check PHP error logs for specific errors</li>
                        <li>Verify file paths and permissions</li>
                        <li>Test with the "Test PDF" button above</li>
                    </ul>
                </div>
            </div>
            
            <!-- Support -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #96588a;">
                <h3 style="margin-top: 0; color: #96588a; font-size: 16px;">
                    <span class="dashicons dashicons-phone" style="margin-right: 5px;"></span>
                    Get Support
                </h3>
                <p style="margin: 0 0 15px 0; font-size: 14px; color: #555;">
                    Need help with PDF generation? We're here to help!
                </p>
                <div style="font-size: 13px;">
                    <p style="margin: 0 0 10px 0;">
                        <strong>Email Support:</strong><br>
                        <a href="mailto:support@wbcomdesigns.com" style="color: #96588a; text-decoration: none;">
                            📧 support@wbcomdesigns.com
                        </a>
                    </p>
                    <p style="margin: 0;">
                        <strong>Documentation:</strong><br>
                        <a href="#" style="color: #96588a; text-decoration: none;">
                            📚 View complete setup guide
                        </a>
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
            Professional invoice management with flexible PDF generation options
        </p>
        <p style="margin-bottom: 0; font-size: 12px; opacity: 0.8;">
            Made with ❤️ by Wbcom Designs | Version <?php echo WC_MANUAL_INVOICES_VERSION; ?> | 
            DomPDF: <?php echo $pdf_status['dompdf']['available'] ? '✅' : '❌'; ?> | 
            TCPDF: <?php echo $pdf_status['tcpdf']['available'] ? '✅' : '❌'; ?>
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
        } else if (buttonText.indexOf('Install') !== -1) {
            $button.find('span:not(.dashicons)').text('Installing...');
        }
        
        // Re-enable after form submission (backup)
        setTimeout(function() {
            $button.prop('disabled', false).html(originalText);
        }, 15000); // 15 seconds timeout
    });
    
    // Add tooltips for buttons
    $('[title]').each(function() {
        $(this).on('mouseenter', function() {
            var title = $(this).attr('title');
            var $tooltip = $('<div class="custom-tooltip">' + title + '</div>');
            $('body').append($tooltip);
            
            var offset = $(this).offset();
            $tooltip.css({
                position: 'absolute',
                top: offset.top - $tooltip.outerHeight() - 10,
                left: offset.left + ($(this).outerWidth() / 2) - ($tooltip.outerWidth() / 2),
                background: '#333',
                color: '#fff',
                padding: '8px 12px',
                borderRadius: '4px',
                fontSize: '12px',
                zIndex: 9999,
                whiteSpace: 'nowrap'
            });
        });
        
        $(this).on('mouseleave', function() {
            $('.custom-tooltip').remove();
        });
    });
    
    // Highlight important paths
    $('code').each(function() {
        var text = $(this).text();
        if (text.indexOf('lib/tcpdf') !== -1 || text.indexOf('tcpdf.php') !== -1) {
            $(this).css({
                'background': '#fff3cd',
                'border': '1px solid #ffeaa7',
                'font-weight': 'bold'
            });
        }
    });
    
    // Auto-scroll to installation guide if TCPDF is not available
    <?php if (!$pdf_status['tcpdf']['available']): ?>
    var $installGuide = $('h2:contains("Manual TCPDF Installation Guide")').parent();
    if ($installGuide.length) {
        // Add a subtle highlight animation
        $installGuide.css({
            'animation': 'highlight-pulse 3s ease-in-out',
            'border': '2px solid #0073aa'
        });
    }
    
    // Add CSS for highlight animation
    if (!$('#highlight-animation').length) {
        $('head').append(`
            <style id="highlight-animation">
                @keyframes highlight-pulse {
                    0%, 100% { border-color: #0073aa; }
                    50% { border-color: #00a0d2; box-shadow: 0 0 20px rgba(0, 115, 170, 0.3); }
                }
            </style>
        `);
    }
    <?php endif; ?>
});
</script>