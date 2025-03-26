<?php
/**
 * Admin View: Settings
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap">
    <h1><?php esc_html_e('Report Content Settings', 'report-content'); ?></h1>
    
    <form method="post" action="options.php" class="report-content-settings-form">
        <?php settings_fields('report_content_settings'); ?>
        <?php do_settings_sections('report_content_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Disable on Pages', 'report-content'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php esc_html_e('Disable on Pages', 'report-content'); ?></span>
                        </legend>
                        <label for="report_content_disable_on_pages">
                            <input name="report_content_disable_on_pages" type="checkbox" id="report_content_disable_on_pages" value="1" <?php checked('1', get_option('report_content_disable_on_pages')); ?>>
                            <?php esc_html_e('Disable reporting functionality on Pages', 'report-content'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('If checked, users will not be able to report Pages, only Posts and other content types.', 'report-content'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Auto-hide reported posts', 'report-content'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php esc_html_e('Auto-hide reported posts', 'report-content'); ?></span>
                        </legend>
                        <label for="report_content_auto_hide">
                            <input name="report_content_auto_hide" type="checkbox" id="report_content_auto_hide" value="1" <?php checked('1', get_option('report_content_auto_hide')); ?>>
                            <?php esc_html_e('Automatically hide posts when they reach the minimum number of reports', 'report-content'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Minimum reports to hide', 'report-content'); ?></th>
                <td>
                    <input name="report_content_min_reports" type="number" id="report_content_min_reports" value="<?php echo esc_attr(get_option('report_content_min_reports', '3')); ?>" class="small-text" min="1">
                    <p class="description"><?php esc_html_e('Number of reports required before automatically hiding a post', 'report-content'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Hide method', 'report-content'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php esc_html_e('Hide method', 'report-content'); ?></span>
                        </legend>
                        <label for="report_content_make_private">
                            <input name="report_content_make_private" type="checkbox" id="report_content_make_private" value="1" <?php checked('1', get_option('report_content_make_private')); ?>>
                            <?php esc_html_e('Change post status to private when hiding (only logged-in users can view)', 'report-content'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('If unchecked, posts will remain published but will be filtered from queries', 'report-content'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Email notifications', 'report-content'); ?></th>
                <td>
                    <fieldset>
                        <legend class="screen-reader-text">
                            <span><?php esc_html_e('Email notifications', 'report-content'); ?></span>
                        </legend>
                        <label for="report_content_email_notification">
                            <input name="report_content_email_notification" type="checkbox" id="report_content_email_notification" value="1" <?php checked('1', get_option('report_content_email_notification')); ?>>
                            <?php esc_html_e('Send email notification when content is reported', 'report-content'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Notification email', 'report-content'); ?></th>
                <td>
                    <input name="report_content_notification_email" type="email" id="report_content_notification_email" value="<?php echo esc_attr(get_option('report_content_notification_email', get_option('admin_email'))); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('Email address to receive notifications', 'report-content'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Report reasons', 'report-content'); ?>
                    <p class="description"><?php esc_html_e('One reason per line in format: key|Label Text', 'report-content'); ?></p>
                </th>
                <td class="report-content-reasons-field">
                    <textarea name="report_content_reasons_raw" id="report_content_reasons_raw" rows="10" class="large-text code"><?php
                    $reasons = get_option('report_content_reasons');
                    if (is_array($reasons)) {
                        foreach ($reasons as $key => $label) {
                            echo esc_html($key . '|' . $label) . "\n";
                        }
                    }
                    ?></textarea>
                    <p class="description"><?php esc_html_e('These reasons will appear as options when users report content', 'report-content'); ?></p>
                    
                    <input type="hidden" name="report_content_reasons" id="report_content_reasons" value='<?php echo esc_attr(json_encode(get_option('report_content_reasons', array()))); ?>'>
                    
                    <script>
                        // Process the reasons field before form submission
                        document.addEventListener('DOMContentLoaded', function() {
                            var form = document.querySelector('.report-content-settings-form');
                            var rawInput = document.getElementById('report_content_reasons_raw');
                            var hiddenInput = document.getElementById('report_content_reasons');
                            
                            function processReasons() {
                                var lines = rawInput.value.split('\n');
                                var reasons = {};
                                
                                lines.forEach(function(line) {
                                    line = line.trim();
                                    if (line !== '') {
                                        var parts = line.split('|');
                                        if (parts.length === 2) {
                                            var key = parts[0].trim();
                                            var label = parts[1].trim();
                                            if (key && label) {
                                                reasons[key] = label;
                                            }
                                        }
                                    }
                                });
                                
                                if (Object.keys(reasons).length === 0) {
                                    // Keep at least one default reason if all are removed
                                    reasons = {
                                        'inappropriate': 'Inappropriate Content'
                                    };
                                }
                                
                                hiddenInput.value = JSON.stringify(reasons);
                            }
                            
                            // Process on any change to the textarea
                            rawInput.addEventListener('input', processReasons);
                            
                            // Process before form submission
                            form.addEventListener('submit', function(e) {
                                processReasons();
                            });
                            
                            // Initialize on page load
                            processReasons();
                        });
                    </script>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>