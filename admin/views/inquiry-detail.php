<?php
/**
 * Inquiry Detail View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$questify_db = Questi_Database::get_instance();
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only item selection.
$questify_inquiry_id = isset($_GET['inquiry']) ? absint(wp_unslash($_GET['inquiry'])) : 0;
$questify_inquiry = $questify_db->get_inquiry($questify_inquiry_id);

if (!$questify_inquiry) {
    wp_die(esc_html__('Anfrage nicht gefunden.', 'questify'));
}

$questify_conversations = $questify_db->get_conversations_by_inquiry($questify_inquiry_id);
$questify_matched_faq = $questify_inquiry->matched_faq_id ? $questify_db->get_faq($questify_inquiry->matched_faq_id) : null;
?>

<div class="wrap">
    <h1><?php esc_html_e('Anfrage Details', 'questify'); ?> #<?php echo esc_html((string) $questify_inquiry->id); ?></h1>

    <div class="chatbot-inquiry-detail">
        <div class="chatbot-inquiry-info">
            <h2><?php esc_html_e('Kundendaten', 'questify'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Name:', 'questify'); ?></th>
                    <td><?php echo esc_html($questify_inquiry->user_name); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('E-Mail:', 'questify'); ?></th>
                    <td><a href="mailto:<?php echo esc_attr($questify_inquiry->user_email); ?>"><?php echo esc_html($questify_inquiry->user_email); ?></a></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Datum:', 'questify'); ?></th>
                    <td><?php echo esc_html(date_i18n('d.m.Y H:i:s', strtotime($questify_inquiry->timestamp))); ?> Uhr</td>
                </tr>
                <tr>
                    <th><?php esc_html_e('IP-Adresse:', 'questify'); ?></th>
                    <td><?php echo esc_html($questify_inquiry->ip_address ?: '-'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('User Agent:', 'questify'); ?></th>
                    <td><small><?php echo esc_html($questify_inquiry->user_agent ?: '-'); ?></small></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Session-ID:', 'questify'); ?></th>
                    <td><code><?php echo esc_html($questify_inquiry->session_id); ?></code></td>
                </tr>
            </table>
        </div>

        <div class="chatbot-inquiry-question">
            <h2><?php esc_html_e('Frage', 'questify'); ?></h2>
            <div class="chatbot-question-box">
                <?php echo nl2br(esc_html($questify_inquiry->user_question)); ?>
            </div>
        </div>

        <?php if ($questify_matched_faq): ?>
        <div class="chatbot-inquiry-match">
            <h2><?php esc_html_e('Gefundene Antwort', 'questify'); ?></h2>
            <div class="chatbot-match-box">
                <h3><?php echo esc_html($questify_matched_faq->question); ?></h3>
                <div><?php echo wp_kses_post($questify_matched_faq->answer); ?></div>
                <p class="chatbot-faq-link">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=questi-faqs&action=edit&faq=' . $questify_matched_faq->id)); ?>">
                        <?php esc_html_e('FAQ bearbeiten →', 'questify'); ?>
                    </a>
                </p>
            </div>
            <?php if ($questify_inquiry->was_helpful !== null): ?>
            <p><strong><?php esc_html_e('Hilfreich:', 'questify'); ?></strong>
                <?php
                echo $questify_inquiry->was_helpful === 'yes'
                    ? '👍 ' . esc_html__('Ja', 'questify')
                    : '👎 ' . esc_html__('Nein', 'questify');
                ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="chatbot-inquiry-status">
            <h2><?php esc_html_e('Status ändern', 'questify'); ?></h2>
            <select id="inquiry-status" data-inquiry-id="<?php echo esc_attr((string) $questify_inquiry->id); ?>">
                <option value="new" <?php selected($questify_inquiry->status, 'new'); ?>><?php esc_html_e('Neu', 'questify'); ?></option>
                <option value="in_progress" <?php selected($questify_inquiry->status, 'in_progress'); ?>><?php esc_html_e('In Bearbeitung', 'questify'); ?></option>
                <option value="answered" <?php selected($questify_inquiry->status, 'answered'); ?>><?php esc_html_e('Beantwortet', 'questify'); ?></option>
            </select>
        </div>

        <div class="chatbot-inquiry-actions">
            <?php
            $questify_mailto_email = sanitize_email($questify_inquiry->user_email);
            $questify_mailto_subject = wp_strip_all_tags('Re: ' . (string) $questify_inquiry->user_question);
            $questify_mailto_url = 'mailto:' . $questify_mailto_email . '?subject=' . rawurlencode($questify_mailto_subject);
            ?>
            <a href="<?php echo esc_url($questify_mailto_url); ?>" class="button button-primary">
                <span class="dashicons dashicons-email"></span> <?php esc_html_e('E-Mail senden', 'questify'); ?>
            </a>
            <button type="button" class="button button-secondary chatbot-delete-inquiry" data-inquiry-id="<?php echo esc_attr((string) $questify_inquiry->id); ?>">
                <span class="dashicons dashicons-trash"></span> <?php esc_html_e('Löschen', 'questify'); ?>
            </button>
        </div>
    </div>

    <p><a href="<?php echo esc_url(admin_url('admin.php?page=questi-inquiries')); ?>">&larr; <?php esc_html_e('Zurück zur Übersicht', 'questify'); ?></a></p>
</div>


