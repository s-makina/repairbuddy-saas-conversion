jQuery(document).ready(function($) {
    // Initialize variables - use expenses_data from localization
    const ajaxData = typeof expenses_data !== 'undefined' ? expenses_data : 
                    (typeof ajax_obj !== 'undefined' ? ajax_obj : {});
    
    // Function to show notification
    function showNotification(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        const html = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
        
        // Create container if it doesn't exist
        if ($('#ajax-notifications').length === 0) {
            $('.dashboard-content').prepend('<div id="ajax-notifications" class="mb-3"></div>');
        }
        
        $('#ajax-notifications').html(html);
        
        // Auto-remove after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
    
    // Format currency
    function formatCurrency(amount) {
        const symbol = ajaxData.currency_symbol || '$';
        return symbol + parseFloat(amount).toFixed(2);
    }
    
    // Add Expense Form Submission
    $('#submitExpenseForm').on('click', function(e) {
        e.preventDefault();
        
        const $form = $('#addExpenseForm');
        const $button = $(this);
        const originalText = $button.html();
        
        // Basic validation
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        // Disable button and show loading
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Adding...');
        
        // Get form data
        const formData = $form.serialize();
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification(response.data.message || 'Expense added successfully!');
                    
                    // Reset form
                    $form[0].reset();
                    
                    // Close modal
                    $('#addExpenseModal').modal('hide');
                    
                    // Reload page after 1 second to show new expense
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Failed to add expense!', 'error');
                }
            },
            error: function(xhr, status, error) {
                showNotification('An error occurred. Please try again.', 'error');
                console.error('AJAX Error:', error);
            },
            complete: function() {
                // Re-enable button
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Edit Expense Modal Handler
    $(document).on('click', '[data-bs-target="#editExpenseModal"]', function(e) {
        e.preventDefault();
        
        const expenseId = $(this).data('expense-id');
        const $button = $(this);
        const originalText = $button.html();
        
        // Show loading
        $button.html('<span class="spinner-border spinner-border-sm"></span>');
        
        // Load expense data via AJAX
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'wcrb_get_expense',
                expense_id: expenseId,
                nonce: ajaxData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const expense = response.data;
                    
                    // Populate form fields
                    $('#editExpenseForm input[name="expense_id"]').val(expense.expense_id);
                    $('#editExpenseForm input[name="expense_date"]').val(expense.expense_date);
                    $('#editExpenseForm select[name="category_id"]').val(expense.category_id);
                    $('#editExpenseForm textarea[name="description"]').val(expense.description);
                    $('#editExpenseForm input[name="amount"]').val(expense.amount);
                    $('#editExpenseForm select[name="payment_method"]').val(expense.payment_method);
                    $('#editExpenseForm select[name="payment_status"]').val(expense.payment_status);
                    $('#editExpenseForm input[name="receipt_number"]').val(expense.receipt_number);
                    $('#editExpenseForm select[name="expense_type"]').val(expense.expense_type);
                    $('#editExpenseForm select[name="status"]').val(expense.status);
                    
                    // Show modal
                    $('#editExpenseModal').modal('show');
                } else {
                    showNotification(response.data.message || 'Failed to load expense!', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $button.html(originalText);
            }
        });
    });
    
    // Submit Edit Expense Form
    $('#submitEditExpenseForm').on('click', function(e) {
        e.preventDefault();
        
        const $form = $('#editExpenseForm');
        const $button = $(this);
        const originalText = $button.html();
        
        // Validation
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        // Disable button and show loading
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: $form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Expense updated successfully!');
                    $('#editExpenseModal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Failed to update expense!', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // Delete Expense
    $(document).on('click', '.delete-expense-btn', function(e) {
        e.preventDefault();
        
        const confirmMsg = ajaxData.i18n ? ajaxData.i18n.confirm_delete : 'Are you sure you want to delete this expense?';
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        const expenseId = $(this).data('expense-id');
        const $button = $(this);
        const originalText = $button.html();
        
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'wcrb_delete_expense',
                expense_id: expenseId,
                nonce: ajaxData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Expense deleted successfully!');
                    // Remove row from table
                    $button.closest('tr').fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data.message || 'Failed to delete expense!', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });
    
    // View Expense Details Modal
    $(document).on('click', '[data-bs-target="#viewExpenseModal"]', function(e) {
        e.preventDefault();
        
        const expenseId = $(this).data('expense-id');
        const $button = $(this);
        const originalText = $button.html();
        
        $button.html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'wcrb_get_expense_details',
                expense_id: expenseId,
                nonce: ajaxData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const expense = response.data;
                    
                    const html = `
                        <div class="expense-details-enhanced compact"> <!-- Add compact class -->
                            <div class="row g-0">
                                <!-- Date and Category -->
                                <div class="col-md-6 mb-2">
                                    <div class="info-card date-card">
                                        <div class="info-label">Date</div>
                                        <div class="info-value">${expense.formatted_date}</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="info-card category-card">
                                        <div class="info-label">Category</div>
                                        <div class="info-value">
                                            <span class="category-badge" style="background-color: ${expense.color_code}">
                                                ${expense.category_name}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Description -->
                                <div class="col-12 mb-2">
                                    <div class="info-card description-card">
                                        <div class="info-label">Description</div>
                                        <div class="info-value description-text">
                                            ${expense.description}
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Financial Details -->
                                <div class="col-md-4 mb-2">
                                    <div class="info-card amount-card">
                                        <div class="info-label">Amount</div>
                                        <div class="info-value">${expense.formatted_amount}</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="info-card tax-card">
                                        <div class="info-label">Tax</div>
                                        <div class="info-value">${expense.formatted_tax}</div>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <div class="info-card total-card">
                                        <div class="info-label">Total</div>
                                        <div class="info-value total-amount">${expense.formatted_total}</div>
                                    </div>
                                </div>
                                
                                <!-- Payment Info -->
                                <div class="col-md-6 mb-2">
                                    <div class="info-card payment-status-card">
                                        <div class="info-label">Payment Status</div>
                                        <div class="info-value">
                                            <span class="status-badge badge-${expense.payment_status_class}">
                                                ${expense.payment_status_label}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="info-card payment-method-card">
                                        <div class="info-label">Payment Method</div>
                                        <div class="info-value">${expense.payment_method_label}</div>
                                    </div>
                                </div>
                                
                                <!-- Receipt and Metadata -->
                                <div class="col-md-6 mb-2">
                                    <div class="info-card receipt-card">
                                        <div class="info-label">Receipt Number</div>
                                        <div class="info-value">${expense.receipt_number || 'N/A'}</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="info-card creator-card">
                                        <div class="info-label">Created By</div>
                                        <div class="info-value">${expense.created_by_name || 'System'}</div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <div class="info-card created-at-card">
                                        <div class="info-label">Created At</div>
                                        <div class="info-value">${expense.formatted_created_at}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    $('#viewExpenseModal .expense-details').html(html);
                    $('#viewExpenseModal').modal('show');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            },
            complete: function() {
                $button.html(originalText);
            }
        });
    });
    
    // Real-time amount calculation
    $('#amount, #category_id').on('change input', function() {
        calculateTaxAndTotal();
    });
    
    function calculateTaxAndTotal() {
        const amount = parseFloat($('#amount').val()) || 0;
        const categoryId = $('#category_id').val();
        
        if (amount > 0 && categoryId) {
            $.ajax({
                url: ajaxData.ajax_url,
                type: 'POST',
                data: {
                    action: 'wcrb_calculate_expense_total',
                    amount: amount,
                    category_id: categoryId,
                    nonce: ajaxData.nonce
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#tax_amount').val(response.data.tax_amount);
                        $('#total_amount').val(response.data.total_amount);
                        
                        // Update display if preview elements exist
                        $('.tax-preview').text('Tax: ' + response.data.formatted_tax);
                        $('.total-preview').text('Total: ' + response.data.formatted_total);
                    }
                },
                error: function() {
                    showNotification('Failed to calculate tax and total', 'error');
                }
            });
        }
    }

    // In your JavaScript file, update the export-csv and export-pdf handlers:
    $(document).on('click', '.export-csv, .export-pdf', function(e) {
        e.preventDefault();
        
        const isCSV = $(this).hasClass('export-csv');
        const action = isCSV ? 'wcrb_export_expenses_csv' : 'wcrb_export_expenses_pdf';
        const $button = $(this);
        const originalText = $button.html();
        
        // Show loading
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
        
        // Get ALL filter values from the form
        const $filterForm = $('form[method="get"]'); // Your filter form
        const formData = {
            search: $filterForm.find('input[name="search"]').val(),
            category_id: $filterForm.find('select[name="category_id"]').val(),
            payment_status: $filterForm.find('select[name="payment_status"]').val(),
            start_date: $filterForm.find('input[name="start_date"]').val(),
            end_date: $filterForm.find('input[name="end_date"]').val(),
            action: action,
            nonce: expenses_data.nonce
        };
        
        $.ajax({
            url: expenses_data.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (isCSV) {
                        // CSV download
                        const blob = new Blob([response.data.content], { 
                            type: 'text/csv;charset=utf-8;' 
                        });
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        
                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        showNotification('CSV exported successfully!');
                    } else {
                        // PDF download
                        const byteCharacters = atob(response.data.content);
                        const byteNumbers = new Array(byteCharacters.length);
                        for (let i = 0; i < byteCharacters.length; i++) {
                            byteNumbers[i] = byteCharacters.charCodeAt(i);
                        }
                        const byteArray = new Uint8Array(byteNumbers);
                        const blob = new Blob([byteArray], {type: 'application/pdf'});
                        
                        const link = document.createElement('a');
                        const url = URL.createObjectURL(blob);
                        
                        link.setAttribute('href', url);
                        link.setAttribute('download', response.data.filename);
                        link.style.visibility = 'hidden';
                        
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        
                        showNotification('PDF exported successfully!');
                    }
                } else {
                    showNotification(response.data.message || 'Export failed!', 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Export error:', error);
                showNotification('An error occurred during export.', 'error');
            },
            complete: function() {
                $button.prop('disabled', false).html(originalText);
            }
        });
    });

});