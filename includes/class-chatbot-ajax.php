<?php
/**
 * AJAX-Handler-Klasse
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für AJAX-Handler
 */
class Chatbot_Ajax {

    /**
     * Database-Instanz
     */
    private Chatbot_Database $db;

    /**
     * Matcher-Instanz
     */
    private Chatbot_Matcher $matcher;

    /**
     * Email-Instanz
     */
    private Chatbot_Email $email;

    /**
     * Konstruktor
     */
    public function __construct() {
        $this->db = Chatbot_Database::get_instance();
        $this->matcher = Chatbot_Matcher::get_instance();
        $this->email = Chatbot_Email::get_instance();

        $this->register_ajax_handlers();
    }

    /**
     * Registriert AJAX-Handler
     *
     * @return void
     * @since 1.0.0
     */
    private function register_ajax_handlers(): void {
        // Frontend-AJAX (für nicht eingeloggte Benutzer)
        add_action('wp_ajax_nopriv_chatbot_get_answer', [$this, 'handle_get_answer']);
        add_action('wp_ajax_nopriv_chatbot_send_inquiry', [$this, 'handle_send_inquiry']);
        add_action('wp_ajax_nopriv_chatbot_rate_answer', [$this, 'handle_rate_answer']);
        add_action('wp_ajax_nopriv_chatbot_get_faq_by_id', [$this, 'handle_get_faq_by_id']);

        // Frontend-AJAX (für eingeloggte Benutzer)
        add_action('wp_ajax_chatbot_get_answer', [$this, 'handle_get_answer']);
        add_action('wp_ajax_chatbot_send_inquiry', [$this, 'handle_send_inquiry']);
        add_action('wp_ajax_chatbot_rate_answer', [$this, 'handle_rate_answer']);
        add_action('wp_ajax_chatbot_get_faq_by_id', [$this, 'handle_get_faq_by_id']);

        // Admin-AJAX
        add_action('wp_ajax_chatbot_delete_faq', [$this, 'handle_delete_faq']);
        add_action('wp_ajax_chatbot_toggle_faq_status', [$this, 'handle_toggle_faq_status']);
        add_action('wp_ajax_chatbot_delete_inquiry', [$this, 'handle_delete_inquiry']);
        add_action('wp_ajax_chatbot_update_inquiry_status', [$this, 'handle_update_inquiry_status']);
        add_action('wp_ajax_chatbot_send_test_email', [$this, 'handle_send_test_email']);
        add_action('wp_ajax_chatbot_export_inquiries', [$this, 'handle_export_inquiries']);
        add_action('wp_ajax_chatbot_generate_keywords', [$this, 'handle_generate_keywords']);
        add_action('wp_ajax_chatbot_export_faqs', [$this, 'handle_export_faqs']);
        add_action('wp_ajax_chatbot_import_faqs', [$this, 'handle_import_faqs']);
        add_action('wp_ajax_chatbot_restore_defaults', [$this, 'handle_restore_defaults']);
        add_action('wp_ajax_chatbot_fix_empty_options', [$this, 'handle_fix_empty_options']);
    }

    // ========== Frontend-AJAX-Handler ==========

    /**
     * Holt Antwort für Frage
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_get_answer(): void {
        // Nonce prüfen (false = nicht sterben bei Fehler)
        if (!check_ajax_referer('chatbot_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'questify')]);
            return;
        }

        // Parameter holen
        $question = sanitize_textarea_field(isset($_POST['question']) ? wp_unslash($_POST['question']) : '');
        $session_id = sanitize_text_field(isset($_POST['session_id']) ? wp_unslash($_POST['session_id']) : '');

        if (empty($question)) {
            wp_send_json_error(['message' => __('Bitte geben Sie eine Frage ein.', 'questify')]);
        }

        // Matching durchführen
        $match = $this->matcher->find_best_match($question);

        // Konversation speichern
        $this->db->insert_conversation([
            'session_id' => $session_id,
            'message_type' => 'user',
            'message_text' => $question,
        ]);

        if ($match) {
            $faq = $match['faq'];
            $score = $match['score'];
            $quality = $this->matcher->get_match_quality($score);
            $needs_disambiguation = $match['needs_disambiguation'] ?? false;

            // Wenn Disambiguierung nötig: Auswahlmöglichkeiten zurückgeben
            if ($needs_disambiguation && !empty($match['alternatives'])) {
                // Alle Optionen inkl. beste Antwort sammeln
                $options = [];

                // Beste Antwort zuerst (nur wenn aktiv und existiert)
                if (isset($faq->id) && isset($faq->question) && $faq->active) {
                    $options[] = [
                        'id' => (int) $faq->id,
                        'question' => wp_strip_all_tags($faq->question),
                        'score' => $score,
                    ];
                }

                // Alternativen hinzufügen (max 3 Alternativen, nur aktive FAQs)
                $max_alternatives = 3;
                $count = 0;
                foreach ($match['alternatives'] as $alt) {
                    if ($count >= $max_alternatives) break;

                    // Validierung: Nur wenn FAQ existiert und aktiv ist
                    if (isset($alt['faq']) && isset($alt['faq']->id) && isset($alt['faq']->question) && $alt['faq']->active) {
                        $options[] = [
                            'id' => (int) $alt['faq']->id,
                            'question' => wp_strip_all_tags($alt['faq']->question),
                            'score' => $alt['score'],
                        ];
                        $count++;
                    }
                }

                // Nur wenn wir mindestens 2 Optionen haben
                if (count($options) >= 2) {
                    // Disambiguierungs-Nachricht speichern
                    $disambiguation_message = __('Ich habe mehrere passende Antworten gefunden. Welche dieser Fragen meinen Sie?', 'questify');
                    $this->db->insert_conversation([
                        'session_id' => $session_id,
                        'message_type' => 'bot',
                        'message_text' => $disambiguation_message,
                    ]);

                    wp_send_json_success([
                        'found' => true,
                        'needs_disambiguation' => true,
                        'disambiguation_message' => $disambiguation_message,
                        'options' => $options,
                    ]);
                }

                // WICHTIG: Wenn Disambiguierung fehlschlägt, normale Antwort senden
                // View-Count erhöhen
                $this->db->increment_view_count($faq->id);

                // Bot-Antwort speichern
                $this->db->insert_conversation([
                    'session_id' => $session_id,
                    'message_type' => 'bot',
                    'message_text' => $faq->answer,
                ]);

                wp_send_json_success([
                    'found' => true,
                    'answer' => $faq->answer,
                    'faq_id' => $faq->id,
                    'quality' => $quality,
                    'needs_disambiguation' => false,
                ]);
            }

            // Dieser else-Block wird nur erreicht, wenn needs_disambiguation = false
            if (!$needs_disambiguation) {
                // Normale Antwort ohne Disambiguierung
                // View-Count erhöhen
                $this->db->increment_view_count($faq->id);

                // Bot-Antwort speichern
                $this->db->insert_conversation([
                    'session_id' => $session_id,
                    'message_type' => 'bot',
                    'message_text' => $faq->answer,
                ]);

                wp_send_json_success([
                    'found' => true,
                    'answer' => $faq->answer,
                    'faq_id' => $faq->id,
                    'quality' => $quality,
                    'needs_disambiguation' => false,
                ]);
            }
        } else {
            // Keine Antwort gefunden
            $no_answer_message = get_option(
                'chatbot_no_answer_message',
                'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?'
            );

            // Bot-Antwort speichern
            $this->db->insert_conversation([
                'session_id' => $session_id,
                'message_type' => 'bot',
                'message_text' => $no_answer_message,
            ]);

            wp_send_json_success([
                'found' => false,
                'message' => $no_answer_message,
            ]);
        }
    }

    /**
     * Speichert Anfrage und sendet E-Mail
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_send_inquiry(): void {
        // Nonce prüfen (false = nicht sterben bei Fehler)
        if (!check_ajax_referer('chatbot_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'questify')]);
            return;
        }

        // Parameter holen
        $name = sanitize_text_field(isset($_POST['name']) ? wp_unslash($_POST['name']) : '');
        $email = sanitize_email(isset($_POST['email']) ? wp_unslash($_POST['email']) : '');
        $question = sanitize_textarea_field(isset($_POST['question']) ? wp_unslash($_POST['question']) : '');
        $session_id = sanitize_text_field(isset($_POST['session_id']) ? wp_unslash($_POST['session_id']) : '');
        $faq_id = isset($_POST['faq_id']) ? absint(wp_unslash($_POST['faq_id'])) : null;

        // Validierung
        if (empty($name)) {
            wp_send_json_error(['message' => __('Bitte geben Sie Ihren Namen ein.', 'questify')]);
        }

        if (empty($email) || !is_email($email)) {
            wp_send_json_error(['message' => __('Bitte geben Sie eine gültige E-Mail-Adresse ein.', 'questify')]);
        }

        if (empty($question)) {
            wp_send_json_error(['message' => __('Bitte geben Sie Ihre Frage ein.', 'questify')]);
        }

        // Inquiry speichern
        $inquiry_id = $this->db->insert_inquiry([
            'user_name' => $name,
            'user_email' => $email,
            'user_question' => $question,
            'session_id' => $session_id,
            'matched_faq_id' => $faq_id,
            'status' => 'new',
        ]);

        if (!$inquiry_id) {
            wp_send_json_error(['message' => __('Fehler beim Speichern der Anfrage.', 'questify')]);
        }

        // E-Mail senden
        $email_sent = false;
        try {
            $email_sent = $this->email->send_inquiry_notification($inquiry_id);
        } catch (Exception $e) {
            $email_sent = false;
        }

        // Dankes-Nachricht
        $thank_you_message = get_option(
            'chatbot_thank_you_message',
            'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.'
        );

        // Debug-Modus: E-Mail-Status in Nachricht hinzufügen
        if (get_option('chatbot_debug_mode') && current_user_can('manage_options')) {
            $thank_you_message .= ' [Debug: E-Mail ' . ($email_sent ? 'gesendet' : 'NICHT gesendet') . ']';
        }

        wp_send_json_success([
            'message' => $thank_you_message,
            'inquiry_id' => $inquiry_id,
            'email_sent' => $email_sent,
        ]);
    }

    /**
     * Holt spezifische FAQ nach ID (für Disambiguierung)
     *
     * @return void
     * @since 1.0.3
     */
    public function handle_get_faq_by_id(): void {
        // Nonce prüfen (false = nicht sterben bei Fehler)
        if (!check_ajax_referer('chatbot_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'questify')]);
            return;
        }

        // Parameter holen
        $faq_id = isset($_POST['faq_id']) ? absint(wp_unslash($_POST['faq_id'])) : 0;
        $session_id = sanitize_text_field(isset($_POST['session_id']) ? wp_unslash($_POST['session_id']) : '');

        if (!$faq_id) {
            wp_send_json_error(['message' => __('Ungültige FAQ-ID.', 'questify')]);
            return;
        }

        // FAQ abrufen
        $faq = $this->db->get_faq_by_id($faq_id);

        if (!$faq || !$faq->active) {
            wp_send_json_error(['message' => __('FAQ nicht gefunden oder inaktiv.', 'questify')]);
            return;
        }

        // View-Count erhöhen
        $this->db->increment_view_count($faq->id);

        // Bot-Antwort speichern
        $this->db->insert_conversation([
            'session_id' => $session_id,
            'message_type' => 'bot',
            'message_text' => $faq->answer,
        ]);

        wp_send_json_success([
            'answer' => $faq->answer,
            'faq_id' => (int) $faq->id,
        ]);
    }

    /**
     * Bewertet Antwort
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_rate_answer(): void {
        // Nonce prüfen (false = nicht sterben bei Fehler)
        if (!check_ajax_referer('chatbot_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen. Bitte laden Sie die Seite neu.', 'questify')]);
            return;
        }

        // Parameter holen
        $faq_id = isset($_POST['faq_id']) ? absint(wp_unslash($_POST['faq_id'])) : 0;
        $helpful = sanitize_text_field(isset($_POST['helpful']) ? wp_unslash($_POST['helpful']) : ''); // 'yes' oder 'no'
        $session_id = sanitize_text_field(isset($_POST['session_id']) ? wp_unslash($_POST['session_id']) : '');

        if (!$faq_id || !in_array($helpful, ['yes', 'no'], true)) {
            wp_send_json_error(['message' => __('Ungültige Parameter.', 'questify')]);
        }

        // Inquiry erstellen/aktualisieren für Bewertung
        $host = wp_parse_url(home_url(), PHP_URL_HOST);
        $host = is_string($host) && $host !== '' ? $host : 'example.com';
        $inquiry_id = $this->db->insert_inquiry([
            'user_name' => 'Anonymous',
            'user_email' => 'noreply@' . $host,
            'user_question' => '',
            'session_id' => $session_id,
            'matched_faq_id' => $faq_id,
            'was_helpful' => $helpful,
            'status' => 'answered',
        ]);

        if ($inquiry_id) {
            wp_send_json_success(['message' => __('Vielen Dank für Ihr Feedback!', 'questify')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Speichern der Bewertung.', 'questify')]);
        }
    }

    // ========== Admin-AJAX-Handler ==========

    /**
     * Löscht FAQ
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_delete_faq(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
        }

        $faq_id = isset($_POST['faq_id']) ? absint(wp_unslash($_POST['faq_id'])) : 0;

        if (!$faq_id) {
            wp_send_json_error(['message' => __('Ungültige FAQ-ID.', 'questify')]);
        }

        if ($this->db->delete_faq($faq_id)) {
            wp_send_json_success(['message' => __('FAQ erfolgreich gelöscht.', 'questify')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Löschen.', 'questify')]);
        }
    }

    /**
     * Ändert FAQ-Status
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_toggle_faq_status(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
        }

        $faq_id = isset($_POST['faq_id']) ? absint(wp_unslash($_POST['faq_id'])) : 0;
        $active = isset($_POST['active']) ? absint(wp_unslash($_POST['active'])) : 0;

        if (!$faq_id) {
            wp_send_json_error(['message' => __('Ungültige FAQ-ID.', 'questify')]);
        }

        if ($this->db->update_faq($faq_id, ['active' => $active])) {
            wp_send_json_success(['message' => __('Status geändert.', 'questify')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Ändern des Status.', 'questify')]);
        }
    }

    /**
     * Löscht Anfrage
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_delete_inquiry(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? absint(wp_unslash($_POST['inquiry_id'])) : 0;

        if (!$inquiry_id) {
            wp_send_json_error(['message' => __('Ungültige Anfrage-ID.', 'questify')]);
        }

        if ($this->db->delete_inquiry($inquiry_id)) {
            wp_send_json_success(['message' => __('Anfrage erfolgreich gelöscht.', 'questify')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Löschen.', 'questify')]);
        }
    }

    /**
     * Aktualisiert Anfrage-Status
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_update_inquiry_status(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
        }

        $inquiry_id = isset($_POST['inquiry_id']) ? absint(wp_unslash($_POST['inquiry_id'])) : 0;
        $status = sanitize_text_field(isset($_POST['status']) ? wp_unslash($_POST['status']) : '');

        if (!$inquiry_id || !in_array($status, ['new', 'in_progress', 'answered'])) {
            wp_send_json_error(['message' => __('Ungültige Parameter.', 'questify')]);
        }

        if ($this->db->update_inquiry($inquiry_id, ['status' => $status])) {
            wp_send_json_success(['message' => __('Status aktualisiert.', 'questify')]);
        } else {
            wp_send_json_error(['message' => __('Fehler beim Aktualisieren.', 'questify')]);
        }
    }

    /**
     * Sendet Test-E-Mail
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_send_test_email(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Keine Berechtigung.', 'questify')]);
        }

        if ($this->email->send_test_email()) {
            wp_send_json_success(['message' => esc_html__('Test-E-Mail erfolgreich versendet!', 'questify')]);
        } else {
            wp_send_json_error(['message' => esc_html__('E-Mail konnte nicht versendet werden. Prüfen Sie die Einstellungen.', 'questify')]);
        }
    }

    /**
     * Exportiert Anfragen als CSV
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_export_inquiries(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'questify'));
        }

        $inquiries = $this->db->get_all_inquiries();

        // CSV-Header
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="chatbot-inquiries-' . gmdate('Y-m-d') . '.csv"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Output stream.

        // BOM für Excel UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

        // Spaltenüberschriften
        fputcsv($output, [
            'ID',
            'Datum',
            'Name',
            'E-Mail',
            'Frage',
            'Status',
            'Beantwortet',
            'Hilfreich',
        ], ';');

        // Daten
        foreach ($inquiries as $inquiry) {
            fputcsv($output, [
                $inquiry->id,
                date_i18n('d.m.Y H:i', strtotime($inquiry->timestamp)),
                $inquiry->user_name,
                $inquiry->user_email,
                $inquiry->user_question,
                $inquiry->status,
                $inquiry->matched_faq_id ? 'Ja' : 'Nein',
                $inquiry->was_helpful ?: '-',
            ], ';');
        }

        fclose($output); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Output stream.
        exit;
    }

    /**
     * Generiert Keywords für FAQ
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_generate_keywords(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => esc_html__('Keine Berechtigung.', 'questify')]);
        }

        $question = sanitize_textarea_field(isset($_POST['question']) ? wp_unslash($_POST['question']) : '');
        $answer = wp_kses_post(isset($_POST['answer']) ? wp_unslash($_POST['answer']) : '');

        if (empty($question)) {
            wp_send_json_error(['message' => esc_html__('Bitte geben Sie eine Frage ein.', 'questify')]);
        }

        $generator = Chatbot_Keyword_Generator::get_instance();
        $keywords = $generator->generate_keywords($question, $answer);

        wp_send_json_success([
            'keywords' => $keywords,
            'message' => esc_html__('Keywords erfolgreich generiert!', 'questify')
        ]);
    }

    /**
     * Exportiert FAQs als JSON oder CSV
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_export_faqs(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'questify'));
        }

        $format = sanitize_text_field(isset($_GET['format']) ? wp_unslash($_GET['format']) : 'json');
        $include_inactive = isset($_GET['include_inactive']) ? true : false;

        // FAQs holen
        $args = [];
        if (!$include_inactive) {
            $args['active'] = 1;
        }

        $faqs = $this->db->get_all_faqs($args);

        if ($format === 'csv') {
            // CSV-Export
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="chatbot-faqs-' . gmdate('Y-m-d') . '.csv"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $output = fopen('php://output', 'w'); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Output stream.

            // BOM für Excel UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

            // Spaltenüberschriften
            fputcsv($output, [
                'ID',
                'Frage',
                'Antwort',
                'Keywords',
                'Aktiv',
                'Aufrufe',
                'Erstellt',
            ], ';');

            // Daten
            foreach ($faqs as $faq) {
                fputcsv($output, [
                    $faq->id,
                    $faq->question,
                    wp_strip_all_tags($faq->answer),
                    $faq->keywords,
                    $faq->active ? 'Ja' : 'Nein',
                    $faq->view_count,
                    date_i18n('d.m.Y H:i', strtotime($faq->created_at)),
                ], ';');
            }

            fclose($output); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Output stream.
        } else {
            // JSON-Export
            header('Content-Type: application/json; charset=utf-8');
            header('Content-Disposition: attachment; filename="chatbot-faqs-' . gmdate('Y-m-d') . '.json"');
            header('Pragma: no-cache');
            header('Expires: 0');

            $export_data = [];
            foreach ($faqs as $faq) {
                $export_data[] = [
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                    'keywords' => $faq->keywords,
                    'active' => $faq->active,
                ];
            }

            echo wp_json_encode([
                'version' => QUESTIFY_VERSION,
                'exported_at' => current_time('mysql'),
                'count' => count($export_data),
                'faqs' => $export_data,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }

        exit;
    }

    /**
     * Importiert FAQs aus JSON, CSV oder Copy & Paste
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_import_faqs(): void {
        // Nonce-Prüfung (false = nicht direkt sterben, sondern false zurückgeben)
        if (!check_ajax_referer('chatbot_admin_ajax', 'nonce', false)) {
            wp_send_json_error(['message' => __('Sicherheitsprüfung fehlgeschlagen. Bitte Seite neu laden.', 'questify')]);
            return;
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
            return;
        }

        $import_method = sanitize_text_field(isset($_POST['import_method']) ? wp_unslash($_POST['import_method']) : 'file');
        $imported = 0;
        $errors = [];

        try {
            if ($import_method === 'paste') {
                // Copy & Paste Import
                if (!isset($_POST['import_data'])) {
                    throw new Exception(__('Keine Import-Daten gefunden.', 'questify'));
                }

                // JSON dekodieren
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Raw JSON input is validated by json_decode and per-field sanitization below.
                $raw_data = isset($_POST['import_data']) ? wp_unslash($_POST['import_data']) : '';
                if (!is_string($raw_data)) {
                    $raw_data = '';
                }
                $import_data = json_decode($raw_data, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new Exception(__('JSON Dekodierungsfehler: ', 'questify') . json_last_error_msg());
                }

                if (!is_array($import_data) || empty($import_data)) {
                    throw new Exception(__('Ungültiges Datenformat oder keine Daten vorhanden.', 'questify'));
                }

                // Keyword-Generator laden (nur wenn noch nicht geladen)
                if (!class_exists('Chatbot_Keyword_Generator')) {
                    $generator_file = QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-keyword-generator.php';
                    if (!file_exists($generator_file)) {
                        throw new Exception(__('Keyword-Generator Datei nicht gefunden.', 'questify'));
                    }
                    require_once $generator_file;
                }

                $keyword_generator = Chatbot_Keyword_Generator::get_instance();

                foreach ($import_data as $index => $item) {
                    try {
                        // Sicherstellen dass $item ein Array ist
                        if (!is_array($item)) {
                            $errors[] = sprintf(
                                /* translators: %d: line number */
                                __('Zeile %d: Ungültiges Datenformat.', 'questify'),
                                $index + 1
                            );
                            continue;
                        }

                        $question = isset($item['question']) ? trim($item['question']) : '';
                        $answer = isset($item['answer']) ? trim($item['answer']) : '';
                        $keywords = isset($item['keywords']) ? trim($item['keywords']) : '';

                        if (empty($question) || empty($answer)) {
                            $errors[] = sprintf(
                                /* translators: %d: line number */
                                __('Zeile %d übersprungen: Frage oder Antwort fehlt.', 'questify'),
                                $index + 1
                            );
                            continue;
                        }

                        // Wenn keine Keywords angegeben, automatisch generieren
                        if (empty($keywords)) {
                            $keywords = $keyword_generator->generate_keywords($question, $answer);
                        }

                        // Sichere Bereinigung
                        $clean_question = sanitize_textarea_field($question);

                        // Für Antworten: Erlaube grundlegendes HTML
                        $allowed_html = [
                            'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
                            'u' => [], 'a' => ['href' => [], 'title' => [], 'target' => []],
                            'ul' => [], 'ol' => [], 'li' => [], 'h1' => [], 'h2' => [], 'h3' => [],
                            'h4' => [], 'h5' => [], 'h6' => [], 'blockquote' => [], 'pre' => [],
                            'code' => [], 'span' => ['class' => []], 'div' => ['class' => []]
                        ];
                        $clean_answer = wp_kses($answer, $allowed_html);

                        $clean_keywords = sanitize_textarea_field($keywords);

                        // FAQ einfügen
                        $result = $this->db->insert_faq([
                            'question' => $clean_question,
                            'answer' => $clean_answer,
                            'keywords' => $clean_keywords,
                            'active' => 1,
                        ]);

                        if ($result) {
                            $imported++;
                        } else {
                            $errors[] = sprintf(
                                /* translators: %s: FAQ question (truncated) */
                                __('FAQ konnte nicht importiert werden: %s', 'questify'),
                                substr($question, 0, 50)
                            );
                        }

                    } catch (Exception $e) {
                        $errors[] = sprintf(
                            /* translators: 1: line number, 2: error message */
                            __('Zeile %1$d: %2$s', 'questify'),
                            $index + 1,
                            $e->getMessage()
                        );
                    }
                }

            } else {
                // Datei-Upload Import
                $file_error = isset($_FILES['import_file']['error']) ? (int) $_FILES['import_file']['error'] : null;
                if (!isset($_FILES['import_file']) || !is_array($_FILES['import_file']) || $file_error !== UPLOAD_ERR_OK) {
                    wp_send_json_error(['message' => __('Keine Datei hochgeladen oder Upload-Fehler.', 'questify')]);
                }

                $file_name = isset($_FILES['import_file']['name']) ? sanitize_file_name(wp_unslash($_FILES['import_file']['name'])) : '';
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Validated via is_uploaded_file() and used only as a filesystem path.
                $file_tmp = isset($_FILES['import_file']['tmp_name']) ? $_FILES['import_file']['tmp_name'] : '';
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                if (!in_array($file_ext, ['json', 'csv', 'txt'], true)) {
                    wp_send_json_error(['message' => __('Nur JSON-, CSV- oder TXT-Dateien sind erlaubt.', 'questify')]);
                }

                if (!is_string($file_tmp) || $file_tmp === '') {
                    wp_send_json_error(['message' => __('Upload-Datei nicht gefunden.', 'questify')]);
                }

                if (!is_uploaded_file($file_tmp)) {
                    wp_send_json_error(['message' => __('Ungültige Upload-Datei.', 'questify')]);
                }

                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();

                global $wp_filesystem;
                if (!$wp_filesystem) {
                    throw new Exception(__('Dateisystem konnte nicht initialisiert werden.', 'questify'));
                }

                if ($file_ext === 'json') {
                    // JSON-Import
                    $json_content = $wp_filesystem->get_contents($file_tmp);
                    if ($json_content === false) {
                        throw new Exception(__('Datei konnte nicht gelesen werden.', 'questify'));
                    }
                    $data = json_decode($json_content, true);

                    if (!$data || !isset($data['faqs'])) {
                        throw new Exception(__('Ungültiges JSON-Format.', 'questify'));
                    }

                    // Erlaubte HTML-Tags definieren
                    $allowed_html = [
                        'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
                        'u' => [], 'a' => ['href' => [], 'title' => [], 'target' => []],
                        'ul' => [], 'ol' => [], 'li' => [], 'h1' => [], 'h2' => [], 'h3' => [],
                        'h4' => [], 'h5' => [], 'h6' => [], 'blockquote' => [], 'pre' => [],
                        'code' => [], 'span' => ['class' => []], 'div' => ['class' => []]
                    ];

                    foreach ($data['faqs'] as $faq_data) {
                        if (empty($faq_data['question']) || empty($faq_data['answer'])) {
                            $errors[] = __('FAQ übersprungen: Frage oder Antwort fehlt.', 'questify');
                            continue;
                        }

                        $result = $this->db->insert_faq([
                            'question' => sanitize_textarea_field($faq_data['question']),
                            'answer' => wp_kses($faq_data['answer'], $allowed_html),
                            'keywords' => sanitize_textarea_field($faq_data['keywords'] ?? ''),
                            'active' => isset($faq_data['active']) ? (int) $faq_data['active'] : 1,
                        ]);

                        if ($result) {
                            $imported++;
                        } else {
                            $errors[] = sprintf(
                                /* translators: %s: FAQ question */
                                __('FAQ konnte nicht importiert werden: %s', 'questify'),
                                $faq_data['question']
                            );
                        }
                    }

                } else {
                    // CSV/TXT-Import mit flexiblem Parsing
                    $file_contents = $wp_filesystem->get_contents($file_tmp);
                    if ($file_contents === false) {
                        throw new Exception(__('Datei konnte nicht gelesen werden.', 'questify'));
                    }

                    // BOM entfernen falls vorhanden
                    if (strncmp($file_contents, chr(0xEF) . chr(0xBB) . chr(0xBF), 3) === 0) {
                        $file_contents = substr($file_contents, 3);
                    }

                    // Keyword-Generator laden (nur wenn noch nicht geladen)
                    if (!class_exists('Chatbot_Keyword_Generator')) {
                        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-keyword-generator.php';
                    }
                    $keyword_generator = Chatbot_Keyword_Generator::get_instance();

                    $lines = preg_split("/\r\n|\n|\r/", $file_contents);
                    $lines = is_array($lines) ? $lines : [];

                    $first_line = '';
                    foreach ($lines as $line) {
                        if (trim((string) $line) !== '') {
                            $first_line = (string) $line;
                            break;
                        }
                    }

                    // Trennzeichen automatisch erkennen
                    $delimiter = ';';
                    if (substr_count($first_line, "\t") > substr_count($first_line, ';')) {
                        $delimiter = "\t";
                    } elseif (substr_count($first_line, ',') > substr_count($first_line, ';')) {
                        $delimiter = ',';
                    } elseif (substr_count($first_line, '|') > substr_count($first_line, ';')) {
                        $delimiter = '|';
                    }

                    // Header erkennen
                    $line_index = 0;
                    $header_row = [];
                    while ($line_index < count($lines)) {
                        $line = (string) $lines[$line_index];
                        if (trim($line) === '') {
                            $line_index++;
                            continue;
                        }
                        $header_row = str_getcsv($line, $delimiter);
                        break;
                    }

                    $has_header = !empty($header_row[0]) && in_array(strtolower((string) $header_row[0]), ['frage', 'question', 'id'], true);
                    if ($has_header) {
                        $line_index++;
                    }

                    for (; $line_index < count($lines); $line_index++) {
                        $line = (string) $lines[$line_index];
                        if (trim($line) === '') {
                            continue;
                        }

                        $row = str_getcsv($line, $delimiter);
                        if (count($row) < 2) {
                            continue;
                        }

                        // Flexible Spaltenerkennung
                        // Format kann sein: [Frage, Antwort, Keywords] oder [ID, Frage, Antwort, Keywords]
                        $question = '';
                        $answer = '';
                        $keywords = '';

                        if (count($row) === 2) {
                            // Einfachstes Format: Frage, Antwort
                            $question = trim($row[0]);
                            $answer = trim($row[1]);
                        } elseif (count($row) === 3) {
                            // Format: Frage, Antwort, Keywords
                            $question = trim($row[0]);
                            $answer = trim($row[1]);
                            $keywords = trim($row[2]);
                        } else {
                            // Format mit mehr Spalten (z.B. Export-Format)
                            // Versuche zu erkennen, ob erste Spalte eine ID ist
                            if (is_numeric($row[0])) {
                                $question = trim($row[1] ?? '');
                                $answer = trim($row[2] ?? '');
                                $keywords = trim($row[3] ?? '');
                            } else {
                                $question = trim($row[0]);
                                $answer = trim($row[1]);
                                $keywords = trim($row[2] ?? '');
                            }
                        }

                        if (empty($question) || empty($answer)) {
                            $errors[] = __('Zeile übersprungen: Frage oder Antwort fehlt.', 'questify');
                            continue;
                        }

                        // Wenn keine Keywords angegeben, automatisch generieren
                        if (empty($keywords)) {
                            $keywords = $keyword_generator->generate_keywords($question, $answer);
                        }

                        // Sichere Bereinigung
                        $allowed_html = [
                            'p' => [], 'br' => [], 'strong' => [], 'b' => [], 'em' => [], 'i' => [],
                            'u' => [], 'a' => ['href' => [], 'title' => [], 'target' => []],
                            'ul' => [], 'ol' => [], 'li' => [], 'h1' => [], 'h2' => [], 'h3' => [],
                            'h4' => [], 'h5' => [], 'h6' => [], 'blockquote' => [], 'pre' => [],
                            'code' => [], 'span' => ['class' => []], 'div' => ['class' => []]
                        ];

                        $result = $this->db->insert_faq([
                            'question' => sanitize_textarea_field($question),
                            'answer' => wp_kses($answer, $allowed_html),
                            'keywords' => sanitize_textarea_field($keywords),
                            'active' => 1,
                        ]);

                        if ($result) {
                            $imported++;
                        } else {
                            $errors[] = sprintf(
                                /* translators: %s: FAQ question (truncated) */
                                __('FAQ konnte nicht importiert werden: %s', 'questify'),
                                substr($question, 0, 50)
                            );
                        }
                    }

                }
            }

            // Cache leeren
            $this->db->clear_faq_cache();

            // Erfolg
            $message = sprintf(
                /* translators: %d: number of imported FAQs */
                _n('%d FAQ erfolgreich importiert.', '%d FAQs erfolgreich importiert.', $imported, 'questify'),
                $imported
            );

            if (!empty($errors)) {
                $message .= ' ' . sprintf(
                    /* translators: %d: number of errors */
                    __('%d Fehler aufgetreten.', 'questify'),
                    count($errors)
                );
            }

            wp_send_json_success([
                'message' => $message,
                'imported' => $imported,
                'errors' => $errors,
            ]);

        } catch (Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage(),
                'debug' => get_option('chatbot_debug_mode') ? $e->getTraceAsString() : null
            ]);
        } catch (Error $e) {
            // PHP Fehler abfangen
            wp_send_json_error([
                'message' => __('Ein schwerwiegender Fehler ist aufgetreten beim Import.', 'questify'),
                'debug' => get_option('chatbot_debug_mode') ? $e->getMessage() : null
            ]);
        }
    }

    // ========== Hilfsmethoden ==========


    /**
     * Stellt Standardwerte wieder her
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_restore_defaults(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
        }

        // Activator laden und Standardwerte wiederherstellen
        require_once QUESTIFY_PLUGIN_DIR . 'includes/class-chatbot-activator.php';
        Chatbot_Activator::restore_defaults();

        wp_send_json_success([
            'message' => __('Standardwerte wurden erfolgreich wiederhergestellt!', 'questify')
        ]);
    }

    /**
     * Füllt leere Optionen mit Standardwerten
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_fix_empty_options(): void {
        check_ajax_referer('chatbot_admin_ajax', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Keine Berechtigung.', 'questify')]);
        }

        $default_options = [
            'chatbot_welcome_message' => 'Hallo! 😊 Wie kann ich Ihnen helfen?',
            'chatbot_placeholder_text' => 'Stellen Sie Ihre Frage...',
            'chatbot_no_answer_message' => 'Ich konnte leider keine passende Antwort finden. Möchten Sie uns Ihre Frage per E-Mail senden?',
            'chatbot_thank_you_message' => 'Vielen Dank! Wir haben Ihre Anfrage erhalten und melden uns in Kürze bei Ihnen.',
            'chatbot_button_text' => 'Fragen?',
            'chatbot_gdpr_text' => 'Ich akzeptiere die Datenschutzerklärung.',
        ];

        $fixed = 0;

        foreach ($default_options as $key => $default_value) {
            $current_value = get_option($key);

            // Wenn Option leer ist (leer, false, null, nur Leerzeichen), mit Standardwert füllen
            if (empty($current_value) || trim($current_value) === '') {
                update_option($key, $default_value);
                $fixed++;
            }
        }

        wp_send_json_success([
            'message' => sprintf(
                /* translators: %d: number of options */
                __('%d leere Option(en) wurden mit Standardwerten gefüllt!', 'questify'),
                $fixed
            ),
            'fixed' => $fixed
        ]);
    }
}
