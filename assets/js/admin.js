/**
 * WooCommerce Manual Invoices - Enhanced Admin JavaScript with Select2
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
            this.initCustomerToggle();
        },
        
        /**
         * Initialize customer select with Select2 and AJAX search
         */
        initCustomerSelect: function() {
            $('#customer_select').select2({
                placeholder: wc_manual_invoices.i18n_select_customer || 'Select a customer...',
                allowClear: true,
                minimumInputLength: 2,
                width: '100%',
                ajax: {
                    url: wc_manual_invoices.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            action: 'wc_manual_invoice_search_customers',
                            term: params.term,
                            page: params.page || 1,
                            nonce: wc_manual_invoices.nonce
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        
                        return {
                            results: data.results || [],
                            pagination: {
                                more: data.pagination && data.pagination.more
                            }
                        };
                    },
                    cache: true,
                    error: function(xhr, status, error) {
                        console.error('Customer search error:', error);
                        WCManualInvoices.showNotice('error', 'Failed to search customers. Please try again.');
                    }
                },
                language: {
                    inputTooShort: function() {
                        return wc_manual_invoices.i18n_search_customers || 'Type at least 2 characters to search customers';
                    },
                    searching: function() {
                        return wc_manual_invoices.i18n_searching || 'Searching customers...';
                    },
                    noResults: function() {
                        return wc_manual_invoices.i18n_no_customers || 'No customers found';
                    },
                    loadingMore: function() {
                        return wc_manual_invoices.i18n_loading_more || 'Loading more results...';
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: function(customer) {
                    if (customer.loading) {
                        return customer.text;
                    }
                    
                    var template = '<div class="customer-result">';
                    template += '<div class="customer-name">' + customer.name + '</div>';
                    template += '<div class="customer-email">' + customer.email + '</div>';
                    if (customer.orders_count) {
                        template += '<div class="customer-meta">' + customer.orders_count + ' previous orders</div>';
                    }
                    template += '</div>';
                    
                    return $(template);
                },
                templateSelection: function(customer) {
                    return customer.name || customer.text;
                }
            }).on('select2:select', function(e) {
                var customer = e.params.data;
                if (customer.id) {
                    WCManualInvoices.loadCustomerDetails(customer.id);
                    $('.customer-details').slideUp();
                    $('#customer_select').closest('.section-content').find('.customer-toggle').show();
                }
            }).on('select2:clear', function() {
                $('.customer-details').slideDown();
                WCManualInvoices.clearCustomerForm();
                $('#customer_select').closest('.section-content').find('.customer-toggle').hide();
            });
        },
        
        /**
         * Initialize product select with Select2 and AJAX search
         */
        initProductSelect: function() {
            // Initialize existing product selects
            $('.product-select').each(function() {
                WCManualInvoices.setupProductSelect($(this));
            });
            
            // Initialize new product selects when rows are added
            $(document).on('DOMNodeInserted', '.invoice-product-row', function() {
                var $select = $(this).find('.product-select');
                if ($select.length && !$select.hasClass('select2-hidden-accessible')) {
                    WCManualInvoices.setupProductSelect($select);
                }
            });
        },
        
        /**
         * Setup individual product select with Select2
         */
        setupProductSelect: function($element) {
            if ($element.hasClass('select2-hidden-accessible')) {
                return; // Already initialized
            }
            
            $element.select2({
                placeholder: wc_manual_invoices.i18n_select_product || 'Search for a product...',
                allowClear: true,
                minimumInputLength: 1, // Reduced from 2 to 1 for better UX
                width: '100%',
                ajax: {
                    url: wc_manual_invoices.ajax_url,
                    type: 'POST',
                    dataType: 'json',
                    delay: 300,
                    data: function(params) {
                        return {
                            action: 'wc_manual_invoice_search_products',
                            term: params.term,
                            page: params.page || 1,
                            nonce: wc_manual_invoices.nonce
                        };
                    },
                    processResults: function(data, params) {
                        params.page = params.page || 1;
                        
                        console.log('Product search response:', data); // Debug log
                        
                        if (data && data.results) {
                            return {
                                results: data.results,
                                pagination: {
                                    more: data.pagination && data.pagination.more
                                }
                            };
                        } else {
                            return {
                                results: [],
                                pagination: {
                                    more: false
                                }
                            };
                        }
                    },
                    cache: true,
                    error: function(xhr, status, error) {
                        console.error('Product search error:', xhr.responseText);
                        console.error('Status:', status);
                        console.error('Error:', error);
                        WCManualInvoices.showNotice('error', 'Failed to search products. Please check console for details.');
                    }
                },
                language: {
                    inputTooShort: function() {
                        return wc_manual_invoices.i18n_search_products || 'Type at least 1 character to search products';
                    },
                    searching: function() {
                        return wc_manual_invoices.i18n_searching || 'Searching products...';
                    },
                    noResults: function() {
                        return wc_manual_invoices.i18n_no_products || 'No products found';
                    },
                    errorLoading: function() {
                        return 'Error loading results. Please try again.';
                    }
                },
                escapeMarkup: function(markup) {
                    return markup;
                },
                templateResult: function(product) {
                    if (product.loading) {
                        return product.text;
                    }
                    
                    if (!product.name) {
                        return product.text || 'Unknown Product';
                    }
                    
                    var template = '<div class="product-result">';
                    template += '<div class="product-name">' + (product.name || '') + '</div>';
                    template += '<div class="product-price">' + (product.price_formatted || '') + '</div>';
                    if (product.sku) {
                        template += '<div class="product-sku">SKU: ' + product.sku + '</div>';
                    }
                    if (product.stock_status) {
                        template += '<div class="product-stock ' + product.stock_status + '">' + (product.stock_text || '') + '</div>';
                    }
                    template += '</div>';
                    
                    return $(template);
                },
                templateSelection: function(product) {
                    return product.name || product.text || 'Select Product';
                }
            }).on('select2:select', function(e) {
                var product = e.params.data;
                var $row = $(this).closest('.invoice-product-row');
                if (product.id) {
                    WCManualInvoices.loadProductDetails(product.id, $row);
                }
            });
        },
        
        /**
         * Initialize customer toggle functionality
         */
        initCustomerToggle: function() {
            // Add toggle button for customer details
            var toggleButton = '<p class="customer-toggle" style="display: none; margin-top: 10px;">' +
                               '<a href="#" class="button button-secondary" id="toggle-customer-details">' +
                               '<span class="dashicons dashicons-edit"></span> Edit Customer Details</a></p>';
            
            $('#customer_select').closest('td').append(toggleButton);
            
            $(document).on('click', '#toggle-customer-details', function(e) {
                e.preventDefault();
                $('.customer-details').slideToggle();
                
                var $button = $(this);
                var $icon = $button.find('.dashicons');
                
                if ($('.customer-details').is(':visible')) {
                    $button.html('<span class="dashicons dashicons-hidden"></span> Hide Customer Details');
                } else {
                    $button.html('<span class="dashicons dashicons-edit"></span> Edit Customer Details');
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
                
                // Clear values and destroy select2
                $template.find('select').val('').trigger('change');
                $template.find('input').val('');
                
                if ($template.find('.product-select').hasClass('select2-hidden-accessible')) {
                    $template.find('.product-select').select2('destroy');
                }
                
                // Reset the select element
                $template.find('.product-select').removeClass('select2-hidden-accessible');
                $template.find('.select2-container').remove();
                
                $container.append($template);
                
                // Initialize Select2 on the new row
                WCManualInvoices.setupProductSelect($template.find('.product-select'));
                
                WCManualInvoices.updateRowNumbers();
            });
            
            // Remove product row
            $(document).on('click', '.remove-product-row', function() {
                var $rows = $('#invoice-products .invoice-product-row');
                if ($rows.length > 1) {
                    var $row = $(this).closest('.invoice-product-row');
                    
                    // Destroy select2 before removing
                    if ($row.find('.product-select').hasClass('select2-hidden-accessible')) {
                        $row.find('.product-select').select2('destroy');
                    }
                    
                    $row.slideUp(300, function() {
                        $(this).remove();
                        WCManualInvoices.calculateTotals();
                        WCManualInvoices.updateRowNumbers();
                    });
                } else {
                    WCManualInvoices.showNotice('warning', 'At least one product row is required.');
                }
            });
            
            // Add custom item row
            $('#add-custom-item-row').on('click', function() {
                var $container = $('#invoice-custom-items');
                var $template = $container.find('.invoice-custom-item-row:first').clone();
                
                $template.find('input').val('');
                $container.append($template);
                WCManualInvoices.updateRowNumbers();
            });
            
            // Remove custom item row
            $(document).on('click', '.remove-custom-item-row', function() {
                var $rows = $('#invoice-custom-items .invoice-custom-item-row');
                if ($rows.length > 1) {
                    $(this).closest('.invoice-custom-item-row').slideUp(300, function() {
                        $(this).remove();
                        WCManualInvoices.calculateTotals();
                        WCManualInvoices.updateRowNumbers();
                    });
                }
            });
            
            // Add fee row
            $('#add-fee-row').on('click', function() {
                var $container = $('#invoice-fees');
                var $template = $container.find('.invoice-fee-row:first').clone();
                
                $template.find('input').val('');
                $container.append($template);
                WCManualInvoices.updateRowNumbers();
            });
            
            // Remove fee row
            $(document).on('click', '.remove-fee-row', function() {
                var $rows = $('#invoice-fees .invoice-fee-row');
                if ($rows.length > 1) {
                    $(this).closest('.invoice-fee-row').slideUp(300, function() {
                        $(this).remove();
                        WCManualInvoices.calculateTotals();
                        WCManualInvoices.updateRowNumbers();
                    });
                }
            });
        },
        
        /**
         * Update row numbers for accessibility
         */
        updateRowNumbers: function() {
            $('#invoice-products .invoice-product-row').each(function(index) {
                $(this).find('input, select').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        // Update array indices
                        var newName = name.replace(/\[\d*\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
            
            $('#invoice-custom-items .invoice-custom-item-row').each(function(index) {
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/\[\d*\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
            
            $('#invoice-fees .invoice-fee-row').each(function(index) {
                $(this).find('input').each(function() {
                    var name = $(this).attr('name');
                    if (name) {
                        var newName = name.replace(/\[\d*\]/, '[' + index + ']');
                        $(this).attr('name', newName);
                    }
                });
            });
        },
        
        /**
         * Initialize AJAX actions
         */
        initAjaxActions: function() {
            // Send invoice email
            $(document).on('click', '.send-invoice-email', function(e) {
                e.preventDefault();
                var $button = $(this);
                var orderId = $button.data('order-id');
                
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');
                
                WCManualInvoices.sendInvoiceEmail(orderId).always(function() {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-email-alt"></span>');
                });
            });
            
            // Generate PDF
            $(document).on('click', '.generate-pdf', function(e) {
                e.preventDefault();
                var $button = $(this);
                var orderId = $button.data('order-id');
                
                $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span>');
                
                WCManualInvoices.generatePDF(orderId).always(function() {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-pdf"></span>');
                });
            });
            
            // Clone invoice
            $(document).on('click', '.clone-invoice', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                if (confirm(wc_manual_invoices.i18n_confirm_clone || 'Are you sure you want to clone this invoice?')) {
                    WCManualInvoices.cloneInvoice(orderId);
                }
            });
            
            // Delete invoice
            $(document).on('click', '.delete-invoice', function(e) {
                e.preventDefault();
                var orderId = $(this).data('order-id');
                if (confirm(wc_manual_invoices.i18n_confirm_delete || 'Are you sure you want to delete this invoice? This action cannot be undone.')) {
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
                              $('.invoice-custom-item-row input[name*="custom_item_names"]').filter(function() { return $(this).val(); }).length > 0;
                
                if (!hasCustomer) {
                    e.preventDefault();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_customer_required || 'Please select a customer or enter email address.');
                    $('html, body').animate({
                        scrollTop: $('#customer_select').offset().top - 100
                    }, 500);
                    return false;
                }
                
                if (!hasItems) {
                    e.preventDefault();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_items_required || 'Please add at least one item to the invoice.');
                    $('html, body').animate({
                        scrollTop: $('#invoice-products').offset().top - 100
                    }, 500);
                    return false;
                }
                
                // Show loading state
                WCManualInvoices.showLoading();
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
                },
                error: function() {
                    WCManualInvoices.showNotice('error', 'Failed to load customer details.');
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
                        var quantity = $row.find('input[name*="product_quantities"]').val() || 1;
                        var total = parseFloat(product.price) * parseInt(quantity);
                        
                        $row.find('input[name*="product_totals"]').val(total.toFixed(2));
                        WCManualInvoices.calculateTotals();
                    }
                },
                error: function() {
                    WCManualInvoices.showNotice('error', 'Failed to load product details.');
                }
            });
        },
        
        /**
         * Clear customer form
         */
        clearCustomerForm: function() {
            $('#customer_email, #billing_first_name, #billing_last_name, #billing_phone')
                .add('#billing_address_1, #billing_address_2, #billing_city, #billing_state')
                .add('#billing_postcode').val('');
            $('#billing_country').val('US');
        },
        
        /**
         * Calculate totals with real-time updates
         */
        calculateTotals: function() {
            $(document).on('input', 'input[name*="product_quantities"], input[name*="product_totals"], input[name*="custom_item_totals"]', function() {
                // Auto-calculate product totals when quantity changes
                var $row = $(this).closest('.invoice-product-row');
                if ($(this).is('input[name*="product_quantities"]') && $row.length) {
                    var $productSelect = $row.find('.product-select');
                    if ($productSelect.val()) {
                        // Get product data from select2
                        var productData = $productSelect.select2('data')[0];
                        if (productData && productData.price) {
                            var quantity = parseInt($(this).val()) || 1;
                            var total = parseFloat(productData.price) * quantity;
                            $row.find('input[name*="product_totals"]').val(total.toFixed(2));
                        }
                    }
                }
                
                // Update grand total display if we have one
                WCManualInvoices.updateGrandTotal();
            });
        },
        
        /**
         * Update grand total display
         */
        updateGrandTotal: function() {
            var subtotal = 0;
            
            // Add product totals
            $('input[name*="product_totals"]').each(function() {
                if ($(this).val()) {
                    subtotal += parseFloat($(this).val()) || 0;
                }
            });
            
            // Add custom item totals
            $('input[name*="custom_item_totals"]').each(function() {
                if ($(this).val()) {
                    subtotal += parseFloat($(this).val()) || 0;
                }
            });
            
            // Add fees
            $('input[name*="fee_amounts"]').each(function() {
                if ($(this).val()) {
                    subtotal += parseFloat($(this).val()) || 0;
                }
            });
            
            // Add shipping
            var shipping = parseFloat($('#shipping_total').val()) || 0;
            subtotal += shipping;
            
            // Add tax
            var tax = parseFloat($('#tax_total').val()) || 0;
            subtotal += tax;
            
            // Update display if element exists
            if ($('#invoice-total-display').length) {
                $('#invoice-total-display').text(WCManualInvoices.formatPrice(subtotal));
            }
        },
        
        /**
         * Format price with currency
         */
        formatPrice: function(price) {
            var formattedPrice = price.toFixed(2);
            var symbol = wc_manual_invoices.currency_symbol || '$';
            var position = wc_manual_invoices.currency_position || 'left';
            
            switch (position) {
                case 'left':
                    return symbol + formattedPrice;
                case 'right':
                    return formattedPrice + symbol;
                case 'left_space':
                    return symbol + ' ' + formattedPrice;
                case 'right_space':
                    return formattedPrice + ' ' + symbol;
                default:
                    return symbol + formattedPrice;
            }
        },
        
        /**
         * Send invoice email
         */
        sendInvoiceEmail: function(orderId) {
            WCManualInvoices.showLoading();
            
            return $.ajax({
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
                        WCManualInvoices.showNotice('success', response.data.message || 'Email sent successfully');
                    } else {
                        WCManualInvoices.showNotice('error', response.data.message || 'Failed to send email');
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error || 'An error occurred. Please try again.');
                }
            });
        },
        
        /**
         * Generate PDF
         */
        generatePDF: function(orderId) {
            WCManualInvoices.showLoading();
            
            return $.ajax({
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
                        if (response.data.download_url) {
                            window.open(response.data.download_url, '_blank');
                        }
                        WCManualInvoices.showNotice('success', response.data.message || 'PDF generated successfully');
                    } else {
                        WCManualInvoices.showNotice('error', response.data.message || 'Failed to generate PDF');
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error || 'An error occurred. Please try again.');
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
                        WCManualInvoices.showNotice('success', response.data.message || 'Invoice cloned successfully');
                        if (response.data.redirect_url) {
                            setTimeout(function() {
                                window.location.href = response.data.redirect_url;
                            }, 1500);
                        } else {
                            setTimeout(function() {
                                location.reload();
                            }, 1500);
                        }
                    } else {
                        WCManualInvoices.showNotice('error', response.data.message || 'Failed to clone invoice');
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error || 'An error occurred. Please try again.');
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
                        WCManualInvoices.showNotice('success', response.data.message || 'Invoice deleted successfully');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        WCManualInvoices.showNotice('error', response.data.message || 'Failed to delete invoice');
                    }
                },
                error: function() {
                    WCManualInvoices.hideLoading();
                    WCManualInvoices.showNotice('error', wc_manual_invoices.i18n_ajax_error || 'An error occurred. Please try again.');
                }
            });
        },
        
        /**
         * Show loading overlay
         */
        showLoading: function() {
            $('#wc-manual-invoices-loading').fadeIn(200);
        },
        
        /**
         * Hide loading overlay
         */
        hideLoading: function() {
            $('#wc-manual-invoices-loading').fadeOut(200);
        },
        
        /**
         * Show notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible wc-manual-invoices-notice">' +
                          '<p>' + message + '</p>' +
                          '<button type="button" class="notice-dismiss"><span class="screen-reader-text">Dismiss</span></button>' +
                          '</div>');
            
            // Insert after header or at top of page
            if ($('.wc-manual-invoices-header').length) {
                $('.wc-manual-invoices-header').after($notice);
            } else {
                $('.wrap h1').first().after($notice);
            }
            
            // Handle dismiss button
            $notice.find('.notice-dismiss').on('click', function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
            
            // Auto-dismiss after 5 seconds for success messages
            if (type === 'success') {
                setTimeout(function() {
                    $notice.fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 5000);
            }
            
            // Scroll to notice
            $('html, body').animate({
                scrollTop: $notice.offset().top - 100
            }, 300);
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        WCManualInvoices.init();
        
        // Add some styles for Select2 results
        if (!$('#wc-manual-invoices-select2-styles').length) {
            $('head').append(`
                <style id="wc-manual-invoices-select2-styles">
                .customer-result, .product-result {
                    padding: 8px 0;
                }
                .customer-name, .product-name {
                    font-weight: 600;
                    color: #2c3e50;
                    margin-bottom: 4px;
                }
                .customer-email, .product-price {
                    color: #7f8c8d;
                    font-size: 13px;
                    margin-bottom: 2px;
                }
                .customer-meta, .product-sku {
                    color: #96588a;
                    font-size: 12px;
                    font-style: italic;
                }
                .product-stock {
                    font-size: 11px;
                    padding: 2px 6px;
                    border-radius: 3px;
                    margin-top: 4px;
                    display: inline-block;
                }
                .product-stock.instock {
                    background: #d4edda;
                    color: #155724;
                }
                .product-stock.outofstock {
                    background: #f8d7da;
                    color: #721c24;
                }
                .dashicons.spin {
                    animation: rotation 1s infinite linear;
                }
                @keyframes rotation {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(359deg); }
                }
                </style>
            `);
        }
    });
    
})(jQuery);