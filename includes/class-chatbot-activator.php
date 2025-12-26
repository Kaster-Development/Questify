<?php
/**
 * Plugin-Aktivierungs-Klasse
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für Plugin-Aktivierung
 */
class Chatbot_Activator {

    /**
     * Aktivierungs-Funktion
     *
     * @since 1.0.0
     */
    public static function activate(): void {
        // Mindestanforderungen prüfen
        self::check_requirements();

        // Datenbanktabellen erstellen
        self::create_tables();

        // Standard-Optionen setzen
        self::set_default_options();

        // Cronjob registrieren
        self::schedule_cron_jobs();

        // Redirect-Flag setzen
        set_transient('chatbot_activation_redirect', true, 30);

        // Flush Rewrite Rules
        flush_rewrite_rules();
    }

    /**
     * Prüft Mindestanforderungen
     *
     * @since 1.0.0
     */
    private static function check_requirements(): void {
        global $wp_version;

        // WordPress-Version prüfen
        if (version_compare($wp_version, '6.5', '<')) {
            deactivate_plugins(QUESTIFY_PLUGIN_BASENAME);
            wp_die(
                esc_html__('Questify benötigt WordPress 6.5 oder höher.', 'questify'),
                esc_html__('Plugin-Aktivierung fehlgeschlagen', 'questify'),
                ['back_link' => true]
            );
        }

        // PHP-Version prüfen
        if (version_compare(PHP_VERSION, '8.2', '<')) {
            deactivate_plugins(QUESTIFY_PLUGIN_BASENAME);
            wp_die(
                esc_html__('Questify benötigt PHP 8.2 oder höher.', 'questify'),
                esc_html__('Plugin-Aktivierung fehlgeschlagen', 'questify'),
                ['back_link' => true]
            );
        }
    }

    /**
     * Erstellt Datenbanktabellen
     *
     * @since 1.0.0
     */
    private static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $prefix = $wpdb->prefix;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Tabelle: chatbot_faqs
        $sql_faqs = "CREATE TABLE {$prefix}chatbot_faqs (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            question TEXT NOT NULL,
            answer TEXT NOT NULL,
            keywords TEXT,
            active TINYINT(1) DEFAULT 1,
            view_count INT(11) DEFAULT 0,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY active (active),
            KEY created_at (created_at),
            FULLTEXT KEY keywords (keywords)
        ) $charset_collate;";

        dbDelta($sql_faqs);

        // Tabelle: chatbot_inquiries
        $sql_inquiries = "CREATE TABLE {$prefix}chatbot_inquiries (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_name VARCHAR(255) NOT NULL,
            user_email VARCHAR(255) NOT NULL,
            user_question TEXT NOT NULL,
            matched_faq_id BIGINT(20) UNSIGNED NULL,
            was_helpful ENUM('yes', 'no') NULL,
            status ENUM('new', 'in_progress', 'answered') DEFAULT 'new',
            ip_address VARCHAR(45),
            user_agent TEXT,
            session_id VARCHAR(255),
            timestamp DATETIME NOT NULL,
            email_sent TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            KEY matched_faq_id (matched_faq_id),
            KEY status (status),
            KEY timestamp (timestamp),
            KEY session_id (session_id),
            KEY user_email (user_email)
        ) $charset_collate;";

        dbDelta($sql_inquiries);

        // Tabelle: chatbot_conversations
        $sql_conversations = "CREATE TABLE {$prefix}chatbot_conversations (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(255) NOT NULL,
            inquiry_id BIGINT(20) UNSIGNED NULL,
            message_type ENUM('user', 'bot') NOT NULL,
            message_text TEXT NOT NULL,
            timestamp DATETIME NOT NULL,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY inquiry_id (inquiry_id),
            KEY timestamp (timestamp)
        ) $charset_collate;";

        dbDelta($sql_conversations);

        // Versionsnummer speichern
        add_option('questify_db_version', QUESTIFY_VERSION);
    }

    /**
     * Setzt Standard-Optionen
     *
     * @since 1.0.0
     */
    private static function set_default_options(): void {
        $default_options = [
            // Allgemein
            'chatbot_enabled' => true,
            'chatbot_welcome_message' => 'Hallo! 😊 Wie kann ich Ihnen helfen?',
            'chatbot_placeholder_text' => 'Stellen Sie Ihre Frage...',
            'chatbot_no_answer_message' => 'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?',
            'chatbot_thank_you_message' => 'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.',
            'chatbot_history_mode' => 'manual',

            // Design
            'chatbot_position' => 'right',
            'chatbot_primary_color' => '#0073aa',
            'chatbot_button_text' => 'Fragen?',
            'chatbot_size' => 'medium',
            'chatbot_text_color' => '#333333',
            'chatbot_user_text_color' => '#ffffff',
            'chatbot_font_family' => 'system',
            'chatbot_font_size' => '14px',

            // E-Mail
            'chatbot_notification_emails' => get_option('admin_email'),
            'chatbot_email_prefix' => '[Chatbot]',

            // Erweitert
            'chatbot_gdpr_checkbox' => true,
            'chatbot_gdpr_text' => 'Ich akzeptiere die Datenschutzerklärung.',
            'chatbot_ip_anonymize_days' => 30,
            'chatbot_auto_embed' => true,
            'chatbot_exclude_pages' => [],
            'chatbot_debug_mode' => false,

            // Matching
            'chatbot_min_score' => 60,
            'chatbot_fuzzy_matching' => true,
            'chatbot_levenshtein_threshold' => 3,
            'chatbot_stopwords' => 'der, die, das, und, oder, aber, ist, sind, haben, sein, ein, eine, mit, von, zu, für, auf, an, in, aus',
        ];

        foreach ($default_options as $key => $value) {
            // Prüfen ob Option existiert und nicht leer ist
            $existing = get_option($key, null);

            // Wenn Option nicht existiert ODER leer ist, Standardwert setzen
            if ($existing === null || $existing === '' || $existing === false) {
                update_option($key, $value);
            } else {
                // Option existiert, nur hinzufügen wenn noch nicht vorhanden
                add_option($key, $value);
            }
        }
    }

    /**
     * Stellt alle Standardwerte wieder her (für Reset-Funktion)
     *
     * @since 1.0.0
     */
    public static function restore_defaults(): void {
        $default_options = [
            // Allgemein
            'chatbot_enabled' => true,
            'chatbot_welcome_message' => 'Hallo! 😊 Wie kann ich Ihnen helfen?',
            'chatbot_placeholder_text' => 'Stellen Sie Ihre Frage...',
            'chatbot_no_answer_message' => 'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?',
            'chatbot_thank_you_message' => 'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.',
            'chatbot_history_mode' => 'manual',

            // Design
            'chatbot_position' => 'right',
            'chatbot_primary_color' => '#0073aa',
            'chatbot_button_text' => 'Fragen?',
            'chatbot_size' => 'medium',
            'chatbot_text_color' => '#333333',
            'chatbot_user_text_color' => '#ffffff',
            'chatbot_font_family' => 'system',
            'chatbot_font_size' => '14px',

            // E-Mail
            'chatbot_notification_emails' => get_option('admin_email'),
            'chatbot_email_prefix' => '[Chatbot]',

            // Erweitert
            'chatbot_gdpr_checkbox' => true,
            'chatbot_gdpr_text' => 'Ich akzeptiere die Datenschutzerklärung.',
            'chatbot_ip_anonymize_days' => 30,
            'chatbot_auto_embed' => true,
            'chatbot_exclude_pages' => [],
            'chatbot_debug_mode' => false,

            // Matching
            'chatbot_min_score' => 60,
            'chatbot_fuzzy_matching' => true,
            'chatbot_levenshtein_threshold' => 3,
            'chatbot_stopwords' => 'der, die, das, und, oder, aber, ist, sind, haben, sein, ein, eine, mit, von, zu, für, auf, an, in, aus',
        ];

        foreach ($default_options as $key => $value) {
            update_option($key, $value);
        }
    }

    /**
     * Registriert Cronjobs
     *
     * @since 1.0.0
     */
    private static function schedule_cron_jobs(): void {
        if (!wp_next_scheduled('chatbot_cleanup_old_data')) {
            wp_schedule_event(time(), 'daily', 'chatbot_cleanup_old_data');
        }
    }
}
