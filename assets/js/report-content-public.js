/**
 * Report Content - Public JavaScript
 */
(function($) {
    'use strict';

    // When document is ready
    $(document).ready(function() {
        // Toggle report form when button is clicked
        $('.report-content-button').on('click', function(e) {
            e.preventDefault();
            $(this).siblings('.report-content-form').slideToggle(200);
        });

        // Handle cancel button
        $('.report-content-cancel').on('click', function(e) {
            e.preventDefault();
            $(this).closest('.report-content-form').slideUp(200);
        });

        // Show/hide "other reason" textarea
        $('input[name="report_reason"]').on('change', function() {
            const $textarea = $(this).closest('.report-content-reason').find('textarea');
            
            if ($(this).val() === 'other') {
                $textarea.slideDown(200);
            } else {
                $('textarea[name="report_reason_other"]').slideUp(200);
            }
        });

        // Handle form submission
        $('.report-content-form-fields').on('submit', function(e) {
            e.preventDefault();
            
            const $form = $(this);
            const $message = $form.find('.report-content-message');
            
            // Get form data
            const postId = $form.find('input[name="post_id"]').val();
            const reasonValue = $form.find('input[name="report_reason"]:checked').val();
            const reasonText = $form.find('textarea[name="report_reason_other"]').val();
            
            // Validate form
            if (!reasonValue) {
                $message
                    .removeClass('success')
                    .addClass('error')
                    .text(reportContent.i18n.error)
                    .show();
                return;
            }

            // If user selected 'other' but didn't provide a reason
            if (reasonValue === 'other' && !reasonText) {
                $message
                    .removeClass('success')
                    .addClass('error')
                    .text(reportContent.i18n.error)
                    .show();
                return;
            }

            // Confirm before submitting
            if (!confirm(reportContent.i18n.confirm)) {
                return;
            }

            // Disable form elements during submission
            $form.find('button, input, textarea').prop('disabled', true);
            
            // Send AJAX request
            $.ajax({
                url: reportContent.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'report_content',
                    nonce: reportContent.nonce,
                    post_id: postId,
                    reason: reasonValue,
                    reason_text: reasonText
                },
                success: function(response) {
                    if (response.success) {
                        $message
                            .removeClass('error')
                            .addClass('success')
                            .text(response.data.message)
                            .show();
                        
                        // Hide the form inputs but keep the message visible
                        $form.find('.report-content-reasons, .report-content-actions').hide();
                    } else {
                        $message
                            .removeClass('success')
                            .addClass('error')
                            .text(response.data.message)
                            .show();
                        
                        // Re-enable form elements
                        $form.find('button, input, textarea').prop('disabled', false);
                    }
                },
                error: function() {
                    $message
                        .removeClass('success')
                        .addClass('error')
                        .text(reportContent.i18n.error)
                        .show();
                    
                    // Re-enable form elements
                    $form.find('button, input, textarea').prop('disabled', false);
                }
            });
        });
    });

})(jQuery);