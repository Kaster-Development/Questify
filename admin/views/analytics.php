<?php
/**
 * Analytics View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$questify_db = Questi_Database::get_instance();

// Zeitraum-Filter
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filtering.
$questify_period = isset($_GET['period']) ? sanitize_text_field(wp_unslash($_GET['period'])) : 'month';
$questify_days = match($questify_period) {
    'week' => 7,
    'month' => 30,
    'quarter' => 90,
    'year' => 365,
    default => 30
};

$questify_stats = $questify_db->get_dashboard_stats($questify_period);
$questify_timeline_data = $questify_db->get_timeline_data($questify_days);
$questify_top_faqs = $questify_db->get_top_faqs(10);

// Timeline-Daten für Chart.js vorbereiten
$questify_timeline_labels = [];
$questify_timeline_values = [];
foreach ($questify_timeline_data as $questify_timeline_row) {
    $questify_timeline_labels[] = date_i18n('d.m.', strtotime($questify_timeline_row->date));
    $questify_timeline_values[] = $questify_timeline_row->count;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Zeitraum-Filter -->
    <div class="chatbot-period-filter">
        <label><?php esc_html_e('Zeitraum:', 'questify'); ?></label>
        <select id="period-filter">
            <option value="week" <?php selected($questify_period, 'week'); ?>><?php esc_html_e('Letzte 7 Tage', 'questify'); ?></option>
            <option value="month" <?php selected($questify_period, 'month'); ?>><?php esc_html_e('Letzter Monat', 'questify'); ?></option>
            <option value="quarter" <?php selected($questify_period, 'quarter'); ?>><?php esc_html_e('Letzte 3 Monate', 'questify'); ?></option>
            <option value="year" <?php selected($questify_period, 'year'); ?>><?php esc_html_e('Letztes Jahr', 'questify'); ?></option>
        </select>
    </div>

    <!-- Statistik-Karten -->
    <div class="chatbot-stats-cards">
        <div class="chatbot-stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['total_inquiries'] ?? 0)); ?></h3>
                <p><?php esc_html_e('Gesamt-Anfragen', 'questify'); ?></p>
            </div>
        </div>
        <div class="chatbot-stat-card">
            <div class="stat-icon stat-success">✓</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['answered_percent'] ?? 0)); ?>%</h3>
                <p><?php esc_html_e('Beantwortet', 'questify'); ?></p>
            </div>
        </div>
        <div class="chatbot-stat-card">
            <div class="stat-icon stat-warning">⚠</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['not_answered_percent'] ?? 0)); ?>%</h3>
                <p><?php esc_html_e('Nicht beantwortet', 'questify'); ?></p>
            </div>
        </div>
        <div class="chatbot-stat-card">
            <div class="stat-icon stat-info">👍</div>
            <div class="stat-content">
                <h3><?php echo esc_html(number_format_i18n($questify_stats['helpful_rate'] ?? 0)); ?>%</h3>
                <p><?php esc_html_e('Hilfreich-Rate', 'questify'); ?></p>
            </div>
        </div>
    </div>

    <div class="questi-analytics-grid">
        <!-- Zeitverlauf -->
        <div class="chatbot-chart-box">
            <h2><?php esc_html_e('Anfragen im Zeitverlauf', 'questify'); ?></h2>
            <canvas id="timeline-chart"></canvas>
        </div>

        <!-- Erfolgsquote -->
        <div class="chatbot-chart-box">
            <h2><?php esc_html_e('Erfolgsquote', 'questify'); ?></h2>
            <canvas id="success-chart"></canvas>
        </div>

        <!-- Top FAQs -->
        <div class="chatbot-chart-box chatbot-chart-box-wide">
            <h2><?php esc_html_e('Top 10 FAQs', 'questify'); ?></h2>
            <canvas id="top-faqs-chart"></canvas>
        </div>
    </div>
</div>

<?php
// Dynamische Chart-Daten über wp_add_inline_script übergeben
$questify_analytics_data = wp_json_encode([
    'analyticsUrl' => admin_url('admin.php?page=questi-analytics'),
    'timelineLabels' => $questify_timeline_labels,
    'timelineValues' => array_map('absint', $questify_timeline_values),
    'successCounts' => [
        absint($questify_stats['answered'] ?? 0),
        absint($questify_stats['not_answered'] ?? 0),
    ],
    'topFaqLabels' => array_map(
        static fn($questify_faq) => wp_trim_words($questify_faq->question, 8),
        is_array($questify_top_faqs) ? $questify_top_faqs : []
    ),
    'topFaqViews' => array_map(
        static fn($questify_faq) => absint($questify_faq->view_count),
        is_array($questify_top_faqs) ? $questify_top_faqs : []
    ),
]);

wp_add_inline_script(
    'questi-analytics-script',
    'var questiAnalytics = ' . $questify_analytics_data . ';', // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped via wp_json_encode
    'before'
);
?>


