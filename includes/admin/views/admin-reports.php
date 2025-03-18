<?php
/**
 * Admin View: Reports
 *
 * @package Report_Content
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Build status filter URLs
$allUrl = admin_url('admin.php?page=report-content');
$pendingUrl = admin_url('admin.php?page=report-content&status=pending');
$resolvedUrl = admin_url('admin.php?page=report-content&status=resolved');
$dismissedUrl = admin_url('admin.php?page=report-content&status=dismissed');

// Current status filter
$currentStatus = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';

?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e('Content Reports', 'report-content'); ?></h1>
    
    <hr class="wp-header-end">
    
    <h2 class="screen-reader-text"><?php esc_html_e('Filter reports list', 'report-content'); ?></h2>
    
    <ul class="subsubsub">
        <li>
            <a href="<?php echo esc_url($allUrl); ?>" <?php echo $currentStatus === '' ? 'class="current"' : ''; ?>>
                <?php esc_html_e('All', 'report-content'); ?>
                <span class="count">(<?php echo esc_html($counts['total']); ?>)</span>
            </a> |
        </li>
        <li>
            <a href="<?php echo esc_url($pendingUrl); ?>" <?php echo $currentStatus === 'pending' ? 'class="current"' : ''; ?>>
                <?php esc_html_e('Pending', 'report-content'); ?>
                <span class="count">(<?php echo esc_html($counts['pending']); ?>)</span>
            </a> |
        </li>
        <li>
            <a href="<?php echo esc_url($resolvedUrl); ?>" <?php echo $currentStatus === 'resolved' ? 'class="current"' : ''; ?>>
                <?php esc_html_e('Resolved', 'report-content'); ?>
                <span class="count">(<?php echo esc_html($counts['resolved']); ?>)</span>
            </a> |
        </li>
        <li>
            <a href="<?php echo esc_url($dismissedUrl); ?>" <?php echo $currentStatus === 'dismissed' ? 'class="current"' : ''; ?>>
                <?php esc_html_e('Dismissed', 'report-content'); ?>
                <span class="count">(<?php echo esc_html($counts['dismissed']); ?>)</span>
            </a>
        </li>
    </ul>
    
    <?php if (!empty($reports)) : ?>
        <table class="wp-list-table widefat fixed striped reports">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-id"><?php esc_html_e('ID', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-post"><?php esc_html_e('Post', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-reason"><?php esc_html_e('Report Reason', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-user"><?php esc_html_e('Reported By', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'report-content'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reports as $report) : 
                    $postTitle = $report->post_title ? $report->post_title : __('(No title)', 'report-content');
                    $isHidden = $this->postHandler->isPostHidden($report->post_id);
                    ?>
                    <tr>
                        <td class="column-id"><?php echo esc_html($report->id); ?></td>
                        <td class="column-post">
                            <strong>
                                <a href="<?php echo esc_url($report->post_permalink); ?>" target="_blank">
                                    <?php echo esc_html($postTitle); ?>
                                </a>
                                <?php if ($isHidden) : ?>
                                    <span class="hidden-status"><?php esc_html_e('(Hidden)', 'report-content'); ?></span>
                                <?php endif; ?>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(get_edit_post_link($report->post_id)); ?>">
                                        <?php esc_html_e('Edit Post', 'report-content'); ?>
                                    </a>
                                    |
                                </span>
                                <span class="view">
                                    <a href="<?php echo esc_url($report->post_permalink); ?>" target="_blank">
                                        <?php esc_html_e('View Post', 'report-content'); ?>
                                    </a>
                                </span>
                            </div>
                        </td>
                        <td class="column-reason"><?php echo esc_html($report->report_reason); ?></td>
                        <td class="column-user">
                            <?php 
                            if ($report->user_id > 0) {
                                $userUrl = get_edit_user_link($report->user_id);
                                echo '<a href="' . esc_url($userUrl) . '">' . esc_html($report->user_name) . '</a>';
                            } else {
                                echo esc_html($report->user_name);
                            }
                            ?>
                            <div class="row-actions">
                                <span class="ip"><?php echo esc_html($report->ip_address); ?></span>
                            </div>
                        </td>
                        <td class="column-date">
                            <?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($report->reported_date))); ?>
                        </td>
                        <td class="column-status">
                            <span class="report-status status-<?php echo esc_attr($report->status); ?>">
                                <?php 
                                switch ($report->status) {
                                    case 'pending':
                                        esc_html_e('Pending', 'report-content');
                                        break;
                                    case 'resolved':
                                        esc_html_e('Resolved', 'report-content');
                                        break;
                                    case 'dismissed':
                                        esc_html_e('Dismissed', 'report-content');
                                        break;
                                    default:
                                        echo esc_html(ucfirst($report->status));
                                        break;
                                }
                                ?>
                            </span>
                        </td>
                        <td class="column-actions">
                            <?php if ($report->status === 'pending') : ?>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=report-content&action=resolve&report=' . $report->id), 'report_content_action')); ?>" class="button button-small action-resolve">
                                    <?php esc_html_e('Resolve', 'report-content'); ?>
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=report-content&action=dismiss&report=' . $report->id), 'report_content_action')); ?>" class="button button-small action-dismiss">
                                    <?php esc_html_e('Dismiss', 'report-content'); ?>
                                </a>
                                <?php if (!$isHidden) : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=report-content&action=hide_post&report=' . $report->id), 'report_content_action')); ?>" class="button button-small action-hide">
                                        <?php esc_html_e('Hide Post', 'report-content'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=report-content&action=unhide_post&report=' . $report->id), 'report_content_action')); ?>" class="button button-small action-unhide">
                                        <?php esc_html_e('Unhide Post', 'report-content'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php else : ?>
                                <?php if ($isHidden) : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=report-content&action=unhide_post&report=' . $report->id), 'report_content_action')); ?>" class="button button-small action-unhide">
                                        <?php esc_html_e('Unhide Post', 'report-content'); ?>
                                    </a>
                                <?php else : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=report-content&action=hide_post&report=' . $report->id), 'report_content_action')); ?>" class="button button-small action-hide">
                                        <?php esc_html_e('Hide Post', 'report-content'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th scope="col" class="manage-column column-id"><?php esc_html_e('ID', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-post"><?php esc_html_e('Post', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-reason"><?php esc_html_e('Report Reason', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-user"><?php esc_html_e('Reported By', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-date"><?php esc_html_e('Date', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-status"><?php esc_html_e('Status', 'report-content'); ?></th>
                    <th scope="col" class="manage-column column-actions"><?php esc_html_e('Actions', 'report-content'); ?></th>
                </tr>
            </tfoot>
        </table>
        
        <?php if ($pagination['totalPages'] > 1) : ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <span class="displaying-num">
                        <?php printf(_n('%s item', '%s items', $pagination['total'], 'report-content'), number_format_i18n($pagination['total'])); ?>
                    </span>
                    
                    <span class="pagination-links">
                        <?php
                        $pageLinks = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $pagination['totalPages'],
                            'current' => $pagination['currentPage'],
                        ));
                        
                        echo $pageLinks;
                        ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
        
    <?php else : ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No reports found.', 'report-content'); ?></p>
        </div>
    <?php endif; ?>
</div>