<?php
/**
 * Frontend-Klasse
 *
 * @package Questify
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für Frontend-Anzeige
 */
class Questi_Frontend {

    /**
     * Konstruktor
     */
    public function __construct() {
        // Hooks registrieren
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
        add_action('wp_footer', [$this, 'render_chat_widget']);
        add_shortcode('questi_faq_chat', [$this, 'shortcode_handler']);
    }

    /**
     * Lädt Frontend-Assets
     *
     * @return void
     * @since 1.0.0
     */
    public function enqueue_frontend_assets(): void {
        // Prüfen ob Chatbot aktiviert ist
        if (!get_option('questi_enabled', true)) {
            return;
        }

        // Prüfen ob auf ausgeschlossenen Seiten
        if ($this->is_excluded_page()) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'questi-style',
            QUESTIFY_PLUGIN_URL . 'public/css/chatbot-style.css',
            [],
            QUESTIFY_VERSION
        );

        // Inline-CSS für angepasste Farben und Schriftarten
        $primary_color = get_option('questi_primary_color', '#0073aa');
        $header_color = get_option('questi_header_color', '#0073aa');
        $header_text_color = get_option('questi_header_text_color', '#ffffff');
        $text_color = get_option('questi_text_color', '#333333');
        $user_text_color = get_option('questi_user_text_color', '#ffffff');
        $font_family = get_option('questi_font_family', 'system');
        $font_size = get_option('questi_font_size', '14px');

        // Font-Family basierend auf Einstellung
        $font_css = $font_family === 'system'
            ? '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif'
            : $font_family;

        $custom_css = "
            :root {
                --chatbot-primary-color: {$primary_color};
                --chatbot-header-color: {$header_color};
                --chatbot-header-text-color: {$header_text_color};
                --chatbot-text-color: {$text_color};
                --chatbot-user-text-color: {$user_text_color};
                --chatbot-font-family: {$font_css};
                --chatbot-font-size: {$font_size};
            }
        ";
        wp_add_inline_style('questi-style', $custom_css);

        // JavaScript
        wp_enqueue_script(
            'questi-script',
            QUESTIFY_PLUGIN_URL . 'public/js/chatbot-script.js',
            ['jquery'],
            QUESTIFY_VERSION,
            true
        );

        // Lokalisierung
        wp_localize_script('questi-script', 'questiData', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('questi_ajax'),
            'settings' => $this->get_frontend_settings(),
        ]);
    }

    /**
     * Rendert Chat-Widget
     *
     * @return void
     * @since 1.0.0
     */
    public function render_chat_widget(): void {
        // Prüfen ob Chatbot aktiviert ist
        if (!get_option('questi_enabled', true)) {
            return;
        }

        // Prüfen ob auf ausgeschlossenen Seiten
        if ($this->is_excluded_page()) {
            return;
        }

        // Prüfen ob Auto-Embed aktiviert ist
        if (!get_option('questi_auto_embed', true)) {
            return;
        }

        echo '<div id="chatbot-container"></div>';
    }

    /**
     * Shortcode-Handler
     *
     * @param array $atts Shortcode-Attribute
     * @return string
     * @since 1.0.0
     */
    public function shortcode_handler(array $atts = []): string {
        $atts = shortcode_atts([
            'position' => get_option('questi_position', 'right'),
            'color' => get_option('questi_primary_color', '#0073aa'),
            'size' => get_option('questi_size', 'medium'),
        ], $atts);

        // Assets laden wenn nicht schon geladen
        $this->enqueue_frontend_assets();

        return sprintf(
            '<div id="chatbot-container" data-position="%s" data-color="%s" data-size="%s"></div>',
            esc_attr($atts['position']),
            esc_attr($atts['color']),
            esc_attr($atts['size'])
        );
    }

    /**
     * Holt Frontend-Einstellungen
     *
     * @return array
     * @since 1.0.0
     */
    private function get_frontend_settings(): array {
        // Hilfsfunktion: get_option mit echtem Fallback (auch bei leerem String)
        $get_option_with_fallback = function($key, $fallback) {
            $value = get_option($key, $fallback);
            // Wenn leer oder nur Leerzeichen, nutze Fallback
            return (empty($value) || trim($value) === '') ? $fallback : $value;
        };

        return [
            'welcomeMessage' => $get_option_with_fallback('questi_welcome_message', 'Hallo! 😊 Wie kann ich Ihnen helfen?'),
            'placeholderText' => $get_option_with_fallback('questi_placeholder_text', 'Stellen Sie Ihre Frage...'),
            'noAnswerMessage' => $get_option_with_fallback('questi_no_answer_message', 'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?'),
            'thankYouMessage' => $get_option_with_fallback('questi_thank_you_message', 'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.'),
            'position' => get_option('questi_position', 'right'),
            'primaryColor' => get_option('questi_primary_color', '#0073aa'),
            'title' => get_option('questi_title', 'FAQ Chatbot'),
            'buttonText' => get_option('questi_button_text', 'Fragen?'),
            'size' => get_option('questi_size', 'medium'),
            'textColor' => get_option('questi_text_color', '#333333'),
            'userTextColor' => get_option('questi_user_text_color', '#ffffff'),
            'fontFamily' => get_option('questi_font_family', 'system'),
            'fontSize' => get_option('questi_font_size', '14px'),
            'historyMode' => get_option('questi_history_mode', 'manual'),
            'gdprCheckbox' => get_option('questi_gdpr_checkbox', true),
            'gdprText' => get_option('questi_gdpr_text', 'Ich akzeptiere die Datenschutzerklärung.'),
            'strings' => [
                'send' => __('Senden', 'questify'),
                'cancel' => __('Abbrechen', 'questify'),
                'yes' => __('Ja', 'questify'),
                'no' => __('Nein', 'questify'),
                'helpful' => __('War das hilfreich?', 'questify'),
                'typing' => __('Schreibt...', 'questify'),
                'offline' => __('Keine Verbindung', 'questify'),
                'namePlaceholder' => __('Ihr Name', 'questify'),
                'emailPlaceholder' => __('Ihre E-Mail', 'questify'),
                'submit' => __('Absenden', 'questify'),
                'close' => __('Schließen', 'questify'),
                'minimize' => __('Minimieren', 'questify'),
            ],
        ];
    }

    /**
     * Prüft ob aktuelle Seite ausgeschlossen ist
     *
     * @return bool
     * @since 1.0.0
     */
    private function is_excluded_page(): bool {
        $excluded_pages = get_option('questi_exclude_pages', []);

        if (empty($excluded_pages) || !is_array($excluded_pages)) {
            return false;
        }

        $current_page_id = get_the_ID();

        return in_array($current_page_id, $excluded_pages);
    }
}
