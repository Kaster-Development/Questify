<?php
/**
 * Inquiries List View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$questify_db = Chatbot_Database::get_instance();

// Pagination
$per_page = 20;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
$questify_current_page = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
$questify_offset = ($questify_current_page - 1) * $per_page;

// Filter
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filtering.
$questify_filter_status = isset($_GET['filter_status']) ? sanitize_text_field(wp_unslash($_GET['filter_status'])) : null;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only search.
$questify_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

$questify_inquiry_args = [
    'limit' => $per_page,
    'offset' => $questify_offset,
    'orderby' => 'timestamp',
    'order' => 'DESC',
];

if ($questify_filter_status) {
    $questify_inquiry_args['status'] = $questify_filter_status;
}

if (!empty($questify_search)) {
    $questify_inquiry_args['search'] = $questify_search;
}

$questify_inquiries = $questify_db->get_all_inquiries($questify_inquiry_args);
$questify_total = $questify_db->count_inquiries($questify_inquiry_args);
$questify_total_pages = ceil($questify_total / $per_page);
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    <hr class="wp-header-end">

    <!-- Filter -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_status" id="filter-status">
                <option value=""><?php esc_html_e('Alle Status', 'questify'); ?></option>
                <option value="new" <?php selected($questify_filter_status, 'new'); ?>><?php esc_html_e('Neu', 'questify'); ?></option>
                <option value="in_progress" <?php selected($questify_filter_status, 'in_progress'); ?>><?php esc_html_e('In Bearbeitung', 'questify'); ?></option>
                <option value="answered" <?php selected($questify_filter_status, 'answered'); ?>><?php esc_html_e('Beantwortet', 'questify'); ?></option>
            </select>
            <input type="button" class="button" value="<?php echo esc_attr__('Filtern', 'questify'); ?>" onclick="location.href='<?php echo esc_js(admin_url('admin.php?page=chatbot-inquiries')); ?>&filter_status=' + document.getElementById('filter-status').value">

            <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=chatbot_export_inquiries&nonce=' . wp_create_nonce('chatbot_admin_ajax'))); ?>" class="button">
                <span class="dashicons dashicons-download"></span> <?php esc_html_e('CSV Export', 'questify'); ?>
            </a>
        </div>

        <div class="alignright">
            <form method="get">
                <input type="hidden" name="page" value="chatbot-inquiries">
                <input type="search" name="s" value="<?php echo esc_attr($questify_search); ?>" placeholder="<?php echo esc_attr__('Suchen...', 'questify'); ?>">
                <input type="submit" class="button" value="<?php echo esc_attr__('Suchen', 'questify'); ?>">
            </form>
        </div>
    </div>

    <?php if (empty($questify_inquiries)): ?>
        <div class="chatbot-empty-state-large">
            <span class="dashicons dashicons-email"></span>
            <h2><?php esc_html_e('Noch keine Anfragen', 'questify'); ?></h2>
            <p><?php esc_html_e('Anfragen werden hier angezeigt, sobald Besucher den Chatbot nutzen.', 'questify'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('ID', 'questify'); ?></th>
                    <th><?php esc_html_e('Datum/Zeit', 'questify'); ?></th>
                    <th><?php esc_html_e('Name', 'questify'); ?></th>
                    <th><?php esc_html_e('E-Mail', 'questify'); ?></th>
                    <th><?php esc_html_e('Frage', 'questify'); ?></th>
                    <th><?php esc_html_e('Status', 'questify'); ?></th>
                    <th><?php esc_html_e('Aktionen', 'questify'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($questify_inquiries as $questify_inquiry): ?>
                <tr>
                    <td><?php echo esc_html((string) $questify_inquiry->id); ?></td>
                    <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($questify_inquiry->timestamp))); ?></td>
                    <td><?php echo esc_html($questify_inquiry->user_name); ?></td>
                    <td><a href="mailto:<?php echo esc_attr($questify_inquiry->user_email); ?>"><?php echo esc_html($questify_inquiry->user_email); ?></a></td>
                    <td><?php echo esc_html(wp_trim_words($questify_inquiry->user_question, 15)); ?></td>
                    <td>
                        <?php
                        $questify_status_class = match($questify_inquiry->status) {
                            'new' => 'status-new',
                            'in_progress' => 'status-progress',
                            'answered' => 'status-answered',
                            default => ''
                        };
                        $questify_status_label = match($questify_inquiry->status) {
                            'new' => __('Neu', 'questify'),
                            'in_progress' => __('In Bearbeitung', 'questify'),
                            'answered' => __('Beantwortet', 'questify'),
                            default => $questify_inquiry->status
                        };
                        ?>
                        <span class="chatbot-status-badge <?php echo esc_attr($questify_status_class); ?>"><?php echo esc_html($questify_status_label); ?></span>
                    </td>
                    <td class="chatbot-actions">
                        <a href="<?php echo esc_url(admin_url('admin.php?page=chatbot-inquiries&action=view&inquiry=' . $questify_inquiry->id)); ?>" title="<?php echo esc_attr__('Ansehen', 'questify'); ?>">
                            <span class="dashicons dashicons-visibility"></span>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <?php if ($questify_total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php echo wp_kses_post(paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $questify_total_pages,
                    'current' => $questify_current_page,
                ])); ?>
            </div>
        </div>
        <?php endif; ?>

        <p class="chatbot-info-text">
            <?php
            printf(
                /* translators: %d: number of inquiries */
                esc_html(_n('%d Anfrage gefunden', '%d Anfragen gefunden', $total, 'questify')),
                absint($total)
            );
            ?>
        </p>
    <?php endif; ?>
</div>
