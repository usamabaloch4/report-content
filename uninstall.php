<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Report_Content
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all related options
delete_option('report_content_auto_hide');
delete_option('report_content_min_reports');
delete_option('report_content_make_private');
delete_option('report_content_email_notification');
delete_option('report_content_notification_email');
delete_option('report_content_reasons');

// Delete the database table
global $wpdb;
$table_name = $wpdb->prefix . 'reported_content';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Delete post meta for hidden posts
delete_post_meta_by_key('_report_content_hidden');