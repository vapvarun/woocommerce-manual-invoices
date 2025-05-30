<?php
/**
 * Simplified PDF Settings Page Template
 * 
 * Focuses on bundled DomPDF with optional TCPDF alternative
 * Save as: templates/admin-pdf-settings-simple.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get PDF library status
$pdf_status = WC_Manual_Invoice_PDF_Manager::get_library_status();
$available_library = WC_Manual_Invoice_PDF_Manager::get_available_library();

// Handle TCPDF installation
if (isset($_POST['install_tcpdf']) && wp_verify_nonce($_POST['_wpnonce'], 'install_tcpdf')) {
    $result = WC_Manual_Invoice_PDF_Manager::install_tcpdf();
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p><strong>Installation Failed:</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Success!</strong> ' . esc_html($result['message']) . '</p></div>';
    }
}

// Handle test PDF generation
if (isset($_POST['test_pdf']) && wp_verify_nonce($_POST['_wpnonce'], 'test_pdf_generation')) {
    $test_result = WC_Manual_Invoice_PDF_Manager::test_pdf_generation();
    if (is_wp_error($test_result)) {
        echo '<div class="notice notice-error"><p><strong>Test Failed:</strong> ' . esc_html($test_result->get_error_message()) . '</p></div>';
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
                            <?php _e('No PDF library found. Invoices will be generated as formatted text files.', 'wc-manual-invoices'); ?>
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
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: <?php echo $pdf_status['dompdf']['available'] ? '#28a745' : '#dc3545'; ?>;">
                            Status: <?php echo $pdf_status['dompdf']['available'] ? 'Ready' : 'Not Found'; ?>
                        </strong>
                    </div>
                    
                    <?php if ($pdf_status['dompdf']['available']): ?>
                        <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Working', 'wc-manual-invoices'); ?>
                        </span>
                    <?php else: ?>
                        <div style="color: #dc3545; font-size: 12px;">
                            <strong>Issue:</strong> Library files missing from lib/dompdf/
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- TCPDF (Optional) -->
            <div style="border: 2px solid <?php echo $pdf_status['tcpdf']['available'] ? '#28a745' : '#6c757d'; ?>; border-radius: 8px; padding: 20px; position: relative; <?php echo !$pdf_status['tcpdf']['available'] ? 'opacity: 0.8;' : ''; ?>">
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
                    <h4 style="margin-top: 0; margin-bottom: 10px; color: #6c757d; font-size: 14px;">When to use:</h4>
                    <ul style="margin: 0; padding-left: 20px; font-size: 13px; color: #555;">
                        <li>🔧 Need complex Unicode support</li>
                        <li>🔧 Require specific PDF features</li>
                        <li>🔧 DomPDF doesn't meet your needs</li>
                        <li>🔧 Advanced table layouts</li>
                    </ul>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <strong style="color: <?php echo $pdf_status['tcpdf']['available'] ? '#28a745' : '#6c757d'; ?>;">
                            Status: <?php echo $pdf_status['tcpdf']['available'] ? 'Installed' : 'Not Installed'; ?>
                        </strong>
                    </div>
                    
                    <?php if (!$pdf_status['tcpdf']['available']): ?>
                        <form method="post" style="display: inline;">
                            <?php wp_nonce_field('install_tcpdf'); ?>
                            <button type="submit" name="install_tcpdf" class="button" style="background: #6c757d; color: white; border: none;">
                                <span class="dashicons dashicons-download" style="margin-right: 5px;"></span>
                                <?php _e('Install TCPDF', 'wc-manual-invoices'); ?>
                            </button>
                        </form>
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
        </div>
    </div>
    
    <!-- Help & Support -->
    <div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-sos"></span>
            <?php _e('Need Help?', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            
            <!-- Troubleshooting -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #17a2b8;">
                <h3 style="margin-top: 0; color: #17a2b8; font-size: 16px;">
                    <span class="dashicons dashicons-admin-tools" style="margin-right: 5px;"></span>
                    Troubleshooting
                </h3>
                <ul style="margin: 0; padding-left: 20px; font-size: 14px; color: #555;">
                    <li>DomPDF should work out of the box - it's bundled with the plugin</li>
                    <li>If DomPDF shows as "Not Found", check that lib/dompdf/ folder exists</li>
                    <li>Increase PHP memory limit to 256MB+ for large invoices</li>
                    <li>Set max execution time to 120+ seconds</li>
                </ul>
            </div>
            
            <!-- Manual Setup -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #28a745;">
                <h3 style="margin-top: 0; color: #28a745; font-size: 16px;">
                    <span class="dashicons dashicons-admin-users" style="margin-right: 5px;"></span>
                    Manual Installation
                </h3>
                <p style="margin: 0; font-size: 14px; color: #555;">
                    DomPDF should be bundled with your plugin download. If it's missing:
                </p>
                <ol style="margin: 10px 0 0 20px; padding: 0; font-size: 13px; color: #555;">
                    <li>Download DomPDF from <a href="https://github.com/dompdf/dompdf/releases" target="_blank">GitHub</a></li>
                    <li>Extract to <code>/wp-content/plugins/woocommerce-manual-invoices/lib/dompdf/</code></li>
                    <li>Ensure autoload.inc.php file exists</li>
                </ol>
            </div>
            
            <!-- Support -->
            <div style="background: #f8f9fa; padding: 20px; border-radius: 6px; border-left: 4px solid #96588a;">
                <h3 style="margin-top: 0; color: #96588a; font-size: 16px;">
                    <span class="dashicons dashicons-phone" style="margin-right: 5px;"></span>
                    Get Support
                </h3>
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #555;">
                    Need help with PDF generation? We're here to help!
                </p>
                <p style="margin: 0; font-size: 13px;">
                    <a href="mailto:support@wbcomdesigns.com" style="color: #96588a; text-decoration: none;">
                        📧 support@wbcomdesigns.com
                    </a>
                </p>
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
            Professional invoice management with bundled PDF generation
        </p>
        <p style="margin-bottom: 0; font-size: 12px; opacity: 0.8;">
            Made with ❤️ by Wbcom Designs | Version <?php echo WC_MANUAL_INVOICES_VERSION; ?>
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
</style>

<script>
jQuery(document).ready(function($) {
    // Add loading states to buttons
    $('button[type="submit"]').on('click', function() {
        var $button = $(this);
        var originalText = $button.html();
        
        $button.prop('disabled', true)
               .html('<span class="dashicons dashicons-update" style="animation: rotation 1s infinite linear; margin-right: 5px;"></span>Processing...');
        
        // Re-enable after form submission (backup)
        setTimeout(function() {
            $button.prop('disabled', false).html(originalText);
        }, 10000);
    });
    
    // Add CSS for loading animation
    $('<style>@keyframes rotation { from { transform: rotate(0deg); } to { transform: rotate(359deg); } }</style>').appendTo('head');
});
</script>