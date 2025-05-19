<?php
/**
 * Manual Invoice PDF Generator
 * 
 * Handles PDF generation for manual invoices
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF {
    
    /**
     * Generate PDF for invoice
     * 
     * @param int $order_id Order ID
     * @param bool $force_regenerate Force regeneration of existing PDF
     * @return string|false PDF path or false on failure
     */
    public static function generate_pdf($order_id, $force_regenerate = false) {
        $order = wc_get_order($order_id);
        
        if (!$order || !$order->get_meta('_is_manual_invoice')) {
            return false;
        }
        
        // Check if PDF already exists
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/wc-manual-invoices/';
        $pdf_filename = 'invoice-' . $order_id . '.pdf';
        $pdf_path = $pdf_dir . $pdf_filename;
        
        if (!$force_regenerate && file_exists($pdf_path)) {
            return $pdf_path;
        }
        
        // Create directory if it doesn't exist
        if (!file_exists($pdf_dir)) {
            wp_mkdir_p($pdf_dir);
            
            // Add .htaccess file to protect the directory
            $htaccess_content = "Order deny,allow\nDeny from all\n<Files ~ \"\.pdf$\">\nAllow from all\n</Files>";
            file_put_contents($pdf_dir . '.htaccess', $htaccess_content);
        }
        
        // Generate HTML content
        $html_content = self::get_invoice_html($order);
        
        // Try to use different PDF libraries based on availability
        if (class_exists('Dompdf\Dompdf')) {
            return self::generate_with_dompdf($html_content, $pdf_path, $order);
        } elseif (class_exists('TCPDF')) {
            return self::generate_with_tcpdf($html_content, $pdf_path, $order);
        } elseif (extension_loaded('gd') || extension_loaded('imagick')) {
            return self::generate_with_html2pdf($html_content, $pdf_path, $order);
        } else {
            // Fallback: Use WordPress built-in functionality or show message
            return self::generate_fallback_pdf($order, $pdf_path);
        }
    }
    
    /**
     * Generate PDF using DomPDF
     * 
     * @param string $html_content HTML content
     * @param string $pdf_path Output path
     * @param WC_Order $order Order object
     * @return string|false PDF path or false
     */
    private static function generate_with_dompdf($html_content, $pdf_path, $order) {
        try {
            // Check if DomPDF is available via composer autoload
            if (!class_exists('Dompdf\Dompdf')) {
                // Try to include from common locations
                $possible_paths = array(
                    WP_CONTENT_DIR . '/vendor/autoload.php',
                    ABSPATH . 'vendor/autoload.php',
                    WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php'
                );
                
                foreach ($possible_paths as $path) {
                    if (file_exists($path)) {
                        require_once $path;
                        break;
                    }
                }
                
                if (!class_exists('Dompdf\Dompdf')) {
                    throw new Exception('DomPDF library not found');
                }
            }
            
            $dompdf = new \Dompdf\Dompdf();
            
            // Set options
            $dompdf->getOptions()->set('defaultFont', 'Arial');
            $dompdf->getOptions()->set('isRemoteEnabled', true);
            $dompdf->getOptions()->set('isHtml5ParserEnabled', true);
            $dompdf->getOptions()->set('isFontSubsettingEnabled', true);
            
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
                // Update order meta with PDF info
                $order->update_meta_data('_invoice_pdf_generated', current_time('mysql'));
                $order->update_meta_data('_invoice_pdf_path', $pdf_path);
                $order->save();
                
                return $pdf_path;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (DomPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate PDF using TCPDF
     * 
     * @param string $html_content HTML content
     * @param string $pdf_path Output path
     * @param WC_Order $order Order object
     * @return string|false PDF path or false
     */
    private static function generate_with_tcpdf($html_content, $pdf_path, $order) {
        try {
            // Check if TCPDF is available
            if (!class_exists('TCPDF')) {
                // Try to include TCPDF
                $tcpdf_path = WP_CONTENT_DIR . '/plugins/tcpdf/tcpdf.php';
                if (file_exists($tcpdf_path)) {
                    require_once $tcpdf_path;
                } else {
                    throw new Exception('TCPDF library not found');
                }
            }
            
            // Create new PDF document
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            
            // Set document information
            $company_info = WC_Manual_Invoices_Settings::get_company_info();
            $pdf->SetCreator('WooCommerce Manual Invoices Pro');
            $pdf->SetAuthor($company_info['name']);
            $pdf->SetTitle('Invoice #' . $order->get_order_number());
            $pdf->SetSubject('Invoice');
            $pdf->SetKeywords('invoice, payment, woocommerce');
            
            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            
            // Set margins
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetAutoPageBreak(true, 15);
            
            // Set image scale factor
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            
            // Add page
            $pdf->AddPage();
            
            // Set font
            $pdf->SetFont('arial', '', 11);
            
            // Write HTML content
            $pdf->writeHTML($html_content, true, false, true, false, '');
            
            // Output to file
            $pdf->Output($pdf_path, 'F');
            
            // Update order meta
            $order->update_meta_data('_invoice_pdf_generated', current_time('mysql'));
            $order->update_meta_data('_invoice_pdf_path', $pdf_path);
            $order->save();
            
            return $pdf_path;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (TCPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate PDF using HTML to PDF conversion (fallback method)
     * 
     * @param string $html_content HTML content
     * @param string $pdf_path Output path
     * @param WC_Order $order Order object
     * @return string|false PDF path or false
     */
    private static function generate_with_html2pdf($html_content, $pdf_path, $order) {
        try {
            // This is a simplified approach using external service or API
            // In a real implementation, you might want to use services like:
            // - wkhtmltopdf
            // - Puppeteer
            // - Chrome headless
            // - External PDF API services
            
            // For now, we'll create a simple text-based PDF alternative
            return self::generate_fallback_pdf($order, $pdf_path);
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (HTML2PDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Fallback PDF generation method
     * 
     * @param WC_Order $order Order object
     * @param string $pdf_path Output path
     * @return string|false PDF path or false
     */
    private static function generate_fallback_pdf($order, $pdf_path) {
        try {
            // Create a detailed text-based invoice
            $content = self::get_text_invoice($order);
            
            // Save as text file with .pdf extension for compatibility
            $text_path = str_replace('.pdf', '.txt', $pdf_path);
            
            if (file_put_contents($text_path, $content)) {
                // Update order meta
                $order->update_meta_data('_invoice_pdf_generated', current_time('mysql'));
                $order->update_meta_data('_invoice_pdf_path', $text_path);
                $order->update_meta_data('_invoice_pdf_type', 'text');
                $order->save();
                
                return $text_path;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (Fallback): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get invoice HTML content for PDF generation
     * 
     * @param WC_Order $order Order object
     * @return string HTML content
     */
    private static function get_invoice_html($order) {
        // Start output buffering
        ob_start();
        
        // Include the PDF template
        $template_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/pdf-invoice.php';
        
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            // Fallback HTML structure
            echo self::get_fallback_html($order);
        }
        
        // Get the content and clean the buffer
        $html_content = ob_get_clean();
        
        // Apply filters to allow customization
        return apply_filters('wc_manual_invoice_pdf_html', $html_content, $order);
    }
    
    /**
     * Get fallback HTML structure if template is missing
     * 
     * @param WC_Order $order Order object
     * @return string HTML content
     */
    private static function get_fallback_html($order) {
        $company_info = WC_Manual_Invoices_Settings::get_company_info();
        $due_date = $order->get_meta('_manual_invoice_due_date');
        $notes = $order->get_meta('_manual_invoice_notes');
        $terms = $order->get_meta('_manual_invoice_terms');
        
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>Invoice #' . $order->get_order_number() . '</title>';
        $html .= '<style>body{font-family:Arial,sans-serif;font-size:12px;line-height:1.4;color:#333;margin:20px;}';
        $html .= '.header{text-align:center;margin-bottom:30px;}.invoice-title{font-size:28px;color:#96588a;margin:0;}';
        $html .= '.invoice-meta{margin-top:10px;font-size:14px;}.company-info,.customer-info{width:48%;display:inline-block;vertical-align:top;}';
        $html .= '.customer-info{margin-left:4%;}.info-title{font-weight:bold;margin-bottom:10px;color:#96588a;}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-bottom:20px;}th,td{padding:10px;border-bottom:1px solid #ddd;text-align:left;}';
        $html .= 'th{background-color:#f7f7f7;font-weight:bold;}.text-right{text-align:right;}.total-row{border-top:2px solid #333;font-weight:bold;}';
        $html .= '.notes,.terms{margin:20px 0;padding:15px;background-color:#f9f9f9;border-left:4px solid #96588a;}';
        $html .= '</style></head><body>';
        
        // Header
        $html .= '<div class="header">';
        $html .= '<h1 class="invoice-title">INVOICE</h1>';
        $html .= '<div class="invoice-meta">';
        $html .= '<strong>#' . $order->get_order_number() . '</strong><br>';
        $html .= 'Date: ' . wc_format_datetime($order->get_date_created());
        if ($due_date) {
            $html .= '<br>Due Date: ' . date_i18n(get_option('date_format'), strtotime($due_date));
        }
        $html .= '</div></div>';
        
        // Company and customer info
        $html .= '<div class="company-info">';
        $html .= '<div class="info-title">From:</div>';
        $html .= '<strong>' . esc_html($company_info['name']) . '</strong><br>';
        if ($company_info['address']) {
            $html .= nl2br(esc_html($company_info['address'])) . '<br>';
        }
        if ($company_info['phone']) {
            $html .= 'Phone: ' . esc_html($company_info['phone']) . '<br>';
        }
        if ($company_info['email']) {
            $html .= 'Email: ' . esc_html($company_info['email']);
        }
        $html .= '</div>';
        
        $html .= '<div class="customer-info">';
        $html .= '<div class="info-title">To:</div>';
        $html .= '<strong>' . esc_html($order->get_formatted_billing_full_name()) . '</strong><br>';
        $html .= esc_html($order->get_billing_email()) . '<br>';
        if ($order->get_billing_phone()) {
            $html .= esc_html($order->get_billing_phone()) . '<br>';
        }
        if ($order->get_formatted_billing_address()) {
            $html .= $order->get_formatted_billing_address();
        }
        $html .= '</div>';
        
        $html .= '<div style="clear:both;margin-bottom:30px;"></div>';
        
        // Items table
        $html .= '<table>';
        $html .= '<thead><tr><th>Description</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($order->get_items() as $item) {
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($item->get_name()) . '</strong>';
            if ($item->get_meta('_custom_item_description')) {
                $html .= '<br><small>' . esc_html($item->get_meta('_custom_item_description')) . '</small>';
            }
            $html .= '</td>';
            $html .= '<td class="text-right">' . $item->get_quantity() . '</td>';
            $html .= '<td class="text-right">' . wc_price($item->get_total() / $item->get_quantity()) . '</td>';
            $html .= '<td class="text-right">' . wc_price($item->get_total()) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Totals
        $html .= '<table style="width:300px;margin-left:auto;">';
        $html .= '<tr><td>Subtotal:</td><td class="text-right">' . wc_price($order->get_subtotal()) . '</td></tr>';
        
        foreach ($order->get_fees() as $fee) {
            $html .= '<tr><td>' . esc_html($fee->get_name()) . ':</td><td class="text-right">' . wc_price($fee->get_total()) . '</td></tr>';
        }
        
        if ($order->get_total_shipping() > 0) {
            $html .= '<tr><td>Shipping:</td><td class="text-right">' . wc_price($order->get_total_shipping()) . '</td></tr>';
        }
        
        if ($order->get_total_tax() > 0) {
            $html .= '<tr><td>Tax:</td><td class="text-right">' . wc_price($order->get_total_tax()) . '</td></tr>';
        }
        
        $html .= '<tr class="total-row"><td>Total:</td><td class="text-right">' . $order->get_formatted_order_total() . '</td></tr>';
        $html .= '</table>';
        
        // Payment instructions
        if ($order->needs_payment()) {
            $html .= '<div style="background-color:#f0f0f0;border:2px solid #96588a;padding:20px;margin:30px 0;text-align:center;">';
            $html .= '<h3 style="margin-top:0;color:#96588a;">Payment Instructions</h3>';
            $html .= '<p>Please pay online using the following link:</p>';
            $html .= '<div style="font-family:monospace;word-break:break-all;">' . $order->get_checkout_payment_url() . '</div>';
            $html .= '</div>';
        }
        
        // Notes
        if ($notes) {
            $html .= '<div class="notes">';
            $html .= '<h4 style="margin-top:0;color:#96588a;">Notes:</h4>';
            $html .= '<p>' . nl2br(esc_html($notes)) . '</p>';
            $html .= '</div>';
        }
        
        // Terms
        if ($terms) {
            $html .= '<div class="terms">';
            $html .= '<h4 style="margin-top:0;color:#96588a;">Terms & Conditions:</h4>';
            $html .= '<p>' . nl2br(esc_html($terms)) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Get text-based invoice content (fallback)
     * 
     * @param WC_Order $order Order object
     * @return string Text content
     */
    private static function get_text_invoice($order) {
        $company_info = WC_Manual_Invoices_Settings::get_company_info();
        $due_date = $order->get_meta('_manual_invoice_due_date');
        $notes = $order->get_meta('_manual_invoice_notes');
        $terms = $order->get_meta('_manual_invoice_terms');
        
        $content = '';
        
        // Header
        $content .= str_repeat('=', 60) . "\n";
        $content .= strtoupper($company_info['name']) . "\n";
        $content .= "INVOICE\n";
        $content .= str_repeat('=', 60) . "\n\n";
        
        // Invoice details
        $content .= "Invoice #: " . $order->get_order_number() . "\n";
        $content .= "Date: " . wc_format_datetime($order->get_date_created()) . "\n";
        if ($due_date) {
            $content .= "Due Date: " . date_i18n(get_option('date_format'), strtotime($due_date)) . "\n";
        }
        $content .= "Status: " . ucfirst($order->get_status()) . "\n\n";
        
        // Company details
        $content .= "FROM:\n";
        $content .= str_repeat('-', 30) . "\n";
        $content .= $company_info['name'] . "\n";
        if ($company_info['address']) {
            $content .= $company_info['address'] . "\n";
        }
        if ($company_info['phone']) {
            $content .= "Phone: " . $company_info['phone'] . "\n";
        }
        if ($company_info['email']) {
            $content .= "Email: " . $company_info['email'] . "\n";
        }
        $content .= "\n";
        
        // Customer details
        $content .= "TO:\n";
        $content .= str_repeat('-', 30) . "\n";
        $content .= $order->get_formatted_billing_full_name() . "\n";
        $content .= $order->get_billing_email() . "\n";
        if ($order->get_billing_phone()) {
            $content .= $order->get_billing_phone() . "\n";
        }
        if ($order->get_formatted_billing_address()) {
            $content .= strip_tags($order->get_formatted_billing_address()) . "\n";
        }
        $content .= "\n";
        
        // Items
        $content .= "ITEMS:\n";
        $content .= str_repeat('-', 60) . "\n";
        $content .= sprintf("%-30s %8s %10s %10s\n", "Description", "Qty", "Price", "Total");
        $content .= str_repeat('-', 60) . "\n";
        
        foreach ($order->get_items() as $item) {
            $name = $item->get_name();
            if (strlen($name) > 28) {
                $name = substr($name, 0, 25) . '...';
            }
            
            $content .= sprintf(
                "%-30s %8s %10s %10s\n",
                $name,
                $item->get_quantity(),
                strip_tags(wc_price($item->get_total() / $item->get_quantity())),
                strip_tags(wc_price($item->get_total()))
            );
            
            if ($item->get_meta('_custom_item_description')) {
                $content .= "  " . $item->get_meta('_custom_item_description') . "\n";
            }
        }
        
        $content .= str_repeat('-', 60) . "\n\n";
        
        // Totals
        $content .= "TOTALS:\n";
        $content .= str_repeat('-', 30) . "\n";
        $content .= sprintf("%-20s %s\n", "Subtotal:", strip_tags(wc_price($order->get_subtotal())));
        
        foreach ($order->get_fees() as $fee) {
            $content .= sprintf("%-20s %s\n", $fee->get_name() . ":", strip_tags(wc_price($fee->get_total())));
        }
        
        if ($order->get_total_shipping() > 0) {
            $content .= sprintf("%-20s %s\n", "Shipping:", strip_tags(wc_price($order->get_total_shipping())));
        }
        
        if ($order->get_total_tax() > 0) {
            $content .= sprintf("%-20s %s\n", "Tax:", strip_tags(wc_price($order->get_total_tax())));
        }
        
        $content .= str_repeat('-', 30) . "\n";
        $content .= sprintf("%-20s %s\n", "TOTAL:", strip_tags($order->get_formatted_order_total()));
        $content .= str_repeat('=', 30) . "\n\n";
        
        // Payment instructions
        if ($order->needs_payment()) {
            $content .= "PAYMENT INSTRUCTIONS:\n";
            $content .= str_repeat('-', 60) . "\n";
            $content .= "Please pay online using the following link:\n";
            $content .= $order->get_checkout_payment_url() . "\n\n";
        }
        
        // Notes
        if ($notes) {
            $content .= "NOTES:\n";
            $content .= str_repeat('-', 60) . "\n";
            $content .= $notes . "\n\n";
        }
        
        // Terms
        if ($terms) {
            $content .= "TERMS & CONDITIONS:\n";
            $content .= str_repeat('-', 60) . "\n";
            $content .= $terms . "\n\n";
        }
        
        // Footer
        $content .= str_repeat('=', 60) . "\n";
        $content .= "Invoice generated by " . $company_info['name'] . "\n";
        $content .= "Generated on: " . current_time('Y-m-d H:i:s') . "\n";
        $content .= str_repeat('=', 60) . "\n";
        
        return $content;
    }
    
    /**
     * Get PDF download URL
     * 
     * @param int $order_id Order ID
     * @return string|false Download URL or false
     */
    public static function get_pdf_download_url($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $pdf_path = $order->get_meta('_invoice_pdf_path');
        
        if (!$pdf_path || !file_exists($pdf_path)) {
            return false;
        }
        
        $upload_dir = wp_upload_dir();
        $pdf_url = str_replace($upload_dir['basedir'], $upload_dir['baseurl'], $pdf_path);
        
        return $pdf_url;
    }
    
    /**
     * Get secure PDF download URL with authentication
     * 
     * @param int $order_id Order ID
     * @param string $order_key Order key for security
     * @return string|false Secure download URL or false
     */
    public static function get_secure_pdf_download_url($order_id, $order_key = '') {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        if ($order_key && $order->get_order_key() !== $order_key) {
            return false;
        }
        
        // Generate download URL with nonce for security
        $download_url = add_query_arg(array(
            'wc_manual_invoice_download' => $order_id,
            'order_key' => $order->get_order_key(),
            'nonce' => wp_create_nonce('download_invoice_' . $order_id)
        ), home_url());
        
        return $download_url;
    }
    
    /**
     * Handle PDF download request
     */
    public static function handle_pdf_download() {
        if (!isset($_GET['wc_manual_invoice_download'])) {
            return;
        }
        
        $order_id = intval($_GET['wc_manual_invoice_download']);
        $order_key = sanitize_text_field($_GET['order_key'] ?? '');
        $nonce = sanitize_text_field($_GET['nonce'] ?? '');
        
        // Verify nonce
        if (!wp_verify_nonce($nonce, 'download_invoice_' . $order_id)) {
            wp_die('Security check failed');
        }
        
        $order = wc_get_order($order_id);
        
        if (!$order || $order->get_order_key() !== $order_key) {
            wp_die('Invalid order');
        }
        
        if (!$order->get_meta('_is_manual_invoice')) {
            wp_die('Invalid invoice');
        }
        
        $pdf_path = $order->get_meta('_invoice_pdf_path');
        
        if (!$pdf_path || !file_exists($pdf_path)) {
            // Try to generate PDF if it doesn't exist
            $pdf_path = self::generate_pdf($order_id);
        }
        
        if (!$pdf_path || !file_exists($pdf_path)) {
            wp_die('PDF not available');
        }
        
        // Set headers for download
        $filename = 'invoice-' . $order->get_order_number() . '.pdf';
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($pdf_path));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        
        // Output file
        readfile($pdf_path);
        exit;
    }
    
    /**
     * Delete PDF file
     * 
     * @param int $order_id Order ID
     * @return bool Success
     */
    public static function delete_pdf($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $pdf_path = $order->get_meta('_invoice_pdf_path');
        
        if ($pdf_path && file_exists($pdf_path)) {
            if (unlink($pdf_path)) {
                // Remove meta data
                $order->delete_meta_data('_invoice_pdf_generated');
                $order->delete_meta_data('_invoice_pdf_path');
                $order->delete_meta_data('_invoice_pdf_type');
                $order->save();
                
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get PDF file size
     * 
     * @param int $order_id Order ID
     * @return int|false File size in bytes or false
     */
    public static function get_pdf_size($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $pdf_path = $order->get_meta('_invoice_pdf_path');
        
        if ($pdf_path && file_exists($pdf_path)) {
            return filesize($pdf_path);
        }
        
        return false;
    }
    
    /**
     * Check if PDF exists
     * 
     * @param int $order_id Order ID
     * @return bool PDF exists
     */
    public static function pdf_exists($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order) {
            return false;
        }
        
        $pdf_path = $order->get_meta('_invoice_pdf_path');
        
        return $pdf_path && file_exists($pdf_path);
    }
    
    /**
     * Get PDF info
     * 
     * @param int $order_id Order ID
     * @return array|false PDF info or false
     */
    public static function get_pdf_info($order_id) {
        $order = wc_get_order($order_id);
        
        if (!$order || !self::pdf_exists($order_id)) {
            return false;
        }
        
        $pdf_path = $order->get_meta('_invoice_pdf_path');
        $generated_date = $order->get_meta('_invoice_pdf_generated');
        $pdf_type = $order->get_meta('_invoice_pdf_type');
        
        return array(
            'path' => $pdf_path,
            'size' => filesize($pdf_path),
            'generated_date' => $generated_date,
            'type' => $pdf_type ?: 'pdf',
            'download_url' => self::get_pdf_download_url($order_id),
            'secure_download_url' => self::get_secure_pdf_download_url($order_id, $order->get_order_key())
        );
    }
    
    /**
     * Cleanup old PDF files
     * 
     * @param int $days_old Delete PDFs older than X days
     * @return int Number of files deleted
     */
    public static function cleanup_old_pdfs($days_old = 90) {
        $upload_dir = wp_upload_dir();
        $pdf_dir = $upload_dir['basedir'] . '/wc-manual-invoices/';
        
        if (!is_dir($pdf_dir)) {
            return 0;
        }
        
        $cutoff_time = time() - ($days_old * 24 * 60 * 60);
        $deleted_count = 0;
        
        $files = glob($pdf_dir . 'invoice-*.{pdf,txt}', GLOB_BRACE);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff_time) {
                if (unlink($file)) {
                    $deleted_count++;
                }
            }
        }
        
        return $deleted_count;
    }
}

// Initialize PDF download handler
add_action('init', array('WC_Manual_Invoice_PDF', 'handle_pdf_download'));