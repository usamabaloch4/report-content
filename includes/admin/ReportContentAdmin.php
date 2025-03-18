<?php
/**
 * Handles admin-specific functionality
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for admin-specific functionality.
 * Follows Interface Segregation by separating admin functionality.
 */
class ReportContentAdmin {

    /**
     * The post handler instance.
     *
     * @var ReportContentPostHandler
     */
    private $postHandler;

    /**
     * The settings instance.
     *
     * @var ReportContentSettings
     */
    private $settings;

    /**
     * Constructor to initialize properties.
     */
    public function __construct() {
        $this->postHandler = new ReportContentPostHandler();
        $this->settings = new ReportContentSettings();
    }

    /**
     * Register the stylesheets for the admin area.
     */
    public function enqueueStyles() {
        $screen = get_current_screen();
        
        // Only enqueue on plugin pages
        if (strpos($screen->id, 'report-content') !== false) {
            wp_enqueue_style(
                'report-content-admin',
                REPORT_CONTENT_PLUGIN_URL . 'assets/css/report-content-admin.css',
                array(),
                REPORT_CONTENT_VERSION
            );
        }
    }

    /**
     * Register the JavaScript for the admin area.
     */
    public function enqueueScripts() {
        $screen = get_current_screen();
        
        // Only enqueue on plugin pages
        if (strpos($screen->id, 'report-content') !== false) {
            wp_enqueue_script(
                'report-content-admin',
                REPORT_CONTENT_PLUGIN_URL . 'assets/js/report-content-admin.js',
                array('jquery'),
                REPORT_CONTENT_VERSION,
                true
            );
            
            // Localize the script with data for ajax
            wp_localize_script(
                'report-content-admin',
                'reportContentAdmin',
                array(
                    'ajaxUrl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('report-content-admin'),
                    'i18n' => array(
                        'confirmResolve' => __('Are you sure you want to mark this report as resolved?', 'report-content'),
                        'confirmDismiss' => __('Are you sure you want to dismiss this report?', 'report-content'),
                        'confirmHide' => __('Are you sure you want to hide this post?', 'report-content'),
                        'confirmUnhide' => __('Are you sure you want to unhide this post?', 'report-content'),
                    ),
                )
            );
        }
    }

    /**
     * Add plugin menu pages in the admin area.
     */
    public function addMenuPages() {
        // Main menu
        add_menu_page(
            __('Report Content', 'report-content'),
            __('Report Content', 'report-content'),
            'manage_options',
            'report-content',
            array($this, 'displayReportsPage'),
            'dashicons-flag',
            30
        );
        
        // Reports submenu (same as parent)
        add_submenu_page(
            'report-content',
            __('Reported Content', 'report-content'),
            __('Reported Content', 'report-content'),
            'manage_options',
            'report-content',
            array($this, 'displayReportsPage')
        );
        
        // Settings submenu
        add_submenu_page(
            'report-content',
            __('Report Content Settings', 'report-content'),
            __('Settings', 'report-content'),
            'manage_options',
            'report-content-settings',
            array($this, 'displaySettingsPage')
        );
    }

    /**
     * Register settings with WordPress Settings API.
     */
    public function registerSettings() {
        $this->settings->registerSettings();
    }

    /**
     * Display the reports management page.
     */
    public function displayReportsPage() {
        // Process actions if any
        $this->processReportActions();
        
        // Get current page number
        $page = isset($_GET['paged']) ? absint($_GET['paged']) : 1;
        
        // Get status filter if any
        $status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : null;
        
        // Get reports
        $data = $this->postHandler->getReports($page, 20, $status);
        $reports = $data['reports'];
        $pagination = array(
            'total' => $data['total'],
            'totalPages' => $data['totalPages'],
            'currentPage' => $data['currentPage'],
        );
        
        // Get report counts by status
        $counts = $this->postHandler->getReportCounts();
        
        // Include the view template
        include REPORT_CONTENT_PLUGIN_DIR . 'includes/admin/views/admin-reports.php';
    }

    /**
     * Display the settings page.
     */
    public function displaySettingsPage() {
        // Include the view template
        include REPORT_CONTENT_PLUGIN_DIR . 'includes/admin/views/admin-settings.php';
    }

    /**
     * Process report actions from the admin area.
     */
    private function processReportActions() {
        // Check if we have an action to process
        if (!isset($_GET['action']) || !isset($_GET['report'])) {
            return;
        }
        
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'report_content_action')) {
            wp_die(__('Security check failed', 'report-content'));
        }
        
        $action = sanitize_text_field($_GET['action']);
        $reportId = absint($_GET['report']);
        
        // Process the action
        switch ($action) {
            case 'resolve':
                $this->postHandler->updateReportStatus($reportId, 'resolved');
                break;
                
            case 'dismiss':
                $this->postHandler->updateReportStatus($reportId, 'dismissed');
                break;
                
            case 'hide_post':
                // Get post ID from report
                global $wpdb;
                $tableName = $wpdb->prefix . 'reported_content';
                $postId = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $tableName WHERE id = %d", $reportId));
                
                if ($postId) {
                    $this->postHandler->hidePost($postId);
                }
                break;
                
            case 'unhide_post':
                // Get post ID from report
                global $wpdb;
                $tableName = $wpdb->prefix . 'reported_content';
                $postId = $wpdb->get_var($wpdb->prepare("SELECT post_id FROM $tableName WHERE id = %d", $reportId));
                
                if ($postId) {
                    $this->postHandler->unhidePost($postId);
                }
                break;
        }

        // Do redirect with high priority to ensure it happens before output
        add_action('admin_init', function() {
            $redirectUrl = admin_url('admin.php?page=report-content&status=' . (isset($_GET['status']) ? $_GET['status'] : ''));
            wp_safe_redirect($redirectUrl);
            exit;
        }, 1);
    }

    /**
     * Add meta box to post editing screen to show if a post has been reported.
     */
    public function addReportedMetaBox() {
        add_meta_box(
            'report_content_meta_box',
            __('Content Reports', 'report-content'),
            array($this, 'renderReportedMetaBox'),
            null, // Add to all post types
            'side',
            'high'
        );
    }

    /**
     * Render the reported meta box content.
     *
     * @param WP_Post $post The post object.
     */
    public function renderReportedMetaBox($post) {
        global $wpdb;
        $tableName = $wpdb->prefix . 'reported_content';
        
        // Count reports for this post
        $reportCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tableName WHERE post_id = %d", $post->ID));
        
        if ($reportCount > 0) {
            $pendingCount = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $tableName WHERE post_id = %d AND status = 'pending'", $post->ID));
            $isHidden = $this->postHandler->isPostHidden($post->ID);
            
            echo '<p><strong>' . sprintf(_n('%d report found for this content', '%d reports found for this content', $reportCount, 'report-content'), $reportCount) . '</strong></p>';
            
            if ($pendingCount > 0) {
                echo '<p>' . sprintf(_n('%d pending report', '%d pending reports', $pendingCount, 'report-content'), $pendingCount) . '</p>';
            }
            
            echo '<p><a href="' . admin_url('admin.php?page=report-content&post_id=' . $post->ID) . '" class="button">' . __('View Reports', 'report-content') . '</a></p>';
            
            if ($isHidden) {
                echo '<p><strong>' . __('This post is currently hidden from public view due to reports.', 'report-content') . '</strong></p>';
            }
        } else {
            echo '<p>' . __('No reports found for this content.', 'report-content') . '</p>';
        }
    }
}