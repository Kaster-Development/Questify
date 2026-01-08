<?php
/**
 * FAQ Edit View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

// FAQ holen wenn Edit-Modus
$questify_db = Chatbot_Database::get_instance();
$questify_faq_id = isset($_GET['faq']) ? absint(wp_unslash($_GET['faq'])) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only item selection.
$questify_faq = $questify_faq_id > 0 ? $questify_db->get_faq($questify_faq_id) : null;

$questify_is_edit = $questify_faq !== null;
$questify_page_title = $questify_is_edit ? __('FAQ bearbeiten', 'questify') : __('Neue FAQ erstellen', 'questify');
?>

<div class="wrap">
    <h1><?php echo esc_html($questify_page_title); ?></h1>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="questi_save_faq">
        <?php wp_nonce_field('questi_save_faq', 'questi_faq_nonce'); ?>

        <?php if ($questify_is_edit): ?>
        <input type="hidden" name="faq_id" value="<?php echo esc_attr($questify_faq->id); ?>">
        <?php endif; ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Hauptinhalt -->
                <div id="post-body-content">
                    <!-- Frage -->
                    <div class="chatbot-form-group">
                           <label for="question"><?php esc_html_e('Frage', 'questify'); ?> <span class="required">*</span></label>
                        <input type="text"
                               id="question"
                               name="question"
                               class="large-text"
                               value="<?php echo $questify_is_edit ? esc_attr($questify_faq->question) : ''; ?>"
                               placeholder="<?php echo esc_attr__('z.B. Was sind Ihre ü–ffnungszeiten?', 'questify'); ?>"
                               required>
                           <p class="description"><?php esc_html_e('Die Frage, die Ihre Kunden stellen kü¶nnten (mindestens 10 Zeichen).', 'questify'); ?></p>
                    </div>

                    <!-- Antwort -->
                    <div class="chatbot-form-group">
                        <label for="answer"><?php esc_html_e('Antwort', 'questify'); ?> <span class="required">*</span></label>
                        <?php
                        $questify_content = $questify_is_edit ? $questify_faq->answer : '';
                        wp_editor($questify_content, 'answer', [
                            'textarea_name' => 'answer',
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                            'teeny' => true,
                            'quicktags' => true,
                        ]);
                        ?>
                        <p class="description"><?php esc_html_e('Die Antwort, die der Chatbot geben soll (mindestens 20 Zeichen).', 'questify'); ?></p>
                    </div>

                    <!-- Keywords -->
                    <div class="chatbot-form-group">
                        <label for="keywords">
                            <?php esc_html_e('Keywords (Schlü¼sselwü¶rter)', 'questify'); ?>
                            <button type="button" id="generate-keywords-btn" class="button button-secondary" style="margin-left: 10px;">
                                <span class="dashicons dashicons-update-alt" style="margin-top: 3px;"></span>
                                <?php esc_html_e('Keywords automatisch generieren', 'questify'); ?>
                            </button>
                        </label>
                        <textarea id="keywords"
                                  name="keywords"
                                  rows="3"
                                  class="large-text"
                                  placeholder="<?php echo esc_attr__('z.B. ü¶ffnungszeiten, business hours, wann offen', 'questify'); ?>"><?php echo $questify_is_edit ? esc_textarea($questify_faq->keywords) : ''; ?></textarea>
                        <p class="description">
                            <?php esc_html_e('Alternative Suchbegriffe, durch Komma getrennt. Diese helfen beim Matching der Fragen.', 'questify'); ?>
                            <br>
                            <strong><?php esc_html_e('ðŸ’¡ Tipp:', 'questify'); ?></strong>
                            <?php esc_html_e('Lassen Sie das Feld leer - Keywords werden automatisch beim Speichern generiert! Oder klicken Sie auf "Keywords automatisch generieren" fü¼r eine Vorschau.', 'questify'); ?>
                        </p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <!-- Verü¶ffentlichen-Box -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2><?php esc_html_e('Verü¶ffentlichen', 'questify'); ?></h2>
                        </div>
                        <div class="inside">
                            <div class="submitbox">
                                <!-- Status -->
                                <div class="misc-pub-section">
                                    <label>
                                        <input type="checkbox"
                                               name="active"
                                               value="1"
                                               <?php checked($questify_is_edit ? $questify_faq->active : 1, 1); ?>>
                                        <?php esc_html_e('Aktiv (im Chatbot verwenden)', 'questify'); ?>
                                    </label>
                                </div>

                                <?php if ($questify_is_edit): ?>
                                <!-- Statistiken -->
                                <div class="misc-pub-section">
                                    <strong><?php esc_html_e('Aufrufe:', 'questify'); ?></strong>
                                    <?php echo esc_html(number_format_i18n($questify_faq->view_count)); ?>
                                </div>
                                <div class="misc-pub-section">
                                    <strong><?php esc_html_e('Erstellt:', 'questify'); ?></strong>
                                    <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($questify_faq->created_at))); ?>
                                </div>
                                <div class="misc-pub-section">
                                    <strong><?php esc_html_e('Aktualisiert:', 'questify'); ?></strong>
                                    <?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($questify_faq->updated_at))); ?>
                                </div>
                                <?php endif; ?>

                                <!-- Buttons -->
                                <div id="major-publishing-actions">
                                    <div id="delete-action">
                                        <?php if ($questify_is_edit): ?>
                                        <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=questi-faqs&action=delete&faq=' . $questify_faq->id), 'delete-faq-' . $questify_faq->id)); ?>"
                                           class="submitdelete deletion"
                                           onclick="return confirm('<?php echo esc_js(__('Sind Sie sicher, dass Sie diese FAQ lü¶schen mü¶chten?', 'questify')); ?>');">
                                            <?php esc_html_e('Lü¶schen', 'questify'); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div id="publishing-action">
                                        <button type="submit" class="button button-primary button-large">
                                            <span class="dashicons dashicons-saved"></span>
                                            <?php echo esc_html($questify_is_edit ? __('Aktualisieren', 'questify') : __('Verü¶ffentlichen', 'questify')); ?>
                                        </button>
                                        <button type="submit" name="save_and_new" class="button button-secondary">
                                            <?php esc_html_e('Speichern & Neu', 'questify'); ?>
                                        </button>
                                    </div>
                                    <div class="clear"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Hilfe-Box -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2><?php esc_html_e('ðŸ’¡ Tipps', 'questify'); ?></h2>
                        </div>
                        <div class="inside">
                            <ul>
                                <li><?php esc_html_e('Formulieren Sie Fragen so, wie Ihre Kunden sie stellen wü¼rden.', 'questify'); ?></li>
                                <li><?php esc_html_e('Verwenden Sie klare, verstü¤ndliche Antworten.', 'questify'); ?></li>
                                <li><?php esc_html_e('Keywords verbessern die Trefferquote erheblich.', 'questify'); ?></li>
                                <li><?php esc_html_e('HTML ist in der Antwort erlaubt (Links, Listen, etc.).', 'questify'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <p class="chatbot-back-link">
        <a href="<?php echo esc_url(admin_url('admin.php?page=questi-faqs')); ?>">
            &larr; <?php esc_html_e('Zurü¼ck zur üœbersicht', 'questify'); ?>
        </a>
    </p>
</div>

<style>
.chatbot-form-group {
    margin-bottom: 25px;
}
.chatbot-form-group label {
    display: block;
    font-weight: 600;
    margin-bottom: 8px;
}
.chatbot-form-group .required {
    color: #d63638;
}
.chatbot-back-link {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #ddd;
}
#publishing-action .button-primary .dashicons {
    margin-top: 4px;
    margin-right: 5px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Keywords automatisch generieren
    $('#generate-keywords-btn').on('click', function() {
        var button = $(this);
        var originalText = button.html();

        // Frage und Antwort holen
        var question = $('#question').val().trim();
        var answer = '';

        // WordPress-Editor-Content holen (verschiedene Methoden je nach Editor-Typ)
        if (typeof tinymce !== 'undefined' && tinymce.get('answer')) {
            answer = tinymce.get('answer').getContent();
        } else if ($('#answer').length) {
            answer = $('#answer').val();
        }

        if (!question) {
            alert('<?php echo esc_js(__('Bitte geben Sie zuerst eine Frage ein.', 'questify')); ?>');
            return;
        }

        // Button deaktivieren
        button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> <?php echo esc_js(__('Generiere...', 'questify')); ?>');

        // AJAX-Request
        $.post(ajaxurl, {
            action: 'questi_generate_keywords',
            nonce: questiAdmin.nonce,
            question: question,
            answer: answer
        }, function(response) {
            button.prop('disabled', false).html(originalText);

            if (response.success) {
                // Keywords in Textfeld einfü¼gen
                var currentKeywords = $('#keywords').val().trim();
                var newKeywords = response.data.keywords;

                if (currentKeywords) {
                    // Bestehende Keywords mit neuen zusammenfü¼hren
                    var combined = currentKeywords + ', ' + newKeywords;
                    // Duplikate entfernen
                    var keywordArray = combined.split(',').map(function(k) { return k.trim(); });
                    keywordArray = keywordArray.filter(function(item, pos, self) {
                        return item && self.indexOf(item) === pos;
                    });
                    $('#keywords').val(keywordArray.join(', '));
                } else {
                    $('#keywords').val(newKeywords);
                }

                // Erfolgsmeldung
                var successMsg = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p>' + response.data.message + '</p></div>');
                $('#keywords').after(successMsg);
                setTimeout(function() {
                    successMsg.fadeOut(function() { $(this).remove(); });
                }, 3000);
            } else {
                alert(response.data.message || questiAdmin.strings.error);
            }
        }).fail(function() {
            button.prop('disabled', false).html(originalText);
            alert(questiAdmin.strings.error);
        });
    });
});

// CSS fü¼r Spin-Animation
var style = document.createElement('style');
style.innerHTML = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>


