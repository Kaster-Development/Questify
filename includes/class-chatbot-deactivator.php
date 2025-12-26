<?php
/**
 * Plugin-Deaktivierungs-Klasse
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für Plugin-Deaktivierung
 */
class Chatbot_Deactivator {

    /**
     * Deaktivierungs-Funktion
     *
     * @since 1.0.0
     */
    public static function deactivate(): void {
        // Cronjobs deregistrieren
        self::unschedule_cron_jobs();

        // Transients löschen
        self::clear_transients();

        // Flush Rewrite Rules
        flush_rewrite_rules();
    }

    /**
     * Deregistriert Cronjobs
     *
     * @since 1.0.0
     */
    private static function unschedule_cron_jobs(): void {
        $timestamp = wp_next_scheduled('chatbot_cleanup_old_data');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'chatbot_cleanup_old_data');
        }
    }

    /**
     * Löscht alle Plugin-Transients
     *
     * @since 1.0.0
     */
    private static function clear_transients(): void {
        global $wpdb;

        // Alle Chatbot-Transients löschen
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Cleanup query on deactivation.
        $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_chatbot_%'
             OR option_name LIKE '_transient_timeout_chatbot_%'"
        );
    }
}
