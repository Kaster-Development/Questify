<?php
/**
 * Admin Dashboard View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

$questify_db = Chatbot_Database::get_instance();
$questify_stats = $questify_db->get_dashboard_stats('month');
$questify_top_faqs = $questify_db->get_top_faqs(5);
$questify_recent_inquiries = $questify_db->get_all_inquiries(['limit' => 5, 'orderby' => 'timestamp', 'order' => 'DESC']);

// Welcome-Message bei Aktivierung
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI hint.
$questify_welcome = isset($_GET['welcome']) ? sanitize_text_field(wp_unslash($_GET['welcome'])) : '';
$questify_show_welcome = ($questify_welcome === '1');
?>

<div class="wrap questi-dashboard">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
        <span class="chatbot-version">v<?php echo esc_html(QUESTIFY_VERSION); ?></span>
    </h1>

    <?php if ($questify_show_welcome): ?>
    <div class="notice notice-success is-dismissible">
        <h2>ðŸŽ‰ <?php esc_html_e('Willkommen bei Questify!', 'questify'); ?></h2>
        <p><?php esc_html_e('Das Plugin wurde erfolgreich aktiviert. Hier sind die nü¤chsten Schritte:', 'questify'); ?></p>
        <ol>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=questi-faqs&action=add')); ?>"><?php esc_html_e('Erste FAQ erstellen', 'questify'); ?></a></li>
            <li><a href="<?php echo esc_url(admin_url('admin.php?page=questi-settings')); ?>"><?php esc_html_e('Einstellungen anpassen', 'questify'); ?></a></li>
            <li><?php esc_html_e('Chatbot auf Ihrer Website testen', 'questify'); ?></li>
        </ol>
    </div>
    <?php endif; ?>

    <!-- Statistik-Karten -->
    <div class="chatbot-stats-cards">
        <div class="chatbot-stat-card">
            <div class="stat-icon">ðŸ“Š</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['total_inquiries'] ?? 0)); ?></h3>
                <p><?php esc_html_e('Gesamt-Anfragen', 'questify'); ?></p>
                <span class="stat-period"><?php esc_html_e('Letzter Monat', 'questify'); ?></span>
            </div>
        </div>

        <div class="chatbot-stat-card">
            <div class="stat-icon stat-success">âœ“</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['answered_percent'] ?? 0)); ?>%</h3>
                <p><?php esc_html_e('Beantwortet', 'questify'); ?></p>
                <span class="stat-detail"><?php echo esc_html(number_format_i18n($questify_stats['answered'] ?? 0)); ?> <?php esc_html_e('Anfragen', 'questify'); ?></span>
            </div>
        </div>

        <div class="chatbot-stat-card">
            <div class="stat-icon stat-warning">âš </div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['not_answered_percent'] ?? 0)); ?>%</h3>
                <p><?php esc_html_e('Nicht beantwortet', 'questify'); ?></p>
                <span class="stat-detail"><?php echo esc_html(number_format_i18n($questify_stats['not_answered'] ?? 0)); ?> <?php esc_html_e('Anfragen', 'questify'); ?></span>
            </div>
        </div>

        <div class="chatbot-stat-card">
            <div class="stat-icon stat-info">ðŸ‘</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['helpful_rate'] ?? 0)); ?>%</h3>
                <p><?php esc_html_e('Hilfreich-Rate', 'questify'); ?></p>
                <span class="stat-detail"><?php esc_html_e('Durchschnitt', 'questify'); ?></span>
            </div>
        </div>
    </div>

    <div class="questi-dashboard-grid">
        <!-- Top FAQs -->
        <div class="questi-dashboard-box">
            <h2>
                <span class="dashicons dashicons-star-filled"></span>
                <?php esc_html_e('Top 5 FAQs', 'questify'); ?>
            </h2>

            <?php if (!empty($questify_top_faqs)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Frage', 'questify'); ?></th>
                            <th><?php esc_html_e('Aufrufe', 'questify'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questify_top_faqs as $questify_faq): ?>
                        <tr>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=questi-faqs&action=edit&faq=' . $questify_faq->id)); ?>">
                                    <?php echo esc_html(wp_trim_words($questify_faq->question, 10)); ?>
                                </a>
                            </td>
                            <td><strong><?php echo esc_html(number_format_i18n($questify_faq->view_count)); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="chatbot-empty-state">
                    <?php esc_html_e('Noch keine FAQ-Aufrufe vorhanden.', 'questify'); ?>
                </p>
            <?php endif; ?>

            <p class="chatbot-box-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=questi-faqs')); ?>">
                    <?php esc_html_e('Alle FAQs anzeigen', 'questify'); ?> â†’
                </a>
            </p>
        </div>

        <!-- Letzte Anfragen -->
        <div class="questi-dashboard-box">
            <h2>
                <span class="dashicons dashicons-email"></span>
                <?php esc_html_e('Letzte Anfragen', 'questify'); ?>
            </h2>

            <?php if (!empty($questify_recent_inquiries)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'questify'); ?></th>
                            <th><?php esc_html_e('Frage', 'questify'); ?></th>
                            <th><?php esc_html_e('Status', 'questify'); ?></th>
                            <th><?php esc_html_e('Datum', 'questify'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($questify_recent_inquiries as $questify_inquiry): ?>
                        <tr>
                            <td><?php echo esc_html($questify_inquiry->user_name); ?></td>
                            <td>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=questi-inquiries&action=view&inquiry=' . $questify_inquiry->id)); ?>">
                                    <?php echo esc_html(wp_trim_words($questify_inquiry->user_question, 8)); ?>
                                </a>
                            </td>
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
                                <span class="chatbot-status-badge <?php echo esc_attr($questify_status_class); ?>">
                                    <?php echo esc_html($questify_status_label); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($questify_inquiry->timestamp))); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="chatbot-empty-state">
                    <?php esc_html_e('Noch keine Anfragen vorhanden.', 'questify'); ?>
                </p>
            <?php endif; ?>

            <p class="chatbot-box-footer">
                <a href="<?php echo esc_url(admin_url('admin.php?page=questi-inquiries')); ?>">
                    <?php esc_html_e('Alle Anfragen anzeigen', 'questify'); ?> â†’
                </a>
            </p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="chatbot-quick-links">
        <h2><?php esc_html_e('Schnellzugriff', 'questify'); ?></h2>
        <div class="quick-links-grid">
            <a href="<?php echo esc_url(admin_url('admin.php?page=questi-faqs&action=add')); ?>" class="quick-link">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php esc_html_e('Neue FAQ erstellen', 'questify'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=questi-analytics')); ?>" class="quick-link">
                <span class="dashicons dashicons-chart-line"></span>
                <span><?php esc_html_e('Statistiken ansehen', 'questify'); ?></span>
            </a>
            <a href="<?php echo esc_url(admin_url('admin.php?page=questi-settings')); ?>" class="quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php esc_html_e('Einstellungen', 'questify'); ?></span>
            </a>
            <a href="<?php echo esc_url(home_url()); ?>" target="_blank" class="quick-link">
                <span class="dashicons dashicons-external"></span>
                <span><?php esc_html_e('Chatbot testen', 'questify'); ?></span>
            </a>
        </div>
    </div>

    <!-- Hilfe-Box -->
    <div class="chatbot-help-box">
        <h3><?php esc_html_e('Benü¶tigen Sie Hilfe?', 'questify'); ?></h3>
        <p><?php esc_html_e('Hier finden Sie nü¼tzliche Ressourcen:', 'questify'); ?></p>
        <ul>
            <li>
                <a href="https://kaster-development.de/wp-faq-chat/docs" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons dashicons-book-alt" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php esc_html_e('Dokumentation', 'questify'); ?>
                </a>
            </li>
            <li>
                <a href="https://github.com/Kaster-Development/Questify/issues" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons dashicons-bug" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php esc_html_e('Support', 'questify'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo esc_url(admin_url('admin.php?page=questi-settings')); ?>">
                    <span class="dashicons dashicons-admin-tools" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php esc_html_e('Debug-Modus aktivieren', 'questify'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>


