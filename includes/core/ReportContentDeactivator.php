<?php
/**
 * Fired during plugin deactivation
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for cleanup during plugin deactivation.
 */
class ReportContentDeactivator {

    /**
     * Run tasks during plugin deactivation
     */
    public static function deactivate() {
        // We don't delete the data on deactivation, only on uninstall
        // This is just a placeholder for any cleanup tasks if needed
    }

}