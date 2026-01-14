/**
 * Profile Edit Page JavaScript
 * Handles profile and password form submissions
 */

jQuery(document).ready(function($) {
    // Profile form AJAX submission
    $('#profileForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.html('<i class="bi bi-arrow-repeat spinner"></i> Updating...');
        $submitBtn.prop('disabled', true);
        
        // Clear previous messages - FIXED SELECTORS
        $('.alert-danger, .alert-success').addClass('d-none').text('');
        
        const formData = new FormData(this);
        formData.append('action', 'wcrb_update_profile');
        
        $.ajax({
            url: wcrb_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                // Show success message
                if ($('#formSuccess').length) {
                    $('#formSuccess').removeClass('d-none').text(response.data.message || response.message);
                    $('#formErrors').addClass('d-none');
                } else {
                    // Create success alert if it doesn't exist
                    $form.prepend('<div class="alert alert-success alert-dismissible fade show" id="formSuccess" role="alert">' + 
                        (response.data.message || response.message) + 
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>');
                }
            } else {
                // Show error message
                if ($('#formErrors').length) {
                    $('#formErrors').removeClass('d-none').text(response.data.message || response.message);
                    $('#formSuccess').addClass('d-none');
                } else {
                    // Create error alert if it doesn't exist
                    $form.prepend('<div class="alert alert-danger alert-dismissible fade show" id="formErrors" role="alert">' + 
                        (response.data.message || response.message) + 
                        '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                        '</div>');
                }
            }
        })
        .fail(function(xhr, status, error) {
            // Show error message
            if ($('#formErrors').length) {
                $('#formErrors').removeClass('d-none').text('An error occurred. Please try again.');
                $('#formSuccess').addClass('d-none');
            } else {
                $form.prepend('<div class="alert alert-danger alert-dismissible fade show" id="formErrors" role="alert">' + 
                    'An error occurred. Please try again.' + 
                    '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                    '</div>');
            }
        })
        .always(function() {
            $submitBtn.html(originalText);
            $submitBtn.prop('disabled', false);
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                $('#formSuccess, #formErrors').fadeOut('slow', function() {
                    $(this).addClass('d-none');
                });
            }, 5000);
        });
    });
    
    // Password form AJAX submission
    $('#passwordForm').on('submit', function(e) {
        e.preventDefault();
        
        const $form = $(this);
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.html();
        
        // Show loading state
        $submitBtn.html('<i class="bi bi-arrow-repeat spinner"></i> Updating...');
        $submitBtn.prop('disabled', true);
        
        const formData = new FormData(this);
        formData.append('action', 'wcrb_update_password');
        
        $.ajax({
            url: wcrb_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json'
        })
        .done(function(response) {
            if (response.success) {
                // Show success message
                const $successAlert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">' +
                    '<i class="bi bi-check-circle me-2"></i>' + response.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('#passwordForm').prepend($successAlert);
                $form[0].reset(); // Clear the form
                
                // Auto-remove success message after 5 seconds
                setTimeout(() => {
                    $successAlert.alert('close');
                }, 5000);
            } else {
                // Show error message
                const $errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                    '<i class="bi bi-exclamation-triangle me-2"></i>' + response.message +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('#passwordForm').prepend($errorAlert);
            }
        })
        .fail(function(xhr, status, error) {
            const $errorAlert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">' +
                '<i class="bi bi-exclamation-triangle me-2"></i>An error occurred. Please try again.' +
                '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                '</div>');
            $('#passwordForm').prepend($errorAlert);
        })
        .always(function() {
            $submitBtn.html(originalText);
            $submitBtn.prop('disabled', false);
        });
    });

    // Profile photo upload functionality
    function initProfilePhotoUpload() {
        const $uploadInput = $('#profilePhotoUpload');
        const $progressBar = $('#uploadProgress');
        const $progressFill = $progressBar.find('.progress-bar');
        const $uploadStatus = $('#uploadStatus');
        const $removeButton = $('#removeProfilePhoto');
        const $avatar = $('.profile-picture-wrapper img');

        // File upload handler
        $uploadInput.on('change', function(e) {
            const file = this.files[0];
            if (!file) return;

            // Validate file
            if (!validateImageFile(file)) {
                return;
            }

            // Show progress
            $progressBar.removeClass('d-none');
            $uploadStatus.html('<div class="alert alert-info">Uploading...</div>');

            const formData = new FormData();
            formData.append('profile_photo', file);
            formData.append('action', 'wcrb_update_profile_photo');
            formData.append('wcrb_profile_photo_nonce', $('#profilePhotoForm input[name="wcrb_profile_photo_nonce"]').val());
            formData.append('user_id', $('#profilePhotoForm input[name="user_id"]').val());

            $.ajax({
                url: wcrb_ajax.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                xhr: function() {
                    const xhr = new window.XMLHttpRequest();
                    xhr.upload.addEventListener('progress', function(evt) {
                        if (evt.lengthComputable) {
                            const percentComplete = (evt.loaded / evt.total) * 100;
                            $progressFill.css('width', percentComplete + '%');
                        }
                    }, false);
                    return xhr;
                }
            })
            .done(function(response) {
                if (response.success) {
                    // Show success message
                    $uploadStatus.html('<div class="alert alert-success">' + response.data.message + '</div>');
                    
                    // Update avatar image with the returned URL
                    if (response.data.avatar_url) {
                        $avatar.attr('src', response.data.avatar_url + '?t=' + new Date().getTime());
                    } else {
                        // Fallback: force refresh by adding timestamp
                        const currentSrc = $avatar.attr('src');
                        const newSrc = currentSrc.split('?')[0] + '?t=' + new Date().getTime();
                        $avatar.attr('src', newSrc);
                    }
                    
                    // Show remove button if it exists
                    if ($removeButton.length) {
                        $removeButton.removeClass('d-none');
                    }
                    
                    // Reset form
                    $uploadInput.val('');
                } else {
                    $uploadStatus.html('<div class="alert alert-danger">' + response.data + '</div>');
                }
            })
            .fail(function(xhr, status, error) {
                $uploadStatus.html('<div class="alert alert-danger">Upload failed. Please try again.</div>');
            })
            .always(function() {
                setTimeout(() => {
                    $progressBar.addClass('d-none');
                    $progressFill.css('width', '0%');
                }, 2000);
            });
        });

        // File validation function
        function validateImageFile(file) {
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            const maxSize = 2 * 1024 * 1024; // 2MB

            if (!validTypes.includes(file.type)) {
                $uploadStatus.html('<div class="alert alert-danger">Please select a valid image file (JPG, PNG, GIF).</div>');
                return false;
            }

            if (file.size > maxSize) {
                $uploadStatus.html('<div class="alert alert-danger">File size must be less than 2MB.</div>');
                return false;
            }

            return true;
        }
    }

    // Initialize profile photo upload
    initProfilePhotoUpload();
});