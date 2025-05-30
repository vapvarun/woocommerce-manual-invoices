<?php
/**
 * Enhanced Manual Invoice PDF Generator with Multiple Library Support
 * 
 * Handles PDF generation for manual invoices with fallback support
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF {
    
    /**
     * Available PDF libraries in order of preference
     */
    private static $pdf_libraries = array(
        'dompdf' => 'DomPDF',
        'tcpdf' => 'TCPDF', 
        'mpdf' => 'mPDF',
        'fpdf' => 'FPDF'
    );
    
    /**
     * Generate PDF for invoice with automatic library detection
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
            $htaccess_content = "# WooCommerce Manual Invoices PDF Protection\n";
            $htaccess_content .= "Order deny,allow\n";
            $htaccess_content .= "Deny from all\n";
            $htaccess_content .= "<Files ~ \"\.pdf$\">\n";
            $htaccess_content .= "Allow from all\n";
            $htaccess_content .= "</Files>\n";
            $htaccess_content .= "<Files ~ \"\.txt$\">\n";
            $htaccess_content .= "Allow from all\n";
            $htaccess_content .= "</Files>";
            file_put_contents($pdf_dir . '.htaccess', $htaccess_content);
            
            // Add index.php for extra security
            file_put_contents($pdf_dir . 'index.php', '<?php // Silence is golden');
        }
        
        // Try different PDF libraries
        $available_library = self::detect_available_library();
        
        if ($available_library) {
            $pdf_path = self::generate_with_library($available_library, $order, $pdf_path);
        } else {
            // Fallback to HTML/text version
            $pdf_path = self::generate_fallback_pdf($order, $pdf_path);
        }
        
        if ($pdf_path && file_exists($pdf_path)) {
            // Update order meta with PDF info
            $order->update_meta_data('_invoice_pdf_generated', current_time('mysql'));
            $order->update_meta_data('_invoice_pdf_path', $pdf_path);
            $order->update_meta_data('_invoice_pdf_library', $available_library ?: 'fallback');
            $order->save();
            
            return $pdf_path;
        }
        
        return false;
    }
    
    /**
     * Detect available PDF library
     * 
     * @return string|false Available library name or false
     */
    private static function detect_available_library() {
        // Check for DomPDF
        if (self::is_dompdf_available()) {
            return 'dompdf';
        }
        
        // Check for TCPDF
        if (self::is_tcpdf_available()) {
            return 'tcpdf';
        }
        
        // Check for mPDF
        if (self::is_mpdf_available()) {
            return 'mpdf';
        }
        
        // Check for FPDF
        if (self::is_fpdf_available()) {
            return 'fpdf';
        }
        
        return false;
    }
    
    /**
     * Check if DomPDF is available
     */
    private static function is_dompdf_available() {
        // Try to load DomPDF from various locations
        $possible_paths = array(
            // Composer autoload locations
            ABSPATH . 'vendor/autoload.php',
            WP_CONTENT_DIR . '/vendor/autoload.php',
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php',
            // Direct DomPDF paths
            WP_CONTENT_DIR . '/plugins/dompdf/autoload.inc.php',
            ABSPATH . 'wp-content/plugins/dompdf/dompdf_config.inc.php'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    if (class_exists('\Dompdf\Dompdf') || class_exists('DOMPDF')) {
                        return true;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return class_exists('\Dompdf\Dompdf') || class_exists('DOMPDF');
    }
    
    /**
     * Check if TCPDF is available
     */
    private static function is_tcpdf_available() {
        // Try to include TCPDF
        $possible_paths = array(
            WP_CONTENT_DIR . '/plugins/tcpdf/tcpdf.php',
            ABSPATH . 'vendor/tecnickcom/tcpdf/tcpdf.php',
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/tcpdf.php'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    return true;
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return class_exists('TCPDF');
    }
    
    /**
     * Check if mPDF is available
     */
    private static function is_mpdf_available() {
        // Try to load mPDF
        $possible_paths = array(
            ABSPATH . 'vendor/autoload.php',
            WP_CONTENT_DIR . '/vendor/autoload.php',
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'vendor/autoload.php'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    if (class_exists('\Mpdf\Mpdf')) {
                        return true;
                    }
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return class_exists('\Mpdf\Mpdf');
    }
    
    /**
     * Check if FPDF is available
     */
    private static function is_fpdf_available() {
        $possible_paths = array(
            WP_CONTENT_DIR . '/plugins/fpdf/fpdf.php',
            WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/fpdf/fpdf.php'
        );
        
        foreach ($possible_paths as $path) {
            if (file_exists($path)) {
                try {
                    require_once $path;
                    return true;
                } catch (Exception $e) {
                    continue;
                }
            }
        }
        
        return class_exists('FPDF');
    }
    
    /**
     * Generate PDF using detected library
     */
    private static function generate_with_library($library, $order, $pdf_path) {
        switch ($library) {
            case 'dompdf':
                return self::generate_with_dompdf($order, $pdf_path);
            case 'tcpdf':
                return self::generate_with_tcpdf($order, $pdf_path);
            case 'mpdf':
                return self::generate_with_mpdf($order, $pdf_path);
            case 'fpdf':
                return self::generate_with_fpdf($order, $pdf_path);
            default:
                return false;
        }
    }
    
    /**
     * Generate PDF using DomPDF
     */
    private static function generate_with_dompdf($order, $pdf_path) {
        try {
            // Initialize DomPDF
            if (class_exists('\Dompdf\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $options = $dompdf->getOptions();
            } else {
                // Legacy DomPDF
                $dompdf = new DOMPDF();
                $options = null;
            }
            
            // Set options if available
            if ($options) {
                $options->set('defaultFont', 'Arial');
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true);
                $options->set('chroot', array(ABSPATH, WP_CONTENT_DIR));
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
    
    /**
     * Generate PDF using TCPDF
     */
    private static function generate_with_tcpdf($order, $pdf_path) {
        try {
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
            
            // Get HTML content
            $html_content = self::get_invoice_html($order);
            
            // Write HTML content
            $pdf->writeHTML($html_content, true, false, true, false, '');
            
            // Output to file
            $pdf->Output($pdf_path, 'F');
            
            return $pdf_path;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (TCPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate PDF using mPDF
     */
    private static function generate_with_mpdf($order, $pdf_path) {
        try {
            // Create mPDF instance
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 15,
                'margin_bottom' => 15,
                'tempDir' => sys_get_temp_dir()
            ]);
            
            // Set document info
            $company_info = WC_Manual_Invoices_Settings::get_company_info();
            $mpdf->SetTitle('Invoice #' . $order->get_order_number());
            $mpdf->SetAuthor($company_info['name']);
            $mpdf->SetCreator('WooCommerce Manual Invoices Pro');
            
            // Get HTML content
            $html_content = self::get_invoice_html($order);
            
            // Write HTML
            $mpdf->WriteHTML($html_content);
            
            // Output to file
            $mpdf->Output($pdf_path, 'F');
            
            return $pdf_path;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (mPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Generate PDF using FPDF (text-based, simpler)
     */
    private static function generate_with_fpdf($order, $pdf_path) {
        try {
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial', 'B', 16);
            
            // Company info
            $company_info = WC_Manual_Invoices_Settings::get_company_info();
            
            // Header
            $pdf->Cell(0, 10, $company_info['name'], 0, 1, 'C');
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(0, 10, 'INVOICE #' . $order->get_order_number(), 0, 1, 'C');
            $pdf->Ln(10);
            
            // Customer info
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 10, 'Bill To:', 0, 1);
            $pdf->SetFont('Arial', '', 10);
            $pdf->Cell(0, 6, $order->get_formatted_billing_full_name(), 0, 1);
            $pdf->Cell(0, 6, $order->get_billing_email(), 0, 1);
            
            if ($order->get_billing_phone()) {
                $pdf->Cell(0, 6, $order->get_billing_phone(), 0, 1);
            }
            
            $pdf->Ln(10);
            
            // Items header
            $pdf->SetFont('Arial', 'B', 10);
            $pdf->Cell(80, 10, 'Description', 1);
            $pdf->Cell(30, 10, 'Quantity', 1);
            $pdf->Cell(30, 10, 'Price', 1);
            $pdf->Cell(30, 10, 'Total', 1);
            $pdf->Ln();
            
            // Items
            $pdf->SetFont('Arial', '', 10);
            foreach ($order->get_items() as $item) {
                $pdf->Cell(80, 8, $item->get_name(), 1);
                $pdf->Cell(30, 8, $item->get_quantity(), 1, 0, 'C');
                $pdf->Cell(30, 8, wc_price($item->get_total() / $item->get_quantity()), 1, 0, 'R');
                $pdf->Cell(30, 8, wc_price($item->get_total()), 1, 0, 'R');
                $pdf->Ln();
            }
            
            // Total
            $pdf->Ln(5);
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(140, 10, 'Total:', 0, 0, 'R');
            $pdf->Cell(30, 10, strip_tags($order->get_formatted_order_total()), 0, 1, 'R');
            
            // Payment link if needed
            if ($order->needs_payment()) {
                $pdf->Ln(10);
                $pdf->SetFont('Arial', '', 10);
                $pdf->Cell(0, 10, 'Pay online: ' . $order->get_checkout_payment_url(), 0, 1);
            }
            
            // Output to file
            $pdf->Output('F', $pdf_path);
            
            return $pdf_path;
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (FPDF): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get invoice HTML content optimized for PDF generation
     */
    private static function get_invoice_html($order) {
        $company_info = WC_Manual_Invoices_Settings::get_company_info();
        $due_date = $order->get_meta('_manual_invoice_due_date');
        $notes = $order->get_meta('_manual_invoice_notes');
        $terms = $order->get_meta('_manual_invoice_terms');
        $invoice_number = WC_Manual_Invoices_Settings::get_invoice_number($order->get_id());
        
        $html = '<!DOCTYPE html>';
        $html .= '<html><head><meta charset="UTF-8">';
        $html .= '<title>Invoice #' . esc_html($order->get_order_number()) . '</title>';
        
        // Enhanced CSS for better PDF rendering
        $html .= '<style>
            @page { margin: 20mm; }
            body { 
                font-family: Arial, sans-serif; 
                font-size: 11px; 
                line-height: 1.4; 
                color: #333; 
                margin: 0; 
                padding: 0;
            }
            .header { 
                display: table; 
                width: 100%; 
                margin-bottom: 30px; 
                border-bottom: 2px solid #96588a;
                padding-bottom: 20px;
            }
            .company-info { 
                display: table-cell; 
                vertical-align: top; 
                width: 60%; 
            }
            .invoice-title { 
                display: table-cell; 
                vertical-align: top; 
                text-align: right; 
                width: 40%; 
            }
            .invoice-title h1 { 
                margin: 0; 
                font-size: 28px; 
                color: #96588a; 
                font-weight: bold;
            }
            .company-name { 
                font-size: 18px; 
                font-weight: bold; 
                color: #96588a; 
                margin-bottom: 8px;
            }
            .addresses { 
                display: table; 
                width: 100%; 
                margin-bottom: 30px; 
            }
            .from-address, .to-address { 
                display: table-cell; 
                vertical-align: top; 
                width: 50%; 
                padding-right: 20px;
            }
            .to-address { 
                padding-right: 0; 
                padding-left: 20px;
            }
            .address-title { 
                font-weight: bold; 
                margin-bottom: 10px; 
                color: #96588a; 
                font-size: 14px;
                border-bottom: 1px solid #ddd;
                padding-bottom: 5px;
            }
            .items-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 25px;
                border: 1px solid #ddd;
            }
            .items-table th { 
                background-color: #96588a; 
                color: white; 
                padding: 12px 8px; 
                text-align: left; 
                font-weight: bold;
                border: 1px solid #ddd;
            }
            .items-table td { 
                padding: 10px 8px; 
                border: 1px solid #ddd; 
                vertical-align: top;
            }
            .items-table tr:nth-child(even) { 
                background-color: #f9f9f9; 
            }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .totals-table { 
                width: 300px; 
                margin-left: auto; 
                margin-bottom: 25px;
                border-collapse: collapse;
            }
            .totals-table td { 
                padding: 8px 12px; 
                border-bottom: 1px solid #eee;
            }
            .totals-table .total-row { 
                border-top: 2px solid #333; 
                font-weight: bold; 
                font-size: 14px;
                background-color: #f5f5f5;
            }
            .payment-info { 
                background-color: #f0f0f0; 
                border: 2px solid #96588a; 
                padding: 20px; 
                margin: 25px 0; 
                text-align: center;
                border-radius: 5px;
            }
            .payment-info h3 { 
                margin-top: 0; 
                color: #96588a; 
                font-size: 16px;
            }
            .payment-link { 
                font-family: monospace; 
                font-size: 10px; 
                word-break: break-all; 
                color: #96588a;
                background-color: white;
                padding: 8px;
                border: 1px solid #ddd;
                margin-top: 10px;
                display: block;
            }
            .notes, .terms { 
                margin: 20px 0; 
                padding: 15px; 
                background-color: #f9f9f9; 
                border-left: 4px solid #96588a;
                border-radius: 3px;
            }
            .notes h4, .terms h4 { 
                margin-top: 0; 
                color: #96588a; 
                font-size: 14px;
            }
            .footer { 
                margin-top: 40px; 
                padding-top: 15px; 
                border-top: 1px solid #ddd; 
                text-align: center; 
                font-size: 10px; 
                color: #666;
            }
            .invoice-meta { 
                font-size: 12px; 
                color: #666; 
                margin-top: 10px;
            }
            .status-badge {
                display: inline-block;
                padding: 4px 8px;
                border-radius: 3px;
                font-size: 10px;
                font-weight: bold;
                text-transform: uppercase;
            }
            .status-pending { background: #fff3cd; color: #856404; }
            .status-processing { background: #d1ecf1; color: #0c5460; }
            .status-completed { background: #d4edda; color: #155724; }
        </style>';
        
        $html .= '</head><body>';
        
        // Header
        $html .= '<div class="header">';
        $html .= '<div class="company-info">';
        
        if (!empty($company_info['logo'])) {
            $html .= '<img src="' . esc_url($company_info['logo']) . '" style="max-width: 150px; height: auto; margin-bottom: 10px;" alt="' . esc_attr($company_info['name']) . '">';
        }
        
        $html .= '<div class="company-name">' . esc_html($company_info['name']) . '</div>';
        
        if (!empty($company_info['address'])) {
            $html .= '<div>' . nl2br(esc_html($company_info['address'])) . '</div>';
        }
        
        if (!empty($company_info['phone'])) {
            $html .= '<div>Phone: ' . esc_html($company_info['phone']) . '</div>';
        }
        
        if (!empty($company_info['email'])) {
            $html .= '<div>Email: ' . esc_html($company_info['email']) . '</div>';
        }
        
        $html .= '</div>';
        $html .= '<div class="invoice-title">';
        $html .= '<h1>INVOICE</h1>';
        $html .= '<div class="invoice-meta">';
        $html .= '<strong>' . esc_html($invoice_number) . '</strong><br>';
        $html .= 'Date: ' . esc_html(wc_format_datetime($order->get_date_created())) . '<br>';
        
        if ($due_date) {
            $html .= 'Due: ' . esc_html(date_i18n(get_option('date_format'), strtotime($due_date)));
            if (strtotime($due_date) < current_time('timestamp') && $order->needs_payment()) {
                $html .= ' <span style="color: #dc3545; font-weight: bold;">(OVERDUE)</span>';
            }
            $html .= '<br>';
        }
        
        $html .= 'Status: <span class="status-badge status-' . esc_attr($order->get_status()) . '">' . 
                 esc_html(wc_get_order_status_name($order->get_status())) . '</span>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';
        
        // Addresses
        $html .= '<div class="addresses">';
        $html .= '<div class="from-address">';
        $html .= '<div class="address-title">From</div>';
        $html .= '<strong>' . esc_html($company_info['name']) . '</strong><br>';
        
        if (!empty($company_info['address'])) {
            $html .= nl2br(esc_html($company_info['address'])) . '<br>';
        }
        
        if (!empty($company_info['phone'])) {
            $html .= 'Phone: ' . esc_html($company_info['phone']) . '<br>';
        }
        
        if (!empty($company_info['email'])) {
            $html .= 'Email: ' . esc_html($company_info['email']);
        }
        
        $html .= '</div>';
        $html .= '<div class="to-address">';
        $html .= '<div class="address-title">To</div>';
        $html .= '<strong>' . esc_html($order->get_formatted_billing_full_name()) . '</strong><br>';
        $html .= esc_html($order->get_billing_email()) . '<br>';
        
        if ($order->get_billing_phone()) {
            $html .= 'Phone: ' . esc_html($order->get_billing_phone()) . '<br>';
        }
        
        if ($order->get_formatted_billing_address()) {
            $html .= $order->get_formatted_billing_address();
        }
        
        $html .= '</div>';
        $html .= '</div>';
        
        // Items table
        $html .= '<table class="items-table">';
        $html .= '<thead><tr>';
        $html .= '<th style="width: 50%;">Description</th>';
        $html .= '<th style="width: 15%;" class="text-center">Qty</th>';
        $html .= '<th style="width: 15%;" class="text-right">Price</th>';
        $html .= '<th style="width: 20%;" class="text-right">Total</th>';
        $html .= '</tr></thead><tbody>';
        
        foreach ($order->get_items() as $item) {
            $item_total = $item->get_total();
            $item_quantity = $item->get_quantity();
            $item_price = $item_quantity > 0 ? $item_total / $item_quantity : 0;
            
            $html .= '<tr>';
            $html .= '<td><strong>' . esc_html($item->get_name()) . '</strong>';
            
            if ($item->get_meta('_custom_item_description')) {
                $html .= '<br><small style="color: #666;">' . esc_html($item->get_meta('_custom_item_description')) . '</small>';
            }
            
            $html .= '</td>';
            $html .= '<td class="text-center">' . esc_html($item_quantity) . '</td>';
            $html .= '<td class="text-right">' . strip_tags(wc_price($item_price)) . '</td>';
            $html .= '<td class="text-right">' . strip_tags(wc_price($item_total)) . '</td>';
            $html .= '</tr>';
        }
        
        $html .= '</tbody></table>';
        
        // Totals
        $html .= '<table class="totals-table">';
        $html .= '<tr><td>Subtotal:</td><td class="text-right">' . strip_tags(wc_price($order->get_subtotal())) . '</td></tr>';
        
        foreach ($order->get_fees() as $fee) {
            $html .= '<tr><td>' . esc_html($fee->get_name()) . ':</td><td class="text-right">' . strip_tags(wc_price($fee->get_total())) . '</td></tr>';
        }
        
        if ($order->get_total_shipping() > 0) {
            $html .= '<tr><td>Shipping:</td><td class="text-right">' . strip_tags(wc_price($order->get_total_shipping())) . '</td></tr>';
        }
        
        if ($order->get_total_tax() > 0) {
            $html .= '<tr><td>Tax:</td><td class="text-right">' . strip_tags(wc_price($order->get_total_tax())) . '</td></tr>';
        }
        
        $html .= '<tr class="total-row"><td><strong>Total:</strong></td><td class="text-right"><strong>' . strip_tags($order->get_formatted_order_total()) . '</strong></td></tr>';
        $html .= '</table>';
        
        // Payment info
        if ($order->needs_payment()) {
            $html .= '<div class="payment-info">';
            $html .= '<h3>Payment Instructions</h3>';
            $html .= '<p>Please pay online using the link below:</p>';
            $html .= '<div class="payment-link">' . esc_url($order->get_checkout_payment_url()) . '</div>';
            $html .= '</div>';
        }
        
        // Notes
        if ($notes) {
            $html .= '<div class="notes">';
            $html .= '<h4>Notes</h4>';
            $html .= '<p>' . nl2br(esc_html($notes)) . '</p>';
            $html .= '</div>';
        }
        
        // Terms
        if ($terms) {
            $html .= '<div class="terms">';
            $html .= '<h4>Terms & Conditions</h4>';
            $html .= '<p>' . nl2br(esc_html($terms)) . '</p>';
            $html .= '</div>';
        }
        
        // Footer
        $html .= '<div class="footer">';
        $footer_text = WC_Manual_Invoices_Settings::get_setting('invoice_footer');
        if ($footer_text) {
            $html .= nl2br(esc_html($footer_text));
        } else {
            $html .= 'Invoice generated by ' . esc_html($company_info['name']) . '<br>';
            $html .= 'Powered by WooCommerce Manual Invoices Pro | ' . esc_html(current_time('Y-m-d H:i'));
        }
        $html .= '</div>';
        
        $html .= '</body></html>';
        
        return $html;
    }
    
    /**
     * Fallback PDF generation (HTML to text)
     */
    private static function generate_fallback_pdf($order, $pdf_path) {
        try {
            // Create detailed text-based invoice
            $content = self::get_text_invoice($order);
            
            // Save as text file with descriptive extension
            $text_path = str_replace('.pdf', '.txt', $pdf_path);
            
            if (file_put_contents($text_path, $content)) {
                return $text_path;
            }
            
        } catch (Exception $e) {
            error_log('WC Manual Invoices PDF Error (Fallback): ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * Get text-based invoice content (enhanced)
     */
    private static function get_text_invoice($order) {
        $company_info = WC_Manual_Invoices_Settings::get_company_info();
        $due_date = $order->get_meta('_manual_invoice_due_date');
        $notes = $order->get_meta('_manual_invoice_notes');
        $terms = $order->get_meta('_manual_invoice_terms');
        $invoice_number = WC_Manual_Invoices_Settings::get_invoice_number($order->get_id());
        
        $content = '';
        
        // Header with ASCII art style
        $content .= str_repeat('=', 80) . "\n";
        $content .= "                    " . strtoupper($company_info['name']) . "\n";
        $content .= "                           INVOICE\n";
        $content .= str_repeat('=', 80) . "\n\n";
        
        // Invoice details in a box
        $content .= "┌" . str_repeat('─', 78) . "┐\n";
        $content .= "│ Invoice: " . str_pad($invoice_number, 20) . " │ Date: " . str_pad(wc_format_datetime($order->get_date_created()), 20) . " │\n";
        if ($due_date) {
            $due_formatted = date_i18n(get_option('date_format'), strtotime($due_date));
            $overdue_text = (strtotime($due_date) < current_time('timestamp') && $order->needs_payment()) ? ' (OVERDUE!)' : '';
            $content .= "│ Due Date: " . str_pad($due_formatted . $overdue_text, 66) . " │\n";
        }
        $content .= "│ Status: " . str_pad(wc_get_order_status_name($order->get_status()), 68) . " │\n";
        $content .= "└" . str_repeat('─', 78) . "┘\n\n";
        
        // Two-column layout for addresses
        $content .= "FROM:" . str_repeat(' ', 35) . "TO:\n";
        $content .= str_repeat('-', 38) . " " . str_repeat('-', 38) . "\n";
        
        $from_lines = array();
        $from_lines[] = $company_info['name'];
        if ($company_info['address']) {
            $from_lines = array_merge($from_lines, explode("\n", $company_info['address']));
        }
        if ($company_info['phone']) {
            $from_lines[] = 'Phone: ' . $company_info['phone'];
        }
        if ($company_info['email']) {
            $from_lines[] = 'Email: ' . $company_info['email'];
        }
        
        $to_lines = array();
        $to_lines[] = $order->get_formatted_billing_full_name();
        $to_lines[] = $order->get_billing_email();
        if ($order->get_billing_phone()) {
            $to_lines[] = 'Phone: ' . $order->get_billing_phone();
        }
        if ($order->get_formatted_billing_address()) {
            $to_lines = array_merge($to_lines, explode('<br/>', $order->get_formatted_billing_address()));
        }
        
        $max_lines = max(count($from_lines), count($to_lines));
        for ($i = 0; $i < $max_lines; $i++) {
            $from_line = isset($from_lines[$i]) ? $from_lines[$i] : '';
            $to_line = isset($to_lines[$i]) ? strip_tags($to_lines[$i]) : '';
            $content .= str_pad($from_line, 38) . " " . $to_line . "\n";
        }
        
        $content .= "\n";
        
        // Items table with better formatting
        $content .= "INVOICE ITEMS:\n";
        $content .= "┌" . str_repeat('─', 78) . "┐\n";
        $content .= "│" . str_pad("Description", 35) . "│" . str_pad("Qty", 8) . "│" . str_pad("Price", 12) . "│" . str_pad("Total", 12) . "│\n";
        $content .= "├" . str_repeat('─', 35) . "┼" . str_repeat('─', 8) . "┼" . str_repeat('─', 12) . "┼" . str_repeat('─', 12) . "┤\n";
        
        foreach ($order->get_items() as $item) {
            $name = $item->get_name();
            if (strlen($name) > 33) {
                $name = substr($name, 0, 30) . '...';
            }
            
            $item_total = $item->get_total();
            $item_quantity = $item->get_quantity();
            $item_price = $item_quantity > 0 ? $item_total / $item_quantity : 0;
            
            $content .= "│" . str_pad($name, 35) . "│" . str_pad($item_quantity, 8, ' ', STR_PAD_LEFT) . "│" . 
                       str_pad(strip_tags(wc_price($item_price)), 12, ' ', STR_PAD_LEFT) . "│" . 
                       str_pad(strip_tags(wc_price($item_total)), 12, ' ', STR_PAD_LEFT) . "│\n";
            
            if ($item->get_meta('_custom_item_description')) {
                $desc = $item->get_meta('_custom_item_description');
                if (strlen($desc) > 33) {
                    $desc = substr($desc, 0, 30) . '...';
                }
                $content .= "│  " . str_pad($desc, 33) . "│" . str_repeat(' ', 8) . "│" . str_repeat(' ', 12) . "│" . str_repeat(' ', 12) . "│\n";
            }
        }
        
        $content .= "└" . str_repeat('─', 78) . "┘\n\n";
        
        // Totals section
        $content .= "TOTALS:\n";
        $content .= "┌" . str_repeat('─', 40) . "┐\n";
        $content .= "│" . str_pad("Subtotal:", 25) . str_pad(strip_tags(wc_price($order->get_subtotal())), 14, ' ', STR_PAD_LEFT) . "│\n";
        
        foreach ($order->get_fees() as $fee) {
            $content .= "│" . str_pad($fee->get_name() . ":", 25) . str_pad(strip_tags(wc_price($fee->get_total())), 14, ' ', STR_PAD_LEFT) . "│\n";
        }
        
        if ($order->get_total_shipping() > 0) {
            $content .= "│" . str_pad("Shipping:", 25) . str_pad(strip_tags(wc_price($order->get_total_shipping())), 14, ' ', STR_PAD_LEFT) . "│\n";
        }
        
        if ($order->get_total_tax() > 0) {
            $content .= "│" . str_pad("Tax:", 25) . str_pad(strip_tags(wc_price($order->get_total_tax())), 14, ' ', STR_PAD_LEFT) . "│\n";
        }
        
        $content .= "├" . str_repeat('─', 40) . "┤\n";
        $content .= "│" . str_pad("TOTAL:", 25) . str_pad(strip_tags($order->get_formatted_order_total()), 14, ' ', STR_PAD_LEFT) . "│\n";
        $content .= "└" . str_repeat('═', 40) . "┘\n\n";
        
        // Payment instructions
        if ($order->needs_payment()) {
            $content .= "PAYMENT INSTRUCTIONS:\n";
            $content .= str_repeat('=', 80) . "\n";
            $content .= "Please pay online using the following secure link:\n\n";
            $content .= $order->get_checkout_payment_url() . "\n\n";
            $content .= "This link will redirect you to our secure payment processor.\n";
            $content .= str_repeat('=', 80) . "\n\n";
        }
        
        // Notes
        if ($notes) {
            $content .= "NOTES:\n";
            $content .= str_repeat('-', 40) . "\n";
            $content .= wordwrap($notes, 76) . "\n\n";
        }
        
        // Terms
        if ($terms) {
            $content .= "TERMS & CONDITIONS:\n";
            $content .= str_repeat('-', 40) . "\n";
            $content .= wordwrap($terms, 76) . "\n\n";
        }
        
        // Footer
        $content .= str_repeat('=', 80) . "\n";
        $content .= "Generated by: " . $company_info['name'] . "\n";
        $content .= "Powered by: WooCommerce Manual Invoices Pro\n";
        $content .= "Date: " . current_time('Y-m-d H:i:s T') . "\n";
        $content .= str_repeat('=', 80) . "\n";
        
        return $content;
    }
    
    /**
     * Get PDF library status for admin display
     * 
     * @return array Library status information
     */
    public static function get_pdf_library_status() {
        $status = array();
        
        foreach (self::$pdf_libraries as $key => $name) {
            $method = 'is_' . $key . '_available';
            $status[$key] = array(
                'name' => $name,
                'available' => self::$method(),
                'description' => self::get_library_description($key)
            );
        }
        
        return $status;
    }
    
    /**
     * Get library description
     */
    private static function get_library_description($library) {
        $descriptions = array(
            'dompdf' => 'Best overall compatibility with HTML/CSS. Recommended for most users.',
            'tcpdf' => 'Excellent for complex layouts and Unicode support. Good alternative to DomPDF.',
            'mpdf' => 'Good balance of features and performance. Supports most CSS properties.',
            'fpdf' => 'Basic PDF generation. Lightweight but limited styling options.'
        );
        
        return isset($descriptions[$library]) ? $descriptions[$library] : '';
    }
    
    /**
     * Install recommended PDF library
     */
    public static function install_dompdf_via_composer() {
        if (!function_exists('exec')) {
            return new WP_Error('exec_disabled', 'exec() function is disabled on this server.');
        }
        
        $composer_path = self::find_composer();
        if (!$composer_path) {
            return new WP_Error('composer_not_found', 'Composer not found on this server.');
        }
        
        $plugin_dir = WC_MANUAL_INVOICES_PLUGIN_PATH;
        $command = "cd {$plugin_dir} && {$composer_path} require dompdf/dompdf";
        
        exec($command, $output, $return_var);
        
        if ($return_var === 0) {
            return true;
        } else {
            return new WP_Error('composer_failed', 'Failed to install DomPDF: ' . implode("\n", $output));
        }
    }
    
    /**
     * Find composer executable
     */
    private static function find_composer() {
        $possible_paths = array(
            '/usr/local/bin/composer',
            '/usr/bin/composer',
            'composer',
            'composer.phar'
        );
        
        foreach ($possible_paths as $path) {
            if (shell_exec("which {$path}")) {
                return $path;
            }
        }
        
        return false;
    }
    
    // Keep all existing methods from the original class
    // (get_pdf_download_url, get_secure_pdf_download_url, handle_pdf_download, 
    //  delete_pdf, get_pdf_size, pdf_exists, get_pdf_info, cleanup_old_pdfs)
    
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
        
        // Determine file type and set appropriate headers
        $file_extension = pathinfo($pdf_path, PATHINFO_EXTENSION);
        $filename = 'invoice-' . $order->get_order_number() . '.' . $file_extension;
        
        if ($file_extension === 'pdf') {
            header('Content-Type: application/pdf');
        } else {
            header('Content-Type: text/plain');
        }
        
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
                $order->delete_meta_data('_invoice_pdf_library');
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
        $pdf_library = $order->get_meta('_invoice_pdf_library');
        
        return array(
            'path' => $pdf_path,
            'size' => filesize($pdf_path),
            'generated_date' => $generated_date,
            'library' => $pdf_library ?: 'unknown',
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