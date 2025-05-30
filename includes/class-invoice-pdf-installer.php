<?php
/**
 * Complete SSL Bypass Solution for DomPDF Installation
 * 
 * This file provides comprehensive PDF library installation with multiple fallback methods
 * and complete SSL bypass capabilities for problematic hosting environments.
 * 
 * Save as: includes/class-invoice-pdf-installer.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF_Installer {
    
    /**
     * Available PDF libraries with download information
     */
    private static $pdf_libraries = array(
        'dompdf' => array(
            'name' => 'DomPDF',
            'version' => '2.0.4',
            'download_url' => 'https://github.com/dompdf/dompdf/archive/refs/tags/v2.0.4.zip',
            'folder_name' => 'dompdf-2.0.4',
            'main_file' => 'autoload.inc.php',
            'test_class' => 'Dompdf\\Dompdf',
            'fallback_class' => 'DOMPDF',
            'composer_package' => 'dompdf/dompdf',
            'description' => 'Best overall compatibility with HTML/CSS. Recommended for most users.'
        ),
        'tcpdf' => array(
            'name' => 'TCPDF',
            'version' => '6.6.5',
            'download_url' => 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.5.zip',
            'folder_name' => 'TCPDF-6.6.5',
            'main_file' => 'tcpdf.php',
            'test_class' => 'TCPDF',
            'composer_package' => 'tecnickcom/tcpdf',
            'description' => 'Excellent for complex layouts and Unicode support. Good alternative to DomPDF.'
        ),
        'mpdf' => array(
            'name' => 'mPDF',
            'version' => '8.2.2',
            'download_url' => 'https://github.com/mpdf/mpdf/archive/refs/tags/v8.2.2.zip',
            'folder_name' => 'mpdf-8.2.2',
            'main_file' => 'autoload.php',
            'test_class' => 'Mpdf\\Mpdf',
            'composer_package' => 'mpdf/mpdf',
            'description' => 'Good balance of features and performance. Supports most CSS properties.'
        ),
        'fpdf' => array(
            'name' => 'FPDF',
            'version' => '1.85',
            'download_url' => 'http://www.fpdf.org/en/download/fpdf185.zip',
            'folder_name' => 'fpdf185',
            'main_file' => 'fpdf.php',
            'test_class' => 'FPDF',
            'description' => 'Basic PDF generation. Lightweight but limited styling options.'
        )
    );
    
    /**
     * Primary installation method - Direct download with multiple fallbacks
     * This completely bypasses GitHub API and SSL issues
     * 
     * @param string $library Library to install (dompdf, tcpdf, mpdf, fpdf)
     * @param string $method Installation method (auto, composer, download, manual)
     * @return array|WP_Error Installation result
     */
    public static function install_pdf_library($library = 'dompdf', $method = 'auto') {
        if (!isset(self::$pdf_libraries[$library])) {
            return new WP_Error('invalid_library', 'Invalid PDF library specified: ' . $library);
        }
        
        $lib_info = self::$pdf_libraries[$library];
        $plugin_dir = WC_MANUAL_INVOICES_PLUGIN_PATH;
        $lib_dir = $plugin_dir . 'lib/';
        $target_dir = $lib_dir . $library . '/';
        
        // Create lib directory if it doesn't exist
        if (!file_exists($lib_dir)) {
            wp_mkdir_p($lib_dir);
        }
        
        // Check if already installed and working
        if (self::is_library_installed($library)) {
            return array(
                'success' => true,
                'message' => sprintf('%s is already installed and working', $lib_info['name']),
                'method' => 'existing',
                'library' => $library,
                'path' => $target_dir
            );
        }
        
        // Determine installation methods to try
        $methods_to_try = array();
        
        if ($method === 'auto') {
            // Try all available methods in order of preference
            if (self::is_composer_available()) {
                $methods_to_try[] = 'composer';
            }
            $methods_to_try[] = 'download';
        } elseif ($method === 'composer' && self::is_composer_available()) {
            $methods_to_try[] = 'composer';
        } elseif ($method === 'download') {
            $methods_to_try[] = 'download';
        } else {
            return self::get_manual_installation_instructions($library, $lib_info);
        }
        
        $last_error = null;
        
        // Try each installation method
        foreach ($methods_to_try as $install_method) {
            $result = null;
            
            switch ($install_method) {
                case 'composer':
                    $result = self::install_via_composer($library, $lib_info);
                    break;
                case 'download':
                    $result = self::install_via_download($library, $lib_info, $target_dir);
                    break;
            }
            
            if ($result && !is_wp_error($result) && isset($result['success']) && $result['success']) {
                return $result;
            }
            
            if (is_wp_error($result)) {
                $last_error = $result;
                error_log('WC Manual Invoices: ' . $install_method . ' installation failed for ' . $library . ': ' . $result->get_error_message());
            }
        }
        
        // All automatic methods failed, return manual instructions
        return self::get_manual_installation_instructions($library, $lib_info, $last_error);
    }
    
    /**
     * Install library via Composer
     */
    private static function install_via_composer($library, $lib_info) {
        if (!isset($lib_info['composer_package'])) {
            return new WP_Error('composer_unsupported', 'This library does not support Composer installation');
        }
        
        $package = $lib_info['composer_package'];
        $plugin_dir = WC_MANUAL_INVOICES_PLUGIN_PATH;
        
        // Create composer.json if it doesn't exist
        $composer_json = $plugin_dir . 'composer.json';
        if (!file_exists($composer_json)) {
            self::create_composer_json($plugin_dir);
        }
        
        // Try different composer commands
        $composer_commands = array(
            'composer require ' . $package . ' --no-dev --optimize-autoloader',
            'composer.phar require ' . $package . ' --no-dev --optimize-autoloader',
            '/usr/local/bin/composer require ' . $package . ' --no-dev --optimize-autoloader'
        );
        
        foreach ($composer_commands as $base_command) {
            $command = sprintf('cd %s && %s 2>&1', escapeshellarg($plugin_dir), $base_command);
            
            $output = array();
            $return_var = null;
            exec($command, $output, $return_var);
            
            if ($return_var === 0) {
                // Verify installation
                if (self::is_library_installed($library)) {
                    return array(
                        'success' => true,
                        'message' => sprintf('%s installed successfully via Composer', $lib_info['name']),
                        'method' => 'composer',
                        'library' => $library,
                        'output' => implode("\n", $output)
                    );
                }
            }
        }
        
        return new WP_Error('composer_failed', 'Composer installation failed. Output: ' . implode("\n", $output));
    }
    
    /**
     * Install library via direct download with multiple fallback methods
     */
    private static function install_via_download($library, $lib_info, $target_dir) {
        $temp_file = sys_get_temp_dir() . '/' . $library . '_' . time() . '.zip';
        
        // Multiple download sources to try
        $download_sources = self::get_download_sources($lib_info);
        
        foreach ($download_sources as $source) {
            $result = self::download_and_extract($source, $temp_file, $target_dir, $lib_info);
            
            if (!is_wp_error($result)) {
                return array(
                    'success' => true,
                    'message' => sprintf('%s installed successfully from %s', $lib_info['name'], $source['name']),
                    'method' => 'download',
                    'library' => $library,
                    'source' => $source['name'],
                    'path' => $target_dir
                );
            }
            
            error_log('WC Manual Invoices: Download failed from ' . $source['name'] . ': ' . $result->get_error_message());
        }
        
        return new WP_Error('all_downloads_failed', 'All download sources failed. Please try manual installation.');
    }
    
    /**
     * Get multiple download sources for reliability
     */
    private static function get_download_sources($lib_info) {
        $sources = array();
        
        // Primary source - GitHub releases
        $sources[] = array(
            'name' => 'GitHub Direct',
            'url' => $lib_info['download_url'],
            'folder_name' => $lib_info['folder_name']
        );
        
        // Alternative GitHub URLs
        if (strpos($lib_info['download_url'], 'github.com') !== false) {
            // Try codeload.github.com as alternative
            $alt_url = str_replace('github.com', 'codeload.github.com', $lib_info['download_url']);
            $alt_url = str_replace('/archive/', '/zip/', $alt_url);
            
            $sources[] = array(
                'name' => 'GitHub Codeload',
                'url' => $alt_url,
                'folder_name' => $lib_info['folder_name']
            );
            
            // Try zipball format
            $zipball_url = str_replace('/archive/refs/tags/', '/zipball/', $lib_info['download_url']);
            $sources[] = array(
                'name' => 'GitHub Zipball',
                'url' => $zipball_url,
                'folder_name' => '*' // Variable folder name
            );
        }
        
        return $sources;
    }
    
    /**
     * Download and extract library with multiple methods
     */
    private static function download_and_extract($source, $temp_file, $target_dir, $lib_info) {
        // Try downloading with different methods
        $download_methods = array(
            array('method' => 'wp_remote_get', 'name' => 'WordPress HTTP API'),
            array('method' => 'curl', 'name' => 'cURL'),
            array('method' => 'wget', 'name' => 'wget')
        );
        
        $download_success = false;
        
        foreach ($download_methods as $method_info) {
            $result = self::download_file($source['url'], $temp_file, $method_info['method']);
            
            if (!is_wp_error($result)) {
                $download_success = true;
                break;
            }
        }
        
        if (!$download_success) {
            return new WP_Error('download_failed', 'Failed to download from ' . $source['url']);
        }
        
        // Extract the downloaded file
        $extract_result = self::extract_archive($temp_file, $target_dir, $source['folder_name']);
        
        // Clean up temp file
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        if (is_wp_error($extract_result)) {
            return $extract_result;
        }
        
        // Verify installation
        if (!file_exists($target_dir . $lib_info['main_file'])) {
            return new WP_Error('verification_failed', 'Main library file not found after extraction');
        }
        
        return true;
    }
    
    /**
     * Download file using specified method
     */
    private static function download_file($url, $destination, $method = 'wp_remote_get') {
        switch ($method) {
            case 'wp_remote_get':
                return self::download_via_wp_http($url, $destination);
            case 'curl':
                return self::download_via_curl($url, $destination);
            case 'wget':
                return self::download_via_wget($url, $destination);
            default:
                return new WP_Error('invalid_method', 'Invalid download method');
        }
    }
    
    /**
     * Download via WordPress HTTP API with maximum compatibility
     */
    private static function download_via_wp_http($url, $destination) {
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
        
        if (file_put_contents($destination, $body) === false) {
            return new WP_Error('wp_http_error', 'Failed to save downloaded file');
        }
        
        return true;
    }
    
    /**
     * Download via cURL with SSL bypass
     */
    private static function download_via_curl($url, $destination) {
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
            CURLOPT_USERAGENT => 'WooCommerce-Manual-Invoices/1.0',
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
        
        if (file_put_contents($destination, $data) === false) {
            return new WP_Error('curl_error', 'Failed to save downloaded file');
        }
        
        return true;
    }
    
    /**
     * Download via wget with SSL bypass
     */
    private static function download_via_wget($url, $destination) {
        if (!function_exists('exec') || !self::is_command_available('wget')) {
            return new WP_Error('wget_unavailable', 'wget is not available');
        }
        
        $command = sprintf(
            'wget --no-check-certificate --timeout=300 --tries=3 -O %s %s 2>&1',
            escapeshellarg($destination),
            escapeshellarg($url)
        );
        
        $output = array();
        $return_var = null;
        exec($command, $output, $return_var);
        
        if ($return_var !== 0 || !file_exists($destination)) {
            return new WP_Error('wget_error', 'wget failed: ' . implode("\n", $output));
        }
        
        return true;
    }
    
    /**
     * Extract archive to target directory
     */
    private static function extract_archive($zip_file, $target_dir, $folder_pattern) {
        // Try PHP's ZipArchive first
        if (class_exists('ZipArchive')) {
            $result = self::extract_with_ziparchive($zip_file, $target_dir, $folder_pattern);
            if (!is_wp_error($result)) {
                return $result;
            }
        }
        
        // Fallback to command line unzip
        if (self::is_command_available('unzip')) {
            return self::extract_with_unzip($zip_file, $target_dir, $folder_pattern);
        }
        
        return new WP_Error('extraction_failed', 'No extraction method available (ZipArchive or unzip command)');
    }
    
    /**
     * Extract using PHP ZipArchive
     */
    private static function extract_with_ziparchive($zip_file, $target_dir, $folder_pattern) {
        $zip = new ZipArchive;
        $result = $zip->open($zip_file);
        
        if ($result !== TRUE) {
            return new WP_Error('zip_error', "Failed to open ZIP file: error code {$result}");
        }
        
        $temp_extract = sys_get_temp_dir() . '/wc_pdf_extract_' . time() . '/';
        
        if (!$zip->extractTo($temp_extract)) {
            $zip->close();
            return new WP_Error('zip_error', 'Failed to extract ZIP file');
        }
        
        $zip->close();
        
        // Find the extracted folder
        $extracted_folders = self::find_extracted_folder($temp_extract, $folder_pattern);
        
        if (empty($extracted_folders)) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('extraction_error', 'Library folder not found in extracted archive');
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
        
        return true;
    }
    
    /**
     * Extract using command line unzip
     */
    private static function extract_with_unzip($zip_file, $target_dir, $folder_pattern) {
        $temp_extract = sys_get_temp_dir() . '/wc_pdf_extract_' . time() . '/';
        wp_mkdir_p($temp_extract);
        
        $command = sprintf(
            'cd %s && unzip -q %s 2>&1',
            escapeshellarg($temp_extract),
            escapeshellarg($zip_file)
        );
        
        $output = array();
        $return_var = null;
        exec($command, $output, $return_var);
        
        if ($return_var !== 0) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('unzip_error', 'Unzip command failed: ' . implode("\n", $output));
        }
        
        // Find and move the extracted folder
        $extracted_folders = self::find_extracted_folder($temp_extract, $folder_pattern);
        
        if (empty($extracted_folders)) {
            self::recursive_rmdir($temp_extract);
            return new WP_Error('extraction_error', 'Library folder not found in extracted archive');
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
        
        return true;
    }
    
    /**
     * Find extracted folder using pattern matching
     */
    private static function find_extracted_folder($temp_extract, $folder_pattern) {
        $extracted_folders = array();
        
        // Try exact pattern match first
        if ($folder_pattern !== '*') {
            $extracted_folders = glob($temp_extract . $folder_pattern);
        }
        
        // If no exact match or pattern is *, try common patterns
        if (empty($extracted_folders)) {
            $patterns = array(
                '*dompdf*',
                '*tcpdf*',
                '*mpdf*',
                '*fpdf*',
                '*'
            );
            
            foreach ($patterns as $pattern) {
                $folders = glob($temp_extract . $pattern);
                $folders = array_filter($folders, 'is_dir');
                if (!empty($folders)) {
                    $extracted_folders = $folders;
                    break;
                }
            }
        }
        
        return $extracted_folders;
    }
    
    /**
     * Check if library is installed and working
     */
    public static function is_library_installed($library) {
        if (!isset(self::$pdf_libraries[$library])) {
            return false;
        }
        
        $lib_info = self::$pdf_libraries[$library];
        
        // Check manual installation
        $manual_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/' . $library . '/' . $lib_info['main_file'];
        if (file_exists($manual_path)) {
            try {
                require_once $manual_path;
                return self::test_library_class($lib_info);
            } catch (Exception $e) {
                // Continue to other checks
            }
        }
        
        // Check composer installation
        $composer_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php';
        if (file_exists($composer_path)) {
            try {
                require_once $composer_path;
                return self::test_library_class($lib_info);
            } catch (Exception $e) {
                // Continue to other checks
            }
        }
        
        return false;
    }
    
    /**
     * Test if library class is available
     */
    private static function test_library_class($lib_info) {
        if (isset($lib_info['test_class']) && class_exists($lib_info['test_class'])) {
            return true;
        }
        
        if (isset($lib_info['fallback_class']) && class_exists($lib_info['fallback_class'])) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get available PDF libraries status
     */
    public static function get_pdf_library_status() {
        $status = array();
        
        foreach (self::$pdf_libraries as $key => $info) {
            $status[$key] = array(
                'name' => $info['name'],
                'version' => $info['version'],
                'available' => self::is_library_installed($key),
                'description' => $info['description']
            );
        }
        
        return $status;
    }
    
    /**
     * Get best available library
     */
    public static function get_best_available_library() {
        $preferred_order = array('dompdf', 'tcpdf', 'mpdf', 'fpdf');
        
        foreach ($preferred_order as $library) {
            if (self::is_library_installed($library)) {
                return $library;
            }
        }
        
        return false;
    }
    
    /**
     * Get system information for diagnostics
     */
    public static function get_system_info() {
        return array(
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'extensions' => array(
                'gd' => extension_loaded('gd'),
                'dom' => extension_loaded('dom'),
                'mbstring' => extension_loaded('mbstring'),
                'zip' => extension_loaded('zip'),
                'curl' => extension_loaded('curl'),
                'openssl' => extension_loaded('openssl'),
                'zlib' => extension_loaded('zlib')
            ),
            'functions' => array(
                'exec' => function_exists('exec'),
                'shell_exec' => function_exists('shell_exec'),
                'curl_init' => function_exists('curl_init'),
                'file_get_contents' => function_exists('file_get_contents'),
                'fopen' => function_exists('fopen')
            ),
            'write_permissions' => array(
                'plugin_dir' => is_writable(WC_MANUAL_INVOICES_PLUGIN_PATH),
                'uploads_dir' => is_writable(wp_upload_dir()['basedir']),
                'temp_dir' => is_writable(sys_get_temp_dir())
            ),
            'server_info' => array(
                'software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
                'os' => PHP_OS,
                'architecture' => php_uname('m')
            )
        );
    }
    
    /**
     * Get installation methods available on this system
     */
    public static function get_available_methods() {
        $methods = array();
        
        if (self::is_composer_available()) {
            $methods['composer'] = array(
                'name' => 'Composer',
                'description' => 'Automatic installation using Composer package manager',
                'difficulty' => 'Easy',
                'available' => true
            );
        }
        
        $system = self::get_system_info();
        if ($system['extensions']['curl'] || $system['functions']['file_get_contents']) {
            $methods['download'] = array(
                'name' => 'Direct Download',
                'description' => 'Download and install automatically',
                'difficulty' => 'Easy',
                'available' => true
            );
        }
        
        $methods['manual'] = array(
            'name' => 'Manual Installation',
            'description' => 'Download and upload files manually via FTP',
            'difficulty' => 'Medium',
            'available' => true
        );
        
        return $methods;
    }
    
    /**
     * Get installation recommendations based on system capabilities
     */
    public static function get_recommendations() {
        $system = self::get_system_info();
        $recommendations = array();
        
        // Composer recommendation
        if (self::is_composer_available()) {
            $recommendations[] = array(
                'method' => 'composer',
                'title' => 'Composer Installation (Recommended)',
                'description' => 'Most reliable method using Composer package manager. Handles dependencies automatically.',
                'difficulty' => 'Easy',
                'command' => 'composer require dompdf/dompdf',
                'priority' => 1
            );
        }
        
        // Direct download recommendation
        if ($system['extensions']['curl'] || $system['functions']['file_get_contents']) {
            $recommendations[] = array(
                'method' => 'download',
                'title' => 'Automatic Download',
                'description' => 'Download and install PDF library files automatically with SSL bypass.',
                'difficulty' => 'Easy',
                'priority' => 2
            );
        }
        
        // Manual installation
        $recommendations[] = array(
            'method' => 'manual',
            'title' => 'Manual Installation',
            'description' => 'Download library files manually and upload via FTP. Most reliable but requires more steps.',
            'difficulty' => 'Medium',
            'priority' => 3
        );
        
        // Hosting support
        $recommendations[] = array(
            'method' => 'hosting',
            'title' => 'Contact Hosting Provider',
            'description' => 'Ask your hosting provider to install PDF libraries server-wide.',
            'difficulty' => 'Easy',
            'note' => 'Best option if you have multiple WordPress sites or limited server access.',
            'priority' => 4
        );
        
        // Sort by priority
        usort($recommendations, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });
        
        return $recommendations;
    }
    
    /**
     * Get manual installation instructions
     */
    private static function get_manual_installation_instructions($library, $lib_info, $last_error = null) {
        $instructions = array(
            'success' => false,
            'manual' => true,
            'library' => $library,
            'instructions' => array(
                'title' => sprintf('Manual Installation Required for %s', $lib_info['name']),
                'description' => 'Automatic installation failed. Please install manually using the steps below:',
                'steps' => array(
                    sprintf('1. Download %s from: %s', $lib_info['name'], $lib_info['download_url']),
                    '2. Extract the downloaded ZIP file',
                    sprintf('3. Rename the extracted folder to "%s"', $library),
                    sprintf('4. Upload the "%s" folder to: %s', $library, WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/'),
                    sprintf('5. Verify this file exists: %s', WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/' . $library . '/' . $lib_info['main_file']),
                    '6. Set file permissions: 755 for directories, 644 for files',
                    '7. Refresh this page to verify installation'
                ),
                'verification' => sprintf('After installation, this file should exist: %s', WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/' . $library . '/' . $lib_info['main_file']),
                'troubleshooting' => array(
                    'If you continue having issues:',
                    '• Contact your hosting provider about SSL certificate configuration',
                    '• Ask them to install PDF libraries via Composer server-side',
                    '• Use an alternative PDF library (TCPDF, mPDF, or FPDF)',
                    '• Enable PHP extensions: gd, dom, mbstring, zip'
                )
            )
        );
        
        if ($last_error && is_wp_error($last_error)) {
            $instructions['last_error'] = $last_error->get_error_message();
        }
        
        return $instructions;
    }
    
    /**
     * Create minimal composer.json file
     */
    private static function create_composer_json($plugin_dir) {
        $composer_config = array(
            'name' => 'wbcomdesigns/woocommerce-manual-invoices',
            'description' => 'WooCommerce Manual Invoices Pro - PDF Library Dependencies',
            'type' => 'wordpress-plugin',
            'require' => array(
                'php' => '>=8.0'
            ),
            'config' => array(
                'vendor-dir' => 'vendor',
                'optimize-autoloader' => true,
                'sort-packages' => true
            ),
            'minimum-stability' => 'stable',
            'prefer-stable' => true
        );
        
        $composer_json = $plugin_dir . 'composer.json';
        file_put_contents($composer_json, json_encode($composer_config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        return $composer_json;
    }
    
    /**
     * Check if Composer is available
     */
    public static function is_composer_available() {
        if (!function_exists('exec')) {
            return false;
        }
        
        $composer_commands = array('composer', 'composer.phar', '/usr/local/bin/composer', '/usr/bin/composer');
        
        foreach ($composer_commands as $composer) {
            $output = array();
            $return_var = null;
            exec("which {$composer} 2>/dev/null || command -v {$composer} 2>/dev/null", $output, $return_var);
            
            if ($return_var === 0 && !empty($output)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if command is available
     */
    private static function is_command_available($command) {
        if (!function_exists('exec')) {
            return false;
        }
        
        $output = array();
        $return_var = null;
        exec("which {$command} 2>/dev/null || command -v {$command} 2>/dev/null", $output, $return_var);
        
        return $return_var === 0 && !empty($output);
    }
    
    /**
     * Recursively remove directory
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
    
    /**
     * Quick installation method for dashboard
     */
    public static function quick_install_dompdf() {
        return self::install_pdf_library('dompdf', 'auto');
    }
    
    /**
     * Test PDF generation with installed library
     */
    public static function test_pdf_generation($library = null) {
        if (!$library) {
            $library = self::get_best_available_library();
        }
        
        if (!$library) {
            return new WP_Error('no_library', 'No PDF library is installed');
        }
        
        try {
            // Create simple test HTML
            $test_html = '<!DOCTYPE html>
<html>
<head>
    <title>PDF Test</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        h1 { color: #96588a; }
        .test-box { border: 2px solid #96588a; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>PDF Generation Test</h1>
    <div class="test-box">
        <p><strong>Library:</strong> ' . strtoupper($library) . '</p>
        <p><strong>Test Date:</strong> ' . current_time('Y-m-d H:i:s') . '</p>
        <p><strong>Status:</strong> PDF generation is working correctly!</p>
    </div>
    <p>This test PDF was generated by WooCommerce Manual Invoices Pro to verify that the PDF library is working properly.</p>
</body>
</html>';
            
            // Generate test PDF
            $upload_dir = wp_upload_dir();
            $pdf_dir = $upload_dir['basedir'] . '/wc-manual-invoices/';
            $test_file = $pdf_dir . 'test-' . time() . '.pdf';
            
            if (!file_exists($pdf_dir)) {
                wp_mkdir_p($pdf_dir);
            }
            
            $result = self::generate_test_pdf($library, $test_html, $test_file);
            
            if ($result) {
                return array(
                    'success' => true,
                    'message' => 'PDF generation test successful!',
                    'library' => $library,
                    'file_path' => $test_file,
                    'file_size' => filesize($test_file),
                    'download_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $test_file)
                );
            } else {
                return new WP_Error('generation_failed', 'PDF generation failed during test');
            }
            
        } catch (Exception $e) {
            return new WP_Error('test_error', 'PDF test failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate test PDF with specific library
     */
    private static function generate_test_pdf($library, $html, $output_file) {
        switch ($library) {
            case 'dompdf':
                return self::test_dompdf($html, $output_file);
            case 'tcpdf':
                return self::test_tcpdf($html, $output_file);
            case 'mpdf':
                return self::test_mpdf($html, $output_file);
            case 'fpdf':
                return self::test_fpdf($output_file);
            default:
                return false;
        }
    }
    
    /**
     * Test DomPDF generation
     */
    private static function test_dompdf($html, $output_file) {
        $dompdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
        $composer_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php';
        
        if (file_exists($dompdf_path)) {
            require_once $dompdf_path;
        } elseif (file_exists($composer_path)) {
            require_once $composer_path;
        } else {
            return false;
        }
        
        if (class_exists('Dompdf\\Dompdf')) {
            $dompdf = new \Dompdf\Dompdf();
        } elseif (class_exists('DOMPDF')) {
            $dompdf = new DOMPDF();
        } else {
            return false;
        }
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $pdf_content = $dompdf->output();
        
        return file_put_contents($output_file, $pdf_content) !== false;
    }
    
    /**
     * Test TCPDF generation
     */
    private static function test_tcpdf($html, $output_file) {
        $tcpdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/tcpdf.php';
        $composer_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php';
        
        if (file_exists($tcpdf_path)) {
            require_once $tcpdf_path;
        } elseif (file_exists($composer_path)) {
            require_once $composer_path;
        }
        
        if (!class_exists('TCPDF')) {
            return false;
        }
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output($output_file, 'F');
        
        return file_exists($output_file);
    }
    
    /**
     * Test mPDF generation
     */
    private static function test_mpdf($html, $output_file) {
        $composer_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php';
        
        if (file_exists($composer_path)) {
            require_once $composer_path;
        }
        
        if (!class_exists('Mpdf\\Mpdf')) {
            return false;
        }
        
        $mpdf = new \Mpdf\Mpdf(['format' => 'A4']);
        $mpdf->WriteHTML($html);
        $mpdf->Output($output_file, 'F');
        
        return file_exists($output_file);
    }
    
    /**
     * Test FPDF generation (simple text-based)
     */
    private static function test_fpdf($output_file) {
        $fpdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/fpdf/fpdf.php';
        
        if (file_exists($fpdf_path)) {
            require_once $fpdf_path;
        }
        
        if (!class_exists('FPDF')) {
            return false;
        }
        
        $pdf = new FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'PDF Generation Test', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Library: FPDF', 0, 1);
        $pdf->Cell(0, 10, 'Date: ' . current_time('Y-m-d H:i:s'), 0, 1);
        $pdf->Cell(0, 10, 'Status: Working correctly!', 0, 1);
        $pdf->Output('F', $output_file);
        
        return file_exists($output_file);
    }
}
?>