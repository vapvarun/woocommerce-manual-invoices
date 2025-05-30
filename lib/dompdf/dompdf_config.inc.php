<?php
/**
 * DomPDF Configuration File
 * Save as: lib/dompdf/dompdf_config.inc.php
 * 
 * This file provides configuration for the bundled DomPDF library
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Only define constants if they haven't been defined yet
if (!defined('DOMPDF_ENABLE_AUTOLOAD')) {
    
    /**
     * Enable HTML5 parser
     */
    define("DOMPDF_ENABLE_HTML5PARSER", true);
    
    /**
     * Enable CSS parser
     */
    define("DOMPDF_ENABLE_CSS_FLOAT", true);
    
    /**
     * Enable remote file access (for images)
     */
    define("DOMPDF_ENABLE_REMOTE", true);
    
    /**
     * Set default paper size
     */
    define("DOMPDF_DEFAULT_PAPER_SIZE", "A4");
    
    /**
     * Set default paper orientation
     */
    define("DOMPDF_DEFAULT_PAPER_ORIENTATION", "portrait");
    
    /**
     * Set default font
     */
    define("DOMPDF_DEFAULT_FONT", "Arial");
    
    /**
     * Enable autoloader
     */
    define("DOMPDF_ENABLE_AUTOLOAD", true);
    
    /**
     * Set DPI for rendering
     */
    define("DOMPDF_DPI", 96);
    
    /**
     * Font directory
     */
    if (!defined('DOMPDF_FONT_DIR')) {
        define("DOMPDF_FONT_DIR", dirname(__FILE__) . "/lib/fonts/");
    }
    
    /**
     * Font cache directory
     */
    if (!defined('DOMPDF_FONT_CACHE')) {
        $upload_dir = wp_upload_dir();
        $font_cache = $upload_dir['basedir'] . '/wc-manual-invoices/dompdf-fonts/';
        
        // Create font cache directory if it doesn't exist
        if (!file_exists($font_cache)) {
            wp_mkdir_p($font_cache);
        }
        
        define("DOMPDF_FONT_CACHE", $font_cache);
    }
    
    /**
     * Temp directory
     */
    if (!defined('DOMPDF_TEMP_DIR')) {
        $upload_dir = wp_upload_dir();
        $temp_dir = $upload_dir['basedir'] . '/wc-manual-invoices/dompdf-temp/';
        
        // Create temp directory if it doesn't exist
        if (!file_exists($temp_dir)) {
            wp_mkdir_p($temp_dir);
        }
        
        define("DOMPDF_TEMP_DIR", $temp_dir);
    }
    
    /**
     * Enable font subsetting
     */
    define("DOMPDF_ENABLE_FONTSUBSETTING", true);
    
    /**
     * Unicode support
     */
    define("DOMPDF_UNICODE_ENABLED", true);
    
    /**
     * Image DPI
     */
    define("DOMPDF_IMAGE_DPI", 96);
    
    /**
     * Maximum execution time for PDF generation
     */
    define("DOMPDF_MAX_EXECUTION_TIME", 120);
    
    /**
     * Memory limit for PDF generation
     */
    define("DOMPDF_MEMORY_LIMIT", "256M");
    
    /**
     * Enable PDF compression
     */
    define("DOMPDF_PDF_BACKEND", "CPDF");
    
    /**
     * Security settings
     */
    define("DOMPDF_ENABLE_PHP", false);
    define("DOMPDF_ENABLE_JAVASCRIPT", false);
    
    /**
     * Log file location
     */
    if (!defined('DOMPDF_LOG_OUTPUT_FILE')) {
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/wc-manual-invoices/dompdf.log';
        define("DOMPDF_LOG_OUTPUT_FILE", $log_file);
    }
    
    /**
     * Enable logging
     */
    define("DOMPDF_ENABLE_LOG", false); // Set to true for debugging
    
    /**
     * Chroot settings for security
     */
    if (!defined('DOMPDF_CHROOT')) {
        define("DOMPDF_CHROOT", ABSPATH);
    }
    
    /**
     * Set admin username/password (optional, for debugging)
     */
    define("DOMPDF_ADMIN_USERNAME", "admin");
    define("DOMPDF_ADMIN_PASSWORD", "password");
    
}

/**
 * WordPress-specific DomPDF configuration
 */
class WC_DomPDF_Config {
    
    /**
     * Initialize DomPDF configuration for WordPress
     */
    public static function init() {
        // Set memory limit if not already set higher
        $current_limit = ini_get('memory_limit');
        if (self::return_bytes($current_limit) < self::return_bytes('256M')) {
            ini_set('memory_limit', '256M');
        }
        
        // Set max execution time for PDF generation
        if (ini_get('max_execution_time') < 120) {
            ini_set('max_execution_time', 120);
        }
        
        // Ensure required directories exist
        self::ensure_directories();
    }
    
    /**
     * Ensure required directories exist with proper permissions
     */
    private static function ensure_directories() {
        $dirs = array(
            DOMPDF_FONT_CACHE,
            DOMPDF_TEMP_DIR,
            dirname(DOMPDF_LOG_OUTPUT_FILE)
        );
        
        foreach ($dirs as $dir) {
            if (!file_exists($dir)) {
                wp_mkdir_p($dir);
                
                // Add .htaccess for security
                $htaccess = $dir . '.htaccess';
                if (!file_exists($htaccess)) {
                    file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
                }
                
                // Add index.php for extra security
                $index = $dir . 'index.php';
                if (!file_exists($index)) {
                    file_put_contents($index, '<?php // Silence is golden');
                }
            }
        }
    }
    
    /**
     * Convert memory limit strings to bytes
     */
    private static function return_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $val = (int) $val;
        
        switch($last) {
            case 'g':
                $val *= 1024;
            case 'm':
                $val *= 1024;
            case 'k':
                $val *= 1024;
        }
        
        return $val;
    }
    
    /**
     * Get DomPDF options array for modern DomPDF versions
     */
    public static function get_options() {
        return array(
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'defaultFont' => DOMPDF_DEFAULT_FONT,
            'defaultPaperSize' => DOMPDF_DEFAULT_PAPER_SIZE,
            'defaultPaperOrientation' => DOMPDF_DEFAULT_PAPER_ORIENTATION,
            'fontDir' => DOMPDF_FONT_DIR,
            'fontCache' => DOMPDF_FONT_CACHE,
            'tempDir' => DOMPDF_TEMP_DIR,
            'chroot' => DOMPDF_CHROOT,
            'logOutputFile' => DOMPDF_LOG_OUTPUT_FILE,
            'isLoggingEnabled' => DOMPDF_ENABLE_LOG,
            'dpi' => DOMPDF_DPI,
            'isPhpEnabled' => DOMPDF_ENABLE_PHP,
            'isJavascriptEnabled' => DOMPDF_ENABLE_JAVASCRIPT
        );
    }
    
    /**
     * Clean up old temporary files
     */
    public static function cleanup_temp_files($max_age_hours = 24) {
        $temp_dir = DOMPDF_TEMP_DIR;
        
        if (!is_dir($temp_dir)) {
            return;
        }
        
        $max_age = time() - ($max_age_hours * 3600);
        $files = glob($temp_dir . '*');
        
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $max_age) {
                unlink($file);
            }
        }
    }
}

// Initialize DomPDF configuration
WC_DomPDF_Config::init();

// Schedule cleanup of temp files
if (!wp_next_scheduled('wc_dompdf_cleanup')) {
    wp_schedule_event(time(), 'daily', 'wc_dompdf_cleanup');
}

// Hook for cleanup
add_action('wc_dompdf_cleanup', array('WC_DomPDF_Config', 'cleanup_temp_files'));