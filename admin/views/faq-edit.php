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

$db = Chatbot_Database::get_instance();

// FAQ holen wenn Edit-Modus
$faq_id = isset($_GET['faq']) ? (int) $_GET['faq'] : 0;
$faq = $faq_id > 0 ? $db->get_faq($faq_id) : null;

$is_edit = $faq !== null;
$page_title = $is_edit ? __('FAQ bearbeiten', 'questify') : __('Neue FAQ erstellen', 'questify');
?>

<div class="wrap">
    <h1><?php echo esc_html($page_title); ?></h1>

    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <input type="hidden" name="action" value="chatbot_save_faq">
        <?php wp_nonce_field('chatbot_save_faq', 'chatbot_faq_nonce'); ?>

        <?php if ($is_edit): ?>
        <input type="hidden" name="faq_id" value="<?php echo $faq->id; ?>">
        <?php endif; ?>

        <div id="poststuff">
            <div id="post-body" class="metabox-holder columns-2">
                <!-- Hauptinhalt -->
                <div id="post-body-content">
                    <!-- Frage -->
                    <div class="chatbot-form-group">
                        <label for="question"><?php _e('Frage', 'questify'); ?> <span class="required">*</span></label>
                        <input type="text"
                               id="question"
                               name="question"
                               class="large-text"
                               value="<?php echo $is_edit ? esc_attr($faq->question) : ''; ?>"
                               placeholder="<?php _e('z.B. Was sind Ihre Öffnungszeiten?', 'questify'); ?>"
                               required>
                        <p class="description"><?php _e('Die Frage, die Ihre Kunden stellen könnten (mindestens 10 Zeichen).', 'questify'); ?></p>
                    </div>

                    <!-- Antwort -->
                    <div class="chatbot-form-group">
                        <label for="answer"><?php _e('Antwort', 'questify'); ?> <span class="required">*</span></label>
                        <?php
                        $content = $is_edit ? $faq->answer : '';
                        wp_editor($content, 'answer', [
                            'textarea_name' => 'answer',
                            'textarea_rows' => 10,
                            'media_buttons' => false,
                            'teeny' => true,
                            'quicktags' => true,
                        ]);
                        ?>
                        <p class="description"><?php _e('Die Antwort, die der Chatbot geben soll (mindestens 20 Zeichen).', 'questify'); ?></p>
                    </div>

                    <!-- Keywords -->
                    <div class="chatbot-form-group">
                        <label for="keywords">
                            <?php _e('Keywords (Schlüsselwörter)', 'questify'); ?>
                            <button type="button" id="generate-keywords-btn" class="button button-secondary" style="margin-left: 10px;">
                                <span class="dashicons dashicons-update-alt" style="margin-top: 3px;"></span>
                                <?php _e('Keywords automatisch generieren', 'questify'); ?>
                            </button>
                        </label>
                        <textarea id="keywords"
                                  name="keywords"
                                  rows="3"
                                  class="large-text"
                                  placeholder="<?php _e('z.B. öffnungszeiten, business hours, wann offen', 'questify'); ?>"><?php echo $is_edit ? esc_textarea($faq->keywords) : ''; ?></textarea>
                        <p class="description">
                            <?php _e('Alternative Suchbegriffe, durch Komma getrennt. Diese helfen beim Matching der Fragen.', 'questify'); ?>
                            <br>
                            <strong><?php _e('💡 Tipp:', 'questify'); ?></strong>
                            <?php _e('Lassen Sie das Feld leer - Keywords werden automatisch beim Speichern generiert! Oder klicken Sie auf "Keywords automatisch generieren" für eine Vorschau.', 'questify'); ?>
                        </p>
                    </div>
                </div>

                <!-- Sidebar -->
                <div id="postbox-container-1" class="postbox-container">
                    <!-- Veröffentlichen-Box -->
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2><?php _e('Veröffentlichen', 'questify'); ?></h2>
                        </div>
                        <div class="inside">
                            <div class="submitbox">
                                <!-- Status -->
                                <div class="misc-pub-section">
                                    <label>
                                        <input type="checkbox"
                                               name="active"
                                               value="1"
                                               <?php checked($is_edit ? $faq->active : 1, 1); ?>>
                                        <?php _e('Aktiv (im Chatbot verwenden)', 'questify'); ?>
                                    </label>
                                </div>

                                <?php if ($is_edit): ?>
                                <!-- Statistiken -->
                                <div class="misc-pub-section">
                                    <strong><?php _e('Aufrufe:', 'questify'); ?></strong>
                                    <?php echo number_format_i18n($faq->view_count); ?>
                                </div>
                                <div class="misc-pub-section">
                                    <strong><?php _e('Erstellt:', 'questify'); ?></strong>
                                    <?php echo date_i18n('d.m.Y H:i', strtotime($faq->created_at)); ?>
                                </div>
                                <div class="misc-pub-section">
                                    <strong><?php _e('Aktualisiert:', 'questify'); ?></strong>
                                    <?php echo date_i18n('d.m.Y H:i', strtotime($faq->updated_at)); ?>
                                </div>
                                <?php endif; ?>

                                <!-- Buttons -->
                                <div id="major-publishing-actions">
                                    <div id="delete-action">
                                        <?php if ($is_edit): ?>
                                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=chatbot-faqs&action=delete&faq=' . $faq->id), 'delete-faq-' . $faq->id); ?>"
                                           class="submitdelete deletion"
                                           onclick="return confirm('<?php _e('Sind Sie sicher, dass Sie diese FAQ löschen möchten?', 'questify'); ?>');">
                                            <?php _e('Löschen', 'questify'); ?>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                    <div id="publishing-action">
                                        <button type="submit" class="button button-primary button-large">
                                            <span class="dashicons dashicons-saved"></span>
                                            <?php echo $is_edit ? __('Aktualisieren', 'questify') : __('Veröffentlichen', 'questify'); ?>
                                        </button>
                                        <button type="submit" name="save_and_new" class="button button-secondary">
                                            <?php _e('Speichern & Neu', 'questify'); ?>
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
                            <h2><?php _e('💡 Tipps', 'questify'); ?></h2>
                        </div>
                        <div class="inside">
                            <ul>
                                <li><?php _e('Formulieren Sie Fragen so, wie Ihre Kunden sie stellen würden.', 'questify'); ?></li>
                                <li><?php _e('Verwenden Sie klare, verständliche Antworten.', 'questify'); ?></li>
                                <li><?php _e('Keywords verbessern die Trefferquote erheblich.', 'questify'); ?></li>
                                <li><?php _e('HTML ist in der Antwort erlaubt (Links, Listen, etc.).', 'questify'); ?></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <p class="chatbot-back-link">
        <a href="<?php echo admin_url('admin.php?page=chatbot-faqs'); ?>">
            &larr; <?php _e('Zurück zur Übersicht', 'questify'); ?>
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
            alert('<?php _e('Bitte geben Sie zuerst eine Frage ein.', 'questify'); ?>');
            return;
        }

        // Button deaktivieren
        button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> <?php _e('Generiere...', 'questify'); ?>');

        // AJAX-Request
        $.post(ajaxurl, {
            action: 'chatbot_generate_keywords',
            nonce: chatbotAdmin.nonce,
            question: question,
            answer: answer
        }, function(response) {
            button.prop('disabled', false).html(originalText);

            if (response.success) {
                // Keywords in Textfeld einfügen
                var currentKeywords = $('#keywords').val().trim();
                var newKeywords = response.data.keywords;

                if (currentKeywords) {
                    // Bestehende Keywords mit neuen zusammenführen
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
                alert(response.data.message || chatbotAdmin.strings.error);
            }
        }).fail(function() {
            button.prop('disabled', false).html(originalText);
            alert(chatbotAdmin.strings.error);
        });
    });
});

// CSS für Spin-Animation
var style = document.createElement('style');
style.innerHTML = '@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }';
document.head.appendChild(style);
</script>
