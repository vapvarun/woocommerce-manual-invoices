<?php
/**
 * Simplified PDF Library Manager
 * 
 * Bundles DomPDF with the plugin and provides TCPDF as alternative
 * Save as: includes/class-invoice-pdf-manager.php
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF_Manager {
    
    /**
     * Bundled libraries configuration
     */
    private static $libraries = array(
        'dompdf' => array(
            'name' => 'DomPDF',
            'description' => 'Bundled with plugin - HTML to PDF with excellent CSS support',
            'bundled' => true,
            'path' => 'lib/dompdf/autoload.inc.php',
            'class' => 'Dompdf\\Dompdf',
            'fallback_class' => 'DOMPDF'
        ),
        'tcpdf' => array(
            'name' => 'TCPDF',
            'description' => 'Optional alternative - Excellent for complex layouts and Unicode',
            'bundled' => false,
            'download_url' => 'https://github.com/tecnickcom/TCPDF/archive/refs/tags/6.6.5.zip',
            'path' => 'lib/tcpdf/tcpdf.php',
            'class' => 'TCPDF'
        )
    );
    
    /**
     * Get the best available PDF library
     * 
     * @return string|false Library name or false if none available
     */
    public static function get_available_library() {
        // First try bundled DomPDF
        if (self::is_library_available('dompdf')) {
            return 'dompdf';
        }
        
        // Fall back to TCPDF if installed
        if (self::is_library_available('tcpdf')) {
            return 'tcpdf';
        }
        
        return false;
    }
    
    /**
     * Check if a specific library is available
     * 
     * @param string $library Library name
     * @return bool
     */
    public static function is_library_available($library) {
        if (!isset(self::$libraries[$library])) {
            return false;
        }
        
        $config = self::$libraries[$library];
        $path = WC_MANUAL_INVOICES_PLUGIN_PATH . $config['path'];
        
        // Check if file exists
        if (!file_exists($path)) {
            return false;
        }
        
        // Try to load the library
        try {
            require_once $path;
            
            // Check if main class exists
            if (class_exists($config['class'])) {
                return true;
            }
            
            // Check fallback class if defined
            if (isset($config['fallback_class']) && class_exists($config['fallback_class'])) {
                return true;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices: Failed to load ' . $library . ': ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get library status for admin display
     * 
     * @return array
     */
    public static function get_library_status() {
        $status = array();
        
        foreach (self::$libraries as $key => $config) {
            $status[$key] = array(
                'name' => $config['name'],
                'description' => $config['description'],
                'bundled' => $config['bundled'],
                'available' => self::is_library_available($key),
                'path' => WC_MANUAL_INVOICES_PLUGIN_PATH . $config['path']
            );
        }
        
        return $status;
    }
    
    /**
     * Generate PDF using the best available library
     * 
     * @param WC_Order $order Order object
     * @param string $output_path PDF output path
     * @return string|false PDF path or false on failure
     */
    public static function generate_pdf($order, $output_path) {
        $library = self::get_available_library();
        
        if (!$library) {
            // Generate text fallback
            return self::generate_text_invoice($order, $output_path);
        }
        
        switch ($library) {
            case 'dompdf':
                return self::generate_with_dompdf($order, $output_path);
            case 'tcpdf':
                return self::generate_with_tcpdf($order, $output_path);
            default:
                return self::generate_text_invoice($order, $output_path);
        }
    }
    
    /**
     * Generate PDF with DomPDF
     */
    private static function generate_with_dompdf($order, $output_path) {
        try {
            $config = self::$libraries['dompdf'];
            require_once WC_MANUAL_INVOICES_PLUGIN_PATH . $config['path'];
            
            // Initialize DomPDF
            if (class_exists('Dompdf\\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $options = $dompdf->getOptions();
                $options->set('defaultFont', 'Arial');
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
            } else {
                // Legacy DomPDF
                $dompdf = new DOMPDF();
            }
            
            // Get invoice HTML
            $html = self::get_invoice_html($order);
            
            // Generate PDF
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            
            // Save to file
            $pdf_content = $dompdf->output();
            if (file_put_contents($output_path, $pdf_content)) {
                return $output_path;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (DomPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate PDF with TCPDF
     */
    private static function generate_with_tcpdf($order, $output_path) {
        try {
            $config = self::$libraries['tcpdf'];
            require_once WC_MANUAL_INVOICES_PLUGIN_PATH . $config['path'];
            
            // Create PDF
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
            // Set document info
            $company_info = WC_Manual_Invoices_Settings::get_company_info();
            $pdf->SetCreator('WooCommerce Manual Invoices Pro');
            $pdf->SetAuthor($company_info['name']);
            $pdf->SetTitle('Invoice #' . $order->get_order_number());
            
            // Configure PDF
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            
            // Add page and content
            $pdf->AddPage();
            $pdf->SetFont('arial', '', 11);
            
            $html = self::get_invoice_html($order);
            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Save to file
            $pdf->Output($output_path, 'F');
            
            return $output_path;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (TCPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate text-based invoice as fallback
     */
    private static function generate_text_invoice($order, $output_path) {
        try {
            $text_path = str_replace('.pdf', '.txt', $output_path);
            $content = self::get_text_invoice_content($order);
            
            if (file_put_contents($text_path, $content)) {
                return $text_path;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices Text Error: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get invoice HTML content
     */
    private static function get_invoice_html($order) {
        $company_info = WC_Manual_Invoices_Settings::get_company_info();
        $due_date = $order->get_meta('_manual_invoice_due_date');
        $notes = $order->get_meta('_manual_invoice_notes');
        $terms = $order->get_meta('_manual_invoice_terms');
        $invoice_number = WC_Manual_Invoices_Settings::get_invoice_number($order->get_id());
        
        // Start building HTML
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>Invoice #' . esc_html($order->get_order_number()) . '</title>';
        
        // CSS optimized for PDF
        $html .= '<style>
            body { font-family: Arial, sans-serif; font-size: 11px; line-height: 1.4; color: #333; margin: 0; padding: 0; }
            .header { margin-bottom: 30px; border-bottom: 2px solid #96588a; padding-bottom: 20px; }
            .company-info { float: left; width: 60%; }
            .invoice-title { float: right; width: 35%; text-align: right; }
            .invoice-title h1 { margin: 0; font-size: 28px; color: #96588a; }
            .company-name { font-size: 18px; font-weight: bold; color: #96588a; margin-bottom: 8px; }
            .clear { clear: both; }
            .addresses { margin-bottom: 30px; }
            .from-address, .to-address { float: left; width: 45%; margin-right: 5%; }
            .address-title { font-weight: bold; margin-bottom: 10px; color: #96588a; font-size: 14px; }
            .items-table { width: 100%; border-collapse: collapse; margin-bottom: 25px; }
            .items-table th { background-color: #96588a; color: white; padding: 12px 8px; text-align: left; }
            .items-table td { padding: 10px 8px; border-bottom: 1px solid #ddd; }
            .totals-table { width: 300px; margin-left: auto; margin-bottom: 25px; }
            .totals-table td { padding: 8px 12px; }
            .total-row { border-top: 2px solid #333; font-weight: bold; font-size: 14px; }
            .payment-info { background: #f0f0f0; border: 2px solid #96588a; padding: 20px; margin: 25px 0; text-align: center; }
            .notes, .terms { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #96588a; }
            .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; border-top: 1px solid #ddd; padding-top: 15px; }
        </style></head><body>';
        
        // Header
        $html .= '<div class="header">';
        $html .= '<div class="company-info">';
        if (!empty($company_info['logo'])) {
            $html .= '<img src="' . esc_url($company_info['logo']) . '" style="max-width: 150px; height: auto; margin-bottom: 10px;">';
        }
        $html .= '<div class="company-name">' . esc_html($company_info['name']) . '</div>';
        if (!empty($company_info['address'])) {
            $html .= '<div>' . nl2br(esc_html($company_info['address'])) . '</div>';
        }
        $html .= '</div>';
        
        $html .= '<div class="invoice-title">';
        $html .= '<h1>INVOICE</h1>';
        $html .= '<div><strong>' . esc_html($invoice_number) . '</strong><br>';
        $html .= 'Date: ' . esc_html(wc_format_datetime($order->get_date_created()));
        if ($due_date) {
            $html .= '<br>Due: ' . esc_html(date_i18n(get_option('date_format'), strtotime($due_date)));
        }
        $html .= '</div></div>';
        $html .= '<div class="clear"></div></div>';
        
        // Addresses
        $html .= '<div class="addresses">';
        $html .= '<div class="from-address">';
        $html .= '<div class="address-title">From</div>';
        $html .= '<strong>' . esc_html($company_info['name']) . '</strong><br>';
        if (!empty($company_info['address'])) {
            $html .= nl2br(esc_html($company_info['address'])) . '<br>';
        }
        if (!empty($company_info['email'])) {
            $html .= 'Email: ' . esc_html($company_info['email']);
        }
        $html .= '</div>';
        
        $html .= '<div class="to-address">';
        $html .= '<div class="address-title">To</div>';
        $html .= '<strong>' . esc_html($order->get_formatted_billing_full_name()) . '</strong><br>';
        $html .= esc_html($order->get_billing_email()) . '<br>';
        if ($order->get_formatted_billing_address()) {
            $html .= $order->get_formatted_billing_address();
        }
        $html .= '</div>';
        $html .= '<div class="clear"></div></div>';
        
        // Items table
        $html .= '<table class="items-table">';
        $html .= '<thead><tr><th>Description</th><th style="text-align: center;">Qty</th><th style="text-align: right;">Price</th><th style="text-align: right;">Total</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($order->get_items() as $item) {
            $item_total = $item->get_total();
            $item_quantity = $item->get_quantity();
            $item_price = $item_quantity > 0 ? $item_total / $item_quantity : 0;
            
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($item->get_name()) . '</strong>';
            if ($item->get_meta('_custom_item_description')) {
                $html .= '<br><small>' . esc_html($item->get_meta('_custom_item_description')) . '</small>';
            }
            $html .= '</td>';
            $html .= '<td style="text-align: center;">' . esc_html($item_quantity) . '</td>';
            $html .= '<td style="text-align: right;">' . strip_tags(wc_price($item_price)) . '</td>';
            $html .= '<td style="text-align: right;">' . strip_tags(wc_price($item_total)) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Totals
        $html .= '<table class="totals-table">';
        $html .= '<tr><td>Subtotal:</td><td style="text-align: right;">' . strip_tags(wc_price($order->get_subtotal())) . '</td></tr>';
        
        foreach ($order->get_fees() as $fee) {
            $html .= '<tr><td>' . esc_html($fee->get_name()) . ':</td><td style="text-align: right;">' . strip_tags(wc_price($fee->get_total())) . '</td></tr>';
        }
        
        if ($order->get_total_shipping() > 0) {
            $html .= '<tr><td>Shipping:</td><td style="text-align: right;">' . strip_tags(wc_price($order->get_total_shipping())) . '</td></tr>';
        }
        
        if ($order->get_total_tax() > 0) {
            $html .= '<tr><td>Tax:</td><td style="text-align: right;">' . strip_tags(wc_price($order->get_total_tax())) . '</td></tr>';
        }
        
        $html .= '<tr class="total-row"><td><strong>Total:</strong></td><td style="text-align: right;"><strong>' . strip_tags($order->get_formatted_order_total()) . '</strong></td></tr>';
        $html .= '</table>';
        
        // Payment info if needed
        if ($order->needs_payment()) {
            $html .= '<div class="payment-info">';
            $html .= '<h3>Payment Instructions</h3>';
            $html .= '<p>Please pay online using the link below:</p>';
            $html .= '<div style="font-family: monospace; font-size: 10px; word-break: break-all;">' . esc_url($order->get_checkout_payment_url()) . '</div>';
            $html .= '</div>';
        }
        
        // Notes and terms
        if ($notes) {
            $html .= '<div class="notes"><h4>Notes</h4><p>' . nl2br(esc_html($notes)) . '</p></div>';
        }
        
        if ($terms) {
            $html .= '<div class="terms"><h4>Terms & Conditions</h4><p>' . nl2br(esc_html($terms)) . '</p></div>';
        }
        
        // Footer
        $html .= '<div class="footer">';
        $html .= 'Invoice generated by ' . esc_html($company_info['name']) . '<br>';
        $html .= 'Powered by WooCommerce Manual Invoices Pro | ' . esc_html(current_time('Y-m-d H:i'));
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get text invoice content for fallback
     */
    private static function get_text_invoice_content($order) {
        $company_info = WC_Manual_Invoices_Settings::get_company_info();
        $invoice_number = WC_Manual_Invoices_Settings::get_invoice_number($order->get_id());
        
        $content = str_repeat('=', 80) . "\n";
        $content .= "                    " . strtoupper($company_info['name']) . "\n";
        $content .= "                           INVOICE\n";
        $content .= str_repeat('=', 80) . "\n\n";
        
        $content .= "Invoice: " . $invoice_number . "\n";
        $content .= "Date: " . wc_format_datetime($order->get_date_created()) . "\n";
        $content .= "Total: " . strip_tags($order->get_formatted_order_total()) . "\n\n";
        
        $content .= "CUSTOMER:\n";
        $content .= $order->get_formatted_billing_full_name() . "\n";
        $content .= $order->get_billing_email() . "\n\n";
        
        $content .= "ITEMS:\n";
        $content .= str_repeat('-', 80) . "\n";
        
        foreach ($order->get_items() as $item) {
            $content .= sprintf("%-50s %5d %15s\n", 
                substr($item->get_name(), 0, 50), 
                $item->get_quantity(), 
                strip_tags(wc_price($item->get_total()))
            );
        }
        
        $content .= str_repeat('-', 80) . "\n";
        $content .= sprintf("%66s %13s\n", "TOTAL:", strip_tags($order->get_formatted_order_total()));
        
        if ($order->needs_payment()) {
            $content .= "\n\nPAYMENT LINK:\n";
            $content .= $order->get_checkout_payment_url() . "\n";
        }
        
        return $content;
    }
    
    /**
     * Install TCPDF library
     * 
     * @return array|WP_Error Installation result
     */
    public static function install_tcpdf() {
        $config = self::$libraries['tcpdf'];
        $target_dir = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/';
        
        // Create lib directory if needed
        $lib_dir = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/';
        if (!file_exists($lib_dir)) {
            wp_mkdir_p($lib_dir);
        }
        
        // Check if already installed
        if (self::is_library_available('tcpdf')) {
            return array(
                'success' => true,
                'message' => 'TCPDF is already installed and working',
                'method' => 'existing'
            );
        }
        
        // Try to download and install
        $temp_file = sys_get_temp_dir() . '/tcpdf_' . time() . '.zip';
        
        // Download
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
        file_put_contents($temp_file, $body);
        
        // Extract
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive;
            if ($zip->open($temp_file) === TRUE) {
                $temp_extract = sys_get_temp_dir() . '/tcpdf_extract_' . time() . '/';
                $zip->extractTo($temp_extract);
                $zip->close();
                
                // Find extracted folder
                $folders = glob($temp_extract . 'TCPDF*');
                if (!empty($folders)) {
                    $source_folder = $folders[0];
                    
                    // Move to target location
                    if (rename($source_folder, $target_dir)) {
                        // Clean up
                        unlink($temp_file);
                        self::cleanup_directory($temp_extract);
                        
                        return array(
                            'success' => true,
                            'message' => 'TCPDF installed successfully',
                            'method' => 'download'
                        );
                    }
                }
            }
        }
        
        // Clean up on failure
        if (file_exists($temp_file)) {
            unlink($temp_file);
        }
        
        return new WP_Error('install_failed', 'Failed to extract TCPDF. Please install manually.');
    }
    
    /**
     * Clean up directory recursively
     */
    private static function cleanup_directory($dir) {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), array('.', '..'));
            foreach ($files as $file) {
                $path = $dir . DIRECTORY_SEPARATOR . $file;
                is_dir($path) ? self::cleanup_directory($path) : unlink($path);
            }
            rmdir($dir);
        }
    }
    
    /**
     * Test PDF generation
     * 
     * @return array|WP_Error Test result
     */
    public static function test_pdf_generation() {
        $library = self::get_available_library();
        
        if (!$library) {
            return new WP_Error('no_library', 'No PDF library is available. Text invoices will be generated instead.');
        }
        
        // Create a test HTML content
        $test_html = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>PDF Test</title></head><body>';
        $test_html .= '<h1 style="color: #96588a;">PDF Generation Test</h1>';
        $test_html .= '<p><strong>Library:</strong> ' . strtoupper($library) . '</p>';
        $test_html .= '<p><strong>Date:</strong> ' . current_time('Y-m-d H:i:s') . '</p>';
        $test_html .= '<p>This test PDF verifies that the PDF library is working correctly.</p>';
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
            // Create a dummy order object for testing
            $test_order = new stdClass();
            $test_order->get_order_number = function() { return 'TEST-001'; };
            $test_order->get_date_created = function() { return new WC_DateTime(); };
            $test_order->get_formatted_billing_full_name = function() { return 'Test Customer'; };
            $test_order->get_billing_email = function() { return 'test@example.com'; };
            $test_order->get_formatted_billing_address = function() { return '123 Test Street'; };
            $test_order->get_items = function() { return array(); };
            $test_order->get_subtotal = function() { return 100; };
            $test_order->get_fees = function() { return array(); };
            $test_order->get_total_shipping = function() { return 0; };
            $test_order->get_total_tax = function() { return 0; };
            $test_order->get_formatted_order_total = function() { return '$100.00'; };
            $test_order->needs_payment = function() { return false; };
            $test_order->get_meta = function() { return ''; };
            
            // For simple test, just generate basic content
            if ($library === 'dompdf') {
                $config = self::$libraries['dompdf'];
                require_once WC_MANUAL_INVOICES_PLUGIN_PATH . $config['path'];
                
                if (class_exists('Dompdf\\Dompdf')) {
                    $dompdf = new \Dompdf\Dompdf();
                } else {
                    $dompdf = new DOMPDF();
                }
                
                $dompdf->loadHtml($test_html);
                $dompdf->setPaper('A4', 'portrait');
                $dompdf->render();
                
                if (file_put_contents($test_file, $dompdf->output())) {
                    return array(
                        'success' => true,
                        'message' => 'PDF test successful using ' . strtoupper($library),
                        'library' => $library,
                        'download_url' => str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $test_file)
                    );
                }
            }
            
        } catch (Exception $e) {
            return new WP_Error('test_failed', 'PDF test failed: ' . $e->getMessage());
        }
        
        return new WP_Error('test_failed', 'PDF test failed for unknown reason');
    }
}