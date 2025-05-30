<?php
/**
 * Complete SSL Bypass Solution for DomPDF Installation
 * 
 * This completely bypasses GitHub API and SSL issues by using direct download
 * and fallback to alternative sources
 */

class WC_Manual_Invoice_PDF_Installer {
    
    /**
     * Primary installation method - Direct download without Composer
     * This is the most reliable method that bypasses all SSL and API issues
     */
    public static function install_dompdf_direct() {
        $plugin_dir = WC_MANUAL_INVOICES_PLUGIN_PATH;
        $lib_dir = $plugin_dir . 'lib/';
        $dompdf_dir = $lib_dir . 'dompdf/';
        
        // Create directories
        if (!file_exists($lib_dir)) {
            wp_mkdir_p($lib_dir);
        }
        
        // Multiple download sources to try (in order of preference)
        $download_sources = array(
            // Source 1: GitHub releases (direct ZIP, no API)
            array(
                'url' => 'https://github.com/dompdf/dompdf/archive/refs/tags/v2.0.3.zip',
                'name' => 'GitHub Direct',
                'extract_folder' => 'dompdf-2.0.3'
            ),
            // Source 2: GitHub codeload (alternative GitHub URL)
            array(
                'url' => 'https://codeload.github.com/dompdf/dompdf/zip/refs/tags/v2.0.3',
                'name' => 'GitHub Codeload',
                'extract_folder' => 'dompdf-2.0.3'
            ),
            // Source 3: Packagist direct download
            array(
                'url' => 'https://repo.packagist.org/p2/dompdf/dompdf.json',
                'name' => 'Packagist',
                'extract_folder' => 'dompdf-2.0.3',
                'type' => 'packagist_json' // Special handling
            ),
            // Source 4: Mirror/CDN sources
            array(
                'url' => 'https://github.com/dompdf/dompdf/zipball/v2.0.3',
                'name' => 'GitHub Zipball',
                'extract_folder' => 'dompdf-*' // Variable folder name
            )
        );
        
        foreach ($download_sources as $index => $source) {
            $result = self::download_and_extract_dompdf($source, $lib_dir, $dompdf_dir);
            
            if (!is_wp_error($result)) {
                return array(
                    'success' => true,
                    'message' => "DomPDF installed successfully from {$source['name']}",
                    'source' => $source['name'],
                    'method' => 'direct_download',
                    'path' => $dompdf_dir
                );
            }
            
            // Log the failed attempt
            error_log("WC Manual Invoices: Download attempt " . ($index + 1) . " failed from {$source['name']}: " . $result->get_error_message());
        }
        
        // All download sources failed
        return new WP_Error(
            'all_downloads_failed',
            'All download sources failed. This is likely due to server SSL configuration. Please try manual installation.'
        );
    }
    
    /**
     * Download and extract DomPDF from a source
     */
    private static function download_and_extract_dompdf($source, $lib_dir, $target_dir) {
        $temp_file = $lib_dir . 'dompdf_temp.zip';
        
        // Method 1: WordPress HTTP API (most compatible)
        $result = self::download_via_wp_http($source['url'], $temp_file);
        
        if (is_wp_error($result)) {
            // Method 2: cURL with SSL bypass
            $result = self::download_via_curl($source['url'], $temp_file);
        }
        
        if (is_wp_error($result)) {
            // Method 3: wget with SSL bypass
            $result = self::download_via_wget($source['url'], $temp_file);
        }
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        // Extract the downloaded file
        if (file_exists($temp_file)) {
            $extract_result = self::extract_dompdf_archive($temp_file, $lib_dir, $target_dir, $source['extract_folder']);
            unlink($temp_file); // Clean up temp file
            return $extract_result;
        }
        
        return new WP_Error('download_failed', 'Failed to download file');
    }
    
    /**
     * Download via WordPress HTTP API with maximum compatibility
     */
    private static function download_via_wp_http($url, $temp_file) {
        $args = array(
            'timeout' => 300,
            'sslverify' => false,
            'sslcertificates' => false,
            'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            'headers' => array(
                'Accept' => 'application/zip, application/octet-stream, */*',
                'Cache-Control' => 'no-cache',
            ),
            'httpversion' => '1.1',
            'blocking' => true,
            'compress' => false,
            'decompress' => false
        );
        
        $response = wp_remote_get($url, $args);
        
        if (is_wp_error($response)) {
            return new WP_Error('wp_http_error', 'WordPress HTTP error: ' . $response->get_error_message());
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('wp_http_error', "HTTP error: {$response_code}");
        }
        
        $body = wp_remote_retrieve_body($response);
        if (empty($body)) {
            return new WP_Error('wp_http_error', 'Downloaded file is empty');
        }
        
        if (file_put_contents($temp_file, $body) === false) {
            return new WP_Error('wp_http_error', 'Failed to save downloaded file');
        }
        
        return true;
    }
    
    /**
     * Download via cURL with SSL bypass
     */
    private static function download_via_curl($url, $temp_file) {
        if (!function_exists('curl_init')) {
            return new WP_Error('curl_unavailable', 'cURL is not available');
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_USERAGENT => 'WordPress-DomPDF-Installer/1.0',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/zip, application/octet-stream, */*',
                'Cache-Control: no-cache'
            )
        ));
        
        $data = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($data === false || !empty($error)) {
            return new WP_Error('curl_error', 'cURL error: ' . $error);
        }
        
        if ($http_code !== 200) {
            return new WP_Error('curl_error', "HTTP error: {$http_code}");
        }
        
        if (file_put_contents($temp_file, $data) === false) {
            return new WP_Error('curl_error', 'Failed to save downloaded file');
        }
        
        return true;
    }
    
    /**
     * Download via wget with SSL bypass
     */
    private static function download_via_wget($url, $temp_file) {
        if (!self::is_command_available('wget')) {
            return new WP_Error('wget_unavailable', 'wget is not available');
        }
        
        $command = sprintf(
            'wget --no-check-certificate --timeout=300 --tries=3 -O %s %s 2>&1',
            escapeshellarg($temp_file),
            escapeshellarg($url)
        );
        
        ob_start();
        $return_var = null;
        $output = array();
        exec($command, $output, $return_var);
        ob_end_clean();
        
        if ($return_var !== 0 || !file_exists($temp_file)) {
            return new WP_Error('wget_error', 'wget failed: ' . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * Extract DomPDF archive
     */
    private static function extract_dompdf_archive($zip_file, $lib_dir, $target_dir, $extract_folder_pattern) {
        // Try PHP's ZipArchive first
        if (class_exists('ZipArchive')) {
            $result = self::extract_with_ziparchive($zip_file, $lib_dir, $target_dir, $extract_folder_pattern);
            if (!is_wp_error($result)) {
                return $result;
            }
        }
        
        // Fallback to command line unzip
        if (self::is_command_available('unzip')) {
            return self::extract_with_unzip($zip_file, $lib_dir, $target_dir, $extract_folder_pattern);
        }
        
        return new WP_Error('extraction_failed', 'No extraction method available (ZipArchive or unzip command)');
    }
    
    /**
     * Extract using PHP ZipArchive
     */
    private static function extract_with_ziparchive($zip_file, $lib_dir, $target_dir, $extract_folder_pattern) {
        $zip = new ZipArchive;
        $result = $zip->open($zip_file);
        
        if ($result !== TRUE) {
            return new WP_Error('zip_error', "Failed to open ZIP file: error code {$result}");
        }
        
        $temp_extract = $lib_dir . 'temp_extract_' . time() . '/';
        
        if (!$zip->extractTo($temp_extract)) {
            $zip->close();
            return new WP_Error('zip_error', 'Failed to extract ZIP file');
        }
        
        $zip->close();
        
        // Find the extracted folder
        $extracted_folders = glob($temp_extract . $extract_folder_pattern);
        
        if (empty($extracted_folders)) {
            // Try alternative patterns
            $extracted_folders = glob($temp_extract . 'dompdf*');
            if (empty($extracted_folders)) {
                $extracted_folders = glob($temp_extract . '*');
                // Filter to directories only
                $extracted_folders = array_filter($extracted_folders, 'is_dir');
            }
        }
        
        if (empty($extracted_folders)) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('extraction_error', 'No DomPDF folder found in extracted archive');
        }
        
        $source_folder = $extracted_folders[0];
        
        // Remove existing target directory if it exists
        if (is_dir($target_dir)) {
            self::recursive_rmdir($target_dir);
        }
        
        // Move to final location
        if (!rename($source_folder, $target_dir)) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('extraction_error', 'Failed to move extracted folder to target location');
        }
        
        // Clean up temp directory
        self::recursive_rmdir($temp_extract);
        
        // Verify installation
        if (self::verify_dompdf_files($target_dir)) {
            return true;
        } else {
            return new WP_Error('verification_failed', 'DomPDF files not found after extraction');
        }
    }
    
    /**
     * Extract using command line unzip
     */
    private static function extract_with_unzip($zip_file, $lib_dir, $target_dir, $extract_folder_pattern) {
        $temp_extract = $lib_dir . 'temp_extract_' . time() . '/';
        wp_mkdir_p($temp_extract);
        
        $command = sprintf(
            'cd %s && unzip -q %s 2>&1',
            escapeshellarg($temp_extract),
            escapeshellarg($zip_file)
        );
        
        ob_start();
        $return_var = null;
        $output = array();
        exec($command, $output, $return_var);
        ob_end_clean();
        
        if ($return_var !== 0) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('unzip_error', 'Unzip command failed: ' . implode("\n", $output));
        }
        
        // Find and move the extracted folder (same logic as ZipArchive method)
        $extracted_folders = glob($temp_extract . $extract_folder_pattern);
        
        if (empty($extracted_folders)) {
            $extracted_folders = glob($temp_extract . 'dompdf*');
            if (empty($extracted_folders)) {
                $extracted_folders = glob($temp_extract . '*');
                $extracted_folders = array_filter($extracted_folders, 'is_dir');
            }
        }
        
        if (empty($extracted_folders)) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('extraction_error', 'No DomPDF folder found in extracted archive');
        }
        
        $source_folder = $extracted_folders[0];
        
        if (is_dir($target_dir)) {
            self::recursive_rmdir($target_dir);
        }
        
        if (!rename($source_folder, $target_dir)) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('extraction_error', 'Failed to move extracted folder');
        }
        
        self::recursive_rmdir($temp_extract);
        
        if (self::verify_dompdf_files($target_dir)) {
            return true;
        } else {
            return new WP_Error('verification_failed', 'DomPDF files not found after extraction');
        }
    }
    
    /**
     * Verify DomPDF installation
     */
    private static function verify_dompdf_files($dompdf_dir) {
        $required_files = array(
            'autoload.inc.php',
            'src/Dompdf.php',
            'src/Options.php'
        );
        
        foreach ($required_files as $file) {
            if (!file_exists($dompdf_dir . $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Completely bypass Composer - create a minimal composer.json that won't trigger downloads
     */
    public static function create_minimal_composer_config($plugin_dir) {
        $composer_json = $plugin_dir . 'composer.json';
        
        // Create a minimal config that just sets up autoloading for manually installed libraries
        $config = array(
            'name' => 'wbcomdesigns/woocommerce-manual-invoices',
            'type' => 'wordpress-plugin',
            'require' => array(), // Empty - no automatic downloads
            'autoload' => array(
                'files' => array(
                    'lib/dompdf/autoload.inc.php'
                )
            ),
            'config' => array(
                'vendor-dir' => 'vendor',
                'preferred-install' => 'dist',
                'optimize-autoloader' => true
            )
        );
        
        file_put_contents($composer_json, json_encode($config, JSON_PRETTY_PRINT));
        
        return $composer_json;
    }
    
    /**
     * Master installation method that completely avoids Composer for DomPDF
     */
    public static function install_dompdf($method = 'auto') {
        // For DomPDF, always use direct download to avoid Composer issues
        if ($method === 'auto' || $method === 'direct' || $method === 'composer') {
            $result = self::install_dompdf_direct();
            
            if (!is_wp_error($result) && isset($result['success'])) {
                // Create minimal composer.json for autoloading
                self::create_minimal_composer_config(WC_MANUAL_INVOICES_PLUGIN_PATH);
                return $result;
            }
        }
        
        // If direct download fails, provide manual instructions
        return array(
            'success' => false,
            'manual' => true,
            'instructions' => array(
                'title' => 'Manual Installation Required',
                'description' => 'Automatic download failed due to server restrictions. Please install manually:',
                'steps' => array(
                    '1. Download DomPDF from: https://github.com/dompdf/dompdf/releases/tag/v2.0.3',
                    '2. Extract the downloaded ZIP file',
                    '3. Rename the extracted folder to "dompdf"',
                    '4. Upload the "dompdf" folder to: ' . WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/',
                    '5. Verify this file exists: ' . WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php',
                    '6. Set file permissions: 755 for directories, 644 for files',
                    '7. Refresh this page to verify installation'
                ),
                'verification' => 'After installation, the following file should exist: ' . WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php',
                'troubleshooting' => array(
                    'If you continue having issues:',
                    '• Contact your hosting provider about SSL certificate configuration',
                    '• Ask them to install DomPDF via Composer server-side',
                    '• Use an alternative PDF library like TCPDF or FPDF'
                )
            )
        );
    }
    
    /**
     * Helper functions (keep existing ones)
     */
    private static function recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir . "/" . $object)) {
                        self::recursive_rmdir($dir . DIRECTORY_SEPARATOR . $object);
                    } else {
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    private static function is_command_available($command) {
        if (!function_exists('exec')) {
            return false;
        }
        
        $return_var = null;
        $output = array();
        exec("which {$command}", $output, $return_var);
        
        return $return_var === 0;
    }
}

/**
 * Update the PDF class to handle manual installation
 */
class WC_Manual_Invoice_PDF {
    
    /**
     * Enhanced library detection that works with manual installation
     */
    private static function is_dompdf_available() {
        // Check for manually installed DomPDF
        $manual_paths = array(
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php',
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/src/Dompdf.php'
        );
        
        foreach ($manual_paths as $path) {
            if (file_exists($path)) {
                // Try to include and test
                try {
                    if (strpos($path, 'autoload.inc.php') !== false) {
                        require_once $path;
                        return class_exists('Dompdf') || class_exists('\Dompdf\Dompdf');
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        // Check for Composer-installed DomPDF
        $composer_paths = array(
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php',
            ABSPATH . 'vendor/autoload.php',
            WP_CONTENT_DIR . '/vendor/autoload.php'
        );
        
        foreach ($composer_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    return class_exists('\Dompdf\Dompdf') || class_exists('Dompdf');
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Load DomPDF with proper path handling
     */
    private static function load_dompdf() {
        // Try manual installation first
        $manual_autoload = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
        if (file_exists($manual_autoload)) {
            require_once $manual_autoload;
            if (class_exists('Dompdf')) {
                return new Dompdf();
            } elseif (class_exists('\Dompdf\Dompdf')) {
                return new \Dompdf\Dompdf();
            }
        }
        
        // Try Composer installation
        $composer_autoload = WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php';
        if (file_exists($composer_autoload)) {
            require_once $composer_autoload;
            if (class_exists('\Dompdf\Dompdf')) {
                return new \Dompdf\Dompdf();
            }
        }
        
        return false;
    }
    
    /**
     * Updated DomPDF generation method
     */
    private static function generate_with_dompdf($order, $pdf_path) {
        try {
            $dompdf = self::load_dompdf();
            
            if (!$dompdf) {
                throw new Exception('DomPDF could not be loaded');
            }
            
            // Configure DomPDF options
            if (method_exists($dompdf, 'getOptions')) {
                $options = $dompdf->getOptions();
                $options->set('defaultFont', 'Arial');
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true);
            }
            
            // Get HTML content
            $html_content = self::get_invoice_html($order);
            
            // Load HTML
            $dompdf->loadHtml($html_content);
            
            // Set paper size and orientation
            $dompdf->setPaper('A4', 'portrait');
            
            // Render PDF
            $dompdf->render();
            
            // Get PDF content
            $pdf_content = $dompdf->output();
            
            // Save to file
            if (file_put_contents($pdf_path, $pdf_content)) {
                return $pdf_path;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (DomPDF): ' . $e->getMessage());
        }
        
        return false;
    }
}
?>