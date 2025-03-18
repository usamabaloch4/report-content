<?php
/**
 * Handles all report operations for posts
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for handling post reporting functionality.
 * Follows Single Responsibility Principle by focusing only on managing reports.
 */
class ReportContentPostHandler {

    /**
     * The database table name.
     *
     * @var string
     */
    private $tableName;

    /**
     * Constructor to set up the database table name.
     */
    public function __construct() {
        global $wpdb;
        $this->tableName = $wpdb->prefix . 'reported_content';
    }

    /**
     * Save a new content report.
     *
     * @param int    $postId        The ID of the post being reported.
     * @param int    $userId        The ID of the user who reported the post.
     * @param string $reportReason  The reason for reporting.
     * @param string $ipAddress     The IP address of the user.
     * @return bool|int The report ID on success, false on failure.
     */
    public function saveReport($postId, $userId, $reportReason, $ipAddress) {
        global $wpdb;

        // Check if this user has already reported this post
        $existingReport = $this->hasUserReported($postId, $userId);
        if ($existingReport) {
            return false; // User has already reported this post
        }

        // Insert the report
        $result = $wpdb->insert(
            $this->tableName,
            array(
                'post_id'      => $postId,
                'user_id'      => $userId,
                'report_reason' => $reportReason,
                'status'       => 'pending',
                'ip_address'   => $ipAddress,
                'reported_date' => current_time('mysql'),
            ),
            array('%d', '%d', '%s', '%s', '%s', '%s')
        );

        if ($result) {
            $reportId = $wpdb->insert_id;
            $this->maybeHidePost($postId);
            return $reportId;
        }

        return false;
    }

    /**
     * Check if a user has already reported a post.
     *
     * @param int $postId The post ID.
     * @param int $userId The user ID.
     * @return bool True if the user has already reported this post.
     */
    public function hasUserReported($postId, $userId) {
        global $wpdb;

        $count = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE post_id = %d AND user_id = %d",
                $postId,
                $userId
            )
        );

        return ($count > 0);
    }

    /**
     * Check if a post should be hidden and update its status if needed.
     *
     * @param int $postId The post ID to check.
     */
    private function maybeHidePost($postId) {
        // Check if auto-hide is enabled
        $autoHide = get_option('report_content_auto_hide', '0');
        if ($autoHide !== '1') {
            return;
        }

        // Check how many reports this post has
        global $wpdb;
        $reportCount = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} WHERE post_id = %d AND status = %s",
                $postId,
                'pending'
            )
        );

        // Check if report count exceeds threshold
        $minReports = get_option('report_content_min_reports', '3');
        if ($reportCount >= intval($minReports)) {
            // Auto-hide the post
            $this->hidePost($postId);
        }
    }

    /**
     * Hide a reported post.
     *
     * @param int $postId The post ID to hide.
     */
    public function hidePost($postId) {
        // Apply a filter to hide the post
        add_post_meta($postId, '_report_content_hidden', '1', true);
        
        // Set the post status to private if configured to do so
        if (get_option('report_content_make_private', '0') === '1') {
            wp_update_post(array(
                'ID' => $postId,
                'post_status' => 'private'
            ));
        }
    }

    /**
     * Unhide a reported post.
     *
     * @param int $postId The post ID to unhide.
     */
    public function unhidePost($postId) {
        delete_post_meta($postId, '_report_content_hidden');
        
        // Restore post status if it was set to private
        if (get_option('report_content_make_private', '0') === '1') {
            wp_update_post(array(
                'ID' => $postId,
                'post_status' => 'publish'
            ));
        }
    }

    /**
     * Get all reports with pagination.
     *
     * @param int    $page     The current page number.
     * @param int    $perPage  Number of reports per page.
     * @param string $status   Optional filter by status.
     * @return array Reports and pagination data.
     */
    public function getReports($page = 1, $perPage = 20, $status = null) {
        global $wpdb;

        $where = '';
        $params = array();

        if ($status) {
            $where = 'WHERE status = %s';
            $params[] = $status;
        }

        // Get total count
        $total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$this->tableName} $where",
                $params
            )
        );

        // Calculate pagination
        $offset = ($page - 1) * $perPage;
        $totalPages = ceil($total / $perPage);

        // Get reports for current page
        $query = $wpdb->prepare(
            "SELECT r.*, p.post_title 
            FROM {$this->tableName} r
            LEFT JOIN {$wpdb->posts} p ON r.post_id = p.ID
            $where
            ORDER BY r.reported_date DESC
            LIMIT %d OFFSET %d",
            array_merge($params, array($perPage, $offset))
        );

        $reports = $wpdb->get_results($query);

        // Add user info to each report
        foreach ($reports as &$report) {
            $user = get_userdata($report->user_id);
            $report->user_name = $user ? $user->display_name : __('Anonymous', 'report-content');
            $report->post_permalink = get_permalink($report->post_id);
        }

        return array(
            'reports' => $reports,
            'total' => $total,
            'totalPages' => $totalPages,
            'currentPage' => $page
        );
    }

    /**
     * Update a report's status.
     *
     * @param int    $reportId    The report ID.
     * @param string $newStatus   The new status.
     * @param string $adminNotes  Optional admin notes.
     * @return bool True on success, false on failure.
     */
    public function updateReportStatus($reportId, $newStatus, $adminNotes = '') {
        global $wpdb;

        $data = array(
            'status' => $newStatus,
            'resolution_date' => current_time('mysql'),
        );
        
        if (!empty($adminNotes)) {
            $data['admin_notes'] = $adminNotes;
        }

        $result = $wpdb->update(
            $this->tableName,
            $data,
            array('id' => $reportId),
            array('%s', '%s', '%s'),
            array('%d')
        );

        return ($result !== false);
    }

    /**
     * Check if a post is hidden due to reports.
     *
     * @param int $postId The post ID to check.
     * @return bool True if the post is hidden.
     */
    public function isPostHidden($postId) {
        $hidden = get_post_meta($postId, '_report_content_hidden', true);
        return !empty($hidden);
    }

    /**
     * Get report counts by status.
     *
     * @return array Counts by status.
     */
    public function getReportCounts() {
        global $wpdb;

        $query = "SELECT status, COUNT(*) as count 
                  FROM {$this->tableName} 
                  GROUP BY status";
                  
        $results = $wpdb->get_results($query);
        
        $counts = array(
            'pending' => 0,
            'resolved' => 0,
            'dismissed' => 0,
            'total' => 0
        );
        
        foreach ($results as $row) {
            $counts[$row->status] = intval($row->count);
            $counts['total'] += intval($row->count);
        }
        
        return $counts;
    }
}