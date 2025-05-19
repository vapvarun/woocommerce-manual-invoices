/**
 * WooCommerce Manual Invoices - Admin JavaScript
 */

(function($) {
    'use strict';
    
    var WCManualInvoices = {
        
        /**
         * Initialize
         */
        init: function() {
            this.initCustomerSelect();
            this.initProductSelect();
            this.initDynamicRows();
            this.initAjaxActions();
            this.initFormValidation();
            this.calculateTotals();
        },
        
        /**
         * Initialize customer select with search
         */
        initCustomerSelect: function() {
            $('#customer_select').select2({
                placeholder: wc_manual_invoices.i18n_select_customer,
                allowClear: true,
                minimumInputLength: 2,
                ajax: {
                    url: wc_manual_invoices.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'wc_manual_invoice_search_customers',
                            term: params.term,
                            nonce: wc_manual_invoices.nonce
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data
                        };
                    },
                    cache: true
                }
            }).on('change', function() {
                var customerId = $(this).val();
                if (customerId) {
                    WCManualInvoices.loadCustomerDetails(customerId);
                    $('.customer-details').hide();
                } else {
                    $('.customer-details').show();
                    WCManualInvoices.clearCustomerForm();
                }
            });
        },
        
        /**
         * Initialize product select with search
         */
        initProductSelect: function() {
            $(document).on('focus', '.product-select', function() {
                if (!$(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2({
                        placeholder: wc_manual_invoices.i18n_select_product,
                        allowClear: true,
                        minimumInputLength: 2,
                        ajax: {
                            url: wc_manual_invoices.ajax_url,
                            type: 'POST',
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    action: 'wc_manual_invoice_search_products',
                                    term: params.term,
                                    nonce: wc_manual_invoices.nonce
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: data
                                };
                            },
                            cache: true
                        }
                    }).on('change', function() {
                        var productId = $(this).val();
                        var $row = $(this).closest('.invoice-product-row');
                        if (productId) {
                            WCManualInvoices.loadProductDetails(productId, $row);
                        }
                    });
                }
            });
        },
        
        /**
         * Initialize dynamic rows
         */
        initDynamicRows: function() {
            // Add product row
            $('#add-product-row').on('click', function() {
                var $container = $('#invoice-products');
                var $template = $container.find('.invoice-product-row:first').clone();
                
                // Clear values
                $template.find('select').val('').trigger('change');
                $template.find('input').val('');
                
                // Destroy select2 if exists
                if ($template.find('.product-select').hasClass('select2-hidden-accessible')) {
                    $template.find('.product-select').select2('destroy');
                }
                
                $container.append($template);
            });
            
            // Remove product row
            $(document).on('click', '.remove-product-row', function() {
                var $rows = $('#invoice-products .invoice-product-row');
                if ($rows.length > 1) {
                    $(this).closest('.invoice-product-row').remove();
                    WCManualInvoices.calculateTotals();
                }
            });
            
            // Add custom item row
            $('#add-custom-item-row').on('click', function() {
                var $container = $('#invoice-custom-items');
                var $template = $container.find('.invoice-custom-item-row:first').clone();
                
                // Clear values
                $template.find('input').val('');
                
                $container.append($template);
            });
            
            // Remove custom item row
            $(document).on('click', '.remove-custom-item-row', function() {
                var $rows = $('#invoice-custom-items .invoice-custom-item-row');
                if ($rows.length > 1) {
                    $(this).closest('.invoice-custom-item-row').remove();
                    WCManualInvoices.calculateTotals();
                }
            });
            
            // Add fee row
            $('#add-fee-row').on('click', function() {
                var $container = $('#invoice-fees');
                var $template = $container.find('.invoice-fee-row:first').clone();
                
                // Clear values
                $template.find('input').val('');
                
                $container.append($template);
            });
            
            // Remove fee row
            $(document).on('click', '.remove-fee-row', function() {
                var $rows = $('#invoice-fees .invoice-fee-row');
                if ($rows.length > 1) {
                    $(this).closest('.invoice-fee-row').remove();
                    WCManualInvoices.calculateTotals();
                }
            });
        },
        
        /**
         * Initialize AJAX actions
         */
        initAjaxActions: function() {
            // Send invoice email
            $(document).on('click', '.send-invoice-email', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                WCManualInvoices.sendInvoiceEmail(orderId);
            });
            
            // Generate PDF
            $(document).on('click', '.generate-pdf', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                WCManualInvoices.generatePDF(orderId);
            });
            
            // Clone invoice
            $(document).on('click', '.clone-invoice', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                if (confirm(wc_manual_invoices.i18n_confirm_clone)) {
                    WCManualInvoices.cloneInvoice(orderId);
                }
            });
            
            // Delete invoice
            $(document).on('click', '.delete-invoice', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                if (confirm(wc_manual_invoices.i18n_confirm_delete)) {
                    WCManualInvoices.deleteInvoice(orderId);
                }
            });
        },
        
        /**
         * Initialize form validation
         */
        initFormValidation: function() {
            $('#wc-manual-invoice-form').on('submit', function(e) {
                var hasCustomer = $('#customer_select').val() || $('#customer_email').val();
                var hasItems = $('.invoice-product-row select').filter(function() { return $(this).val(); }).length > 0 ||
                              $('.invoice-custom-item-row input[name="custom_item_names[]"]').filter(function() { return $(this).val(); }).length > 0;
                
                if (!hasCustomer) {
                    alert(wc_manual_invoices.i18n_customer_required);
                    e.preventDefault();
                    return false;
                }
                
                if (!hasItems) {
                    alert(wc_manual_invoices.i18n_items_required);
                    e.preventDefault();
                    return false;
                }
            });
        },
        
        /**
         * Load customer details
         */
        loadCustomerDetails: function(customerId) {
            $.ajax({
                url: wc_manual_invoices.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_manual_invoice_get_customer_details',
                    customer_id: customerId,
                    nonce: wc_manual_invoices.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var customer = response.data;
                        $('#customer_email').val(customer.email);
                        $('#billing_first_name').val(customer.first_name);
                        $('#billing_last_name').val(customer.last_name);
                        $('#billing_phone').val(customer.phone);
                        $('#billing_address_1').val(customer.address_1);
                        $('#billing_address_2').val(customer.address_2);
                        $('#billing_city').val(customer.city);
                        $('#billing_state').val(customer.state);
                        $('#billing_postcode').val(customer.postcode);
                        $('#billing_country').val(customer.country);
                    }
                }
            });
        },
        
        /**
         * Load product details
         */
        loadProductDetails: function(productId, $row) {
            $.ajax({
                url: wc_manual_invoices.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_manual_invoice_get_product_details',
                    product_id: productId,
                    nonce: wc_manual_invoices.nonce
                },
                success: function(response) {
                    if (response.success) {
                        var product = response.data;
                        var quantity = $row.find('input[name="product_quantities[]"]').val() || 1;
                        var total = parseFloat(product.price) * parseInt(quantity);
                        
                        $row.find('input[name="product_totals[]"]').val(total.toFixed(2));
                        WCManualInvoices.calculateTotals();
                    }
                }
            });
        },
        
        /**
         * Clear customer form
         */
        clearCustomerForm: function() {
            $('#customer_email, #billing_first_name, #billing_last_name, #billing_phone')
                .add('#billing_address_1, #billing_address_2, #billing_city, #billing_state')
                .add('#billing_postcode, #billing_country').val('');
        },
        
        /**
         * Calculate totals
         */
        calculateTotals: function() {
            $(document).on('input', 'input[name="product_quantities[]"], input[name="product_totals[]"], input[name="custom_item_totals[]"]', function() {
                // Auto-calculate product totals when quantity changes
                var $row = $(this).closest('.invoice-product-row');
                if ($(this).is('input[name="product_quantities[]"]') && $row.length) {
                    var $productSelect = $row.find('.product-select');
                    if ($productSelect.val()) {
                        // Get product data from select2
                        var productData = $productSelect.select2('data')[0];
                        if (productData && productData.price) {
                            var quantity = parseInt($(this).val()) || 1;
                            var total = parseFloat(productData.price) * quantity;
                            $row.find('input[name="product_totals[]"]').val(total.toFixed(2));
                        }
                    }
                }
            });
        },
        
        /**
         * Send invoice email
         */
        sendInvoiceEmail: function(orderId) {
            WCManualInvoices.showLoading();
            
            $.ajax({
                url: wc_manual_invoices.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_manual_invoice_send_email',
                    order_id: orderId,
                    nonce: wc_manual_invoices.nonce
                },
                success: function(response) {
                    WCManualInvoices.hideLoading();
                    if (response.success) {
                        WCManualInvoices.showNotice('success', response.data);
                    } else {
                        WCManualInvoices.showNotice('error', response.data);
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error);
                }
            });
        },
        
        /**
         * Generate PDF
         */
        generatePDF: function(orderId) {
            WCManualInvoices.showLoading();
            
            $.ajax({
                url: wc_manual_invoices.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_manual_invoice_generate_pdf',
                    order_id: orderId,
                    nonce: wc_manual_invoices.nonce
                },
                success: function(response) {
                    WCManualInvoices.hideLoading();
                    if (response.success) {
                        window.open(response.data.download_url, '_blank');
                        WCManualInvoices.showNotice('success', response.data.message);
                    } else {
                        WCManualInvoices.showNotice('error', response.data);
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error);
                }
            });
        },
        
        /**
         * Clone invoice
         */
        cloneInvoice: function(orderId) {
            WCManualInvoices.showLoading();
            
            $.ajax({
                url: wc_manual_invoices.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_manual_invoice_clone',
                    order_id: orderId,
                    nonce: wc_manual_invoices.nonce
                },
                success: function(response) {
                    WCManualInvoices.hideLoading();
                    if (response.success) {
                        WCManualInvoices.showNotice('success', response.data.message);
                        if (response.data.redirect_url) {
                            window.location.href = response.data.redirect_url;
                        } else {
                            location.reload();
                        }
                    } else {
                        WCManualInvoices.showNotice('error', response.data);
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error);
                }
            });
        },
        
        /**
         * Delete invoice
         */
        deleteInvoice: function(orderId) {
            WCManualInvoices.showLoading();
            
            $.ajax({
                url: wc_manual_invoices.ajax_url,
                type: 'POST',
                data: {
                    action: 'wc_manual_invoice_delete',
                    order_id: orderId,
                    nonce: wc_manual_invoices.nonce
                },
                success: function(response) {
                    WCManualInvoices.hideLoading();
                    if (response.success) {
                        WCManualInvoices.showNotice('success', response.data);
                        location.reload();
                    } else {
                        WCManualInvoices.showNotice('error', response.data);
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error);
                }
            });
        },
        
        /**
         * Show loading overlay
         */
        showLoading: function() {
            $('#wc-manual-invoices-loading').show();
        },
        
        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#wc-manual-invoices-loading').hide();
        },
        
        /**
         * Show notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $notice.remove();
                });
            }, 5000);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WCManualInvoices.init();
    });
    
    // Localization object
    if (typeof wc_manual_invoices === 'undefined') {
        window.wc_manual_invoices = {
            i18n_select_customer: 'Select a customer...',
            i18n_select_product: 'Select a product...',
            i18n_customer_required: 'Please select a customer or enter email address.',
            i18n_items_required: 'Please add at least one item to the invoice.',
            i18n_confirm_clone: 'Are you sure you want to clone this invoice?',
            i18n_confirm_delete: 'Are you sure you want to delete this invoice? This action cannot be undone.',
            i18n_ajax_error: 'An error occurred. Please try again.'
        };
    }
    
})(jQuery);