/**
 * Computer Repair Shop - AJAX Handler (jQuery)
 * Handles registration, login, and other AJAX functionality
 */

(function($) {
    'use strict';
    class WCRBAjaxHandler {
        constructor() {
            this.ajaxUrl = wcrb_ajax.ajax_url;
            this.nonce = wcrb_ajax.nonce;
            this.init();
        }

        init() {
            // Registration form handler
            this.handleRegistration();
            this.handleStatusUpdate();
            this.handlePriorityUpdate();
            this.handleTakePayment();
            this.handlePaymentCalculation();
            this.handlePaymentSubmission();
            this.handleDuplicateJob();
            this.performDuplicateJob();
            this.handleSearchCustomer();
            this.handleFileUploading();
            this.handleMessageSubmission();
            this.handleDevicesUpdates();
            this.handleNormalForms();
            this.initializeSelect2();
        }

        /**
         * Initialize Select2 for customer search
         */
        initializeSelect2() {
            // Initialize Select2 for any existing elements outside modals
            $('.wcrb_select_customers').not('#addDeviceModal .wcrb_select_customers').select2({
                ajax: {
                    url: this.ajaxUrl,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => {
                        return {
                            q: params.term,
                            security: $('#wcrb_nonce_adrepairbuddy_field').val(),
                            action: 'wcrb_return_customer_data_select2'
                        }
                    },
                    processResults: (data) => {
                        const options = [];
                        if (data) {
                            $.each(data, (index, text) => {
                                options.push({ id: text[0], text: text[1] });
                            });
                        }
                        return { results: options };
                    },
                    cache: true
                },
                minimumInputLength: 3,
                width: '100%'
            });
            
            // Handle modal-based Select2 initialization
            this.initializeModalSelect2();
        }
        
        /**
         * Initialize Select2 specifically for modals
         */
        initializeModalSelect2() {
            // Initialize when modal is shown
            $(document).on('shown.bs.modal', '#addDeviceModal', (e) => {
                const $modal = $(e.target);
                const $select = $modal.find('.wcrb_select_customers');
                
                // Destroy if already initialized
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                
                // Initialize with modal as parent
                $select.select2({
                    dropdownParent: $modal,
                    ajax: {
                        url: this.ajaxUrl,
                        dataType: 'json',
                        delay: 250,
                        data: (params) => {
                            return {
                                q: params.term,
                                security: $modal.find('#wcrb_nonce_adrepairbuddy_field').val(),
                                action: 'wcrb_return_customer_data_select2'
                            }
                        },
                        processResults: (data) => {
                            const options = [];
                            if (data) {
                                $.each(data, (index, text) => {
                                    options.push({ id: text[0], text: text[1] });
                                });
                            }
                            return { results: options };
                        },
                        cache: true
                    },
                    minimumInputLength: 3,
                    width: '100%'
                });
            });
            
            // Clean up when modal is hidden (optional)
            $(document).on('hidden.bs.modal', '#addDeviceModal', (e) => {
                const $select = $(e.target).find('.wcrb_select_customers');
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
            });
            
            // Handle Select2 dropdown z-index issue in modals
            $(document).on('select2:open', (e) => {
                if ($('#addDeviceModal').hasClass('show')) {
                    setTimeout(() => {
                        const $dropdown = $('.select2-dropdown');
                        if ($dropdown.length) {
                            $dropdown.css('z-index', '99999');
                        }
                    }, 10);
                }
            });
        }

        handleSearchCustomer() {
            // This is now handled by initializeSelect2() and initializeModalSelect2()
            // No need for separate handler
        }

        handleNormalForms() {
            const self = this;

            $("form[data-async]").on("submit", function(e) {
                e.preventDefault();

                const $form = $(this);
                const $target = $($form.attr('data-target'));
                const formData = $form.serialize();
                const $input = $form.find("input[name=form_type]");
                
                let $success_class = '.form-message';
                if ($form.attr('data-success-class') !== undefined) {
                    $success_class = $form.attr('data-success-class');
                }
                
                const $reload_location = $form.find("input[name=reload_location]").val();
                let $perform_act;

                if ($input.val() == "wc_request_quote_form") {
                    $perform_act = "wc_cr_submit_quote_form";
                } else if ($input.val() == "wc_create_new_job_form") {
                    $perform_act = "wc_cr_create_new_job";
                } else {
                    $perform_act = $form.find("input[name=form_action]").val();
                    if (typeof $perform_act === "undefined") {
                        $perform_act = "wc_cmp_rep_check_order_status";
                    }
                }

                $.ajax({
                    type: $form.attr('method'),
                    data: formData + '&action=' + $perform_act,
                    url: self.ajaxUrl,
                    dataType: 'json',
                    beforeSend: function() {
                        $($success_class).html("<div class='spinner-border text-primary' role='status'><span class='visually-hidden'>Loading...</span></div>");
                    },
                    success: function(response) {
                        const message = response.message;
                        const success = response.success;
                        const reset_select2 = response.reset_select2;

                        self.showMessage('success', message, $success_class);
                        
                        if ($reload_location !== undefined) {
                            const reloadSelector = '.' + $reload_location;
                            $(reloadSelector).load(window.location + " " + reloadSelector);
                        }
                        
                        if (success == "YES") {
                            $form.trigger("reset");
                            if (reset_select2 == "YES") {
                                $("#customer, #rep_devices").val(null).trigger('change');
                            }
                        }
                    },
                    error: function(xhr, status, error) {
                        self.showMessage('error', 'An error occurred. Please try again.', $success_class);
                    }
                });
            });
        }

        /**
         * Handle user registration
         */
        handleRegistration() {
            $('#registerform').on('submit', (e) => {
                e.preventDefault();
                
                const $form = $(e.target);
                const $submitBtn = $form.find('button[type="submit"]');
                const originalText = $submitBtn.html();
                
                // Show loading state
                $submitBtn.html('<i class="bi bi-arrow-repeat spinner"></i> Processing...');
                $submitBtn.prop('disabled', true);

                const formData = new FormData(e.target);

                // Add AJAX action
                formData.append('action', 'wcrb_register_user');
                formData.append('security', this.nonce);

                $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json'
                })
                .done((response) => {
                    // Handle different response formats
                    let message = response.message || '';
                    let success = response.success || false;
                    let redirect = response.redirect_to || '';

                    if (success === true || success === 'YES') {
                        this.showMessage('success', message, '.resgister_account_form_message');
                        
                        if (redirect) {
                            setTimeout(() => {
                                window.location.href = redirect;
                            }, 2000);
                        } else {
                            $form[0].reset();
                        }
                    } else {
                        this.showMessage('error', message, '.resgister_account_form_message');
                    }
                })
                .fail((xhr, status, error) => {
                    this.showMessage('error', 'An error occurred. Please try again.', '.resgister_account_form_message');
                })
                .always(() => {
                    // Restore button state
                    $submitBtn.html(originalText);
                    $submitBtn.prop('disabled', false);
                });
            });
        }

        /* Handle Priority Update */
        handlePriorityUpdate() {
            const self = this; // Store reference to the class instance
            
            $(".dropdown-item[data-type='update_job_priority']").on('click', function(e) {
                e.preventDefault();

                var $clickedItem = $(this);
                var recordID = $clickedItem.attr("recordid");
                var priorityValue = $clickedItem.attr("data-value");
                var securityNonce = $clickedItem.attr("data-security");

                // Get dropdown elements
                var $dropdown = $clickedItem.closest('.dropdown');
                var $button = $dropdown.find('.dropdown-toggle');
                
                // Store original content for restoration
                var originalContent = $button.html();

                // Close the dropdown immediately
                var bootstrapDropdown = bootstrap.Dropdown.getInstance($button[0]);
                if (bootstrapDropdown) {
                    bootstrapDropdown.hide();
                }

                // Show loading state on button
                $button.html('<div class="spinner-border spinner-border-sm me-2" role="status"></div> Updating...');
                $button.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    data: {
                        'action': 'wcrb_update_job_priority',
                        'recordID': recordID,
                        'priority': priorityValue,
                        'nonce': securityNonce
                    },
                    url: self.ajaxUrl,
                    dataType: 'json',

                    beforeSend: function() {
                        $( "#jobsTable_list .aj_msg" ).html( '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' );
                    },
                    success: function(response) {
                        
                        // Remove global spinner
                        $( "#jobsTable_list .aj_msg" ).html('');

                        if (response.success) {
                            // Update button with new priority
                            var newPriorityText = $clickedItem.find('span').text();
                            var newPriorityIcon = $clickedItem.find('.d-flex i').first().clone();
                            
                            $button.html('');
                            $button.append(newPriorityIcon);
                            $button.append('<span class="ms-2">' + newPriorityText + '</span>');
                            
                            // Update button color based on priority
                            self.updateButtonPriorityClass($button, priorityValue);
                            
                            // Update active state in dropdown
                            $dropdown.find('.dropdown-item').removeClass('active').find('.bi-check2').remove();
                            $clickedItem.addClass('active').append('<i class="bi bi-check2 text-primary ms-2"></i>');
                            
                            // Show success message
                            self.showMessage('success', response.data, '#jobsTable_list .aj_msg');
                            
                        } else {
                            // Restore original button content on error
                            $button.html(originalContent);
                            $button.prop('disabled', false);
                            
                            // Show error message
                            self.showMessage('error', response.data, '#jobsTable_list .aj_msg');
                        }
                        
                        // Re-enable button
                        $button.prop('disabled', false);
                    },
                    error: function(xhr, status, error) {
                        // Restore original button content on AJAX error
                        $button.html(originalContent);
                        $button.prop('disabled', false);
                        
                        // Show error message
                        self.showMessage('error', 'An error occurred while updating priority.', '#jobsTable_list .aj_msg');
                    }
                });
            });
        }

        /* Update button class based on priority value */
        updateButtonPriorityClass($button, priorityValue) {
            // Remove all existing priority classes
            $button.removeClass('btn-primary btn-success btn-secondary btn-info btn-warning btn-danger');
            
            // Add appropriate class based on priority
            switch(priorityValue) {
                case 'low':
                    $button.addClass('btn-success');
                    break;
                case 'normal':
                    $button.addClass('btn-primary');
                    break;
                case 'medium':
                    $button.addClass('btn-info');
                    break;
                case 'high':
                    $button.addClass('btn-warning');
                    break;
                case 'urgent':
                    $button.addClass('btn-danger');
                    break;
                default:
                    $button.addClass('btn-primary');
            }
        }

        /* Handle Status Update */
        handleStatusUpdate() {
            const self = this;
            
            $(".dropdown-item[data-type='job_status_update']").on('click', function(e) {
                e.preventDefault();

                var $clickedItem = $(this);
                var recordID = $clickedItem.attr("recordid");
                var statusValue = $clickedItem.attr("data-value");
                var securityNonce = $clickedItem.attr("data-security");

                // Get dropdown elements
                var $dropdown = $clickedItem.closest('.dropdown');
                var $button = $dropdown.find('.dropdown-toggle');
                
                // Store original content for restoration
                var originalContent = $button.html();

                // Close the dropdown immediately
                var bootstrapDropdown = bootstrap.Dropdown.getInstance($button[0]);
                if (bootstrapDropdown) {
                    bootstrapDropdown.hide();
                }

                // Show loading state on button
                $button.html('<div class="spinner-border spinner-border-sm me-2" role="status"></div> Updating...');
                $button.prop('disabled', true);

                $.ajax({
                    type: 'POST',
                    data: {
                        'action': 'wc_update_job_status',
                        'recordID': recordID,
                        'orderStatus': statusValue,
                        'wcrb_nonce_adrepairbuddy_field': securityNonce
                    },
                    url: self.ajaxUrl,
                    dataType: 'json',

                    beforeSend: function() {
                        $( "#jobsTable_list .aj_msg" ).html( '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>' );
                    },
                    success: function(response) {
                        
                        // Remove global spinner
                        $( "#jobsTable_list .aj_msg" ).html('');

                        if (response.success) {
                            // Update button with new status
                            var newStatusText = $clickedItem.find('span').text();
                            var newStatusIcon = $clickedItem.find('.d-flex i').first().clone();
                            
                            $button.html('');
                            $button.append(newStatusIcon);
                            $button.append('<span class="ms-2">' + newStatusText + '</span>');
                            
                            // Update button color based on status
                            self.updateButtonStatusClass($button, statusValue);
                            
                            // Update active state in dropdown
                            $dropdown.find('.dropdown-item').removeClass('active').find('.bi-check2').remove();
                            $clickedItem.addClass('active').append('<i class="bi bi-check2 text-primary ms-2"></i>');
                            
                            // Show success message
                            self.showMessage('success', response.data, '#jobsTable_list .aj_msg');
                            
                        } else {
                            // Restore original button content on error
                            $button.html(originalContent);
                            $button.prop('disabled', false);
                            
                            // Show error message
                            self.showMessage('error', response.data, '#jobsTable_list .aj_msg');
                        }
                        
                        // Re-enable button
                        $button.prop('disabled', false);
                    }
                });
            });
        }

        /**
         * Update button status class
         */
        updateButtonStatusClass($button, statusValue) {
            // Remove existing status classes
            $button.removeClass('btn-primary btn-warning btn-danger btn-info btn-success btn-secondary');
            
            // Add new status class based on status value
            const statusClasses = {
                'new': 'btn-primary',
                'quote': 'btn-warning',
                'cancelled': 'btn-danger',
                'inprocess': 'btn-info',
                'inservice': 'btn-primary',
                'ready_complete': 'btn-success',
                'delivered': 'btn-success'
            };
            
            const newClass = statusClasses[statusValue] || 'btn-secondary';
            $button.addClass(newClass);
        }

        handlePaymentSubmission() {
            const self = this;

            $('form[name="wcrb_jl_form_submit_payment"]').on("submit", function(e) {
                e.preventDefault();

                var $wcrb_payment_note			 = $('[name="wcrb_payment_note"]').val();
                var $wcrb_payment_datetime 		 = $('[name="wcrb_payment_datetime"]').val();
                var $wcRB_payment_status 		 = $('[name="wcRB_payment_status"]').val();
                var $wcRB_payment_method 		 = $('[name="wcRB_payment_method"]').val();
                var $wcRb_payment_amount 		 = $('[name="wcRb_payment_amount"]').val();
                var $wcrb_transaction_id 		 = $('[name="wcrb_transaction_id"]').val();
                var $wcrb_job_id				 = $('[name="wcrb_job_id"]').val();
                var $wcRB_after_jobstatus 		 = $('[name="wcRB_after_jobstatus"]').val();
                var $wcrb_nonce_add_payment_field = $('[name="wcrb_nonce_add_payment_field"]').val();

                $.ajax({
                    type: 'POST',
                    data: {
                        'action': 'wc_rb_add_payment_into_job',
                        'wcrb_payment_note': $wcrb_payment_note,
                        'wcrb_payment_datetime': $wcrb_payment_datetime,
                        'wcRB_payment_status': $wcRB_payment_status,
                        'wcRB_payment_method': $wcRB_payment_method,
                        'wcRb_payment_amount': $wcRb_payment_amount,
                        'wcrb_transaction_id': $wcrb_transaction_id,
                        'wcRB_after_jobstatus': $wcRB_after_jobstatus,
                        'wcrb_job_id': $wcrb_job_id,
                        'wcrb_nonce_add_payment_field': $wcrb_nonce_add_payment_field
                    },
                    url: self.ajaxUrl,
                    dataType: 'json',

                    beforeSend: function() {
                        $(".set_addpayment_joblist_message").html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                    },
                    success: function(response) {
                        var message = response.message;
                        var success = response.success;
                        
                        //$('.set_addpayment_joblist_message').html('<div class="callout success" data-closable="slide-out-right">'+message+'<button class="close-button" aria-label="Dismiss alert" type="button" data-close><span aria-hidden="true">&times;</span></button></div>');

                        self.showMessage('success', message, '.set_addpayment_joblist_message');

                        if (success == 'YES') {
                            $('form[name="wcrb_jl_form_submit_payment"]').trigger('reset');
                            $('.wcrb_amount_paying').html('0.00');
                            $(".job_id_"+$wcrb_job_id).load(document.location + " .job_id_"+$wcrb_job_id+">*");
                        }
                    }
                });
            });

        }
        
        handleTakePayment() {
            const self = this;
   
            // Use Bootstrap modal show event instead of click event
            $('#openTakePaymentModal').on('show.bs.modal', function (event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var recordID = button.attr('recordid');
                var nonce = button.attr('data-security');
                
                $.ajax({
                    type: 'POST',
                    data: {
                        'action': 'wc_add_joblist_payment_form_output',
                        'recordID': recordID 
                    },
                    url: self.ajaxUrl,
                    dataType: 'json',

                    beforeSend: function() {
                        $('#openTakePaymentModal #replacementpart_joblist_formfields').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                    },
                    success: function(response) {
                        var message 	= response.message;
                        var success 	= response.success;
                        
                        $('#openTakePaymentModal #replacementpart_joblist_formfields').html(message);
                        $('#wcRb_payment_amount').focus();
                        //$('#addjoblistpaymentreveal').foundation('toggle');
                    }
                });
            });
        }

        handleDuplicateJob() {
            const self = this;

            $('#wcrbduplicatejobfront').on('show.bs.modal', function(event) {
                var button = $(event.relatedTarget); // Button that triggered the modal
                var recordID = button.attr('recordid');
                var nonce = button.attr('data-security');

                $("#wcrbduplicatejobfront #replacementpart_joblist_formfields").html('');

                $.ajax({
                    type: 'POST',
                    data: {
                        'action': 'wcrb_return_duplicate_job_fields',
                        'recordID': recordID,
                        'nonce':nonce,
                        'redirect':'current_page'
                    },
                    url: self.ajaxUrl,
                    dataType: 'json',

                    beforeSend: function() {
                        $('#wcrbduplicatejobfront #replacementpart_dp_page_formfields').html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                    },
                    success: function(response) {
                        var message 	= response.message;
                        var success 	= response.success;
                        
                        $('#wcrbduplicatejobfront #replacementpart_dp_page_formfields').html(message);
                    }
                });
            });
        }

        performDuplicateJob() {
            const self = this;

            $(document).on('submit', 'form[name="wcrb_duplicate_page_return"]', function(e) {
                e.preventDefault();

                var $form 	= $(this);
                var formData = $form.serialize();
                var $perform_act = "wcrb_duplicate_page_perform";
                var $success_class = ".duplicate_page_return_message";

                $.ajax({
                    type: 'POST',
                    data: formData + '&action='+$perform_act,
                    url: self.ajaxUrl,
                    async: true,
                    mimeTypes:"multipart/form-data",
                    dataType: 'json',
                    beforeSend: function() {
                        $($success_class).html('<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>');
                    },
                    success: function(response) {
                        var message = response.message;
                        var redirect_url = response.redirect_url;
                        
                        self.showMessage('success', message, $success_class);

                        if (redirect_url != 'NO') {
                            window.location.href = redirect_url;
                        }
                    }
                });
            });
        }
        handleFileUploading() {
            const self = this;

            $(document).on('change', 'input[name="reciepetAttachment"]', function(e) {
                e.preventDefault();

                var fd = new FormData();
                var file = $(document).find('input[type="file"]');
                var security = $(this).attr('data-security');

                $('.attachmentserror').html('');

                var individual_file = file[0].files[0];
                fd.append("file", individual_file);
                fd.append('action', 'wc_upload_file_ajax');
                fd.append('data_security', security);

                $.ajax({
                    type: 'POST',
                    url: self.ajaxUrl,
                    data: fd,
                    contentType: false,
                    processData: false,
                    dataType: 'json',
                    success: function(response) {
                        var message = response.message;
                        var error   = response.error;

                        $('#jobAttachments').append(message);
                        $("#jobAttachments").removeClass('displayNone');
                        $('.attachmentserror').html(error);
                    }
                });
            });
        }
        handleMessageSubmission() {
            const self = this;

            $(document).on("submit", ".orderstatusholder form#wcrb_post_customer_msg", function(e) {
                e.preventDefault();
                var $form 	= $(this);
                var formData = $form.serialize();
                var $perform_act = "wcrb_post_customer_message_status";

                $.ajax({
                    type: 'POST',
                    data: formData + '&action='+$perform_act,
                    url: self.ajaxUrl,
                    async: true,
                    mimeTypes:"multipart/form-data",
                    dataType: 'json',
                    beforeSend: function() {
                        $('.client_msg_post_reply').html("<div class='loader'></div>");
                    },
                    success: function(response) {
                        var message = response.message;
                        var redirect_url = response.redirect_url;

                        $('.client_msg_post_reply').html('<div class="callout success" data-closable="slide-out-right">'+message+'</div>');				
                        
                        if ( $('#wc_job_status_nonce').length ) {
                            if(typeof(redirect_url) != "undefined" && redirect_url !== null) {
                                window.location = redirect_url;
                            }
                        } else {
                            location.reload();
                        }
                    }
                });
            });
        }
        handleDevicesUpdates() {
            const self = this;

            $(document).on("change", 'select.wcrbupdatedatalist', function(e) {
                e.preventDefault();

                var $brandId =  $('select[name="manufacture"]').find(":selected").val();
                var $typeId  =  $('select[name="devicetype"]').find(":selected").val();

                $.ajax({
                    type: 'POST',
                    data: {
                        'action': 'wc_return_devices_datalist',
                        'theBrandId': $brandId, 
                        'theTypeId': $typeId
                    },
                    url: self.ajaxUrl,
                    dataType: 'json',

                    beforeSend: function() {
                        $('.adddevicecustomermessage').html('<div class="spinner-border spinner-border-sm me-2" role="status"></div> Updating...');
                    },
                    success: function(response) {
                        var message 		= response.message;
                        $('.adddevicecustomermessage').html('');
                        $('datalist#device_name_list').html(message);
                    }
                });
            });
        }

        handlePaymentCalculation() {
            $(document).on("input", "#wcRb_payment_amount", () => {
                var grand_total = $(".wcrb_amount_payable_value").val();
                this.wc_update_payment_mode(grand_total);
            });
        }

        wc_update_payment_mode(grand_total) {
            // Convert to numbers and handle empty/undefined values
            grand_total = parseFloat(grand_total) || 0;
            
            $(".wcrb_amount_payable_value").val(grand_total);
            $(".wcrb_amount_payable").html(wc_rb_format_currency(grand_total, "NO"));
            
            var $wcRb_payment_amount = parseFloat($('#wcRb_payment_amount').val()) || 0;
            $(".wcrb_amount_paying").html(wc_rb_format_currency($wcRb_payment_amount, "NO"));
            
            var $theBalance = grand_total - $wcRb_payment_amount;
            $(".wcrb_amount_balance").html(wc_rb_format_currency($theBalance, "YES"));
        }

        /**
         * Show message helper function
         */
        showMessage(type, message, containerSelector) {
            // Remove existing messages
            const $container = $(containerSelector);
            if (!$container.length) return;

            $container.find('.alert').remove();

            // Create new alert
            const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
            const icon = type === 'success' ? 'bi-check-circle' : 'bi-exclamation-triangle';
            
            const alertHtml = `
                <div class="alert ${alertClass} alert-dismissible fade show">
                    <i class="bi ${icon} me-2"></i>${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;

            $container.html(alertHtml);

            // Auto remove success messages after 5 seconds
            if (type === 'success') {
                setTimeout(() => {
                    $container.find('.alert').alert('close');
                }, 5000);
            }
        }
    }

    // Initialize when document is ready
    $(document).ready(function() {
        new WCRBAjaxHandler();
    });

    $("#btnPrint").on("click", function() {
		window.print();
	});

    // Add spinner CSS
    $('<style>')
        .text(`
            .spinner {
                animation: spin 1s linear infinite;
            }
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
        `)
        .appendTo('head');
    
    function wc_rb_format_currency(number, currency_display) {
        try {
            // Safely parse the number
            const parsedNumber = parseFloat(number);
            if (isNaN(parsedNumber)) {
                console.warn('Invalid number provided to wc_rb_format_currency:', number);
                return '0.00';
            }

            // Get formatting settings once
            const currencySettings = getCurrencySettings();
            
            // Format the number
            const formattedNumber = formatNumber(
                Math.abs(parsedNumber), 
                currencySettings.decPlaces, 
                currencySettings.thouSep, 
                currencySettings.decSep
            );
            
            // Add sign for negative numbers
            const signedNumber = parsedNumber < 0 ? `-${formattedNumber}` : formattedNumber;
            
            // Add currency symbol if requested
            return currency_display === 'YES' 
                ? addCurrencySymbol(signedNumber, currencySettings)
                : signedNumber;
                
        } catch (error) {
            return '0.00';
        }
    }

    // Helper function to get all currency settings
    function getCurrencySettings() {
        return {
            selectedCurrency: $('#wc_cr_selected_currency').val() || '',
            currencyPosition: $('#wc_cr_currency_position').val() || 'right',
            thouSep: $('#wc_cr_thousand_separator').val() || ',',
            decSep: $('#wc_cr_decimal_separator').val() || '.',
            decPlaces: Math.abs(parseInt($('#wc_cr_number_of_decimals').val()) || 2)
        };
    }

    // Helper function to format the number
    function formatNumber(number, decPlaces, thouSep, decSep) {
        // Fixed the number to specified decimal places
        const fixedNumber = number.toFixed(decPlaces);
        
        // Split into integer and decimal parts
        const [integerPart, decimalPart] = fixedNumber.split('.');
        
        // Format integer part with thousands separators
        const formattedInteger = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, thouSep);
        
        // Combine with decimal part if needed
        return decPlaces > 0 
            ? `${formattedInteger}${decSep}${decimalPart}`
            : formattedInteger;
    }

    // Helper function to add currency symbol
    function addCurrencySymbol(formattedNumber, settings) {
        if (!settings.selectedCurrency) {
            return formattedNumber;
        }
        
        const { selectedCurrency, currencyPosition } = settings;
        
        switch (currencyPosition) {
            case 'right_space':
                return `${formattedNumber} ${selectedCurrency}`;
            case 'left_space':
                return `${selectedCurrency} ${formattedNumber}`;
            case 'left':
                return `${selectedCurrency}${formattedNumber}`;
            case 'right':
                return `${formattedNumber}${selectedCurrency}`;
            default:
                return `${formattedNumber}${selectedCurrency}`;
        }
    }
})(jQuery);