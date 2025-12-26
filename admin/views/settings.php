<?php
/**
 * Settings View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Helper-Funktion: Gibt Option-Wert oder Standardwert zurück
function get_chatbot_option($option_name, $default = '') {
    $value = get_option($option_name, $default);

    // Wenn Wert leer ist (leerer String), Standardwert zurückgeben
    if ($value === '' || $value === false) {
        return $default;
    }

    return $value;
}

// Standardwerte definieren
$defaults = [
    'chatbot_enabled' => true,
    'chatbot_welcome_message' => 'Hallo! 😊 Wie kann ich Ihnen helfen?',
    'chatbot_placeholder_text' => 'Stellen Sie Ihre Frage...',
    'chatbot_no_answer_message' => 'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?',
    'chatbot_thank_you_message' => 'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.',
    'chatbot_history_mode' => 'manual',
    'chatbot_position' => 'right',
    'chatbot_primary_color' => '#0073aa',
    'chatbot_header_color' => '#0073aa',
    'chatbot_header_text_color' => '#ffffff',
    'chatbot_title' => 'FAQ Chatbot',
    'chatbot_button_text' => 'Fragen?',
    'chatbot_size' => 'medium',
    'chatbot_text_color' => '#333333',
    'chatbot_user_text_color' => '#ffffff',
    'chatbot_font_family' => 'system',
    'chatbot_font_size' => '14px',
    'chatbot_notification_emails' => get_option('admin_email'),
    'chatbot_email_prefix' => '[Chatbot]',
    'chatbot_gdpr_checkbox' => true,
    'chatbot_gdpr_text' => 'Ich akzeptiere die Datenschutzerklärung.',
    'chatbot_ip_anonymize_days' => 30,
    'chatbot_auto_embed' => true,
    'chatbot_exclude_pages' => [],
    'chatbot_debug_mode' => false,
    'chatbot_min_score' => 60,
    'chatbot_fuzzy_matching' => true,
    'chatbot_levenshtein_threshold' => 3,
    'chatbot_stopwords' => 'der, die, das, und, oder, aber, ist, sind, haben, sein, ein, eine, mit, von, zu, für, auf, an, in, aus',
];

// Tab-Handling
$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

// Einstellungen speichern
if (isset($_POST['chatbot_save_settings'])) {
    check_admin_referer('chatbot_settings');

    if (current_user_can('manage_options')) {
        // Nur die Felder des aktuellen Tabs speichern
        $current_tab = sanitize_text_field($_POST['current_tab'] ?? 'general');

        if ($current_tab === 'general') {
            // Allgemein
            update_option('chatbot_enabled', isset($_POST['chatbot_enabled']));
            if (isset($_POST['chatbot_welcome_message'])) {
                update_option('chatbot_welcome_message', sanitize_textarea_field($_POST['chatbot_welcome_message']));
            }
            if (isset($_POST['chatbot_placeholder_text'])) {
                update_option('chatbot_placeholder_text', sanitize_text_field($_POST['chatbot_placeholder_text']));
            }
            if (isset($_POST['chatbot_no_answer_message'])) {
                update_option('chatbot_no_answer_message', sanitize_textarea_field($_POST['chatbot_no_answer_message']));
            }
            if (isset($_POST['chatbot_thank_you_message'])) {
                update_option('chatbot_thank_you_message', sanitize_textarea_field($_POST['chatbot_thank_you_message']));
            }
            if (isset($_POST['chatbot_history_mode'])) {
                update_option('chatbot_history_mode', sanitize_text_field($_POST['chatbot_history_mode']));
            }
        }

        if ($current_tab === 'design') {
            // Design
            if (isset($_POST['chatbot_position'])) {
                update_option('chatbot_position', sanitize_text_field($_POST['chatbot_position']));
            }
            if (isset($_POST['chatbot_primary_color'])) {
                update_option('chatbot_primary_color', sanitize_hex_color($_POST['chatbot_primary_color']));
            }
            if (isset($_POST['chatbot_header_color'])) {
                update_option('chatbot_header_color', sanitize_hex_color($_POST['chatbot_header_color']));
            }
            if (isset($_POST['chatbot_header_text_color'])) {
                update_option('chatbot_header_text_color', sanitize_hex_color($_POST['chatbot_header_text_color']));
            }
            if (isset($_POST['chatbot_title'])) {
                update_option('chatbot_title', sanitize_text_field($_POST['chatbot_title']));
            }
            if (isset($_POST['chatbot_button_text'])) {
                update_option('chatbot_button_text', sanitize_text_field($_POST['chatbot_button_text']));
            }
            if (isset($_POST['chatbot_size'])) {
                update_option('chatbot_size', sanitize_text_field($_POST['chatbot_size']));
            }
            if (isset($_POST['chatbot_text_color'])) {
                update_option('chatbot_text_color', sanitize_hex_color($_POST['chatbot_text_color']));
            }
            if (isset($_POST['chatbot_user_text_color'])) {
                update_option('chatbot_user_text_color', sanitize_hex_color($_POST['chatbot_user_text_color']));
            }
            if (isset($_POST['chatbot_font_family'])) {
                update_option('chatbot_font_family', sanitize_text_field($_POST['chatbot_font_family']));
            }
            if (isset($_POST['chatbot_font_size'])) {
                update_option('chatbot_font_size', sanitize_text_field($_POST['chatbot_font_size']));
            }
        }

        if ($current_tab === 'email') {
            // E-Mail
            if (isset($_POST['chatbot_notification_emails'])) {
                update_option('chatbot_notification_emails', sanitize_text_field($_POST['chatbot_notification_emails']));
            }
            if (isset($_POST['chatbot_email_prefix'])) {
                update_option('chatbot_email_prefix', sanitize_text_field($_POST['chatbot_email_prefix']));
            }
            if (isset($_POST['chatbot_email_format'])) {
                update_option('chatbot_email_format', sanitize_text_field($_POST['chatbot_email_format']));
            }
            update_option('chatbot_email_include_ip', isset($_POST['chatbot_email_include_ip']));
            update_option('chatbot_email_include_faq', isset($_POST['chatbot_email_include_faq']));
            update_option('chatbot_email_include_user_agent', isset($_POST['chatbot_email_include_user_agent']));
            if (isset($_POST['chatbot_email_header_color'])) {
                update_option('chatbot_email_header_color', sanitize_hex_color($_POST['chatbot_email_header_color']));
            }
            if (isset($_POST['chatbot_email_footer_color'])) {
                update_option('chatbot_email_footer_color', sanitize_hex_color($_POST['chatbot_email_footer_color']));
            }
            // Firmeninformationen
            if (isset($_POST['chatbot_company_name'])) {
                update_option('chatbot_company_name', sanitize_text_field($_POST['chatbot_company_name']));
            }
            if (isset($_POST['chatbot_company_address'])) {
                update_option('chatbot_company_address', sanitize_textarea_field($_POST['chatbot_company_address']));
            }
            if (isset($_POST['chatbot_company_phone'])) {
                update_option('chatbot_company_phone', sanitize_text_field($_POST['chatbot_company_phone']));
            }
            if (isset($_POST['chatbot_company_website'])) {
                update_option('chatbot_company_website', esc_url_raw($_POST['chatbot_company_website']));
            }
            if (isset($_POST['chatbot_feedback_email'])) {
                update_option('chatbot_feedback_email', sanitize_email($_POST['chatbot_feedback_email']));
            }
        }

        if ($current_tab === 'advanced') {
            // Erweitert
            update_option('chatbot_gdpr_checkbox', isset($_POST['chatbot_gdpr_checkbox']));
            if (isset($_POST['chatbot_gdpr_text'])) {
                update_option('chatbot_gdpr_text', sanitize_textarea_field($_POST['chatbot_gdpr_text']));
            }
            if (isset($_POST['chatbot_ip_anonymize_days'])) {
                update_option('chatbot_ip_anonymize_days', (int) $_POST['chatbot_ip_anonymize_days']);
            }
            update_option('chatbot_auto_embed', isset($_POST['chatbot_auto_embed']));
            update_option('chatbot_debug_mode', isset($_POST['chatbot_debug_mode']));
        }

        if ($current_tab === 'matching') {
            // Matching
            if (isset($_POST['chatbot_min_score'])) {
                update_option('chatbot_min_score', (int) $_POST['chatbot_min_score']);
            }
            update_option('chatbot_fuzzy_matching', isset($_POST['chatbot_fuzzy_matching']));
            if (isset($_POST['chatbot_levenshtein_threshold'])) {
                update_option('chatbot_levenshtein_threshold', (int) $_POST['chatbot_levenshtein_threshold']);
            }
            if (isset($_POST['chatbot_stopwords'])) {
                update_option('chatbot_stopwords', sanitize_textarea_field($_POST['chatbot_stopwords']));
            }
        }

        echo '<div class="notice notice-success"><p>' . __('Einstellungen gespeichert.', 'questify') . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=chatbot-settings&tab=general" class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php _e('Allgemein', 'questify'); ?></a>
        <a href="?page=chatbot-settings&tab=design" class="nav-tab <?php echo $active_tab === 'design' ? 'nav-tab-active' : ''; ?>"><?php _e('Design', 'questify'); ?></a>
        <a href="?page=chatbot-settings&tab=email" class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>"><?php _e('E-Mail', 'questify'); ?></a>
        <a href="?page=chatbot-settings&tab=advanced" class="nav-tab <?php echo $active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php _e('Erweitert', 'questify'); ?></a>
        <a href="?page=chatbot-settings&tab=matching" class="nav-tab <?php echo $active_tab === 'matching' ? 'nav-tab-active' : ''; ?>"><?php _e('Matching', 'questify'); ?></a>
    </h2>

    <form method="post" action="">
        <?php wp_nonce_field('chatbot_settings'); ?>
        <input type="hidden" name="current_tab" value="<?php echo esc_attr($active_tab); ?>">

        <?php if ($active_tab === 'general'): ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('Chatbot aktivieren', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="chatbot_enabled" value="1" <?php checked(get_chatbot_option('chatbot_enabled', $defaults['chatbot_enabled'])); ?>> <?php _e('Chatbot auf der Website anzeigen', 'questify'); ?></label></td>
                </tr>
                <tr>
                    <th><?php _e('Begrüßungstext', 'questify'); ?></th>
                    <td><textarea name="chatbot_welcome_message" rows="3" class="large-text"><?php echo esc_textarea(get_chatbot_option('chatbot_welcome_message', $defaults['chatbot_welcome_message'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php _e('Platzhalter Eingabefeld', 'questify'); ?></th>
                    <td><input type="text" name="chatbot_placeholder_text" value="<?php echo esc_attr(get_chatbot_option('chatbot_placeholder_text', $defaults['chatbot_placeholder_text'])); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php _e('Nachricht bei fehlender Antwort', 'questify'); ?></th>
                    <td><textarea name="chatbot_no_answer_message" rows="3" class="large-text"><?php echo esc_textarea(get_chatbot_option('chatbot_no_answer_message', $defaults['chatbot_no_answer_message'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php _e('Dankes-Nachricht', 'questify'); ?></th>
                    <td><textarea name="chatbot_thank_you_message" rows="3" class="large-text"><?php echo esc_textarea(get_chatbot_option('chatbot_thank_you_message', $defaults['chatbot_thank_you_message'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php _e('Chat-Verlauf Verhalten', 'questify'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="chatbot_history_mode" value="manual" <?php checked(get_chatbot_option('chatbot_history_mode', $defaults['chatbot_history_mode']), 'manual'); ?>>
                            <?php _e('Papierkorb-Button anzeigen (Benutzer löscht manuell)', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="chatbot_history_mode" value="auto" <?php checked(get_chatbot_option('chatbot_history_mode', $defaults['chatbot_history_mode']), 'auto'); ?>>
                            <?php _e('Automatisch bei jedem Seitenaufruf löschen', 'questify'); ?>
                        </label>
                        <p class="description">
                            <?php _e('Papierkorb: Chat-Verlauf bleibt gespeichert, Benutzer kann ihn mit Button löschen.<br>Automatisch: Chat-Verlauf wird bei jedem Neuladen der Seite gelöscht.', 'questify'); ?>
                        </p>
                    </td>
                </tr>
            </table>

        <?php elseif ($active_tab === 'design'): ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('Chat-Position', 'questify'); ?></th>
                    <td>
                        <label><input type="radio" name="chatbot_position" value="right" <?php checked(get_chatbot_option('chatbot_position', $defaults['chatbot_position']), 'right'); ?>> <?php _e('Rechts unten', 'questify'); ?></label><br>
                        <label><input type="radio" name="chatbot_position" value="left" <?php checked(get_chatbot_option('chatbot_position', $defaults['chatbot_position']), 'left'); ?>> <?php _e('Links unten', 'questify'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Primärfarbe', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_primary_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_primary_color', $defaults['chatbot_primary_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php _e('Farbe für Buttons und Akzente', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Header-Farbe', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_header_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_header_color', $defaults['chatbot_primary_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php _e('Hintergrundfarbe des Chat-Headers', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Header-Textfarbe', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_header_text_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_header_text_color', $defaults['chatbot_header_text_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php _e('Textfarbe des Chat-Titels im Header', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Chat-Titel', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_title" value="<?php echo esc_attr(get_chatbot_option('chatbot_title', $defaults['chatbot_title'])); ?>" class="regular-text">
                        <p class="description"><?php _e('Titel im Chat-Header', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Button-Text', 'questify'); ?></th>
                    <td><input type="text" name="chatbot_button_text" value="<?php echo esc_attr(get_chatbot_option('chatbot_button_text', $defaults['chatbot_button_text'])); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php _e('Chat-Größe', 'questify'); ?></th>
                    <td>
                        <select name="chatbot_size">
                            <option value="small" <?php selected(get_chatbot_option('chatbot_size', $defaults['chatbot_size']), 'small'); ?>><?php _e('Klein (300x400px)', 'questify'); ?></option>
                            <option value="medium" <?php selected(get_chatbot_option('chatbot_size', $defaults['chatbot_size']), 'medium'); ?>><?php _e('Mittel (350x500px)', 'questify'); ?></option>
                            <option value="large" <?php selected(get_chatbot_option('chatbot_size', $defaults['chatbot_size']), 'large'); ?>><?php _e('Groß (400x600px)', 'questify'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Textfarbe (Bot-Nachrichten)', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_text_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_text_color', $defaults['chatbot_text_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php _e('Farbe des Textes in Bot-Nachrichten', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Textfarbe (User-Nachrichten)', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_user_text_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_user_text_color', $defaults['chatbot_user_text_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php _e('Farbe des Textes in User-Nachrichten', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Schriftart', 'questify'); ?></th>
                    <td>
                        <select name="chatbot_font_family">
                            <option value="system" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), 'system'); ?>><?php _e('System Standard', 'questify'); ?></option>
                            <option value="Arial, sans-serif" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), 'Arial, sans-serif'); ?>>Arial</option>
                            <option value="Helvetica, sans-serif" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), 'Helvetica, sans-serif'); ?>>Helvetica</option>
                            <option value="'Segoe UI', sans-serif" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), "'Segoe UI', sans-serif"); ?>>Segoe UI</option>
                            <option value="Verdana, sans-serif" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), 'Verdana, sans-serif'); ?>>Verdana</option>
                            <option value="Georgia, serif" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), 'Georgia, serif'); ?>>Georgia</option>
                            <option value="'Times New Roman', serif" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), "'Times New Roman', serif"); ?>>Times New Roman</option>
                            <option value="'Courier New', monospace" <?php selected(get_chatbot_option('chatbot_font_family', $defaults['chatbot_font_family']), "'Courier New', monospace"); ?>>Courier New</option>
                        </select>
                        <p class="description"><?php _e('Schriftart für den gesamten Chat', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Schriftgröße', 'questify'); ?></th>
                    <td>
                        <select name="chatbot_font_size">
                            <option value="12px" <?php selected(get_chatbot_option('chatbot_font_size', $defaults['chatbot_font_size']), '12px'); ?>>12px (Klein)</option>
                            <option value="14px" <?php selected(get_chatbot_option('chatbot_font_size', $defaults['chatbot_font_size']), '14px'); ?>>14px (Standard)</option>
                            <option value="16px" <?php selected(get_chatbot_option('chatbot_font_size', $defaults['chatbot_font_size']), '16px'); ?>>16px (Groß)</option>
                            <option value="18px" <?php selected(get_chatbot_option('chatbot_font_size', $defaults['chatbot_font_size']), '18px'); ?>>18px (Sehr Groß)</option>
                        </select>
                        <p class="description"><?php _e('Größe der Schrift im Chat', 'questify'); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ($active_tab === 'email'): ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('Benachrichtigungs-E-Mail(s)', 'questify'); ?></th>
                    <td>
                        <input type="text" name="chatbot_notification_emails" value="<?php echo esc_attr(get_chatbot_option('chatbot_notification_emails', $defaults['chatbot_notification_emails'])); ?>" class="regular-text">
                        <p class="description"><?php _e('Mehrere E-Mail-Adressen durch Komma getrennt', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('E-Mail-Betreff-Präfix', 'questify'); ?></th>
                    <td><input type="text" name="chatbot_email_prefix" value="<?php echo esc_attr(get_chatbot_option('chatbot_email_prefix', $defaults['chatbot_email_prefix'])); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php _e('E-Mail-Format', 'questify'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="chatbot_email_format" value="html" <?php checked(get_chatbot_option('chatbot_email_format', 'html'), 'html'); ?>>
                            <?php _e('HTML (mit Design)', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="chatbot_email_format" value="plain" <?php checked(get_chatbot_option('chatbot_email_format', 'html'), 'plain'); ?>>
                            <?php _e('Plain Text (nur Text)', 'questify'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('E-Mail-Informationen', 'questify'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="chatbot_email_include_ip" value="1" <?php checked(get_chatbot_option('chatbot_email_include_ip', true)); ?>>
                            <?php _e('IP-Adresse anzeigen', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="chatbot_email_include_faq" value="1" <?php checked(get_chatbot_option('chatbot_email_include_faq', true)); ?>>
                            <?php _e('Gefundene FAQ-Antwort anzeigen', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="chatbot_email_include_user_agent" value="1" <?php checked(get_chatbot_option('chatbot_email_include_user_agent', false)); ?>>
                            <?php _e('Browser-Info (User Agent) anzeigen', 'questify'); ?>
                        </label>
                        <p class="description"><?php _e('Wählen Sie, welche Informationen in der Benachrichtigungs-E-Mail enthalten sein sollen.', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('E-Mail-Design', 'questify'); ?></th>
                    <td>
                        <p>
                            <label style="display: block; margin-bottom: 10px;">
                                <?php _e('Header-Farbe:', 'questify'); ?>
                                <input type="text" name="chatbot_email_header_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_email_header_color', '#0073aa')); ?>" class="chatbot-color-picker">
                            </label>
                        </p>
                        <p>
                            <label style="display: block; margin-bottom: 10px;">
                                <?php _e('Footer-Farbe:', 'questify'); ?>
                                <input type="text" name="chatbot_email_footer_color" value="<?php echo esc_attr(get_chatbot_option('chatbot_email_footer_color', '#f4f4f4')); ?>" class="chatbot-color-picker">
                            </label>
                        </p>
                        <p class="description"><?php _e('Farben für Header und Footer in HTML-E-Mails', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Test-E-Mail senden', 'questify'); ?></th>
                    <td><button type="button" id="send-test-email" class="button"><?php _e('Test-E-Mail senden', 'questify'); ?></button></td>
                </tr>
            </table>

        <?php elseif ($active_tab === 'advanced'): ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('DSGVO', 'questify'); ?></th>
                    <td>
                        <label><input type="checkbox" name="chatbot_gdpr_checkbox" value="1" <?php checked(get_chatbot_option('chatbot_gdpr_checkbox', $defaults['chatbot_gdpr_checkbox'])); ?>> <?php _e('DSGVO-Checkbox anzeigen', 'questify'); ?></label>
                        <p><textarea name="chatbot_gdpr_text" rows="2" class="large-text"><?php echo esc_textarea(get_chatbot_option('chatbot_gdpr_text', $defaults['chatbot_gdpr_text'])); ?></textarea></p>
                        <p><label><?php _e('IP-Anonymisierung nach', 'questify'); ?> <input type="number" name="chatbot_ip_anonymize_days" value="<?php echo esc_attr(get_chatbot_option('chatbot_ip_anonymize_days', $defaults['chatbot_ip_anonymize_days'])); ?>" min="1" max="365" style="width: 60px;"> <?php _e('Tagen', 'questify'); ?></label></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Einbindung', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="chatbot_auto_embed" value="1" <?php checked(get_chatbot_option('chatbot_auto_embed', $defaults['chatbot_auto_embed'])); ?>> <?php _e('Automatisch auf allen Seiten einbinden', 'questify'); ?></label></td>
                </tr>
                <tr>
                    <th><?php _e('Debug-Modus', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="chatbot_debug_mode" value="1" <?php checked(get_chatbot_option('chatbot_debug_mode', $defaults['chatbot_debug_mode'])); ?>> <?php _e('Debug-Informationen im Error-Log speichern', 'questify'); ?></label></td>
                </tr>
            </table>

        <?php elseif ($active_tab === 'matching'): ?>
            <table class="form-table">
                <tr>
                    <th><?php _e('Mindest-Score', 'questify'); ?></th>
                    <td>
                        <input type="range" name="chatbot_min_score" min="0" max="100" value="<?php echo esc_attr(get_chatbot_option('chatbot_min_score', $defaults['chatbot_min_score'])); ?>" id="min-score-range">
                        <span id="min-score-value"><?php echo esc_html(get_chatbot_option('chatbot_min_score', $defaults['chatbot_min_score'])); ?></span>
                        <p class="description"><?php _e('Minimale Übereinstimmung für eine Antwort (0-100)', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php _e('Fuzzy-Matching', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="chatbot_fuzzy_matching" value="1" <?php checked(get_chatbot_option('chatbot_fuzzy_matching', $defaults['chatbot_fuzzy_matching'])); ?>> <?php _e('Fuzzy-Matching aktivieren', 'questify'); ?></label></td>
                </tr>
                <tr>
                    <th><?php _e('Levenshtein-Distanz', 'questify'); ?></th>
                    <td><input type="number" name="chatbot_levenshtein_threshold" value="<?php echo esc_attr(get_chatbot_option('chatbot_levenshtein_threshold', $defaults['chatbot_levenshtein_threshold'])); ?>" min="1" max="10" style="width: 60px;"></td>
                </tr>
                <tr>
                    <th><?php _e('Stoppwörter', 'questify'); ?></th>
                    <td><textarea name="chatbot_stopwords" rows="4" class="large-text"><?php echo esc_textarea(get_chatbot_option('chatbot_stopwords', $defaults['chatbot_stopwords'])); ?></textarea>
                    <p class="description"><?php _e('Durch Komma getrennt', 'questify'); ?></p></td>
                </tr>
            </table>
        <?php endif; ?>

        <p class="submit">
            <button type="submit" name="chatbot_save_settings" class="button button-primary"><?php _e('Änderungen speichern', 'questify'); ?></button>
            <button type="button" id="restore-defaults" class="button" style="margin-left: 10px;"><?php _e('Standardwerte wiederherstellen', 'questify'); ?></button>
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    $('.chatbot-color-picker').wpColorPicker();

    $('#min-score-range').on('input', function() {
        $('#min-score-value').text($(this).val());
    });

    $('#send-test-email').on('click', function() {
        var button = $(this);
        button.prop('disabled', true).text('<?php _e('Sende...', 'questify'); ?>');

        $.post(ajaxurl, {
            action: 'chatbot_send_test_email',
            nonce: chatbotAdmin.nonce
        }, function(response) {
            button.prop('disabled', false).text('<?php _e('Test-E-Mail senden', 'questify'); ?>');
            alert(response.success ? response.data.message : response.data.message);
        });
    });

    $('#restore-defaults').on('click', function() {
        if (!confirm('<?php _e('Möchten Sie wirklich alle Einstellungen auf die Standardwerte zurücksetzen? Dies kann nicht rückgängig gemacht werden!', 'questify'); ?>')) {
            return;
        }

        var button = $(this);
        var originalText = button.text();
        button.prop('disabled', true).text('<?php _e('Wiederherstellen...', 'questify'); ?>');

        $.post(ajaxurl, {
            action: 'chatbot_restore_defaults',
            nonce: chatbotAdmin.nonce
        }, function(response) {
            button.prop('disabled', false).text(originalText);
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || '<?php _e('Fehler beim Wiederherstellen der Standardwerte.', 'questify'); ?>');
            }
        }).fail(function() {
            button.prop('disabled', false).text(originalText);
            alert('<?php _e('Fehler beim Wiederherstellen der Standardwerte.', 'questify'); ?>');
        });
    });
});
</script>
