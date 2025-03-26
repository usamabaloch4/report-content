<?php
/**
 * Handles public-facing functionality
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class responsible for public-facing functionality.
 * Follows Dependency Inversion Principle by depending on abstractions (interfaces).
 */
class ReportContentPublic {

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
     * Register the stylesheets for the public-facing side of the site.
     */
    public function enqueueStyles() {
        wp_enqueue_style(
            'report-content-public',
            REPORT_CONTENT_PLUGIN_URL . 'assets/css/report-content-public.css',
            array(),
            REPORT_CONTENT_VERSION
        );
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     */
    public function enqueueScripts() {
        wp_enqueue_script(
            'report-content-public',
            REPORT_CONTENT_PLUGIN_URL . 'assets/js/report-content-public.js',
            array('jquery'),
            REPORT_CONTENT_VERSION,
            true
        );

        wp_localize_script(
            'report-content-public',
            'reportContent',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('report_content_nonce'),
                'i18n' => array(
                    'success' => __('Thank you for your report. We will review this content shortly.', 'report-content'),
                    'error' => __('There was an error submitting your report. Please try again.', 'report-content'),
                    'alreadyReported' => __('You have already reported this content.', 'report-content'),
                    'confirm' => __('Are you sure you want to report this content?', 'report-content'),
                )
            )
        );
    }

    /**
     * Add report button to post content.
     *
     * @param string $content The post content.
     * @return string Modified content with report button.
     */
    public function addReportButton($content) {
        // Only add to single posts/pages
        if (!is_singular()) {
            return $content;
        }

        global $post;

        // Skip pages if disabled in settings
        if (is_page() && $this->settings->getSetting('disable_on_pages', '0') === '1') {
            return $content;
        }

        // Don't add to posts that are hidden
        if ($this->postHandler->isPostHidden($post->ID)) {
            return $this->getHiddenContentMessage();
        }

        // Get report reasons
        $reasons = $this->settings->getReportReasons();

        // Generate the button and form HTML
        ob_start();
        ?>
        <div class="report-content-container">
            <button class="report-content-button" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <span class="dashicons dashicons-flag"></span> <?php esc_html_e('Report Content', 'report-content'); ?>
            </button>
            
            <div class="report-content-form" style="display: none;">
                <h3><?php esc_html_e('Report this content', 'report-content'); ?></h3>
                <p><?php esc_html_e('Please select a reason for reporting this content:', 'report-content'); ?></p>
                
                <form id="report-content-form-<?php echo esc_attr($post->ID); ?>" class="report-content-form-fields">
                    <input type="hidden" name="post_id" value="<?php echo esc_attr($post->ID); ?>">
                    
                    <div class="report-content-reasons">
                        <?php foreach ($reasons as $key => $label) : ?>
                            <div class="report-content-reason">
                                <label>
                                    <input type="radio" name="report_reason" value="<?php echo esc_attr($key); ?>">
                                    <?php echo esc_html($label); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="report-content-reason report-content-reason-other">
                            <label>
                                <input type="radio" name="report_reason" value="other">
                                <?php esc_html_e('Other reason', 'report-content'); ?>
                            </label>
                            <textarea name="report_reason_other" placeholder="<?php esc_attr_e('Please explain why you are reporting this content...', 'report-content'); ?>" style="display: none;"></textarea>
                        </div>
                    </div>
                    
                    <div class="report-content-actions">
                        <button type="submit" class="report-content-submit"><?php esc_html_e('Submit Report', 'report-content'); ?></button>
                        <button type="button" class="report-content-cancel"><?php esc_html_e('Cancel', 'report-content'); ?></button>
                    </div>
                    
                    <div class="report-content-message"></div>
                </form>
            </div>
        </div>
        <?php
        $button = ob_get_clean();
        
        return $content . $button;
    }

    /**
     * Get message to display for hidden content.
     *
     * @return string The hidden content message.
     */
    private function getHiddenContentMessage() {
        ob_start();
        ?>
        <div class="report-content-hidden-message">
            <p><?php esc_html_e('This content has been hidden due to user reports.', 'report-content'); ?></p>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Handle AJAX report submission.
     */
    public function handleReport() {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'report_content_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'report-content')));
        }

        // Get post data
        $postId = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
        $reasonKey = isset($_POST['reason']) ? sanitize_key($_POST['reason']) : '';
        $reasonText = isset($_POST['reason_text']) ? sanitize_textarea_field($_POST['reason_text']) : '';

        // Validate post ID
        if (empty($postId) || !get_post($postId)) {
            wp_send_json_error(array('message' => __('Invalid post', 'report-content')));
        }

        // Validate reason
        if (empty($reasonKey)) {
            wp_send_json_error(array('message' => __('Please select a reason for reporting', 'report-content')));
        }

        // Get available reasons
        $reasons = $this->settings->getReportReasons();
        
        // Format the reason for storing
        if ($reasonKey === 'other' && !empty($reasonText)) {
            $reportReason = __('Other', 'report-content') . ': ' . $reasonText;
        } else {
            $reportReason = isset($reasons[$reasonKey]) ? $reasons[$reasonKey] : $reasonKey;
        }

        // Get user information
        $userId = get_current_user_id();
        $ipAddress = $_SERVER['REMOTE_ADDR'];

        // Check if user has already reported this post
        if ($this->postHandler->hasUserReported($postId, $userId)) {
            wp_send_json_error(array('message' => __('You have already reported this content', 'report-content')));
        }

        // Save the report
        $reportId = $this->postHandler->saveReport($postId, $userId, $reportReason, $ipAddress);

        if ($reportId) {
            // Send notification if enabled
            $this->settings->maybeSendNotification($reportId, $postId, $reportReason);
            
            wp_send_json_success(array('message' => __('Thank you for your report. We will review this content shortly.', 'report-content')));
        } else {
            wp_send_json_error(array('message' => __('There was an error saving your report. Please try again.', 'report-content')));
        }
    }

    /**
     * Filter content to hide reported posts.
     *
     * @param WP_Query $query The WordPress query object.
     */
    public function filterReportedPosts($query) {
        // Don't modify admin queries or if user has permissions to view reported content
        if (is_admin() || current_user_can('manage_options') || !$query->is_main_query()) {
            return;
        }

        // Add meta query to exclude hidden posts
        $metaQuery = $query->get('meta_query', array());
        $metaQuery[] = array(
            'relation' => 'OR',
            array(
                'key' => '_report_content_hidden',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => '_report_content_hidden',
                'value' => '1',
                'compare' => '!=',
            ),
        );

        $query->set('meta_query', $metaQuery);
    }
}