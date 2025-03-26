<?php
/**
 * Handles plugin settings management
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for managing plugin settings.
 * Follows Open/Closed Principle by allowing extension with new settings.
 */
class ReportContentSettings {

    /**
     * Get all plugin settings.
     *
     * @return array Array of plugin settings.
     */
    public function getSettings() {
        return array(
            'autoHide' => get_option('report_content_auto_hide', '0'),
            'minReports' => get_option('report_content_min_reports', '3'),
            'makePrivate' => get_option('report_content_make_private', '0'),
            'disableOnPages' => get_option('report_content_disable_on_pages', '0'),
            'reasons' => get_option('report_content_reasons', array(
                'inappropriate' => 'Inappropriate Content',
                'spam' => 'Spam',
                'offensive' => 'Offensive Language',
                'copyright' => 'Copyright Violation',
                'other' => 'Other'
            )),
            'emailNotification' => get_option('report_content_email_notification', '0'),
            'notificationEmail' => get_option('report_content_notification_email', get_option('admin_email')),
        );
    }

    /**
     * Get a specific setting value.
     *
     * @param string $key     The setting key.
     * @param mixed  $default Default value if setting doesn't exist.
     * @return mixed The setting value.
     */
    public function getSetting($key, $default = null) {
        $optionName = 'report_content_' . $key;
        return get_option($optionName, $default);
    }

    /**
     * Update a specific setting value.
     *
     * @param string $key   The setting key.
     * @param mixed  $value The setting value.
     * @return bool True on success, false on failure.
     */
    public function updateSetting($key, $value) {
        $optionName = 'report_content_' . $key;
        return update_option($optionName, $value);
    }

    /**
     * Register all settings with WordPress Settings API.
     */
    public function registerSettings() {
        // Auto-hide setting
        register_setting(
            'report_content_settings',
            'report_content_auto_hide',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitizeCheckbox'),
                'default' => '0',
            )
        );

        // Minimum reports setting
        register_setting(
            'report_content_settings',
            'report_content_min_reports',
            array(
                'type' => 'number',
                'sanitize_callback' => 'absint',
                'default' => 3,
            )
        );

        // Make private setting
        register_setting(
            'report_content_settings',
            'report_content_make_private',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitizeCheckbox'),
                'default' => '0',
            )
        );

        // Disable on pages setting
        register_setting(
            'report_content_settings',
            'report_content_disable_on_pages',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitizeCheckbox'),
                'default' => '0',
            )
        );

        // Email notification setting
        register_setting(
            'report_content_settings',
            'report_content_email_notification',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitizeCheckbox'),
                'default' => '0',
            )
        );

        // Notification email setting
        register_setting(
            'report_content_settings',
            'report_content_notification_email',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_email',
                'default' => get_option('admin_email'),
            )
        );

        // Report reasons setting
        register_setting(
            'report_content_settings',
            'report_content_reasons',
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitizeReasons'),
                'default' => array(
                    'inappropriate' => 'Inappropriate Content',
                    'spam' => 'Spam',
                    'offensive' => 'Offensive Language',
                    'copyright' => 'Copyright Violation',
                    'other' => 'Other'
                ),
            )
        );
    }

    /**
     * Sanitize checkbox values.
     *
     * @param string $value The value to sanitize.
     * @return string '1' if checked, '0' if not.
     */
    public function sanitizeCheckbox($value) {
        return ($value === '1') ? '1' : '0';
    }

    /**
     * Sanitize report reasons array.
     *
     * @param array $reasons The array of report reasons.
     * @return array Sanitized array of report reasons.
     */
    public function sanitizeReasons($value) {
        if (empty($value)) {
            return array(
                'inappropriate' => 'Inappropriate Content'
            );
        }

        // Try to decode JSON
        $reasons = json_decode($value, true);
        if (!is_array($reasons)) {
            return array(
                'inappropriate' => 'Inappropriate Content'
            );
        }

        $sanitized = array();
        foreach ($reasons as $key => $label) {
            if (!empty($key) && !empty($label)) {
                $sanitized[sanitize_key($key)] = sanitize_text_field($label);
            }
        }

        // Ensure we always have at least one reason
        if (empty($sanitized)) {
            $sanitized = array(
                'inappropriate' => 'Inappropriate Content'
            );
        }

        return $sanitized;
    }

    /**
     * Get available report reasons.
     *
     * @return array Array of report reasons.
     */
    public function getReportReasons() {
        return get_option('report_content_reasons', array(
            'inappropriate' => 'Inappropriate Content',
            'spam' => 'Spam',
            'offensive' => 'Offensive Language',
            'copyright' => 'Copyright Violation',
            'other' => 'Other'
        ));
    }

    /**
     * Send email notification for new reports.
     *
     * @param int    $reportId     The report ID.
     * @param int    $postId       The post ID.
     * @param string $reportReason The reason for reporting.
     */
    public function maybeSendNotification($reportId, $postId, $reportReason) {
        // Check if notifications are enabled
        $notificationsEnabled = $this->getSetting('email_notification', '0');
        if ($notificationsEnabled !== '1') {
            return;
        }
        
        $to = $this->getSetting('notification_email', get_option('admin_email'));
        $subject = sprintf(__('[%s] New Content Report #%d', 'report-content'), get_bloginfo('name'), $reportId);
        
        $post = get_post($postId);
        $postTitle = $post ? $post->post_title : __('(Post not found)', 'report-content');
        $adminUrl = admin_url('admin.php?page=report-content-reports&report=' . $reportId);
        
        $message = sprintf(
            __('A post has been reported on your website.

Post: %s
Report ID: %d
Reason: %s

To view and manage this report, please visit:
%s', 'report-content'),
            $postTitle,
            $reportId,
            $reportReason,
            $adminUrl
        );
        
        wp_mail($to, $subject, $message);
    }
}