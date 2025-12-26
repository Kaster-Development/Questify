<?php
/**
 * Inquiry Detail View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

$db = Chatbot_Database::get_instance();
$inquiry_id = isset($_GET['inquiry']) ? (int) $_GET['inquiry'] : 0;
$inquiry = $db->get_inquiry($inquiry_id);

if (!$inquiry) {
    wp_die(__('Anfrage nicht gefunden.', 'questify'));
}

$conversations = $db->get_conversations_by_inquiry($inquiry_id);
$matched_faq = $inquiry->matched_faq_id ? $db->get_faq($inquiry->matched_faq_id) : null;
?>

<div class="wrap">
    <h1><?php _e('Anfrage Details', 'questify'); ?> #<?php echo $inquiry->id; ?></h1>

    <div class="chatbot-inquiry-detail">
        <div class="chatbot-inquiry-info">
            <h2><?php _e('Kundendaten', 'questify'); ?></h2>
            <table class="form-table">
                <tr>
                    <th><?php _e('Name:', 'questify'); ?></th>
                    <td><?php echo esc_html($inquiry->user_name); ?></td>
                </tr>
                <tr>
                    <th><?php _e('E-Mail:', 'questify'); ?></th>
                    <td><a href="mailto:<?php echo esc_attr($inquiry->user_email); ?>"><?php echo esc_html($inquiry->user_email); ?></a></td>
                </tr>
                <tr>
                    <th><?php _e('Datum:', 'questify'); ?></th>
                    <td><?php echo date_i18n('d.m.Y H:i:s', strtotime($inquiry->timestamp)); ?> Uhr</td>
                </tr>
                <tr>
                    <th><?php _e('IP-Adresse:', 'questify'); ?></th>
                    <td><?php echo esc_html($inquiry->ip_address ?: '-'); ?></td>
                </tr>
                <tr>
                    <th><?php _e('User Agent:', 'questify'); ?></th>
                    <td><small><?php echo esc_html($inquiry->user_agent ?: '-'); ?></small></td>
                </tr>
                <tr>
                    <th><?php _e('Session-ID:', 'questify'); ?></th>
                    <td><code><?php echo esc_html($inquiry->session_id); ?></code></td>
                </tr>
            </table>
        </div>

        <div class="chatbot-inquiry-question">
            <h2><?php _e('Frage', 'questify'); ?></h2>
            <div class="chatbot-question-box">
                <?php echo nl2br(esc_html($inquiry->user_question)); ?>
            </div>
        </div>

        <?php if ($matched_faq): ?>
        <div class="chatbot-inquiry-match">
            <h2><?php _e('Gefundene Antwort', 'questify'); ?></h2>
            <div class="chatbot-match-box">
                <h3><?php echo esc_html($matched_faq->question); ?></h3>
                <div><?php echo wp_kses_post($matched_faq->answer); ?></div>
                <p class="chatbot-faq-link">
                    <a href="<?php echo admin_url('admin.php?page=chatbot-faqs&action=edit&faq=' . $matched_faq->id); ?>">
                        <?php _e('FAQ bearbeiten →', 'questify'); ?>
                    </a>
                </p>
            </div>
            <?php if ($inquiry->was_helpful !== null): ?>
            <p><strong><?php _e('Hilfreich:', 'questify'); ?></strong>
                <?php echo $inquiry->was_helpful === 'yes' ? '👍 ' . __('Ja', 'questify') : '👎 ' . __('Nein', 'questify'); ?>
            </p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="chatbot-inquiry-status">
            <h2><?php _e('Status ändern', 'questify'); ?></h2>
            <select id="inquiry-status" data-inquiry-id="<?php echo $inquiry->id; ?>">
                <option value="new" <?php selected($inquiry->status, 'new'); ?>><?php _e('Neu', 'questify'); ?></option>
                <option value="in_progress" <?php selected($inquiry->status, 'in_progress'); ?>><?php _e('In Bearbeitung', 'questify'); ?></option>
                <option value="answered" <?php selected($inquiry->status, 'answered'); ?>><?php _e('Beantwortet', 'questify'); ?></option>
            </select>
        </div>

        <div class="chatbot-inquiry-actions">
            <a href="mailto:<?php echo esc_attr($inquiry->user_email); ?>?subject=Re: <?php echo esc_attr($inquiry->user_question); ?>" class="button button-primary">
                <span class="dashicons dashicons-email"></span> <?php _e('E-Mail senden', 'questify'); ?>
            </a>
            <button type="button" class="button button-secondary chatbot-delete-inquiry" data-inquiry-id="<?php echo $inquiry->id; ?>">
                <span class="dashicons dashicons-trash"></span> <?php _e('Löschen', 'questify'); ?>
            </button>
        </div>
    </div>

    <p><a href="<?php echo admin_url('admin.php?page=chatbot-inquiries'); ?>">&larr; <?php _e('Zurück zur Übersicht', 'questify'); ?></a></p>
</div>

<style>
.chatbot-inquiry-detail > div {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
}
.chatbot-question-box,
.chatbot-match-box {
    background: #f9f9f9;
    padding: 15px;
    border-left: 4px solid #0073aa;
    margin: 10px 0;
}
</style>

<script>
jQuery(document).ready(function($) {
    $('#inquiry-status').on('change', function() {
        var inquiryId = $(this).data('inquiry-id');
        var status = $(this).val();

        $.post(ajaxurl, {
            action: 'chatbot_update_inquiry_status',
            nonce: chatbotAdmin.nonce,
            inquiry_id: inquiryId,
            status: status
        }, function(response) {
            if (response.success) {
                alert(response.data.message);
            }
        });
    });

    $('.chatbot-delete-inquiry').on('click', function() {
        if (!confirm('<?php _e('Sind Sie sicher?', 'questify'); ?>')) return;

        var inquiryId = $(this).data('inquiry-id');

        $.post(ajaxurl, {
            action: 'chatbot_delete_inquiry',
            nonce: chatbotAdmin.nonce,
            inquiry_id: inquiryId
        }, function(response) {
            if (response.success) {
                window.location.href = '<?php echo admin_url('admin.php?page=chatbot-inquiries'); ?>';
            }
        });
    });
});
</script>
