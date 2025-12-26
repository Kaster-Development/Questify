<?php
/**
 * Inquiries List View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$db = Chatbot_Database::get_instance();

// Pagination
$per_page = 20;
$current_page = isset($_GET['paged']) ? max(1, (int) $_GET['paged']) : 1;
$offset = ($current_page - 1) * $per_page;

// Filter
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : null;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';

$args = [
    'limit' => $per_page,
    'offset' => $offset,
    'orderby' => 'timestamp',
    'order' => 'DESC',
];

if ($filter_status) {
    $args['status'] = $filter_status;
}

if (!empty($search)) {
    $args['search'] = $search;
}

$inquiries = $db->get_all_inquiries($args);
$total = $db->count_inquiries($args);
$total_pages = ceil($total / $per_page);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">

    <!-- Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_status" id="filter-status">
                <option value=""><?php _e('Alle Status', 'questify'); ?></option>
                <option value="new" <?php selected($filter_status, 'new'); ?>><?php _e('Neu', 'questify'); ?></option>
                <option value="in_progress" <?php selected($filter_status, 'in_progress'); ?>><?php _e('In Bearbeitung', 'questify'); ?></option>
                <option value="answered" <?php selected($filter_status, 'answered'); ?>><?php _e('Beantwortet', 'questify'); ?></option>
            </select>
            <input type="button" class="button" value="<?php _e('Filtern', 'questify'); ?>" onclick="location.href='<?php echo admin_url('admin.php?page=chatbot-inquiries'); ?>&filter_status=' + document.getElementById('filter-status').value">

            <a href="<?php echo admin_url('admin-ajax.php?action=chatbot_export_inquiries&nonce=' . wp_create_nonce('chatbot_admin_ajax')); ?>" class="button">
                <span class="dashicons dashicons-download"></span> <?php _e('CSV Export', 'questify'); ?>
            </a>
        </div>

        <div class="alignright">
            <form method="get">
                <input type="hidden" name="page" value="chatbot-inquiries">
                <input type="search" name="s" value="<?php echo esc_attr($search); ?>" placeholder="<?php _e('Suchen...', 'questify'); ?>">
                <input type="submit" class="button" value="<?php _e('Suchen', 'questify'); ?>">
            </form>
        </div>
    </div>

    <?php if (empty($inquiries)): ?>
        <div class="chatbot-empty-state-large">
            <span class="dashicons dashicons-email"></span>
            <h2><?php _e('Noch keine Anfragen', 'questify'); ?></h2>
            <p><?php _e('Anfragen werden hier angezeigt, sobald Besucher den Chatbot nutzen.', 'questify'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'questify'); ?></th>
                    <th><?php _e('Datum/Zeit', 'questify'); ?></th>
                    <th><?php _e('Name', 'questify'); ?></th>
                    <th><?php _e('E-Mail', 'questify'); ?></th>
                    <th><?php _e('Frage', 'questify'); ?></th>
                    <th><?php _e('Status', 'questify'); ?></th>
                    <th><?php _e('Aktionen', 'questify'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($inquiries as $inquiry): ?>
                <tr>
                    <td><?php echo $inquiry->id; ?></td>
                    <td><?php echo date_i18n('d.m.Y H:i', strtotime($inquiry->timestamp)); ?></td>
                    <td><?php echo esc_html($inquiry->user_name); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($inquiry->user_email); ?>"><?php echo esc_html($inquiry->user_email); ?></a></td>
                    <td><?php echo esc_html(wp_trim_words($inquiry->user_question, 15)); ?></td>
                    <td>
                        <?php
                        $status_class = match($inquiry->status) {
                            'new' => 'status-new',
                            'in_progress' => 'status-progress',
                            'answered' => 'status-answered',
                            default => ''
                        };
                        $status_label = match($inquiry->status) {
                            'new' => __('Neu', 'questify'),
                            'in_progress' => __('In Bearbeitung', 'questify'),
                            'answered' => __('Beantwortet', 'questify'),
                            default => $inquiry->status
                        };
                        ?>
                        <span class="chatbot-status-badge <?php echo $status_class; ?>"><?php echo esc_html($status_label); ?></span>
                    </td>
                    <td class="chatbot-actions">
                        <a href="<?php echo admin_url('admin.php?page=chatbot-inquiries&action=view&inquiry=' . $inquiry->id); ?>" title="<?php _e('Ansehen', 'questify'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $total_pages,
                    'current' => $current_page,
                ]); ?>
            </div>
        </div>
        <?php endif; ?>

        <p class="chatbot-info-text">
            <?php printf(_n('%d Anfrage gefunden', '%d Anfragen gefunden', $total, 'questify'), $total); ?>
        </p>
    <?php endif; ?>
</div>
