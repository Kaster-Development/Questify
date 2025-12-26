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

$db = Chatbot_Database::get_instance();

// Statistiken holen
$stats = $db->get_dashboard_stats('month');
$top_faqs = $db->get_top_faqs(5);
$recent_inquiries = $db->get_all_inquiries(['limit' => 5, 'orderby' => 'timestamp', 'order' => 'DESC']);

// Welcome-Message bei Aktivierung
$show_welcome = isset($_GET['welcome']) && $_GET['welcome'] === '1';
?>

<div class="wrap chatbot-dashboard">
    <h1>
        <?php echo esc_html(get_admin_page_title()); ?>
        <span class="chatbot-version">v<?php echo QUESTIFY_VERSION; ?></span>
    </h1>

    <?php if ($show_welcome): ?>
    <div class="notice notice-success is-dismissible">
        <h2>🎉 <?php _e('Willkommen bei Questify!', 'questify'); ?></h2>
        <p><?php _e('Das Plugin wurde erfolgreich aktiviert. Hier sind die nächsten Schritte:', 'questify'); ?></p>
        <ol>
            <li><a href="<?php echo admin_url('admin.php?page=chatbot-faqs&action=add'); ?>"><?php _e('Erste FAQ erstellen', 'questify'); ?></a></li>
            <li><a href="<?php echo admin_url('admin.php?page=chatbot-settings'); ?>"><?php _e('Einstellungen anpassen', 'questify'); ?></a></li>
            <li><?php _e('Chatbot auf Ihrer Website testen', 'questify'); ?></li>
        </ol>
    </div>
    <?php endif; ?>

    <!-- Statistik-Karten -->
    <div class="chatbot-stats-cards">
        <div class="chatbot-stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['total_inquiries']); ?></h3>
                <p><?php _e('Gesamt-Anfragen', 'questify'); ?></p>
                <span class="stat-period"><?php _e('Letzter Monat', 'questify'); ?></span>
            </div>
        </div>

        <div class="chatbot-stat-card">
            <div class="stat-icon stat-success">✓</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['answered_percent']); ?>%</h3>
                <p><?php _e('Beantwortet', 'questify'); ?></p>
                <span class="stat-detail"><?php echo number_format_i18n($stats['answered']); ?> <?php _e('Anfragen', 'questify'); ?></span>
            </div>
        </div>

        <div class="chatbot-stat-card">
            <div class="stat-icon stat-warning">⚠</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['not_answered_percent']); ?>%</h3>
                <p><?php _e('Nicht beantwortet', 'questify'); ?></p>
                <span class="stat-detail"><?php echo number_format_i18n($stats['not_answered']); ?> <?php _e('Anfragen', 'questify'); ?></span>
            </div>
        </div>

        <div class="chatbot-stat-card">
            <div class="stat-icon stat-info">👍</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['helpful_rate']); ?>%</h3>
                <p><?php _e('Hilfreich-Rate', 'questify'); ?></p>
                <span class="stat-detail"><?php _e('Durchschnitt', 'questify'); ?></span>
            </div>
        </div>
    </div>

    <div class="chatbot-dashboard-grid">
        <!-- Top FAQs -->
        <div class="chatbot-dashboard-box">
            <h2>
                <span class="dashicons dashicons-star-filled"></span>
                <?php _e('Top 5 FAQs', 'questify'); ?>
            </h2>

            <?php if (!empty($top_faqs)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Frage', 'questify'); ?></th>
                            <th><?php _e('Aufrufe', 'questify'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_faqs as $faq): ?>
                        <tr>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=chatbot-faqs&action=edit&faq=' . $faq->id); ?>">
                                    <?php echo esc_html(wp_trim_words($faq->question, 10)); ?>
                                </a>
                            </td>
                            <td><strong><?php echo number_format_i18n($faq->view_count); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="chatbot-empty-state">
                    <?php _e('Noch keine FAQ-Aufrufe vorhanden.', 'questify'); ?>
                </p>
            <?php endif; ?>

            <p class="chatbot-box-footer">
                <a href="<?php echo admin_url('admin.php?page=chatbot-faqs'); ?>">
                    <?php _e('Alle FAQs anzeigen', 'questify'); ?> →
                </a>
            </p>
        </div>

        <!-- Letzte Anfragen -->
        <div class="chatbot-dashboard-box">
            <h2>
                <span class="dashicons dashicons-email"></span>
                <?php _e('Letzte Anfragen', 'questify'); ?>
            </h2>

            <?php if (!empty($recent_inquiries)): ?>
                <table class="widefat">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'questify'); ?></th>
                            <th><?php _e('Frage', 'questify'); ?></th>
                            <th><?php _e('Status', 'questify'); ?></th>
                            <th><?php _e('Datum', 'questify'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_inquiries as $inquiry): ?>
                        <tr>
                            <td><?php echo esc_html($inquiry->user_name); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=chatbot-inquiries&action=view&inquiry=' . $inquiry->id); ?>">
                                    <?php echo esc_html(wp_trim_words($inquiry->user_question, 8)); ?>
                                </a>
                            </td>
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
                                <span class="chatbot-status-badge <?php echo $status_class; ?>">
                                    <?php echo esc_html($status_label); ?>
                                </span>
                            </td>
                            <td><?php echo date_i18n('d.m.Y H:i', strtotime($inquiry->timestamp)); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p class="chatbot-empty-state">
                    <?php _e('Noch keine Anfragen vorhanden.', 'questify'); ?>
                </p>
            <?php endif; ?>

            <p class="chatbot-box-footer">
                <a href="<?php echo admin_url('admin.php?page=chatbot-inquiries'); ?>">
                    <?php _e('Alle Anfragen anzeigen', 'questify'); ?> →
                </a>
            </p>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="chatbot-quick-links">
        <h2><?php _e('Schnellzugriff', 'questify'); ?></h2>
        <div class="quick-links-grid">
            <a href="<?php echo admin_url('admin.php?page=chatbot-faqs&action=add'); ?>" class="quick-link">
                <span class="dashicons dashicons-plus-alt"></span>
                <span><?php _e('Neue FAQ erstellen', 'questify'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=chatbot-analytics'); ?>" class="quick-link">
                <span class="dashicons dashicons-chart-line"></span>
                <span><?php _e('Statistiken ansehen', 'questify'); ?></span>
            </a>
            <a href="<?php echo admin_url('admin.php?page=chatbot-settings'); ?>" class="quick-link">
                <span class="dashicons dashicons-admin-settings"></span>
                <span><?php _e('Einstellungen', 'questify'); ?></span>
            </a>
            <a href="<?php echo home_url(); ?>" target="_blank" class="quick-link">
                <span class="dashicons dashicons-external"></span>
                <span><?php _e('Chatbot testen', 'questify'); ?></span>
            </a>
        </div>
    </div>

    <!-- Hilfe-Box -->
    <div class="chatbot-help-box">
        <h3><?php _e('Benötigen Sie Hilfe?', 'questify'); ?></h3>
        <p><?php _e('Hier finden Sie nützliche Ressourcen:', 'questify'); ?></p>
        <ul>
            <li>
                <a href="https://kaster-development.de/wp-faq-chat/docs" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons dashicons-book-alt" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php _e('Dokumentation', 'questify'); ?>
                </a>
            </li>
            <li>
                <a href="https://github.com/Kaster-Development/Questify/issues" target="_blank" rel="noopener noreferrer">
                    <span class="dashicons dashicons-bug" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php _e('Support', 'questify'); ?>
                </a>
            </li>
            <li>
                <a href="<?php echo admin_url('admin.php?page=chatbot-settings'); ?>">
                    <span class="dashicons dashicons-admin-tools" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php _e('Debug-Modus aktivieren', 'questify'); ?>
                </a>
            </li>
        </ul>
    </div>
</div>
