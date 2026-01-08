<?php
/**
 * Settings View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Helper-Funktion: Gibt Option-Wert oder Standardwert zurück
function questify_get_chatbot_option($option_name, $default = '') {
    $value = get_option($option_name, $default);

    // Wenn Wert leer ist (leerer String), Standardwert zurückgeben
    if ($value === '' || $value === false) {
        return $default;
    }

    return $value;
}

// Standardwerte definieren
$questify_defaults = [
    'questi_enabled' => true,
    'questi_welcome_message' => 'Hallo! ðŸ˜Š Wie kann ich Ihnen helfen?',
    'questi_placeholder_text' => 'Stellen Sie Ihre Frage...',
    'questi_no_answer_message' => 'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?',
    'questi_thank_you_message' => 'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.',
    'questi_history_mode' => 'manual',
    'questi_position' => 'right',
    'questi_primary_color' => '#0073aa',
    'questi_header_color' => '#0073aa',
    'questi_header_text_color' => '#ffffff',
    'questi_title' => 'FAQ Chatbot',
    'questi_button_text' => 'Fragen?',
    'questi_size' => 'medium',
    'questi_text_color' => '#333333',
    'questi_user_text_color' => '#ffffff',
    'questi_font_family' => 'system',
    'questi_font_size' => '14px',
    'questi_notification_emails' => get_option('admin_email'),
    'questi_email_prefix' => '[Chatbot]',
    'questi_gdpr_checkbox' => true,
    'questi_gdpr_text' => 'Ich akzeptiere die Datenschutzerklärung.',
    'questi_ip_anonymize_days' => 30,
    'questi_auto_embed' => true,
    'questi_exclude_pages' => [],
    'questi_debug_mode' => false,
    'questi_min_score' => 60,
    'questi_fuzzy_matching' => true,
    'questi_levenshtein_threshold' => 3,
    'questi_stopwords' => 'der, die, das, und, oder, aber, ist, sind, haben, sein, ein, eine, mit, von, zu, für, auf, an, in, aus',
];

// Tab-Handling
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only UI state.
$questify_active_tab = isset($_GET['tab']) ? sanitize_text_field(wp_unslash($_GET['tab'])) : 'general';

// Einstellungen speichern
if (isset($_POST['questi_save_settings'])) {
    check_admin_referer('questi_settings');

    if (current_user_can('manage_options')) {
        // Nur die Felder des aktuellen Tabs speichern
        $questify_current_tab = sanitize_text_field(isset($_POST['current_tab']) ? wp_unslash($_POST['current_tab']) : 'general');

        if ($questify_current_tab === 'general') {
            // Allgemein
            update_option('questi_enabled', isset($_POST['questi_enabled']));
            if (isset($_POST['questi_welcome_message'])) {
                update_option('questi_welcome_message', sanitize_textarea_field(wp_unslash($_POST['questi_welcome_message'])));
            }
            if (isset($_POST['questi_placeholder_text'])) {
                update_option('questi_placeholder_text', sanitize_text_field(wp_unslash($_POST['questi_placeholder_text'])));
            }
            if (isset($_POST['questi_no_answer_message'])) {
                update_option('questi_no_answer_message', sanitize_textarea_field(wp_unslash($_POST['questi_no_answer_message'])));
            }
            if (isset($_POST['questi_thank_you_message'])) {
                update_option('questi_thank_you_message', sanitize_textarea_field(wp_unslash($_POST['questi_thank_you_message'])));
            }
            if (isset($_POST['questi_history_mode'])) {
                update_option('questi_history_mode', sanitize_text_field(wp_unslash($_POST['questi_history_mode'])));
            }
        }

        if ($questify_current_tab === 'design') {
            // Design
            if (isset($_POST['questi_position'])) {
                update_option('questi_position', sanitize_text_field(wp_unslash($_POST['questi_position'])));
            }
            if (isset($_POST['questi_primary_color'])) {
                update_option('questi_primary_color', sanitize_hex_color(wp_unslash($_POST['questi_primary_color'])));
            }
            if (isset($_POST['questi_header_color'])) {
                update_option('questi_header_color', sanitize_hex_color(wp_unslash($_POST['questi_header_color'])));
            }
            if (isset($_POST['questi_header_text_color'])) {
                update_option('questi_header_text_color', sanitize_hex_color(wp_unslash($_POST['questi_header_text_color'])));
            }
            if (isset($_POST['questi_title'])) {
                update_option('questi_title', sanitize_text_field(wp_unslash($_POST['questi_title'])));
            }
            if (isset($_POST['questi_button_text'])) {
                update_option('questi_button_text', sanitize_text_field(wp_unslash($_POST['questi_button_text'])));
            }
            if (isset($_POST['questi_size'])) {
                update_option('questi_size', sanitize_text_field(wp_unslash($_POST['questi_size'])));
            }
            if (isset($_POST['questi_text_color'])) {
                update_option('questi_text_color', sanitize_hex_color(wp_unslash($_POST['questi_text_color'])));
            }
            if (isset($_POST['questi_user_text_color'])) {
                update_option('questi_user_text_color', sanitize_hex_color(wp_unslash($_POST['questi_user_text_color'])));
            }
            if (isset($_POST['questi_font_family'])) {
                update_option('questi_font_family', sanitize_text_field(wp_unslash($_POST['questi_font_family'])));
            }
            if (isset($_POST['questi_font_size'])) {
                update_option('questi_font_size', sanitize_text_field(wp_unslash($_POST['questi_font_size'])));
            }
        }

        if ($questify_current_tab === 'email') {
            // E-Mail
            if (isset($_POST['questi_notification_emails'])) {
                update_option('questi_notification_emails', sanitize_text_field(wp_unslash($_POST['questi_notification_emails'])));
            }
            if (isset($_POST['questi_email_prefix'])) {
                update_option('questi_email_prefix', sanitize_text_field(wp_unslash($_POST['questi_email_prefix'])));
            }
            if (isset($_POST['questi_email_format'])) {
                update_option('questi_email_format', sanitize_text_field(wp_unslash($_POST['questi_email_format'])));
            }
            update_option('questi_email_include_ip', isset($_POST['questi_email_include_ip']));
            update_option('questi_email_include_faq', isset($_POST['questi_email_include_faq']));
            update_option('questi_email_include_user_agent', isset($_POST['questi_email_include_user_agent']));
            if (isset($_POST['questi_email_header_color'])) {
                update_option('questi_email_header_color', sanitize_hex_color(wp_unslash($_POST['questi_email_header_color'])));
            }
            if (isset($_POST['questi_email_footer_color'])) {
                update_option('questi_email_footer_color', sanitize_hex_color(wp_unslash($_POST['questi_email_footer_color'])));
            }
            // Firmeninformationen
            if (isset($_POST['questi_company_name'])) {
                update_option('questi_company_name', sanitize_text_field(wp_unslash($_POST['questi_company_name'])));
            }
            if (isset($_POST['questi_company_address'])) {
                update_option('questi_company_address', sanitize_textarea_field(wp_unslash($_POST['questi_company_address'])));
            }
            if (isset($_POST['questi_company_phone'])) {
                update_option('questi_company_phone', sanitize_text_field(wp_unslash($_POST['questi_company_phone'])));
            }
            if (isset($_POST['questi_company_website'])) {
                update_option('questi_company_website', esc_url_raw(wp_unslash($_POST['questi_company_website'])));
            }
            if (isset($_POST['questi_feedback_email'])) {
                update_option('questi_feedback_email', sanitize_email(wp_unslash($_POST['questi_feedback_email'])));
            }
        }

        if ($questify_current_tab === 'advanced') {
            // Erweitert
            update_option('questi_gdpr_checkbox', isset($_POST['questi_gdpr_checkbox']));
            if (isset($_POST['questi_gdpr_text'])) {
                update_option('questi_gdpr_text', sanitize_textarea_field(wp_unslash($_POST['questi_gdpr_text'])));
            }
            if (isset($_POST['questi_ip_anonymize_days'])) {
                update_option('questi_ip_anonymize_days', absint(wp_unslash($_POST['questi_ip_anonymize_days'])));
            }
            update_option('questi_auto_embed', isset($_POST['questi_auto_embed']));
            update_option('questi_debug_mode', isset($_POST['questi_debug_mode']));
        }

        if ($questify_current_tab === 'matching') {
            // Matching
            if (isset($_POST['questi_min_score'])) {
                update_option('questi_min_score', absint(wp_unslash($_POST['questi_min_score'])));
            }
            update_option('questi_fuzzy_matching', isset($_POST['questi_fuzzy_matching']));
            if (isset($_POST['questi_levenshtein_threshold'])) {
                update_option('questi_levenshtein_threshold', absint(wp_unslash($_POST['questi_levenshtein_threshold'])));
            }
            if (isset($_POST['questi_stopwords'])) {
                update_option('questi_stopwords', sanitize_textarea_field(wp_unslash($_POST['questi_stopwords'])));
            }
        }

        echo '<div class="notice notice-success"><p>' . esc_html__('Einstellungen gespeichert.', 'questify') . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <h2 class="nav-tab-wrapper">
        <a href="?page=questi-settings&tab=general" class="nav-tab <?php echo $questify_active_tab === 'general' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Allgemein', 'questify'); ?></a>
        <a href="?page=questi-settings&tab=design" class="nav-tab <?php echo $questify_active_tab === 'design' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Design', 'questify'); ?></a>
        <a href="?page=questi-settings&tab=email" class="nav-tab <?php echo $questify_active_tab === 'email' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('E-Mail', 'questify'); ?></a>
        <a href="?page=questi-settings&tab=advanced" class="nav-tab <?php echo $questify_active_tab === 'advanced' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Erweitert', 'questify'); ?></a>
        <a href="?page=questi-settings&tab=matching" class="nav-tab <?php echo $questify_active_tab === 'matching' ? 'nav-tab-active' : ''; ?>"><?php esc_html_e('Matching', 'questify'); ?></a>
    </h2>

    <form method="post" action="">
        <?php wp_nonce_field('questi_settings'); ?>
        <input type="hidden" name="current_tab" value="<?php echo esc_attr($questify_active_tab); ?>">

        <?php if ($questify_active_tab === 'general'): ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Chatbot aktivieren', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="questi_enabled" value="1" <?php checked(questify_get_chatbot_option('questi_enabled', $questify_defaults['questi_enabled'])); ?>> <?php esc_html_e('Chatbot auf der Website anzeigen', 'questify'); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('BegrüÖŸungstext', 'questify'); ?></th>
                    <td><textarea name="questi_welcome_message" rows="3" class="large-text"><?php echo esc_textarea(questify_get_chatbot_option('questi_welcome_message', $questify_defaults['questi_welcome_message'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Platzhalter Eingabefeld', 'questify'); ?></th>
                    <td><input type="text" name="questi_placeholder_text" value="<?php echo esc_attr(questify_get_chatbot_option('questi_placeholder_text', $questify_defaults['questi_placeholder_text'])); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Nachricht bei fehlender Antwort', 'questify'); ?></th>
                    <td><textarea name="questi_no_answer_message" rows="3" class="large-text"><?php echo esc_textarea(questify_get_chatbot_option('questi_no_answer_message', $questify_defaults['questi_no_answer_message'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Dankes-Nachricht', 'questify'); ?></th>
                    <td><textarea name="questi_thank_you_message" rows="3" class="large-text"><?php echo esc_textarea(questify_get_chatbot_option('questi_thank_you_message', $questify_defaults['questi_thank_you_message'])); ?></textarea></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Chat-Verlauf Verhalten', 'questify'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="questi_history_mode" value="manual" <?php checked(questify_get_chatbot_option('questi_history_mode', $questify_defaults['questi_history_mode']), 'manual'); ?>>
                            <?php esc_html_e('Papierkorb-Button anzeigen (Benutzer löscht manuell)', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="questi_history_mode" value="auto" <?php checked(questify_get_chatbot_option('questi_history_mode', $questify_defaults['questi_history_mode']), 'auto'); ?>>
                            <?php esc_html_e('Automatisch bei jedem Seitenaufruf löschen', 'questify'); ?>
                        </label>
                        <p class="description">
                            <?php echo wp_kses_post(__('Papierkorb: Chat-Verlauf bleibt gespeichert, Benutzer kann ihn mit Button löschen.<br>Automatisch: Chat-Verlauf wird bei jedem Neuladen der Seite gelöscht.', 'questify')); ?>
                        </p>
                    </td>
                </tr>
            </table>

        <?php elseif ($questify_active_tab === 'design'): ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Chat-Position', 'questify'); ?></th>
                    <td>
                        <label><input type="radio" name="questi_position" value="right" <?php checked(questify_get_chatbot_option('questi_position', $questify_defaults['questi_position']), 'right'); ?>> <?php esc_html_e('Rechts unten', 'questify'); ?></label><br>
                        <label><input type="radio" name="questi_position" value="left" <?php checked(questify_get_chatbot_option('questi_position', $questify_defaults['questi_position']), 'left'); ?>> <?php esc_html_e('Links unten', 'questify'); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Primärfarbe', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_primary_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_primary_color', $questify_defaults['questi_primary_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php esc_html_e('Farbe für Buttons und Akzente', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Header-Farbe', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_header_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_header_color', $questify_defaults['questi_primary_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php esc_html_e('Hintergrundfarbe des Chat-Headers', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Header-Textfarbe', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_header_text_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_header_text_color', $questify_defaults['questi_header_text_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php esc_html_e('Textfarbe des Chat-Titels im Header', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Chat-Titel', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_title" value="<?php echo esc_attr(questify_get_chatbot_option('questi_title', $questify_defaults['questi_title'])); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Titel im Chat-Header', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Button-Text', 'questify'); ?></th>
                    <td><input type="text" name="questi_button_text" value="<?php echo esc_attr(questify_get_chatbot_option('questi_button_text', $questify_defaults['questi_button_text'])); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Chat-GröÖŸe', 'questify'); ?></th>
                    <td>
                        <select name="questi_size">
                            <option value="small" <?php selected(questify_get_chatbot_option('questi_size', $questify_defaults['questi_size']), 'small'); ?>><?php esc_html_e('Klein (300x400px)', 'questify'); ?></option>
                            <option value="medium" <?php selected(questify_get_chatbot_option('questi_size', $questify_defaults['questi_size']), 'medium'); ?>><?php esc_html_e('Mittel (350x500px)', 'questify'); ?></option>
                            <option value="large" <?php selected(questify_get_chatbot_option('questi_size', $questify_defaults['questi_size']), 'large'); ?>><?php esc_html_e('Groß (400x600px)', 'questify'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Textfarbe (Bot-Nachrichten)', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_text_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_text_color', $questify_defaults['questi_text_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php esc_html_e('Farbe des Textes in Bot-Nachrichten', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Textfarbe (User-Nachrichten)', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_user_text_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_user_text_color', $questify_defaults['questi_user_text_color'])); ?>" class="chatbot-color-picker">
                        <p class="description"><?php esc_html_e('Farbe des Textes in User-Nachrichten', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Schriftart', 'questify'); ?></th>
                    <td>
                        <select name="questi_font_family">
                            <option value="system" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), 'system'); ?>><?php esc_html_e('System Standard', 'questify'); ?></option>
                            <option value="Arial, sans-serif" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), 'Arial, sans-serif'); ?>>Arial</option>
                            <option value="Helvetica, sans-serif" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), 'Helvetica, sans-serif'); ?>>Helvetica</option>
                            <option value="'Segoe UI', sans-serif" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), "'Segoe UI', sans-serif"); ?>>Segoe UI</option>
                            <option value="Verdana, sans-serif" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), 'Verdana, sans-serif'); ?>>Verdana</option>
                            <option value="Georgia, serif" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), 'Georgia, serif'); ?>>Georgia</option>
                            <option value="'Times New Roman', serif" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), "'Times New Roman', serif"); ?>>Times New Roman</option>
                            <option value="'Courier New', monospace" <?php selected(questify_get_chatbot_option('questi_font_family', $questify_defaults['questi_font_family']), "'Courier New', monospace"); ?>>Courier New</option>
                        </select>
                        <p class="description"><?php esc_html_e('Schriftart für den gesamten Chat', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('SchriftgröÖŸe', 'questify'); ?></th>
                    <td>
                        <select name="questi_font_size">
                            <option value="12px" <?php selected(questify_get_chatbot_option('questi_font_size', $questify_defaults['questi_font_size']), '12px'); ?>>12px (Klein)</option>
                            <option value="14px" <?php selected(questify_get_chatbot_option('questi_font_size', $questify_defaults['questi_font_size']), '14px'); ?>>14px (Standard)</option>
                            <option value="16px" <?php selected(questify_get_chatbot_option('questi_font_size', $questify_defaults['questi_font_size']), '16px'); ?>>16px (Groß)</option>
                            <option value="18px" <?php selected(questify_get_chatbot_option('questi_font_size', $questify_defaults['questi_font_size']), '18px'); ?>>18px (Sehr Groß)</option>
                        </select>
                        <p class="description"><?php esc_html_e('GröÖŸe der Schrift im Chat', 'questify'); ?></p>
                    </td>
                </tr>
            </table>

        <?php elseif ($questify_active_tab === 'email'): ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Benachrichtigungs-E-Mail(s)', 'questify'); ?></th>
                    <td>
                        <input type="text" name="questi_notification_emails" value="<?php echo esc_attr(questify_get_chatbot_option('questi_notification_emails', $questify_defaults['questi_notification_emails'])); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Mehrere E-Mail-Adressen durch Komma getrennt', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('E-Mail-Betreff-Präfix', 'questify'); ?></th>
                    <td><input type="text" name="questi_email_prefix" value="<?php echo esc_attr(questify_get_chatbot_option('questi_email_prefix', $questify_defaults['questi_email_prefix'])); ?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('E-Mail-Format', 'questify'); ?></th>
                    <td>
                        <label>
                            <input type="radio" name="questi_email_format" value="html" <?php checked(questify_get_chatbot_option('questi_email_format', 'html'), 'html'); ?>>
                            <?php esc_html_e('HTML (mit Design)', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="radio" name="questi_email_format" value="plain" <?php checked(questify_get_chatbot_option('questi_email_format', 'html'), 'plain'); ?>>
                            <?php esc_html_e('Plain Text (nur Text)', 'questify'); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('E-Mail-Informationen', 'questify'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="questi_email_include_ip" value="1" <?php checked(questify_get_chatbot_option('questi_email_include_ip', true)); ?>>
                            <?php esc_html_e('IP-Adresse anzeigen', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="questi_email_include_faq" value="1" <?php checked(questify_get_chatbot_option('questi_email_include_faq', true)); ?>>
                            <?php esc_html_e('Gefundene FAQ-Antwort anzeigen', 'questify'); ?>
                        </label><br>
                        <label>
                            <input type="checkbox" name="questi_email_include_user_agent" value="1" <?php checked(questify_get_chatbot_option('questi_email_include_user_agent', false)); ?>>
                            <?php esc_html_e('Browser-Info (User Agent) anzeigen', 'questify'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Wählen Sie, welche Informationen in der Benachrichtigungs-E-Mail enthalten sein sollen.', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('E-Mail-Design', 'questify'); ?></th>
                    <td>
                        <p>
                            <label style="display: block; margin-bottom: 10px;">
                                <?php esc_html_e('Header-Farbe:', 'questify'); ?>
                                <input type="text" name="questi_email_header_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_email_header_color', '#0073aa')); ?>" class="chatbot-color-picker">
                            </label>
                        </p>
                        <p>
                            <label style="display: block; margin-bottom: 10px;">
                                <?php esc_html_e('Footer-Farbe:', 'questify'); ?>
                                <input type="text" name="questi_email_footer_color" value="<?php echo esc_attr(questify_get_chatbot_option('questi_email_footer_color', '#f4f4f4')); ?>" class="chatbot-color-picker">
                            </label>
                        </p>
                        <p class="description"><?php esc_html_e('Farben für Header und Footer in HTML-E-Mails', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Test-E-Mail senden', 'questify'); ?></th>
                    <td><button type="button" id="send-test-email" class="button"><?php esc_html_e('Test-E-Mail senden', 'questify'); ?></button></td>
                </tr>
            </table>

        <?php elseif ($questify_active_tab === 'advanced'): ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('DSGVO', 'questify'); ?></th>
                    <td>
                        <label><input type="checkbox" name="questi_gdpr_checkbox" value="1" <?php checked(questify_get_chatbot_option('questi_gdpr_checkbox', $questify_defaults['questi_gdpr_checkbox'])); ?>> <?php esc_html_e('DSGVO-Checkbox anzeigen', 'questify'); ?></label>
                        <p><textarea name="questi_gdpr_text" rows="2" class="large-text"><?php echo esc_textarea(questify_get_chatbot_option('questi_gdpr_text', $questify_defaults['questi_gdpr_text'])); ?></textarea></p>
                        <p><label><?php esc_html_e('IP-Anonymisierung nach', 'questify'); ?> <input type="number" name="questi_ip_anonymize_days" value="<?php echo esc_attr(questify_get_chatbot_option('questi_ip_anonymize_days', $questify_defaults['questi_ip_anonymize_days'])); ?>" min="1" max="365" style="width: 60px;"> <?php esc_html_e('Tagen', 'questify'); ?></label></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Einbindung', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="questi_auto_embed" value="1" <?php checked(questify_get_chatbot_option('questi_auto_embed', $questify_defaults['questi_auto_embed'])); ?>> <?php esc_html_e('Automatisch auf allen Seiten einbinden', 'questify'); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Debug-Modus', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="questi_debug_mode" value="1" <?php checked(questify_get_chatbot_option('questi_debug_mode', $questify_defaults['questi_debug_mode'])); ?>> <?php esc_html_e('Debug-Informationen im Error-Log speichern', 'questify'); ?></label></td>
                </tr>
            </table>

        <?php elseif ($questify_active_tab === 'matching'): ?>
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e('Mindest-Score', 'questify'); ?></th>
                    <td>
                        <input type="range" name="questi_min_score" min="0" max="100" value="<?php echo esc_attr(questify_get_chatbot_option('questi_min_score', $questify_defaults['questi_min_score'])); ?>" id="min-score-range">
                        <span id="min-score-value"><?php echo esc_html(questify_get_chatbot_option('questi_min_score', $questify_defaults['questi_min_score'])); ?></span>
                        <p class="description"><?php esc_html_e('Minimale Übereinstimmung für eine Antwort (0-100)', 'questify'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Fuzzy-Matching', 'questify'); ?></th>
                    <td><label><input type="checkbox" name="questi_fuzzy_matching" value="1" <?php checked(questify_get_chatbot_option('questi_fuzzy_matching', $questify_defaults['questi_fuzzy_matching'])); ?>> <?php esc_html_e('Fuzzy-Matching aktivieren', 'questify'); ?></label></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Levenshtein-Distanz', 'questify'); ?></th>
                    <td><input type="number" name="questi_levenshtein_threshold" value="<?php echo esc_attr(questify_get_chatbot_option('questi_levenshtein_threshold', $questify_defaults['questi_levenshtein_threshold'])); ?>" min="1" max="10" style="width: 60px;"></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Stoppwörter', 'questify'); ?></th>
                    <td><textarea name="questi_stopwords" rows="4" class="large-text"><?php echo esc_textarea(questify_get_chatbot_option('questi_stopwords', $questify_defaults['questi_stopwords'])); ?></textarea>
                    <p class="description"><?php esc_html_e('Durch Komma getrennt', 'questify'); ?></p></td>
                </tr>
            </table>
        <?php endif; ?>

        <p class="submit">
            <button type="submit" name="questi_save_settings" class="button button-primary"><?php esc_html_e('Ö„nderungen speichern', 'questify'); ?></button>
            <button type="button" id="restore-defaults" class="button" style="margin-left: 10px;"><?php esc_html_e('Standardwerte wiederherstellen', 'questify'); ?></button>
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
        button.prop('disabled', true).text('<?php echo esc_js(__('Sende...', 'questify')); ?>');

        $.post(ajaxurl, {
            action: 'questi_send_test_email',
            nonce: questiAdmin.nonce
        }, function(response) {
            button.prop('disabled', false).text('<?php echo esc_js(__('Test-E-Mail senden', 'questify')); ?>');
            alert(response.success ? response.data.message : response.data.message);
        });
    });

    $('#restore-defaults').on('click', function() {
        if (!confirm('<?php echo esc_js(__('Möchten Sie wirklich alle Einstellungen auf die Standardwerte zurücksetzen? Dies kann nicht rückgängig gemacht werden!', 'questify')); ?>')) {
            return;
        }

        var button = $(this);
        var originalText = button.text();
        button.prop('disabled', true).text('<?php echo esc_js(__('Wiederherstellen...', 'questify')); ?>');

        $.post(ajaxurl, {
            action: 'questi_restore_defaults',
            nonce: questiAdmin.nonce
        }, function(response) {
            button.prop('disabled', false).text(originalText);
            if (response.success) {
                alert(response.data.message);
                location.reload();
            } else {
                alert(response.data.message || '<?php echo esc_js(__('Fehler beim Wiederherstellen der Standardwerte.', 'questify')); ?>');
            }
        }).fail(function() {
            button.prop('disabled', false).text(originalText);
            alert('<?php echo esc_js(__('Fehler beim Wiederherstellen der Standardwerte.', 'questify')); ?>');
        });
    });
});
</script>
