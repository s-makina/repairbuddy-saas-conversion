jQuery(document).ready(function($) {
    // Initialize variables - use either ajax_obj or categories_data consistently
    const ajaxData = typeof categories_data !== 'undefined' ? categories_data : 
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
        
        if ($('#ajax-notifications').length === 0) {
            $('.dashboard-content').prepend('<div id="ajax-notifications" class="mb-3"></div>');
        }
        
        $('#ajax-notifications').html(html);
        
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
    
    // Toggle tax rate field function
    function toggleTaxRateField(isTaxable, formPrefix = '') {
        const $taxRateField = $(`#${formPrefix}tax_rate_field`);
        if ($taxRateField.length) {
            if (isTaxable) {
                $taxRateField.slideDown();
            } else {
                $taxRateField.slideUp();
                $(`#${formPrefix}tax_rate`).val(0);
            }
        }
    }
    
    // Toggle tax rate field for add modal
    $('#taxable').on('change', function() {
        toggleTaxRateField($(this).is(':checked'));
    });
    
    // Toggle tax rate field for edit modal
    $('#edit_taxable').on('change', function() {
        toggleTaxRateField($(this).is(':checked'), 'edit_');
    });
    
    // Add Category Form Submission
    $('#submitCategoryForm').on('click', function(e) {
        e.preventDefault();
        
        const $form = $('#addCategoryForm');
        const $button = $(this);
        const originalText = $button.html();
        
        // Validation
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Adding...');
        
        const formData = $form.serialize();
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Category added successfully!');
                    $form[0].reset();
                    $('#addCategoryModal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Failed to add category!', 'error');
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
    
    // Edit Category Modal Handler
    $(document).on('click', '[data-bs-target="#editCategoryModal"]', function(e) {
        e.preventDefault();
        
        const categoryId = $(this).data('category-id');
        const $button = $(this);
        const originalText = $button.html();
        
        // Show loading state
        $button.html('<span class="spinner-border spinner-border-sm"></span>');
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'wcrb_get_category',
                category_id: categoryId,
                nonce: ajaxData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    const category = response.data;
                    
                    // Populate form
                    $('#edit_category_id').val(category.category_id);
                    $('#edit_category_name').val(category.category_name);
                    $('#edit_category_description').val(category.category_description);
                    $('#edit_color_code').val(category.color_code);
                    $('#edit_sort_order').val(category.sort_order);
                    $('#edit_tax_rate').val(category.tax_rate);
                    $('#edit_taxable').prop('checked', category.taxable == 1);
                    $('#edit_is_active').prop('checked', category.is_active == 1);
                    
                    // Show/hide tax rate based on taxable
                    toggleTaxRateField(category.taxable == 1, 'edit_');
                    
                    $('#editCategoryModal').modal('show');
                } else {
                    showNotification(response.data.message || 'Failed to load category details!', 'error');
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

    // Edit Category Form Submission
    $('#submitEditCategoryForm').on('click', function(e) {
        e.preventDefault();
        
        const $form = $('#editCategoryForm');
        const $button = $(this);
        const originalText = $button.html();
        
        // Validation
        if (!$form[0].checkValidity()) {
            $form[0].reportValidity();
            return;
        }
        
        $button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Updating...');
        
        const formData = $form.serialize();
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Category updated successfully!');
                    $form[0].reset();
                    $('#editCategoryModal').modal('hide');
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                } else {
                    showNotification(response.data.message || 'Failed to update category!', 'error');
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
    
    // Delete Category
    $(document).on('click', '.delete-category-btn', function(e) {
        e.preventDefault();
        
        const confirmMessage = ajaxData.i18n ? ajaxData.i18n.confirm_delete : 'Are you sure you want to delete this category?';
        
        if (!confirm(confirmMessage)) {
            return;
        }
        
        const categoryId = $(this).data('category-id');
        const $card = $(this).closest('.category-card');
        
        $.ajax({
            url: ajaxData.ajax_url,
            type: 'POST',
            data: {
                action: 'wcrb_delete_category',
                category_id: categoryId,
                nonce: ajaxData.nonce
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('Category deleted successfully!');
                    $card.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    showNotification(response.data.message || response.data || 'Failed to delete category!', 'error');
                }
            },
            error: function() {
                showNotification('An error occurred. Please try again.', 'error');
            }
        });
    });
    
    // Color picker preview
    $('input[type="color"]').on('input', function() {
        const color = $(this).val();
        $(this).next('.color-preview').css('background-color', color);
    });
    
    // Initialize color previews
    $('input[type="color"]').each(function() {
        const color = $(this).val();
        $(this).after('<div class="color-preview mt-1" style="width: 30px; height: 30px; border-radius: 4px; background-color: ' + color + '"></div>');
    });
    
    // Handle modal hidden event to reset forms
    $('#addCategoryModal, #editCategoryModal').on('hidden.bs.modal', function() {
        $(this).find('form')[0].reset();
        // Reset tax rate field visibility
        if ($(this).attr('id') === 'addCategoryModal') {
            $('#tax_rate_field').show();
        } else {
            $('#edit_tax_rate_field').hide();
        }
    });
});