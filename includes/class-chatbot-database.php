<?php
/**
 * Datenbank-Operationen-Klasse
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für Datenbank-Operationen
 */
class Chatbot_Database {

    /**
     * Singleton-Instanz
     */
    private static ?Chatbot_Database $instance = null;

    /**
     * WordPress-Datenbank-Objekt
     */
    private wpdb $wpdb;

    /**
     * Tabellennamen
     */
    private string $faqs_table;
    private string $inquiries_table;
    private string $conversations_table;

    /**
     * Singleton-Methode
     */
    public static function get_instance(): Chatbot_Database {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor
     */
    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->faqs_table = $wpdb->prefix . 'chatbot_faqs';
        $this->inquiries_table = $wpdb->prefix . 'chatbot_inquiries';
        $this->conversations_table = $wpdb->prefix . 'chatbot_conversations';
    }

    // ========== FAQ-Methoden ==========

    /**
     * Holt alle aktiven FAQs
     *
     * @return array
     * @since 1.0.0
     */
    public function get_active_faqs(): array {
        $cache_key = 'chatbot_active_faqs';
        $faqs = get_transient($cache_key);

        if ($faqs === false) {
            $sql = 'SELECT * FROM ' . $this->faqs_table . ' WHERE active = %d ORDER BY created_at DESC'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Table name is built from $wpdb->prefix; query is prepared below.
            $prepared_sql = $this->wpdb->prepare($sql, 1); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
            $faqs = $this->wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
            set_transient($cache_key, $faqs, HOUR_IN_SECONDS);
        }

        return $faqs ?: [];
    }

    /**
     * Holt alle FAQs
     *
     * @param array $args Filter-Argumente
     * @return array
     * @since 1.0.0
     */
    public function get_all_faqs(array $args = []): array {
        $defaults = [
            'orderby' => 'created_at',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0,
            'active' => null,
            'search' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $prepare_values = [];

        if ($args['active'] !== null) {
            $where[] = 'active = %d';
            $prepare_values[] = $args['active'];
        }

        if (!empty($args['search'])) {
            $where[] = '(question LIKE %s OR keywords LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if ($orderby === false) {
            $orderby = 'created_at DESC';
        }

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $this->wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $sql = 'SELECT * FROM ' . $this->faqs_table . " {$where_clause} ORDER BY {$orderby}{$limit_clause}"; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query; parts sanitized.
        $prepared_sql = !empty($prepare_values) ? $this->wpdb->prepare($sql, $prepare_values) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared when placeholders exist.

        return $this->wpdb->get_results($prepared_sql) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
    }

    /**
     * Zählt FAQs
     *
     * @param array $args Filter-Argumente
     * @return int
     * @since 1.0.0
     */
    public function count_faqs(array $args = []): int {
        $where = [];
        $prepare_values = [];

        if (isset($args['active'])) {
            $where[] = 'active = %d';
            $prepare_values[] = $args['active'];
        }

        if (!empty($args['search'])) {
            $where[] = '(question LIKE %s OR keywords LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = 'SELECT COUNT(*) FROM ' . $this->faqs_table . " {$where_clause}"; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query; parts sanitized.
        $prepared_sql = !empty($prepare_values) ? $this->wpdb->prepare($sql, $prepare_values) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared when placeholders exist.

        return (int) $this->wpdb->get_var($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
    }

    /**
     * Holt einzelne FAQ
     *
     * @param int $id FAQ-ID
     * @return object|null
     * @since 1.0.0
     */
    public function get_faq(int $id): ?object {
        $sql = 'SELECT * FROM ' . $this->faqs_table . ' WHERE id = %d'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, $id); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $result = $this->wpdb->get_row($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

        return $result ?: null;
    }

    /**
     * Alias für get_faq() - für Disambiguierung
     *
     * @param int $id FAQ-ID
     * @return object|null
     * @since 1.0.3
     */
    public function get_faq_by_id(int $id): ?object {
        return $this->get_faq($id);
    }

    /**
     * Erstellt neue FAQ
     *
     * @param array $data FAQ-Daten
     * @return int|false FAQ-ID oder false
     * @since 1.0.0
     */
    public function insert_faq(array $data): int|false {
        $defaults = [
            'question' => '',
            'answer' => '',
            'keywords' => '',
            'active' => 1,
            'view_count' => 0,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->faqs_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            $data,
            ['%s', '%s', '%s', '%d', '%d', '%s', '%s']
        );

        if ($result) {
            $this->clear_faq_cache();
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Aktualisiert FAQ
     *
     * @param int $id FAQ-ID
     * @param array $data Neue Daten
     * @return bool
     * @since 1.0.0
     */
    public function update_faq(int $id, array $data): bool {
        $data['updated_at'] = current_time('mysql');

        $result = $this->wpdb->update(
            $this->faqs_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            $data,
            ['id' => $id],
            null,
            ['%d']
        );

        if ($result !== false) {
            $this->clear_faq_cache();
            return true;
        }

        return false;
    }

    /**
     * Löscht FAQ
     *
     * @param int $id FAQ-ID
     * @return bool
     * @since 1.0.0
     */
    public function delete_faq(int $id): bool {
        $result = $this->wpdb->delete(
            $this->faqs_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            ['id' => $id],
            ['%d']
        );

        if ($result) {
            $this->clear_faq_cache();
            return true;
        }

        return false;
    }

    /**
     * Erhöht View-Counter
     *
     * @param int $id FAQ-ID
     * @return void
     * @since 1.0.0
     */
    public function increment_view_count(int $id): void {
        $sql = 'UPDATE ' . $this->faqs_table . ' SET view_count = view_count + 1 WHERE id = %d'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, $id); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $this->wpdb->query($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
    }

    // ========== Inquiry-Methoden ==========

    /**
     * Holt alle Anfragen
     *
     * @param array $args Filter-Argumente
     * @return array
     * @since 1.0.0
     */
    public function get_all_inquiries(array $args = []): array {
        $defaults = [
            'orderby' => 'timestamp',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0,
            'status' => null,
            'search' => '',
        ];

        $args = wp_parse_args($args, $defaults);

        $where = [];
        $prepare_values = [];

        if ($args['status'] !== null) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(user_name LIKE %s OR user_email LIKE %s OR user_question LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $orderby = sanitize_sql_orderby($args['orderby'] . ' ' . $args['order']);
        if ($orderby === false) {
            $orderby = 'timestamp DESC';
        }

        $limit_clause = '';
        if ($args['limit'] > 0) {
            $limit_clause = $this->wpdb->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }

        $sql = 'SELECT * FROM ' . $this->inquiries_table . " {$where_clause} ORDER BY {$orderby}{$limit_clause}"; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query; parts sanitized.
        $prepared_sql = !empty($prepare_values) ? $this->wpdb->prepare($sql, $prepare_values) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared when placeholders exist.

        return $this->wpdb->get_results($prepared_sql) ?: []; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
    }

    /**
     * Zählt Anfragen
     *
     * @param array $args Filter-Argumente
     * @return int
     * @since 1.0.0
     */
    public function count_inquiries(array $args = []): int {
        $where = [];
        $prepare_values = [];

        if (isset($args['status'])) {
            $where[] = 'status = %s';
            $prepare_values[] = $args['status'];
        }

        if (!empty($args['search'])) {
            $where[] = '(user_name LIKE %s OR user_email LIKE %s OR user_question LIKE %s)';
            $search_term = '%' . $this->wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }

        $where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . " {$where_clause}"; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query; parts sanitized.
        $prepared_sql = !empty($prepare_values) ? $this->wpdb->prepare($sql, $prepare_values) : $sql; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared when placeholders exist.

        return (int) $this->wpdb->get_var($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
    }

    /**
     * Holt einzelne Anfrage
     *
     * @param int $id Inquiry-ID
     * @return object|null
     * @since 1.0.0
     */
    public function get_inquiry(int $id): ?object {
        $sql = 'SELECT * FROM ' . $this->inquiries_table . ' WHERE id = %d'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, $id); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $result = $this->wpdb->get_row($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

        return $result ?: null;
    }

    /**
     * Erstellt neue Anfrage
     *
     * @param array $data Inquiry-Daten
     * @return int|false Inquiry-ID oder false
     * @since 1.0.0
     */
    public function insert_inquiry(array $data): int|false {
        $defaults = [
            'user_name' => '',
            'user_email' => '',
            'user_question' => '',
            'matched_faq_id' => null,
            'was_helpful' => null,
            'status' => 'new',
            'ip_address' => $this->get_client_ip(),
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : '',
            'session_id' => '',
            'timestamp' => current_time('mysql'),
            'email_sent' => 0,
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->inquiries_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            $data,
            ['%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d']
        );

        if ($result) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Aktualisiert Anfrage
     *
     * @param int $id Inquiry-ID
     * @param array $data Neue Daten
     * @return bool
     * @since 1.0.0
     */
    public function update_inquiry(int $id, array $data): bool {
        $result = $this->wpdb->update(
            $this->inquiries_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            $data,
            ['id' => $id],
            null,
            ['%d']
        );

        return $result !== false;
    }

    /**
     * Löscht Anfrage
     *
     * @param int $id Inquiry-ID
     * @return bool
     * @since 1.0.0
     */
    public function delete_inquiry(int $id): bool {
        // Zuerst zugehörige Conversations löschen
        $this->wpdb->delete(
            $this->conversations_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            ['inquiry_id' => $id],
            ['%d']
        );

        // Dann die Inquiry selbst
        $result = $this->wpdb->delete(
            $this->inquiries_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            ['id' => $id],
            ['%d']
        );

        return (bool) $result;
    }

    // ========== Conversation-Methoden ==========

    /**
     * Speichert Konversations-Nachricht
     *
     * @param array $data Nachricht-Daten
     * @return int|false Message-ID oder false
     * @since 1.0.0
     */
    public function insert_conversation(array $data): int|false {
        $defaults = [
            'session_id' => '',
            'inquiry_id' => null,
            'message_type' => 'bot',
            'message_text' => '',
            'timestamp' => current_time('mysql'),
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $this->wpdb->insert(
            $this->conversations_table, // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is built from $wpdb->prefix and is not user input.
            $data,
            ['%s', '%d', '%s', '%s', '%s']
        );

        if ($result) {
            return $this->wpdb->insert_id;
        }

        return false;
    }

    /**
     * Holt Konversationen nach Session-ID
     *
     * @param string $session_id Session-ID
     * @return array
     * @since 1.0.0
     */
    public function get_conversations_by_session(string $session_id): array {
        $sql = 'SELECT * FROM ' . $this->conversations_table . ' WHERE session_id = %s ORDER BY timestamp ASC'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, $session_id); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $results = $this->wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

        return $results ?: [];
    }

    /**
     * Holt Konversationen nach Inquiry-ID
     *
     * @param int $inquiry_id Inquiry-ID
     * @return array
     * @since 1.0.0
     */
    public function get_conversations_by_inquiry(int $inquiry_id): array {
        $sql = 'SELECT * FROM ' . $this->conversations_table . ' WHERE inquiry_id = %d ORDER BY timestamp ASC'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, $inquiry_id); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $results = $this->wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

        return $results ?: [];
    }

    // ========== Analytics-Methoden ==========

    /**
     * Holt Statistiken für Dashboard
     *
     * @param string $period Zeitraum (today, week, month, year)
     * @return array
     * @since 1.0.0
     */
    public function get_dashboard_stats(string $period = 'month'): array {
        $period = in_array($period, ['today', 'week', 'month', 'year'], true) ? $period : 'month';

        switch ($period) {
            case 'today':
                $total_inquiries_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE DATE(timestamp) = CURDATE()'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $total_inquiries = (int) $this->wpdb->get_var($total_inquiries_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- No user input; internal query.

                $answered_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE matched_faq_id IS NOT NULL AND DATE(timestamp) = CURDATE()'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $answered = (int) $this->wpdb->get_var($answered_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- No user input; internal query.

                $helpful_yes_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE was_helpful = %s AND DATE(timestamp) = CURDATE()'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $helpful_yes_prepared = $this->wpdb->prepare($helpful_yes_sql, 'yes'); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
                $helpful_yes = (int) $this->wpdb->get_var($helpful_yes_prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

                $helpful_total_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE was_helpful IS NOT NULL AND DATE(timestamp) = CURDATE()'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $helpful_total = (int) $this->wpdb->get_var($helpful_total_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- No user input; internal query.
                break;

            case 'week':
            case 'month':
            case 'year':
            default:
                $days = match ($period) {
                    'week' => 7,
                    'month' => 30,
                    'year' => 365,
                    default => 30,
                };

                $total_inquiries_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $total_inquiries_prepared = $this->wpdb->prepare($total_inquiries_sql, $days); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
                $total_inquiries = (int) $this->wpdb->get_var($total_inquiries_prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

                $answered_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE matched_faq_id IS NOT NULL AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $answered_prepared = $this->wpdb->prepare($answered_sql, $days); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
                $answered = (int) $this->wpdb->get_var($answered_prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

                $helpful_yes_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE was_helpful = %s AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $helpful_yes_prepared = $this->wpdb->prepare($helpful_yes_sql, 'yes', $days); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
                $helpful_yes = (int) $this->wpdb->get_var($helpful_yes_prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

                $helpful_total_sql = 'SELECT COUNT(*) FROM ' . $this->inquiries_table . ' WHERE was_helpful IS NOT NULL AND timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY)'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
                $helpful_total_prepared = $this->wpdb->prepare($helpful_total_sql, $days); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
                $helpful_total = (int) $this->wpdb->get_var($helpful_total_prepared); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.
                break;
        }

        // Nicht beantwortet
        $not_answered = $total_inquiries - $answered;

        $helpful_rate = $helpful_total > 0 ? round(($helpful_yes / $helpful_total) * 100, 1) : 0;

        return [
            'total_inquiries' => $total_inquiries,
            'answered' => $answered,
            'not_answered' => $not_answered,
            'answered_percent' => $total_inquiries > 0 ? round(($answered / $total_inquiries) * 100, 1) : 0,
            'not_answered_percent' => $total_inquiries > 0 ? round(($not_answered / $total_inquiries) * 100, 1) : 0,
            'helpful_rate' => $helpful_rate,
        ];
    }

    /**
     * Holt Top FAQs
     *
     * @param int $limit Anzahl
     * @return array
     * @since 1.0.0
     */
    public function get_top_faqs(int $limit = 10): array {
        $sql = 'SELECT id, question, view_count FROM ' . $this->faqs_table . ' WHERE active = %d AND view_count > %d ORDER BY view_count DESC LIMIT %d'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, 1, 0, $limit); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $results = $this->wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

        return $results ?: [];
    }

    /**
     * Holt Zeitverlauf-Daten
     *
     * @param int $days Anzahl Tage
     * @return array
     * @since 1.0.0
     */
    public function get_timeline_data(int $days = 30): array {
        $sql = 'SELECT DATE(timestamp) as date, COUNT(*) as count FROM ' . $this->inquiries_table . ' WHERE timestamp >= DATE_SUB(NOW(), INTERVAL %d DAY) GROUP BY DATE(timestamp) ORDER BY date ASC'; // phpcs:ignore PluginCheck.Security.DirectDB.UnescapedDBParameter, WordPress.DB.PreparedSQL.NotPrepared -- Internal query.
        $prepared_sql = $this->wpdb->prepare($sql, $days); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared query string.
        $results = $this->wpdb->get_results($prepared_sql); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Prepared query string.

        return $results ?: [];
    }

    // ========== Hilfsmethoden ==========

    /**
     * Löscht FAQ-Cache
     *
     * @return void
     * @since 1.0.0
     */
    public function clear_faq_cache(): void {
        delete_transient('chatbot_active_faqs');
    }

    /**
     * Erzeugt Datums-Bedingung für SQL
     *
     * @param string $period Zeitraum
     * @return string
     * @since 1.0.0
     */
    private function get_date_condition(string $period): string {
        return match ($period) {
            'today' => "DATE(timestamp) = CURDATE()",
            'week' => "timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)",
            'month' => "timestamp >= DATE_SUB(NOW(), INTERVAL 30 DAY)",
            'year' => "timestamp >= DATE_SUB(NOW(), INTERVAL 365 DAY)",
            default => "1=1"
        };
    }

    /**
     * Holt Client-IP (anonymisiert)
     *
     * @return string
     * @since 1.0.0
     */
    private function get_client_ip(): string {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['HTTP_CLIENT_IP']));
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $forwarded_for = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            $ip = explode(',', $forwarded_for)[0];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
        }

        return $this->anonymize_ip($ip);
    }

    /**
     * Anonymisiert IP-Adresse
     *
     * @param string $ip IP-Adresse
     * @return string
     * @since 1.0.0
     */
    private function anonymize_ip(string $ip): string {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.0', $ip);
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, (int) strrpos($ip, ':')) . ':0';
        }
        return '';
    }
}
