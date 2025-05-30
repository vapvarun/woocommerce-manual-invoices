<?php
/**
 * Enhanced PDF Library Manager with Manual TCPDF Installation Support
 * 
 * Replaces: includes/class-invoice-pdf-installer.php
 * This class handles both bundled DomPDF and manual TCPDF installation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF_Installer {
    
    /**
     * PDF libraries configuration
     */
    private static $libraries = array(
        'dompdf' => array(
            'name' => 'DomPDF',
            'description' => 'Bundled with plugin - Excellent HTML/CSS support',
            'bundled' => true,
            'required_files' => array(
                'lib/dompdf/autoload.inc.php',
                'lib/dompdf/dompdf_config.inc.php'
            ),
            'classes' => array('Dompdf\\Dompdf', 'DOMPDF'),
            'test_class' => 'Dompdf\\Dompdf'
        ),
        'tcpdf' => array(
            'name' => 'TCPDF',
            'description' => 'Alternative for complex layouts and Unicode support',
            'bundled' => false,
            'download_url' => 'https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip',
            'required_files' => array(
                'lib/tcpdf/tcpdf.php'
            ),
            'classes' => array('TCPDF'),
            'test_class' => 'TCPDF',
            'config_files' => array(
                'lib/tcpdf/config/tcpdf_config.php'
            )
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
                'installation_method' => $config['bundled'] ? 'bundled' : 'manual',
                'download_url' => isset($config['download_url']) ? $config['download_url'] : null,
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
     * Check if a specific library is available and working
     * 
     * @param string $library Library key
     * @return bool
     */
    public static function is_library_available($library) {
        if (!isset(self::$libraries[$library])) {
            return false;
        }
        
        $config = self::$libraries[$library];
        
        // Check if required files exist
        foreach ($config['required_files'] as $file) {
            $full_path = WC_MANUAL_INVOICES_PLUGIN_PATH . $file;
            if (!file_exists($full_path)) {
                return false;
            }
        }
        
        // Try to load and test the library
        try {
            self::load_library($library);
            return self::test_library_class($library);
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Failed to load ' . $library . ': ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Load a specific library
     * 
     * @param string $library Library key
     * @return bool Success
     */
    private static function load_library($library) {
        if (!isset(self::$libraries[$library])) {
            return false;
        }
        
        $config = self::$libraries[$library];
        
        switch ($library) {
            case 'dompdf':
                return self::load_dompdf();
            case 'tcpdf':
                return self::load_tcpdf();
            default:
                return false;
        }
    }
    
    /**
     * Load DomPDF library
     * 
     * @return bool Success
     */
    private static function load_dompdf() {
        $autoload_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
        
        if (file_exists($autoload_path)) {
            require_once $autoload_path;
            return true;
        }
        
        return false;
    }
    
    /**
     * Load TCPDF library manually
     * 
     * @return bool Success
     */
    private static function load_tcpdf() {
        $tcpdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/tcpdf.php';
        
        if (!file_exists($tcpdf_path)) {
            return false;
        }
        
        // Set up TCPDF configuration before loading
        self::setup_tcpdf_config();
        
        // Load TCPDF main file
        require_once $tcpdf_path;
        
        return true;
    }
    
    /**
     * Setup TCPDF configuration constants
     */
    private static function setup_tcpdf_config() {
        // Prevent multiple definitions
        if (defined('K_TCPDF_EXTERNAL_CONFIG')) {
            return;
        }
        
        // Define external config flag
        define('K_TCPDF_EXTERNAL_CONFIG', true);
        
        // Define paths
        $tcpdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/';
        $upload_dir = wp_upload_dir();
        $cache_path = $upload_dir['basedir'] . '/wc-manual-invoices/tcpdf-cache/';
        
        // Create cache directory if it doesn't exist
        if (!file_exists($cache_path)) {
            wp_mkdir_p($cache_path);
        }
        
        // TCPDF Configuration Constants
        define('K_PATH_MAIN', $tcpdf_path);
        define('K_PATH_URL', WC_MANUAL_INVOICES_PLUGIN_URL . 'lib/tcpdf/');
        define('K_PATH_FONTS', $tcpdf_path . 'fonts/');
        define('K_PATH_CACHE', $cache_path);
        define('K_PATH_URL_CACHE', $upload_dir['baseurl'] . '/wc-manual-invoices/tcpdf-cache/');
        define('K_PATH_IMAGES', $tcpdf_path . 'examples/images/');
        define('K_BLANK_IMAGE', $tcpdf_path . 'examples/images/_blank.png');
        define('K_CELL_HEIGHT_RATIO', 1.25);
        define('K_TITLE_MAGNIFICATION', 1.3);
        define('K_SMALL_RATIO', 2/3);
        define('K_THAI_TOPCHARS', true);
        define('K_TCPDF_CALLS_IN_HTML', false);
        define('K_TCPDF_THROW_EXCEPTION_ERROR', false);
        define('K_TIMEZONE', 'UTC');
    }
    
    /**
     * Test if library class can be instantiated
     * 
     * @param string $library Library key
     * @return bool
     */
    private static function test_library_class($library) {
        if (!isset(self::$libraries[$library])) {
            return false;
        }
        
        $config = self::$libraries[$library];
        $test_class = $config['test_class'];
        
        try {
            switch ($library) {
                case 'dompdf':
                    if (class_exists('Dompdf\\Dompdf')) {
                        $test = new \Dompdf\Dompdf();
                        return is_object($test);
                    } elseif (class_exists('DOMPDF')) {
                        $test = new DOMPDF();
                        return is_object($test);
                    }
                    return false;
                    
                case 'tcpdf':
                    if (class_exists('TCPDF')) {
                        $test = new TCPDF();
                        return is_object($test);
                    }
                    return false;
                    
                default:
                    return false;
            }
        } catch (Exception $e) {
            return false;
        }
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
     * Get installation instructions for a library
     * 
     * @param string $library Library key
     * @return array Installation instructions
     */
    private static function get_installation_instructions($library) {
        switch ($library) {
            case 'dompdf':
                return array(
                    'title' => 'DomPDF Installation',
                    'steps' => array(
                        'DomPDF should be bundled with the plugin',
                        'Check that lib/dompdf/ folder exists in the plugin directory',
                        'Ensure autoload.inc.php file is present',
                        'If missing, download DomPDF from GitHub and extract to lib/dompdf/',
                        'Contact support if you continue to have issues'
                    )
                );
                
            case 'tcpdf':
                return array(
                    'title' => 'Manual TCPDF Installation',
                    'steps' => array(
                        'Download TCPDF from https://github.com/tecnickcom/TCPDF/archive/refs/heads/main.zip',
                        'Extract the downloaded ZIP file',
                        'Rename the extracted folder from "TCPDF-main" to "tcpdf"',
                        'Copy the tcpdf folder to: ' . WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/',
                        'Ensure the main file exists at: lib/tcpdf/tcpdf.php',
                        'The fonts and config folders should also be present',
                        'Refresh this page to test the installation'
                    )
                );
                
            default:
                return array(
                    'title' => 'Unknown Library',
                    'steps' => array('Installation instructions not available')
                );
        }
    }
    
    /**
     * Get library version
     * 
     * @param string $library Library key
     * @return string Version or 'Unknown'
     */
    private static function get_library_version($library) {
        if (!self::is_library_available($library)) {
            return 'Not Installed';
        }
        
        try {
            switch ($library) {
                case 'dompdf':
                    self::load_library($library);
                    if (class_exists('Dompdf\\Dompdf')) {
                        return 'Latest (Bundled)';
                    }
                    return 'Legacy Version';
                    
                case 'tcpdf':
                    $tcpdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/tcpdf.php';
                    if (file_exists($tcpdf_path)) {
                        $content = file_get_contents($tcpdf_path);
                        if (preg_match('/\*\s+@version\s+([0-9\.]+)/i', $content, $matches)) {
                            return $matches[1];
                        }
                        return 'Installed';
                    }
                    break;
            }
        } catch (Exception $e) {
            // Error getting version
        }
        
        return 'Unknown';
    }
    
    /**
     * Attempt to install TCPDF automatically (if possible)
     * 
     * @return array|WP_Error Installation result
     */
    public static function install_tcpdf_automatically() {
        // Check if already installed
        if (self::is_library_available('tcpdf')) {
            return array(
                'success' => true,
                'message' => 'TCPDF is already installed and working',
                'method' => 'existing'
            );
        }
        
        $config = self::$libraries['tcpdf'];
        $target_dir = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/';
        
        // Create lib directory if needed
        $lib_dir = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/';
        if (!file_exists($lib_dir)) {
            wp_mkdir_p($lib_dir);
        }
        
        // Try to download and install
        $temp_file = sys_get_temp_dir() . '/tcpdf_' . time() . '.zip';
        
        // Download using WordPress HTTP API
        $response = wp_remote_get($config['download_url'], array(
            'timeout' => 300,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            return new WP_Error('download_failed', 'Failed to download TCPDF: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('download_failed', 'Downloaded file is empty');
        }
        
        // Save temp file
        if (!file_put_contents($temp_file, $body)) {
            return new WP_Error('save_failed', 'Failed to save downloaded file');
        }
        
        // Extract using ZipArchive if available
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            $result = $zip->open($temp_file);
            
            if ($result === TRUE) {
                $temp_extract = sys_get_temp_dir() . '/tcpdf_extract_' . time() . '/';
                
                if (!$zip->extractTo($temp_extract)) {
                    $zip->close();
                    unlink($temp_file);
                    return new WP_Error('extract_failed', 'Failed to extract ZIP file');
                }
                
                $zip->close();
                
                // Find extracted folder (should be TCPDF-main)
                $folders = glob($temp_extract . 'TCPDF*');
                if (!empty($folders)) {
                    $source_folder = $folders[0];
                    
                    // Move to target location
                    if (self::recursive_copy($source_folder, $target_dir)) {
                        // Clean up
                        unlink($temp_file);
                        self::cleanup_directory($temp_extract);
                        
                        // Verify installation
                        if (self::is_library_available('tcpdf')) {
                            return array(
                                'success' => true,
                                'message' => 'TCPDF installed successfully via automatic download',
                                'method' => 'automatic',
                                'version' => self::get_library_version('tcpdf')
                            );
                        } else {
                            return new WP_Error('verify_failed', 'TCPDF files copied but library test failed');
                        }
                    } else {
                        return new WP_Error('copy_failed', 'Failed to copy files to target directory');
                    }
                } else {
                    return new WP_Error('extract_failed', 'No TCPDF folder found in extracted files');
                }
            } else {
                return new WP_Error('zip_error', 'Failed to open ZIP file: ' . $result);
            }
        } else {
            // ZipArchive not available
            unlink($temp_file);
            return new WP_Error('zip_unavailable', 'ZipArchive extension not available. Please install TCPDF manually.');
        }
    }
    
    /**
     * Recursively copy directory
     * 
     * @param string $src Source directory
     * @param string $dst Destination directory
     * @return bool Success
     */
    private static function recursive_copy($src, $dst) {
        if (!is_dir($src)) {
            return false;
        }
        
        if (!is_dir($dst)) {
            wp_mkdir_p($dst);
        }
        
        $dir = opendir($src);
        if (!$dir) {
            return false;
        }
        
        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                $src_path = $src . '/' . $file;
                $dst_path = $dst . '/' . $file;
                
                if (is_dir($src_path)) {
                    if (!self::recursive_copy($src_path, $dst_path)) {
                        closedir($dir);
                        return false;
                    }
                } else {
                    if (!copy($src_path, $dst_path)) {
                        closedir($dir);
                        return false;
                    }
                }
            }
        }
        
        closedir($dir);
        return true;
    }
    
    /**
     * Clean up directory recursively
     * 
     * @param string $dir Directory to clean up
     */
    private static function cleanup_directory($dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                if (is_dir($path)) {
                    self::cleanup_directory($path);
                } else {
                    unlink($path);
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * Test PDF generation with available library
     * 
     * @return array|WP_Error Test result
     */
    public static function test_pdf_generation() {
        // Find best available library
        $library = null;
        if (self::is_library_available('dompdf')) {
            $library = 'dompdf';
        } elseif (self::is_library_available('tcpdf')) {
            $library = 'tcpdf';
        } else {
            return new WP_Error('no_library', 'No PDF library is available for testing');
        }
        
        // Create test HTML content
        $test_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PDF Test</title></head><body>';
        $test_html .= '<h1 style="color: #96588a;">PDF Generation Test</h1>';
        $test_html .= '<p><strong>Library:</strong> ' . strtoupper($library) . '</p>';
        $test_html .= '<p><strong>Date:</strong> ' . current_time('Y-m-d H:i:s') . '</p>';
        $test_html .= '<p><strong>WordPress Version:</strong> ' . get_bloginfo('version') . '</p>';
        $test_html .= '<p><strong>PHP Version:</strong> ' . PHP_VERSION . '</p>';
        $test_html .= '<p>This test PDF verifies that the PDF library is working correctly with your WordPress installation.</p>';
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
            $success = false;
            
            if ($library === 'dompdf') {
                $success = self::test_dompdf($test_html, $test_file);
            } elseif ($library === 'tcpdf') {
                $success = self::test_tcpdf($test_html, $test_file);
            }
            
            if ($success && file_exists($test_file)) {
                return array(
                    'success' => true,
                    'message' => 'PDF test successful using ' . strtoupper($library),
                    'library' => $library,
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
            self::load_library('dompdf');
            
            if (class_exists('Dompdf\\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $options = $dompdf->getOptions();
                $options->set('defaultFont', 'Arial');
            } else {
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
     * Test TCPDF generation
     * 
     * @param string $html HTML content
     * @param string $output_path Output file path
     * @return bool Success
     */
    private static function test_tcpdf($html, $output_path) {
        try {
            self::load_library('tcpdf');
            
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('WooCommerce Manual Invoices Pro');
            $pdf->SetAuthor('Test');
            $pdf->SetTitle('PDF Test');
            
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            
            $pdf->AddPage();
            $pdf->SetFont('arial', '', 11);
            $pdf->writeHTML($html, true, false, true, false, '');
            
            $pdf->Output($output_path, 'F');
            
            return file_exists($output_path);
            
        } catch (Exception $e) {
            error_log('TCPDF test error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get the best available library for PDF generation
     * 
     * @return string|false Library key or false if none available
     */
    public static function get_best_available_library() {
        // Prefer DomPDF as it's bundled and generally easier to use
        if (self::is_library_available('dompdf')) {
            return 'dompdf';
        }
        
        if (self::is_library_available('tcpdf')) {
            return 'tcpdf';
        }
        
        return false;
    }
    
    /**
     * AJAX handler for installing PDF libraries
     * 
     * @param string $library Library to install
     * @param string $method Installation method
     * @return array|WP_Error Installation result
     */
    public static function install_pdf_library($library, $method = 'auto') {
        if ($library === 'tcpdf') {
            return self::install_tcpdf_automatically();
        } else {
            return new WP_Error('unsupported', 'Automatic installation not supported for ' . $library);
        }
    }
}