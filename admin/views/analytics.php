<?php
/**
 * Analytics View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$db = Chatbot_Database::get_instance();

// Zeitraum-Filter
$period = isset($_GET['period']) ? sanitize_text_field($_GET['period']) : 'month';
$days = match($period) {
    'week' => 7,
    'month' => 30,
    'quarter' => 90,
    'year' => 365,
    default => 30
};

$stats = $db->get_dashboard_stats($period);
$timeline_data = $db->get_timeline_data($days);
$top_faqs = $db->get_top_faqs(10);

// Timeline-Daten für Chart.js vorbereiten
$timeline_labels = [];
$timeline_values = [];
foreach ($timeline_data as $data) {
    $timeline_labels[] = date_i18n('d.m.', strtotime($data->date));
    $timeline_values[] = $data->count;
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <!-- Zeitraum-Filter -->
    <div class="chatbot-period-filter">
        <label><?php _e('Zeitraum:', 'questify'); ?></label>
        <select id="period-filter">
            <option value="week" <?php selected($period, 'week'); ?>><?php _e('Letzte 7 Tage', 'questify'); ?></option>
            <option value="month" <?php selected($period, 'month'); ?>><?php _e('Letzter Monat', 'questify'); ?></option>
            <option value="quarter" <?php selected($period, 'quarter'); ?>><?php _e('Letzte 3 Monate', 'questify'); ?></option>
            <option value="year" <?php selected($period, 'year'); ?>><?php _e('Letztes Jahr', 'questify'); ?></option>
        </select>
    </div>

    <!-- Statistik-Karten -->
    <div class="chatbot-stats-cards">
        <div class="chatbot-stat-card">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['total_inquiries']); ?></h3>
                <p><?php _e('Gesamt-Anfragen', 'questify'); ?></p>
            </div>
        </div>
        <div class="chatbot-stat-card">
            <div class="stat-icon stat-success">✓</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['answered_percent']); ?>%</h3>
                <p><?php _e('Beantwortet', 'questify'); ?></p>
            </div>
        </div>
        <div class="chatbot-stat-card">
            <div class="stat-icon stat-warning">⚠</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['not_answered_percent']); ?>%</h3>
                <p><?php _e('Nicht beantwortet', 'questify'); ?></p>
            </div>
        </div>
        <div class="chatbot-stat-card">
            <div class="stat-icon stat-info">👍</div>
            <div class="stat-content">
                <h3><?php echo number_format_i18n($stats['helpful_rate']); ?>%</h3>
                <p><?php _e('Hilfreich-Rate', 'questify'); ?></p>
            </div>
        </div>
    </div>

    <div class="chatbot-analytics-grid">
        <!-- Zeitverlauf -->
        <div class="chatbot-chart-box">
            <h2><?php _e('Anfragen im Zeitverlauf', 'questify'); ?></h2>
            <canvas id="timeline-chart"></canvas>
        </div>

        <!-- Erfolgsquote -->
        <div class="chatbot-chart-box">
            <h2><?php _e('Erfolgsquote', 'questify'); ?></h2>
            <canvas id="success-chart"></canvas>
        </div>

        <!-- Top FAQs -->
        <div class="chatbot-chart-box chatbot-chart-box-wide">
            <h2><?php _e('Top 10 FAQs', 'questify'); ?></h2>
            <canvas id="top-faqs-chart"></canvas>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('#period-filter').on('change', function() {
        window.location.href = '<?php echo admin_url('admin.php?page=chatbot-analytics'); ?>&period=' + $(this).val();
    });

    // Timeline Chart
    new Chart(document.getElementById('timeline-chart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($timeline_labels); ?>,
            datasets: [{
                label: '<?php _e('Anfragen', 'questify'); ?>',
                data: <?php echo json_encode($timeline_values); ?>,
                borderColor: '#0073aa',
                backgroundColor: 'rgba(0, 115, 170, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Success Chart
    new Chart(document.getElementById('success-chart'), {
        type: 'doughnut',
        data: {
            labels: ['<?php _e('Beantwortet', 'questify'); ?>', '<?php _e('Nicht beantwortet', 'questify'); ?>'],
            datasets: [{
                data: [<?php echo $stats['answered']; ?>, <?php echo $stats['not_answered']; ?>],
                backgroundColor: ['#46b450', '#dc3232']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Top FAQs Chart
    new Chart(document.getElementById('top-faqs-chart'), {
        type: 'bar',
        data: {
            labels: [<?php echo implode(',', array_map(function($faq) {
                return '"' . esc_js(wp_trim_words($faq->question, 8)) . '"';
            }, $top_faqs)); ?>],
            datasets: [{
                label: '<?php _e('Aufrufe', 'questify'); ?>',
                data: [<?php echo implode(',', array_map(function($faq) { return $faq->view_count; }, $top_faqs)); ?>],
                backgroundColor: '#0073aa'
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true }
            }
        }
    });
});
</script>

<style>
.chatbot-period-filter {
    margin-bottom: 20px;
}
.chatbot-analytics-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-top: 20px;
}
.chatbot-chart-box {
    background: white;
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    min-height: 300px;
}
.chatbot-chart-box-wide {
    grid-column: span 2;
}
.chatbot-chart-box canvas {
    max-height: 400px;
}
</style>
