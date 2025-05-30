<?php
/**
 * Enhanced Manual Invoice PDF Generator with Both DomPDF and TCPDF Support
 * 
 * Replaces: includes/class-invoice-pdf.php
 * This class works with both bundled DomPDF and manually installed TCPDF
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class WC_Manual_Invoice_PDF {
    
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
        
        // Get the best available library
        $available_library = WC_Manual_Invoice_PDF_Installer::get_best_available_library();
        
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
     * Generate PDF using specified library
     * 
     * @param string $library Library to use
     * @param WC_Order $order Order object
     * @param string $pdf_path Output path
     * @return string|false PDF path or false on failure
     */
    private static function generate_with_library($library, $order, $pdf_path) {
        switch ($library) {
            case 'dompdf':
                return self::generate_with_dompdf($order, $pdf_path);
            case 'tcpdf':
                return self::generate_with_tcpdf($order, $pdf_path);
            default:
                return false;
        }
    }
    
    /**
     * Generate PDF using DomPDF
     */
    private static function generate_with_dompdf($order, $pdf_path) {
        try {
            // Load DomPDF
            $dompdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/dompdf/autoload.inc.php';
            if (!file_exists($dompdf_path)) {
                return false;
            }
            
            require_once $dompdf_path;
            
            // Initialize DomPDF
            if (class_exists('Dompdf\\Dompdf')) {
                $dompdf = new \Dompdf\Dompdf();
                $options = $dompdf->getOptions();
                $options->set('defaultFont', 'Arial');
                $options->set('isRemoteEnabled', true);
                $options->set('isHtml5ParserEnabled', true);
                $options->set('isFontSubsettingEnabled', true);
                $options->set('chroot', array(ABSPATH, WP_CONTENT_DIR));
            } else {
                // Legacy DomPDF
                $dompdf = new DOMPDF();
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
            // Load TCPDF with proper configuration
            $tcpdf_path = WC_MANUAL_INVOICES_PLUGIN_PATH . 'lib/tcpdf/tcpdf.php';
            if (!file_exists($tcpdf_path)) {
                return false;
            }
            
            // Set up TCPDF configuration before loading
            self::setup_tcpdf_config();
            
            require_once $tcpdf_path;
            
            // Create new PDF document
            $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            
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
        
        // Enhanced CSS for better PDF rendering with both libraries
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
            .items-table th.text-center {
                text-align: center;
            }
            .items-table th.text-right {
                text-align: right;
            }
            .items-table td { 
                padding: 10px 8px; 
                border: 1px solid #ddd; 
                vertical-align: top;
            }
            .items-table tr:nth-child(even) { 
                background-color: #f9f9f9; 
            }
            .items-table .item-name {
                font-weight: bold;
                color: #333;
                font-size: 12px;
            }
            .items-table .item-description {
                color: #666;
                font-size: 10px;
                font-style: italic;
                margin-top: 4px;
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
            $html .= '<td><div class="item-name">' . esc_html($item->get_name()) . '</div>';
            
            if ($item->get_meta('_custom_item_description')) {
                $html .= '<div class="item-description">' . esc_html($item->get_meta('_custom_item_description')) . '</div>';
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
        
        // Invoice details
        $content .= "Invoice: " . $invoice_number . "\n";
        $content .= "Date: " . wc_format_datetime($order->get_date_created()) . "\n";
        if ($due_date) {
            $content .= "Due Date: " . date_i18n(get_option('date_format'), strtotime($due_date)) . "\n";
        }
        $content .= "Status: " . wc_get_order_status_name($order->get_status()) . "\n";
        $content .= "Total: " . strip_tags($order->get_formatted_order_total()) . "\n\n";
        
        // Customer info
        $content .= "CUSTOMER:\n";
        $content .= $order->get_formatted_billing_full_name() . "\n";
        $content .= $order->get_billing_email() . "\n";
        if ($order->get_billing_phone()) {
            $content .= $order->get_billing_phone() . "\n";
        }
        $content .= "\n";
        
        // Items
        $content .= "ITEMS:\n";
        $content .= str_repeat('-', 80) . "\n";
        
        foreach ($order->get_items() as $item) {
            $content .= sprintf("%-50s %5d %15s\n", 
                substr($item->get_name(), 0, 50), 
                $item->get_quantity(), 
                strip_tags(wc_price($item->get_total()))
            );
            
            if ($item->get_meta('_custom_item_description')) {
                $content .= "  " . $item->get_meta('_custom_item_description') . "\n";
            }
        }
        
        $content .= str_repeat('-', 80) . "\n";
        $content .= sprintf("%66s %13s\n", "TOTAL:", strip_tags($order->get_formatted_order_total()));
        
        // Payment instructions
        if ($order->needs_payment()) {
            $content .= "\n\nPAYMENT INSTRUCTIONS:\n";
            $content .= str_repeat('=', 80) . "\n";
            $content .= "Please pay online using the following secure link:\n\n";
            $content .= $order->get_checkout_payment_url() . "\n\n";
        }
        
        // Notes and terms
        if ($notes) {
            $content .= "\nNOTES:\n";
            $content .= str_repeat('-', 40) . "\n";
            $content .= wordwrap($notes, 76) . "\n\n";
        }
        
        if ($terms) {
            $content .= "TERMS & CONDITIONS:\n";
            $content .= str_repeat('-', 40) . "\n";
            $content .= wordwrap($terms, 76) . "\n\n";
        }
        
        return $content;
    }
    
    // Keep all existing methods from the original class
    // (get_pdf_download_url, get_secure_pdf_download_url, handle_pdf_download, 
    //  delete_pdf, get_pdf_size, pdf_exists, get_pdf_info, cleanup_old_pdfs)
    
    /**
     * Get PDF download URL
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
     * Check if PDF exists
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
     * Get PDF library status for admin display
     */
    public static function get_pdf_library_status() {
        return WC_Manual_Invoice_PDF_Installer::get_library_status();
    }
}

// Initialize PDF download handler
add_action('init', array('WC_Manual_Invoice_PDF', 'handle_pdf_download'));