/**
 * Report Content - Admin JavaScript
 */
(function($) {
    'use strict';

    // When document is ready
    $(document).ready(function() {

        // Add confirmation for action buttons
        $('.action-resolve').on('click', function() {
            return confirm(reportContentAdmin.i18n.confirmResolve);
        });

        $('.action-dismiss').on('click', function() {
            return confirm(reportContentAdmin.i18n.confirmDismiss);
        });

        $('.action-hide').on('click', function() {
            return confirm(reportContentAdmin.i18n.confirmHide);
        });

        $('.action-unhide').on('click', function() {
            return confirm(reportContentAdmin.i18n.confirmUnhide);
        });

        // Only show the minimum reports field when auto-hide is enabled
        const $autoHideToggle = $('#report_content_auto_hide');
        const $minReportsRow = $('#report_content_min_reports').closest('tr');

        function toggleMinReportsVisibility() {
            if ($autoHideToggle.is(':checked')) {
                $minReportsRow.show();
            } else {
                $minReportsRow.hide();
            }
        }

        // Run on page load
        toggleMinReportsVisibility();

        // Run when toggle changes
        $autoHideToggle.on('change', toggleMinReportsVisibility);

        // Only show notification email field when email notifications are enabled
        const $emailNotificationToggle = $('#report_content_email_notification');
        const $notificationEmailRow = $('#report_content_notification_email').closest('tr');

        function toggleNotificationEmailVisibility() {
            if ($emailNotificationToggle.is(':checked')) {
                $notificationEmailRow.show();
            } else {
                $notificationEmailRow.hide();
            }
        }

        // Run on page load
        toggleNotificationEmailVisibility();

        // Run when toggle changes
        $emailNotificationToggle.on('change', toggleNotificationEmailVisibility);
    });

})(jQuery);