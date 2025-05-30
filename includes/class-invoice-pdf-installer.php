<?php
/**
 * Simplified PDF Library Manager - DomPDF Only
 * 
 * This class handles bundled DomPDF library detection and fallback text generation
 * Removes TCPDF complexity since DomPDF is sufficient for most use cases
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF_Installer {
    
    /**
     * PDF libraries configuration - Simplified to DomPDF only
     */
    private static $libraries = array(
        'dompdf' => array(
            'name' => 'DomPDF',
            'description' => 'Bundled HTML to PDF converter with excellent CSS support',
            'bundled' => true,
            'required_files' => array(
                'lib/dompdf/autoload.inc.php',
                'lib/dompdf/src/Dompdf.php'
            ),
            'classes' => array('Dompdf\\Dompdf', 'DOMPDF'),
            'test_class' => 'Dompdf\\Dompdf'
        )
    );
    
    /**
     * Get library status for all supported libraries
     * 
     * @return array Status information for each library
     */
    public static function get_library_status() {
        $status = array();
        
        foreach (self::$libraries as $key => $config) {
            $status[$key] = array(
                'name' => $config['name'],
                'description' => $config['description'],
                'bundled' => $config['bundled'],
                'available' => self::is_library_available($key),
                'installation_method' => 'bundled',
                'version' => self::get_library_version($key)
            );
            
            if (!$status[$key]['available']) {
                $status[$key]['missing_files'] = self::get_missing_files($key);
                $status[$key]['installation_instructions'] = self::get_installation_instructions($key);
            }
        }
        
        return $status;
    }
    
    /**
     * Check if DomPDF library is available and working
     * 
     * @param string $library Library key
     * @return bool
     */
    public static function is_library_available($library = 'dompdf') {
        if (!isset(self::$libraries[$library])) {
            return false;
        }
        
        $config = self::$libraries[$library];
        
        // Check if required files exist
        foreach ($config['required_files'] as $file) {
            $full_path = WC_MANUAL_INVOICES_PLUGIN_PATH . $file;
            if (!file_exists($full_path)) {
                error_log("WC Manual Invoices: Missing file: $full_path");
                return false;
            }
        }
        
        // Try to load and test the library
        try {
            return self::load_and_test_dompdf();
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Failed to load DomPDF: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load and test DomPDF library
     * 
     * @return bool Success
     */
    private static function load_and_test_dompdf() {
        $autoload_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
        
        if (!file_exists($autoload_path)) {
            return false;
        }
        
        // Load DomPDF
        require_once $autoload_path;
        
        // Test if class exists and can be instantiated
        if (class_exists('Dompdf\\Dompdf')) {
            try {
                $test = new \Dompdf\Dompdf();
                return is_object($test);
            } catch (Exception $e) {
                error_log('WC Manual Invoices: DomPDF instantiation failed: ' . $e->getMessage());
                return false;
            }
        } elseif (class_exists('DOMPDF')) {
            // Legacy DomPDF
            try {
                $test = new DOMPDF();
                return is_object($test);
            } catch (Exception $e) {
                error_log('WC Manual Invoices: Legacy DomPDF instantiation failed: ' . $e->getMessage());
                return false;
            }
        }
        
        return false;
    }
    
    /**
     * Get missing files for a library
     * 
     * @param string $library Library key
     * @return array Missing file paths
     */
    private static function get_missing_files($library) {
        if (!isset(self::$libraries[$library])) {
            return array();
        }
        
        $config = self::$libraries[$library];
        $missing = array();
        
        foreach ($config['required_files'] as $file) {
            $full_path = WC_MANUAL_INVOICES_PLUGIN_PATH . $file;
            if (!file_exists($full_path)) {
                $missing[] = $file;
            }
        }
        
        return $missing;
    }
    
    /**
     * Get installation instructions for DomPDF
     * 
     * @param string $library Library key
     * @return array Installation instructions
     */
    private static function get_installation_instructions($library) {
        return array(
            'title' => 'DomPDF Installation Issue',
            'steps' => array(
                'DomPDF should be bundled with the plugin in the lib/dompdf/ directory',
                'If missing, download the plugin again from the original source',
                'Ensure all plugin files were uploaded correctly during installation',
                'Check that the lib/dompdf/ folder has proper read permissions (755)',
                'Verify autoload.inc.php exists in lib/dompdf/',
                'Contact support if the issue persists'
            ),
            'technical_note' => 'DomPDF should be included in the plugin package. If files are missing, this indicates an incomplete installation.'
        );
    }
    
    /**
     * Get library version
     * 
     * @param string $library Library key
     * @return string Version or status
     */
    private static function get_library_version($library) {
        if (!self::is_library_available($library)) {
            return 'Not Available';
        }
        
        try {
            $autoload_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
            require_once $autoload_path;
            
            if (class_exists('Dompdf\\Dompdf')) {
                // Try to get version from composer.json or version file
                $composer_file = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/composer.json';
                if (file_exists($composer_file)) {
                    $composer_data = json_decode(file_get_contents($composer_file), true);
                    if (isset($composer_data['version'])) {
                        return $composer_data['version'];
                    }
                }
                return 'Bundled (Latest)';
            }
            
            return 'Legacy Version';
        } catch (Exception $e) {
            return 'Unknown';
        }
    }
    
    /**
     * Test PDF generation with DomPDF
     * 
     * @return array|WP_Error Test result
     */
    public static function test_pdf_generation() {
        // Check if DomPDF is available
        if (!self::is_library_available('dompdf')) {
            return new WP_Error('no_library', 'DomPDF library is not available. PDF generation will use text fallback.');
        }
        
        // Create test HTML content
        $test_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PDF Test</title>';
        $test_html .= '<style>body { font-family: Arial, sans-serif; margin: 20px; }</style></head><body>';
        $test_html .= '<h1 style="color: #96588a;">PDF Generation Test</h1>';
        $test_html .= '<p><strong>Library:</strong> DomPDF</p>';
        $test_html .= '<p><strong>Date:</strong> ' . current_time('Y-m-d H:i:s') . '</p>';
        $test_html .= '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
        $test_html .= '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
        $test_html .= '<p>This test PDF verifies that DomPDF is working correctly with your WordPress installation.</p>';
        $test_html .= '<div style="margin-top: 30px; padding: 15px; background: #f0f0f0; border-left: 4px solid #96588a;">';
        $test_html .= '<h3>Test Items Table</h3>';
        $test_html .= '<table style="width: 100%; border-collapse: collapse;">';
        $test_html .= '<tr style="background: #96588a; color: white;"><th style="padding: 10px; text-align: left;">Item</th><th style="padding: 10px; text-align: right;">Price</th></tr>';
        $test_html .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;">Test Product</td><td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">$99.00</td></tr>';
        $test_html .= '<tr><td style="padding: 8px; border-bottom: 1px solid #ddd;">Service Fee</td><td style="padding: 8px; border-bottom: 1px solid #ddd; text-align: right;">$10.00</td></tr>';
        $test_html .= '<tr style="font-weight: bold;"><td style="padding: 8px;">Total</td><td style="padding: 8px; text-align: right;">$109.00</td></tr>';
        $test_html .= '</table></div>';
        $test_html .= '<hr><p style="font-size: 12px; color: #666;">Generated by WooCommerce Manual Invoices Pro</p>';
        $test_html .= '</body></html>';
        
        // Create test file path
        $upload_dir = wp_upload_dir();
        $test_dir = $upload_dir['basedir'] . '/wc-manual-invoices/';
        if (!file_exists($test_dir)) {
            wp_mkdir_p($test_dir);
        }
        
        $test_file = $test_dir . 'test-' . time() . '.pdf';
        
        // Generate test PDF
        try {
            $success = self::test_dompdf($test_html, $test_file);
            
            if ($success && file_exists($test_file)) {
                return array(
                    'success' => true,
                    'message' => 'PDF test successful using DomPDF',
                    'library' => 'dompdf',
                    'file_size' => filesize($test_file),
                    'download_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $test_file)
                );
            } else {
                return new WP_Error('generation_failed', 'PDF file was not created successfully');
            }
            
        } catch (Exception $e) {
            return new WP_Error('test_failed', 'PDF test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Test DomPDF generation
     * 
     * @param string $html HTML content
     * @param string $output_path Output file path
     * @return bool Success
     */
    private static function test_dompdf($html, $output_path) {
        try {
            $autoload_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
            require_once $autoload_path;
            
            if (class_exists('Dompdf\\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $options = $dompdf->getOptions();
                $options->set('defaultFont', 'Arial');
                $options->set('isRemoteEnabled', false); // Security: disable remote resources
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true);
            } else {
                // Legacy DomPDF
                $dompdf = new DOMPDF();
            }
            
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            return file_put_contents($output_path, $dompdf->output()) !== false;
            
        } catch (Exception $e) {
            error_log('DomPDF test error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the best available library for PDF generation
     * 
     * @return string|false Library key or false if none available
     */
    public static function get_best_available_library() {
        if (self::is_library_available('dompdf')) {
            return 'dompdf';
        }
        
        return false;
    }
    
    /**
     * Check if any PDF library is available
     * 
     * @return bool
     */
    public static function has_pdf_library() {
        return self::get_best_available_library() !== false;
    }
    
    /**
     * Get PDF library diagnostics
     * 
     * @return array Diagnostic information
     */
    public static function get_diagnostics() {
        $diagnostics = array(
            'php_version' => PHP_VERSION,
            'wordpress_version' => get_bloginfo('version'),
            'woocommerce_version' => defined('WC_VERSION') ? WC_VERSION : 'Not detected',
            'plugin_path' => WC_MANUAL_INVOICES_PLUGIN_PATH,
            'upload_dir_writable' => is_writable(wp_upload_dir()['basedir']),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'dompdf_available' => self::is_library_available('dompdf'),
            'dompdf_files' => array()
        );
        
        // Check DomPDF files
        $required_files = self::$libraries['dompdf']['required_files'];
        foreach ($required_files as $file) {
            $full_path = WC_MANUAL_INVOICES_PLUGIN_PATH . $file;
            $diagnostics['dompdf_files'][$file] = array(
                'exists' => file_exists($full_path),
                'readable' => file_exists($full_path) && is_readable($full_path),
                'size' => file_exists($full_path) ? filesize($full_path) : 0
            );
        }
        
        return $diagnostics;
    }
    
    /**
     * AJAX handler for installing PDF libraries (simplified - no installation needed)
     * 
     * @param string $library Library to install
     * @param string $method Installation method
     * @return array|WP_Error Installation result
     */
    public static function install_pdf_library($library, $method = 'auto') {
        // Since DomPDF is bundled, no installation is needed
        if ($library === 'dompdf') {
            if (self::is_library_available('dompdf')) {
                return array(
                    'success' => true,
                    'message' => 'DomPDF is already available and working',
                    'method' => 'bundled'
                );
            } else {
                return new WP_Error('bundled_missing', 'DomPDF should be bundled with the plugin. Please reinstall the plugin or contact support.');
            }
        }
        
        return new WP_Error('unsupported', 'Library not supported: ' . $library);
    }
}