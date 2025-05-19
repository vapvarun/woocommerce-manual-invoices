<?php
/**
 * Manual Invoice Email Class
 * 
 * Handles the email template for manual invoices
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('WC_Manual_Invoice_Email')) {
    
    class WC_Manual_Invoice_Email extends WC_Email {
        
        /**
         * Constructor
         */
        public function __construct() {
            $this->id = 'manual_invoice';
            $this->title = __('Manual Invoice', 'wc-manual-invoices');
            $this->description = __('Manual invoice email is sent to customers when an invoice is created manually.', 'wc-manual-invoices');
            $this->template_html = 'emails/manual-invoice.php';
            $this->template_plain = 'emails/plain/manual-invoice.php';
            $this->placeholders = array(
                '{order_date}' => '',
                '{order_number}' => '',
                '{customer_name}' => '',
                '{pay_link}' => '',
            );
            
            // Template path
            $this->template_base = WC_MANUAL_INVOICES_PLUGIN_PATH . 'templates/';
            
            // Triggers
            add_action('woocommerce_order_status_pending_to_processing_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_pending_to_completed_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_pending_to_on-hold_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_failed_to_processing_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_failed_to_completed_notification', array($this, 'trigger'), 10, 2);
            add_action('woocommerce_order_status_failed_to_on-hold_notification', array($this, 'trigger'), 10, 2);
            
            // Call parent constructor
            parent::__construct();
            
            // Customer email
            $this->customer_email = true;
        }
        
        /**
         * Get email subject
         */
        public function get_default_subject() {
            return __('Invoice #{order_number} from {site_title}', 'wc-manual-invoices');
        }
        
        /**
         * Get email heading
         */
        public function get_default_heading() {
            return __('Invoice #{order_number}', 'wc-manual-invoices');
        }
        
        /**
         * Trigger email
         */
        public function trigger($order_id, $order = false) {
            $this->setup_locale();
            
            if ($order_id && !is_a($order, 'WC_Order')) {
                $order = wc_get_order($order_id);
            }
            
            if (is_a($order, 'WC_Order')) {
                $this->object = $order;
                $this->recipient = $this->object->get_billing_email();
                
                // Only send if this is a manual invoice
                if (!$this->object->get_meta('_is_manual_invoice')) {
                    return;
                }
                
                $this->placeholders['{order_date}'] = wc_format_datetime($this->object->get_date_created());
                $this->placeholders['{order_number}'] = $this->object->get_order_number();
                $this->placeholders['{customer_name}'] = $this->object->get_formatted_billing_full_name();
                $this->placeholders['{pay_link}'] = $this->object->get_checkout_payment_url();
            }
            
            if ($this->is_enabled() && $this->get_recipient()) {
                $this->send($this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments());
            }
            
            $this->restore_locale();
        }
        
        /**
         * Get content html
         */
        public function get_content_html() {
            return wc_get_template_html(
                $this->template_html,
                array(
                    'order' => $this->object,
                    'email_heading' => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'sent_to_admin' => false,
                    'plain_text' => false,
                    'email' => $this,
                ),
                '',
                $this->template_base
            );
        }
        
        /**
         * Get content plain
         */
        public function get_content_plain() {
            return wc_get_template_html(
                $this->template_plain,
                array(
                    'order' => $this->object,
                    'email_heading' => $this->get_heading(),
                    'additional_content' => $this->get_additional_content(),
                    'sent_to_admin' => false,
                    'plain_text' => true,
                    'email' => $this,
                ),
                '',
                $this->template_base
            );
        }
        
        /**
         * Default content to show below main email content
         */
        public function get_default_additional_content() {
            return __('Thank you for your business!', 'wc-manual-invoices');
        }
        
        /**
         * Initialize settings form fields
         */
        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'wc-manual-invoices'),
                    'type' => 'checkbox',
                    'label' => __('Enable this email notification', 'wc-manual-invoices'),
                    'default' => 'yes',
                ),
                'subject' => array(
                    'title' => __('Subject', 'wc-manual-invoices'),
                    'type' => 'text',
                    'desc_tip' => true,
                    'description' => sprintf(__('Available placeholders: %s', 'wc-manual-invoices'), '<code>{site_title}, {order_date}, {order_number}, {customer_name}</code>'),
                    'placeholder' => $this->get_default_subject(),
                    'default' => '',
                ),
                'heading' => array(
                    'title' => __('Email heading', 'wc-manual-invoices'),
                    'type' => 'text',
                    'desc_tip' => true,
                    'description' => sprintf(__('Available placeholders: %s', 'wc-manual-invoices'), '<code>{site_title}, {order_date}, {order_number}, {customer_name}</code>'),
                    'placeholder' => $this->get_default_heading(),
                    'default' => '',
                ),
                'additional_content' => array(
                    'title' => __('Additional content', 'wc-manual-invoices'),
                    'description' => __('Text to appear below the main email content.', 'wc-manual-invoices'),
                    'css' => 'width:400px; height: 75px;',
                    'placeholder' => __('N/A', 'wc-manual-invoices'),
                    'type' => 'textarea',
                    'default' => $this->get_default_additional_content(),
                    'desc_tip' => true,
                ),
                'email_type' => array(
                    'title' => __('Email type', 'wc-manual-invoices'),
                    'type' => 'select',
                    'description' => __('Choose which format of email to send.', 'wc-manual-invoices'),
                    'default' => 'html',
                    'class' => 'email_type wc-enhanced-select',
                    'options' => $this->get_email_type_options(),
                    'desc_tip' => true,
                ),
            );
        }
        
        /**
         * Get attachments
         */
        public function get_attachments() {
            $attachments = parent::get_attachments();
            
            // Add PDF invoice if enabled and available
            if ($this->object && class_exists('WC_Manual_Invoice_PDF')) {
                $pdf_path = WC_Manual_Invoice_PDF::generate_pdf($this->object->get_id());
                
                if ($pdf_path && file_exists($pdf_path)) {
                    $attachments[] = $pdf_path;
                }
            }
            
            return apply_filters('wc_manual_invoice_email_attachments', $attachments, $this->object, $this);
        }
    }
}