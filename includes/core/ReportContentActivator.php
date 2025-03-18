<?php
/**
 * Fired during plugin activation
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for tasks that need to run during plugin activation.
 */
class ReportContentActivator {

    /**
     * Run tasks during plugin activation
     */
    public static function activate() {
        self::createTables();
        self::setDefaultOptions();
    }

    /**
     * Create database tables needed by the plugin
     */
    private static function createTables() {
        global $wpdb;

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . 'reported_content';

        $sql = "CREATE TABLE IF NOT EXISTS $tableName (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            post_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            report_reason text NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'pending', 
            ip_address varchar(100) NOT NULL,
            reported_date datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            resolution_date datetime DEFAULT NULL,
            admin_notes text,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY user_id (user_id),
            KEY status (status)
        ) $charsetCollate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Set default plugin options
     */
    private static function setDefaultOptions() {
        // Set default setting: Do not auto-hide reported posts
        if (!get_option('report_content_auto_hide')) {
            add_option('report_content_auto_hide', '0');
        }
        
        // Set default minimum reports before auto-hiding
        if (!get_option('report_content_min_reports')) {
            add_option('report_content_min_reports', '3');
        }
        
        // Set default report reasons
        if (!get_option('report_content_reasons')) {
            $defaultReasons = array(
                'inappropriate' => 'Inappropriate Content',
                'spam' => 'Spam',
                'offensive' => 'Offensive Language',
                'copyright' => 'Copyright Violation',
                'other' => 'Other'
            );
            add_option('report_content_reasons', $defaultReasons);
        }
    }
}