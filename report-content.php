<?php
/**
 * Plugin Name: Report Content
 * Plugin URI: 
 * Description: Allows users to report posts with admin option to hide reported content.
 * Version: 1.0.0
 * Author: Usama Ayaz
 * Author URI: 
 * Text Domain: report-content
 * Domain Path: /languages
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('REPORT_CONTENT_VERSION', '1.0.0');
define('REPORT_CONTENT_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('REPORT_CONTENT_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load required files
require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/ReportContentLoader.php';

// Initialize the plugin
function reportContentInitialize() {
    $plugin = new ReportContentLoader();
    $plugin->run();
}

// Hook into WordPress
add_action('plugins_loaded', 'reportContentInitialize');

// Register activation hook
register_activation_hook(__FILE__, 'reportContentActivate');

// Activation function
function reportContentActivate() {
    // Create database tables if needed
    require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/core/ReportContentActivator.php';
    ReportContentActivator::activate();
}

// Register deactivation hook
register_deactivation_hook(__FILE__, 'reportContentDeactivate');

// Deactivation function
function reportContentDeactivate() {
    require_once REPORT_CONTENT_PLUGIN_DIR . 'includes/core/ReportContentDeactivator.php';
    ReportContentDeactivator::deactivate();
}