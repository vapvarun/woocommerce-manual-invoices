<?php
/**
 * PDF Settings and Library Status Page Template
 * 
 * Template for managing PDF libraries and settings
 * Save as: templates/admin-pdf-settings.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get PDF library status
$pdf_status = WC_Manual_Invoice_PDF::get_pdf_library_status();
$available_library = false;

foreach ($pdf_status as $library => $info) {
    if ($info['available']) {
        $available_library = $library;
        break;
    }
}

// Handle library installation
if (isset($_POST['install_dompdf']) && wp_verify_nonce($_POST['_wpnonce'], 'install_pdf_library')) {
    $result = WC_Manual_Invoice_PDF_Installer::install_dompdf('auto');
    if (is_wp_error($result)) {
        echo '<div class="notice notice-error"><p><strong>Installation Failed:</strong> ' . esc_html($result->get_error_message()) . '</p></div>';
    } elseif (isset($result['success']) && $result['success']) {
        echo '<div class="notice notice-success"><p><strong>Success!</strong> ' . esc_html($result['message']) . ' Please refresh the page to see the updated status.</p></div>';
    } elseif (isset($result['manual'])) {
        // Manual instructions will be shown below
        echo '<div class="notice notice-info"><p><strong>Manual Installation Required:</strong> Please follow the instructions below.</p></div>';
    }
}

// Handle test PDF generation
if (isset($_POST['test_pdf']) && wp_verify_nonce($_POST['_wpnonce'], 'test_pdf_generation')) {
    $test_result = self::generate_test_pdf();
    if (is_wp_error($test_result)) {
        echo '<div class="notice notice-error"><p><strong>Test Failed:</strong> ' . esc_html($test_result->get_error_message()) . '</p></div>';
    } else {
        echo '<div class="notice notice-success"><p><strong>Test Successful!</strong> PDF generated successfully. <a href="' . esc_url($test_result['url']) . '" target="_blank">Download Test PDF</a></p></div>';
    }
}

// Get system information
$system_info = WC_Manual_Invoice_PDF_Installer::get_system_info();
$available_methods = WC_Manual_Invoice_PDF_Installer::get_available_methods();
$recommendations = WC_Manual_Invoice_PDF_Installer::get_recommendations();
?>

<div class="wrap wc-manual-invoices-wrap">
    <!-- Header Section -->
    <div class="wc-manual-invoices-header">
        <div class="header-content">
            <div class="header-text">
                <h1 class="header-title">
                    <span class="dashicons dashicons-pdf" style="margin-right: 10px;"></span>
                    <?php _e('PDF Settings & Library Management', 'wc-manual-invoices'); ?>
                </h1>
                <p class="header-subtitle">
                    <?php _e('Configure PDF generation libraries and test invoice PDF creation', 'wc-manual-invoices'); ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices&tab=create'); ?>" class="btn-header">
                    <span class="dashicons dashicons-plus-alt" style="margin-right: 6px;"></span>
                    <?php _e('Test Invoice', 'wc-manual-invoices'); ?>
                </a>
                <a href="<?php echo admin_url('admin.php?page=wc-manual-invoices'); ?>" class="btn-header">
                    <span class="dashicons dashicons-list-view" style="margin-right: 6px;"></span>
                    <?php _e('All Invoices', 'wc-manual-invoices'); ?>
                </a>
            </div>
        </div>
    </div>
    
    <!-- Status Overview -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-info"></span>
            <?php _e('PDF Generation Status', 'wc-manual-invoices'); ?>
        </h2>
        
        <?php if ($available_library): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-yes-alt" style="font-size: 20px;"></span>
                    <div>
                        <strong><?php _e('PDF Generation Available', 'wc-manual-invoices'); ?></strong><br>
                        <?php printf(__('Using %s library for PDF generation.', 'wc-manual-invoices'), '<strong>' . $pdf_status[$available_library]['name'] . '</strong>'); ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-warning" style="font-size: 20px;"></span>
                    <div>
                        <strong><?php _e('No PDF Library Available', 'wc-manual-invoices'); ?></strong><br>
                        <?php _e('Invoices will be generated as formatted text files. Install a PDF library below for professional PDF generation.', 'wc-manual-invoices'); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Quick Actions -->
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <?php if (!$available_library && in_array('composer', array_keys($available_methods))): ?>
                <form method="post" style="display: inline;">
                    <?php wp_nonce_field('install_pdf_library'); ?>
                    <button type="submit" name="install_dompdf" class="button button-primary">
                        <span class="dashicons dashicons-download" style="margin-right: 5px;"></span>
                        <?php _e('Install DomPDF Now', 'wc-manual-invoices'); ?>
                    </button>
                </form>
            <?php endif; ?>
            
            <form method="post" style="display: inline;">
                <?php wp_nonce_field('test_pdf_generation'); ?>
                <button type="submit" name="test_pdf" class="button button-secondary">
                    <span class="dashicons dashicons-media-document" style="margin-right: 5px;"></span>
                    <?php _e('Test PDF Generation', 'wc-manual-invoices'); ?>
                </button>
            </form>
            
            <button class="button button-secondary check-pdf-status">
                <span class="dashicons dashicons-update" style="margin-right: 5px;"></span>
                <?php _e('Refresh Status', 'wc-manual-invoices'); ?>
            </button>
        </div>
    </div>
    
    <!-- Library Status Table -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-tools"></span>
            <?php _e('PDF Library Status', 'wc-manual-invoices'); ?>
        </h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 20%;"><?php _e('Library', 'wc-manual-invoices'); ?></th>
                    <th style="width: 15%;"><?php _e('Status', 'wc-manual-invoices'); ?></th>
                    <th style="width: 45%;"><?php _e('Description', 'wc-manual-invoices'); ?></th>
                    <th style="width: 20%;"><?php _e('Action', 'wc-manual-invoices'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pdf_status as $library => $info): ?>
                    <tr>
                        <td>
                            <strong><?php echo esc_html($info['name']); ?></strong>
                            <?php if ($info['available'] && $library === $available_library): ?>
                                <br><small style="color: #28a745; font-weight: bold;">
                                    <span class="dashicons dashicons-star-filled" style="font-size: 12px;"></span>
                                    <?php _e('Active', 'wc-manual-invoices'); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($info['available']): ?>
                                <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Available', 'wc-manual-invoices'); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #dc3545; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                                    <span class="dashicons dashicons-dismiss"></span>
                                    <?php _e('Not Found', 'wc-manual-invoices'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php echo esc_html($info['description']); ?>
                            <?php if ($library === 'dompdf'): ?>
                                <br><small style="color: #666; font-style: italic;">
                                    <?php _e('Recommended for most users', 'wc-manual-invoices'); ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!$info['available']): ?>
                                <?php if ($library === 'dompdf'): ?>
                                    <div style="display: flex; flex-direction: column; gap: 5px;">
                                        <?php if (in_array('composer', array_keys($available_methods))): ?>
                                            <button class="button button-small install-pdf-library" 
                                                    data-library="<?php echo esc_attr($library); ?>" 
                                                    data-method="composer">
                                                <?php _e('Install via Composer', 'wc-manual-invoices'); ?>
                                            </button>
                                        <?php endif; ?>
                                        <?php if (in_array('wget', array_keys($available_methods)) || in_array('curl', array_keys($available_methods))): ?>
                                            <button class="button button-small install-pdf-library" 
                                                    data-library="<?php echo esc_attr($library); ?>" 
                                                    data-method="auto">
                                                <?php _e('Auto Download', 'wc-manual-invoices'); ?>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                <?php else: ?>
                                    <small style="color: #666;">
                                        <?php _e('Manual installation required', 'wc-manual-invoices'); ?>
                                    </small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('Ready', 'wc-manual-invoices'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Installation Methods -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-download"></span>
            <?php _e('Installation Methods', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <?php foreach ($recommendations as $method): ?>
                <div style="border: 1px solid #ddd; border-radius: 8px; padding: 20px; background: #f9f9f9;">
                    <h3 style="margin-top: 0; color: #333; display: flex; align-items: center; gap: 8px;">
                        <?php if ($method['method'] === 'composer'): ?>
                            <span class="dashicons dashicons-admin-tools" style="color: #96588a;"></span>
                        <?php elseif ($method['method'] === 'download'): ?>
                            <span class="dashicons dashicons-download" style="color: #96588a;"></span>
                        <?php elseif ($method['method'] === 'manual'): ?>
                            <span class="dashicons dashicons-admin-users" style="color: #96588a;"></span>
                        <?php else: ?>
                            <span class="dashicons dashicons-phone" style="color: #96588a;"></span>
                        <?php endif; ?>
                        <?php echo esc_html($method['title']); ?>
                    </h3>
                    
                    <p style="color: #666; margin-bottom: 15px;">
                        <?php echo esc_html($method['description']); ?>
                    </p>
                    
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-weight: bold; color: #333;">
                            <?php _e('Difficulty:', 'wc-manual-invoices'); ?>
                        </span>
                        <span style="padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; <?php echo $method['difficulty'] === 'Easy' ? 'background: #d4edda; color: #155724;' : 'background: #fff3cd; color: #856404;'; ?>">
                            <?php echo esc_html($method['difficulty']); ?>
                        </span>
                    </div>
                    
                    <?php if (isset($method['command'])): ?>
                        <div style="background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 13px; margin: 10px 0;">
                            <?php echo esc_html($method['command']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($method['note'])): ?>
                        <p style="font-size: 13px; color: #666; font-style: italic; margin-bottom: 0;">
                            <span class="dashicons dashicons-info" style="font-size: 14px;"></span>
                            <?php echo esc_html($method['note']); ?>
                        </p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Manual Installation Instructions -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-editor-help"></span>
            <?php _e('Manual Installation Guide', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <!-- DomPDF Instructions -->
            <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
                <h3 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-star-filled"></span>
                    DomPDF (Recommended)
                </h3>
                
                <p><strong><?php _e('Composer Installation:', 'wc-manual-invoices'); ?></strong></p>
                <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; border-radius: 3px; font-family: monospace;">
                    composer require dompdf/dompdf
                </code>
                
                <p><strong><?php _e('Manual Download:', 'wc-manual-invoices'); ?></strong></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Download from', 'wc-manual-invoices'); ?> <a href="https://github.com/dompdf/dompdf/releases" target="_blank">GitHub Releases</a></li>
                    <li><?php _e('Extract to', 'wc-manual-invoices'); ?> <code>/wp-content/plugins/woocommerce-manual-invoices/lib/dompdf/</code></li>
                    <li><?php _e('Ensure the autoload.inc.php file is accessible', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Set proper file permissions (755 for folders, 644 for files)', 'wc-manual-invoices'); ?></li>
                </ol>
            </div>
            
            <!-- TCPDF Instructions -->
            <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
                <h3 style="margin-top: 0; color: #96588a;">TCPDF</h3>
                
                <p><strong><?php _e('Composer Installation:', 'wc-manual-invoices'); ?></strong></p>
                <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; border-radius: 3px; font-family: monospace;">
                    composer require tecnickcom/tcpdf
                </code>
                
                <p><strong><?php _e('Manual Download:', 'wc-manual-invoices'); ?></strong></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Download from', 'wc-manual-invoices'); ?> <a href="https://tcpdf.org/" target="_blank">TCPDF.org</a></li>
                    <li><?php _e('Extract to', 'wc-manual-invoices'); ?> <code>/wp-content/plugins/tcpdf/</code></li>
                    <li><?php _e('Include the tcpdf.php file in your installation', 'wc-manual-invoices'); ?></li>
                </ol>
            </div>
            
            <!-- mPDF Instructions -->
            <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
                <h3 style="margin-top: 0; color: #96588a;">mPDF</h3>
                
                <p><strong><?php _e('Composer Installation:', 'wc-manual-invoices'); ?></strong></p>
                <code style="background: #f5f5f5; padding: 10px; display: block; margin: 10px 0; border-radius: 3px; font-family: monospace;">
                    composer require mpdf/mpdf
                </code>
                
                <p><strong><?php _e('Manual Download:', 'wc-manual-invoices'); ?></strong></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Download from', 'wc-manual-invoices'); ?> <a href="https://mpdf.github.io/" target="_blank">mPDF website</a></li>
                    <li><?php _e('Extract and follow their installation guide', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Ensure Composer autoloader is available', 'wc-manual-invoices'); ?></li>
                </ol>
            </div>
            
            <!-- FPDF Instructions -->
            <div style="border: 1px solid #ddd; border-radius: 5px; padding: 15px;">
                <h3 style="margin-top: 0; color: #96588a;">FPDF</h3>
                
                <p><strong><?php _e('Manual Download:', 'wc-manual-invoices'); ?></strong></p>
                <ol style="margin-left: 20px;">
                    <li><?php _e('Download from', 'wc-manual-invoices'); ?> <a href="http://www.fpdf.org/" target="_blank">FPDF.org</a></li>
                    <li><?php _e('Extract to', 'wc-manual-invoices'); ?> <code>/wp-content/plugins/fpdf/</code></li>
                    <li><?php _e('Include the fpdf.php file', 'wc-manual-invoices'); ?></li>
                </ol>
                <p><small style="color: #666;">
                    <?php _e('Note: FPDF provides basic PDF generation without HTML support.', 'wc-manual-invoices'); ?>
                </small></p>
            </div>
        </div>
    </div>
    
    <!-- System Requirements -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php _e('System Requirements & Status', 'wc-manual-invoices'); ?>
        </h2>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 30%;"><?php _e('Requirement', 'wc-manual-invoices'); ?></th>
                    <th style="width: 25%;"><?php _e('Current Value', 'wc-manual-invoices'); ?></th>
                    <th style="width: 25%;"><?php _e('Recommended', 'wc-manual-invoices'); ?></th>
                    <th style="width: 20%;"><?php _e('Status', 'wc-manual-invoices'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php
                // System checks
                $checks = array(
                    'PHP Version' => array(
                        'current' => PHP_VERSION,
                        'recommended' => '8.0+',
                        'status' => version_compare(PHP_VERSION, '8.0', '>=')
                    ),
                    'Memory Limit' => array(
                        'current' => ini_get('memory_limit'),
                        'recommended' => '256M+',
                        'status' => self::parse_memory_limit(ini_get('memory_limit')) >= 256
                    ),
                    'Max Execution Time' => array(
                        'current' => ini_get('max_execution_time') . 's',
                        'recommended' => '120s+',
                        'status' => intval(ini_get('max_execution_time')) >= 120 || ini_get('max_execution_time') == 0
                    ),
                    'Upload Max Size' => array(
                        'current' => ini_get('upload_max_filesize'),
                        'recommended' => '10M+',
                        'status' => self::parse_memory_limit(ini_get('upload_max_filesize')) >= 10
                    ),
                    'GD Extension' => array(
                        'current' => extension_loaded('gd') ? __('Enabled', 'wc-manual-invoices') : __('Disabled', 'wc-manual-invoices'),
                        'recommended' => __('Enabled', 'wc-manual-invoices'),
                        'status' => extension_loaded('gd')
                    ),
                    'DOM Extension' => array(
                        'current' => extension_loaded('dom') ? __('Enabled', 'wc-manual-invoices') : __('Disabled', 'wc-manual-invoices'),
                        'recommended' => __('Enabled', 'wc-manual-invoices'),
                        'status' => extension_loaded('dom')
                    ),
                    'MBString Extension' => array(
                        'current' => extension_loaded('mbstring') ? __('Enabled', 'wc-manual-invoices') : __('Disabled', 'wc-manual-invoices'),
                        'recommended' => __('Enabled', 'wc-manual-invoices'),
                        'status' => extension_loaded('mbstring')
                    ),
                    'Exec Function' => array(
                        'current' => function_exists('exec') ? __('Available', 'wc-manual-invoices') : __('Disabled', 'wc-manual-invoices'),
                        'recommended' => __('Available', 'wc-manual-invoices'),
                        'status' => function_exists('exec')
                    ),
                    'Write Permissions' => array(
                        'current' => is_writable(WC_MANUAL_INVOICES_PLUGIN_PATH) ? __('Writable', 'wc-manual-invoices') : __('Read Only', 'wc-manual-invoices'),
                        'recommended' => __('Writable', 'wc-manual-invoices'),
                        'status' => is_writable(WC_MANUAL_INVOICES_PLUGIN_PATH)
                    )
                );
                
                foreach ($checks as $name => $check):
                ?>
                    <tr>
                        <td><strong><?php echo esc_html($name); ?></strong></td>
                        <td>
                            <span style="font-family: monospace; font-size: 13px;">
                                <?php echo esc_html($check['current']); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($check['recommended']); ?></td>
                        <td>
                            <?php if ($check['status']): ?>
                                <span style="color: #28a745; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php _e('OK', 'wc-manual-invoices'); ?>
                                </span>
                            <?php else: ?>
                                <span style="color: #dc3545; font-weight: bold; display: flex; align-items: center; gap: 5px;">
                                    <span class="dashicons dashicons-warning"></span>
                                    <?php _e('Check', 'wc-manual-invoices'); ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Troubleshooting -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-sos"></span>
            <?php _e('Troubleshooting Guide', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
            <div style="border-left: 4px solid #dc3545; padding: 15px; background: #f8f9fa;">
                <h3 style="color: #dc3545; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-dismiss"></span>
                    <?php _e('PDF Generation Fails', 'wc-manual-invoices'); ?>
                </h3>
                <ul style="margin-left: 20px;">
                    <li><?php _e('Check memory limit (increase to 256MB+)', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Verify write permissions on uploads directory', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Ensure PDF library is properly installed', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Check WordPress debug.log for specific errors', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Try generating with a different PDF library', 'wc-manual-invoices'); ?></li>
                </ul>
            </div>
            
            <div style="border-left: 4px solid #ffc107; padding: 15px; background: #f8f9fa;">
                <h3 style="color: #ffc107; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-clock"></span>
                    <?php _e('Slow PDF Generation', 'wc-manual-invoices'); ?>
                </h3>
                <ul style="margin-left: 20px;">
                    <li><?php _e('Optimize images in email templates', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Increase max execution time to 120+ seconds', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Use simpler PDF templates', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Consider server-side PDF caching', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Switch to FPDF for faster basic PDFs', 'wc-manual-invoices'); ?></li>
                </ul>
            </div>
            
            <div style="border-left: 4px solid #17a2b8; padding: 15px; background: #f8f9fa;">
                <h3 style="color: #17a2b8; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-editor-textcolor"></span>
                    <?php _e('Font & Formatting Issues', 'wc-manual-invoices'); ?>
                </h3>
                <ul style="margin-left: 20px;">
                    <li><?php _e('Use web-safe fonts (Arial, Times, Helvetica)', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Avoid custom fonts in PDF templates', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Check character encoding (UTF-8)', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Install additional font packages if needed', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Test with different PDF libraries', 'wc-manual-invoices'); ?></li>
                </ul>
            </div>
            
            <div style="border-left: 4px solid #28a745; padding: 15px; background: #f8f9fa;">
                <h3 style="color: #28a745; margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-layout"></span>
                    <?php _e('Layout Problems', 'wc-manual-invoices'); ?>
                </h3>
                <ul style="margin-left: 20px;">
                    <li><?php _e('Use table-based layouts for better PDF compatibility', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Avoid complex CSS positioning (absolute, fixed)', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Test with different PDF libraries', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Simplify HTML structure', 'wc-manual-invoices'); ?></li>
                    <li><?php _e('Use inline CSS for better compatibility', 'wc-manual-invoices'); ?></li>
                </ul>
            </div>
        </div>
    </div>
    
    <!-- Support & Resources -->
    <div style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; color: #96588a; display: flex; align-items: center; gap: 10px;">
            <span class="dashicons dashicons-book"></span>
            <?php _e('Support & Resources', 'wc-manual-invoices'); ?>
        </h2>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px;">
            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php _e('Documentation Links', 'wc-manual-invoices'); ?>
                </h4>
                <ul style="margin-left: 20px;">
                    <li><a href="https://github.com/dompdf/dompdf" target="_blank"><?php _e('DomPDF Documentation', 'wc-manual-invoices'); ?></a></li>
                    <li><a href="https://tcpdf.org/docs/" target="_blank"><?php _e('TCPDF Documentation', 'wc-manual-invoices'); ?></a></li>
                    <li><a href="https://mpdf.github.io/" target="_blank"><?php _e('mPDF Documentation', 'wc-manual-invoices'); ?></a></li>
                    <li><a href="http://www.fpdf.org/en/tutorial/" target="_blank"><?php _e('FPDF Tutorial', 'wc-manual-invoices'); ?></a></li>
                </ul>
            </div>
            
            <div style="padding: 15px; background: #f8f9fa; border-radius: 5px;">
                <h4 style="margin-top: 0; display: flex; align-items: center; gap: 8px;">
                    <span class="dashicons dashicons-editor-help"></span>
                    <?php _e('Get Support', 'wc-manual-invoices'); ?>
                </h4>
                <ul style="margin-left: 20px;">
                    <li><a href="mailto:support@wbcomdesigns.com"><?php _e('Email Support', 'wc-manual-invoices'); ?></a></li>
                    <li><a href="https://wbcomdesigns.com/contact/" target="_blank"><?php _e('Contact Form', 'wc-manual-invoices'); ?></a></li>
                    <li><a href="https://wordpress.org/support/" target="_blank"><?php _e('WordPress Forums', 'wc-manual-invoices'); ?></a></li>
                    <li><a href="https://docs.woocommerce.com/" target="_blank"><?php _e('WooCommerce Docs', 'wc-manual-invoices'); ?></a></li>
                </ul>
            </div>
        </div>
        
        <!-- Plugin Info Footer -->
        <div style="margin-top: 30px; padding: 20px; background: linear-gradient(135deg, #96588a, #7e4874); color: white; border-radius: 8px; text-align: center;">
            <h3 style="margin-top: 0; color: white; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <span class="dashicons dashicons-heart"></span>
                <?php _e('WooCommerce Manual Invoices Pro', 'wc-manual-invoices'); ?>
            </h3>
            <p style="margin-bottom: 10px; opacity: 0.9;">
                <?php _e('Professional invoice management for WooCommerce stores', 'wc-manual-invoices'); ?>
            </p>
            <p style="margin-bottom: 0; font-size: 14px; opacity: 0.8;">
                <?php _e('Made with ❤️ by Wbcom Designs | Version', 'wc-manual-invoices'); ?> <?php echo WC_MANUAL_INVOICES_VERSION; ?>
            </p>
        </div>
    </div>
</div>

<style>
.wp-list-table th,
.wp-list-table td {
    padding: 12px;
    vertical-align: middle;
}

.button {
    display: inline-flex;
    align-items: center;
    gap: 5px;
}

.dashicons {
    font-size: 16px;
    line-height: 1;
}

code {
    background: #f5f5f5;
    padding: 3px 6px;
    border-radius: 3px;
    font-family: Consolas, Monaco, 'Courier New', monospace;
    font-size: 13px;
    color: #333;
}

.install-pdf-library {
    background: #96588a;
    color: white;
    border: none;
}

.install-pdf-library:hover {
    background: #7e4874;
    color: white;
}

.check-pdf-status:hover {
    background: #f0f0f0;
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

/* Loading animation for install buttons */
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(359deg); }
}

.dashicons.spin {
    animation: spin 1s linear infinite;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Install PDF library via AJAX
    $('.install-pdf-library').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        var library = $button.data('library');
        var method = $button.data('method');
        var originalText = $button.text();
        
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + '<?php _e('Installing...', 'wc-manual-invoices'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'wc_manual_invoice_install_pdf_library',
                library: library,
                method: method,
                nonce: '<?php echo wp_create_nonce('wc_manual_invoices_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    $button.removeClass('install-pdf-library')
                           .addClass('button-secondary')
                           .html('<span class="dashicons dashicons-yes-alt"></span> ' + '<?php _e('Installed', 'wc-manual-invoices'); ?>')
                           .prop('disabled', true);
                    
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p><strong><?php _e('Success!', 'wc-manual-invoices'); ?></strong> ' + response.data.message + '</p></div>')
                        .insertAfter('.wc-manual-invoices-header');
                    
                    // Refresh page after 3 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 3000);
                } else {
                    $button.prop('disabled', false).html(originalText);
                    
                    // Show error message
                    $('<div class="notice notice-error is-dismissible"><p><strong><?php _e('Installation Failed:', 'wc-manual-invoices'); ?></strong> ' + response.data.message + '</p></div>')
                        .insertAfter('.wc-manual-invoices-header');
                }
            },
            error: function() {
                $button.prop('disabled', false).html(originalText);
                
                // Show generic error
                $('<div class="notice notice-error is-dismissible"><p><strong><?php _e('Error:', 'wc-manual-invoices'); ?></strong> <?php _e('Installation failed. Please try again or install manually.', 'wc-manual-invoices'); ?></p></div>')
                    .insertAfter('.wc-manual-invoices-header');
            }
        });
    });
    
    // Check PDF status
    $('.check-pdf-status').on('click', function(e) {
        e.preventDefault();
        
        var $button = $(this);
        $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> ' + '<?php _e('Checking...', 'wc-manual-invoices'); ?>');
        
        // Refresh page after a short delay to check status
        setTimeout(function() {
            location.reload();
        }, 1000);
    });
    
    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if (target.length) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 50
            }, 1000);
        }
    });
});
</script>

<?php
/**
 * Helper functions for the template
 */

// Parse memory limit helper function
function parse_memory_limit($limit) {
    $limit = trim($limit);
    $last = strtolower($limit[strlen($limit)-1]);
    $limit = intval($limit);
    
    switch($last) {
        case 'g':
            $limit *= 1024;
        case 'm':
            $limit *= 1024;
        case 'k':
            $limit *= 1024;
    }
    
    return $limit / (1024 * 1024); // Return in MB
}

// Generate test PDF function
function generate_test_pdf() {
    try {
        // Create a dummy order for testing
        $test_order_data = array(
            'customer_email' => 'test@example.com',
            'billing_first_name' => 'Test',
            'billing_last_name' => 'Customer',
            'custom_items' => array(
                array(
                    'name' => 'Test Service',
                    'description' => 'PDF generation test',
                    'quantity' => 1,
                    'total' => 100.00
                )
            ),
            'notes' => 'This is a test invoice to verify PDF generation is working correctly.',
            'due_date' => date('Y-m-d', strtotime('+30 days'))
        );
        
        $order_id = WC_Manual_Invoice_Generator::create_invoice($test_order_data);
        
        if (is_wp_error($order_id)) {
            return $order_id;
        }
        
        // Generate PDF
        $pdf_path = WC_Manual_Invoice_PDF::generate_pdf($order_id, true);
        
        if (!$pdf_path) {
            return new WP_Error('pdf_generation_failed', 'Failed to generate test PDF');
        }
        
        // Get download URL
        $pdf_url = WC_Manual_Invoice_PDF::get_pdf_download_url($order_id);
        
        return array(
            'success' => true,
            'order_id' => $order_id,
            'pdf_path' => $pdf_path,
            'url' => $pdf_url
        );
        
    } catch (Exception $e) {
        return new WP_Error('test_pdf_error', $e->getMessage());
    }
}
?>