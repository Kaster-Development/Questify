<?php
/**
 * Admin Area Class
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for Admin Area
 */
class Chatbot_Admin {

    /**
     * Database instance
     */
    private Chatbot_Database $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Chatbot_Database::get_instance();

        // Register hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_chatbot_save_faq', [$this, 'handle_save_faq']);
        add_action('admin_notices', [$this, 'show_admin_notices']);

        // Bulk actions for FAQs
        add_filter('bulk_actions-toplevel_page_chatbot-faqs', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-toplevel_page_chatbot-faqs', [$this, 'handle_bulk_actions'], 10, 3);
    }

    /**
     * Add admin menu
     *
     * @return void
     * @since 1.0.0
     */
    public function add_admin_menu(): void {
        // Main menu
        add_menu_page(
            __('FAQ Chatbot', 'questify'),
            __('FAQ Chatbot', 'questify'),
            'manage_options',
            'chatbot-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-format-chat',
            30
        );

        // Dashboard (Untermenü mit gleichem Slug wie Hauptmenü)
        add_submenu_page(
            'chatbot-dashboard',
            __('Dashboard', 'questify'),
            __('Dashboard', 'questify'),
            'manage_options',
            'chatbot-dashboard',
            [$this, 'render_dashboard']
        );

        // Fragen & Antworten
        add_submenu_page(
            'chatbot-dashboard',
            __('Fragen & Antworten', 'questify'),
            __('Fragen & Antworten', 'questify'),
            'manage_options',
            'chatbot-faqs',
            [$this, 'render_faqs']
        );

        // Anfragen
        add_submenu_page(
            'chatbot-dashboard',
            __('Anfragen', 'questify'),
            __('Anfragen', 'questify'),
            'manage_options',
            'chatbot-inquiries',
            [$this, 'render_inquiries']
        );

        // Auswertung
        add_submenu_page(
            'chatbot-dashboard',
            __('Auswertung', 'questify'),
            __('Auswertung', 'questify'),
            'manage_options',
            'chatbot-analytics',
            [$this, 'render_analytics']
        );

        // Einstellungen
        add_submenu_page(
            'chatbot-dashboard',
            __('Einstellungen', 'questify'),
            __('Einstellungen', 'questify'),
            'manage_options',
            'chatbot-settings',
            [$this, 'render_settings']
        );

        // Über den Entwickler
        add_submenu_page(
            'chatbot-dashboard',
            __('Über den Entwickler', 'questify'),
            __('Über den Entwickler', 'questify'),
            'manage_options',
            'chatbot-about',
            [$this, 'render_about']
        );
    }

    /**
     * Lädt Admin-Assets
     *
     * @param string $hook Aktueller Admin-Hook
     * @return void
     * @since 1.0.0
     */
    public function enqueue_admin_assets(string $hook): void {
        // Nur auf unseren Plugin-Seiten laden
        if (strpos($hook, 'chatbot-') === false) {
            return;
        }

        // Dashicons (für Icons in Buttons/Links)
        wp_enqueue_style('dashicons');

        // CSS
        wp_enqueue_style(
            'chatbot-admin-style',
            QUESTIFY_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            QUESTIFY_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'chatbot-admin-script',
            QUESTIFY_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery', 'wp-color-picker'],
            QUESTIFY_VERSION,
            true
        );

        // Chart.js für Analytics
        if ($hook === 'faq-chatbot_page_chatbot-analytics') {
            wp_enqueue_script(
                'chartjs',
                'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
                [],
                '4.4.1',
                true
            );
        }

        // Lokalisierung
        wp_localize_script('chatbot-admin-script', 'chatbotAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('chatbot_admin_ajax'),
            'strings' => [
                'delete_confirm' => __('Sind Sie sicher, dass Sie diesen Eintrag löschen möchten?', 'questify'),
                'error' => __('Ein Fehler ist aufgetreten.', 'questify'),
                'success' => __('Erfolgreich gespeichert.', 'questify'),
            ],
        ]);

        // WordPress Color Picker
        wp_enqueue_style('wp-color-picker');
    }

    /**
     * Registriert Einstellungen
     *
     * @return void
     * @since 1.0.0
     */
    public function register_settings(): void {
        // Alle Einstellungen registrieren
        $settings = [
            'chatbot_enabled',
            'chatbot_welcome_message',
            'chatbot_placeholder_text',
            'chatbot_no_answer_message',
            'chatbot_thank_you_message',
            'chatbot_position',
            'chatbot_primary_color',
            'chatbot_button_text',
            'chatbot_size',
            'chatbot_notification_emails',
            'chatbot_email_prefix',
            'chatbot_rate_limiting_enabled',
            'chatbot_rate_limit_requests',
            'chatbot_rate_limit_window',
            'chatbot_gdpr_checkbox',
            'chatbot_gdpr_text',
            'chatbot_ip_anonymize_days',
            'chatbot_auto_embed',
            'chatbot_exclude_pages',
            'chatbot_debug_mode',
            'chatbot_min_score',
            'chatbot_fuzzy_matching',
            'chatbot_levenshtein_threshold',
            'chatbot_stopwords',
        ];

        foreach ($settings as $setting) {
            register_setting('chatbot_settings', $setting);
        }
    }

    // ========== Render-Methoden ==========

    /**
     * Rendert Dashboard
     *
     * @return void
     * @since 1.0.0
     */
    public function render_dashboard(): void {
        require_once QUESTIFY_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Rendert FAQs-Seite
     *
     * @return void
     * @since 1.0.0
     */
    public function render_faqs(): void {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'edit' || $action === 'add') {
            require_once QUESTIFY_PLUGIN_DIR . 'admin/views/faq-edit.php';
        } else {
            require_once QUESTIFY_PLUGIN_DIR . 'admin/views/faqs-list.php';
        }
    }

    /**
     * Rendert Anfragen-Seite
     *
     * @return void
     * @since 1.0.0
     */
    public function render_inquiries(): void {
        $action = $_GET['action'] ?? 'list';

        if ($action === 'view') {
            require_once QUESTIFY_PLUGIN_DIR . 'admin/views/inquiry-detail.php';
        } else {
            require_once QUESTIFY_PLUGIN_DIR . 'admin/views/inquiries-list.php';
        }
    }

    /**
     * Rendert Analytics-Seite
     *
     * @return void
     * @since 1.0.0
     */
    public function render_analytics(): void {
        require_once QUESTIFY_PLUGIN_DIR . 'admin/views/analytics.php';
    }

    /**
     * Rendert Einstellungen-Seite
     *
     * @return void
     * @since 1.0.0
     */
    public function render_settings(): void {
        require_once QUESTIFY_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Rendert Über-den-Entwickler-Seite
     *
     * @return void
     * @since 1.0.0
     */
    public function render_about(): void {
        require_once QUESTIFY_PLUGIN_DIR . 'admin/views/about.php';
    }

    // ========== Action-Handler ==========

    /**
     * Speichert FAQ
     *
     * @return void
     * @since 1.0.0
     */
    public function handle_save_faq(): void {
        // Nonce prüfen
        if (!isset($_POST['chatbot_faq_nonce']) || !wp_verify_nonce($_POST['chatbot_faq_nonce'], 'chatbot_save_faq')) {
            wp_die(__('Sicherheitsprüfung fehlgeschlagen.', 'questify'));
        }

        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(__('Keine Berechtigung.', 'questify'));
        }

        // Daten holen
        $faq_id = isset($_POST['faq_id']) ? (int) $_POST['faq_id'] : 0;
        $question = sanitize_textarea_field($_POST['question'] ?? '');
        $answer = wp_kses_post($_POST['answer'] ?? '');
        $keywords = sanitize_textarea_field($_POST['keywords'] ?? '');
        $active = isset($_POST['active']) ? 1 : 0;
        $auto_generate_keywords = isset($_POST['auto_generate_keywords']) ? true : false;

        // Validierung
        if (strlen($question) < 10) {
            $this->set_admin_notice('error', __('Die Frage muss mindestens 10 Zeichen haben.', 'questify'));
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        if (strlen($answer) < 20) {
            $this->set_admin_notice('error', __('Die Antwort muss mindestens 20 Zeichen haben.', 'questify'));
            wp_safe_redirect(wp_get_referer());
            exit;
        }

        // Keywords automatisch generieren wenn leer oder wenn explizit gewünscht
        if (empty($keywords) || $auto_generate_keywords) {
            $generator = Chatbot_Keyword_Generator::get_instance();
            $generated_keywords = $generator->generate_keywords($question, $answer);

            // Wenn bereits Keywords vorhanden sind, zusammenführen
            if (!empty($keywords) && $auto_generate_keywords) {
                $existing = array_map('trim', explode(',', $keywords));
                $generated = array_map('trim', explode(',', $generated_keywords));
                $all_keywords = array_unique(array_merge($existing, $generated));
                $keywords = implode(', ', $all_keywords);
            } else {
                $keywords = $generated_keywords;
            }
        }

        // Speichern
        $data = [
            'question' => $question,
            'answer' => $answer,
            'keywords' => $keywords,
            'active' => $active,
        ];

        if ($faq_id > 0) {
            // Update
            $result = $this->db->update_faq($faq_id, $data);
            $message = __('FAQ erfolgreich aktualisiert.', 'questify');
        } else {
            // Insert
            $result = $this->db->insert_faq($data);
            $message = __('FAQ erfolgreich erstellt.', 'questify');
        }

        if ($result) {
            $this->set_admin_notice('success', $message);
        } else {
            $this->set_admin_notice('error', __('Fehler beim Speichern.', 'questify'));
        }

        // Redirect
        if (isset($_POST['save_and_new'])) {
            wp_safe_redirect(admin_url('admin.php?page=chatbot-faqs&action=add'));
        } else {
            wp_safe_redirect(admin_url('admin.php?page=chatbot-faqs'));
        }
        exit;
    }

    /**
     * Fügt Bulk-Actions hinzu
     *
     * @param array $actions Aktionen
     * @return array
     * @since 1.0.0
     */
    public function add_bulk_actions(array $actions): array {
        $actions['activate'] = __('Aktivieren', 'questify');
        $actions['deactivate'] = __('Deaktivieren', 'questify');
        return $actions;
    }

    /**
     * Verarbeitet Bulk-Actions
     *
     * @param string $redirect_to Redirect-URL
     * @param string $doaction Aktion
     * @param array $ids Ausgewählte IDs
     * @return string
     * @since 1.0.0
     */
    public function handle_bulk_actions(string $redirect_to, string $doaction, array $ids): string {
        if (!in_array($doaction, ['activate', 'deactivate', 'delete'])) {
            return $redirect_to;
        }

        $count = 0;

        foreach ($ids as $id) {
            if ($doaction === 'delete') {
                if ($this->db->delete_faq($id)) {
                    $count++;
                }
            } else {
                $active = $doaction === 'activate' ? 1 : 0;
                if ($this->db->update_faq($id, ['active' => $active])) {
                    $count++;
                }
            }
        }

        $redirect_to = add_query_arg('bulk_action', $doaction, $redirect_to);
        $redirect_to = add_query_arg('bulk_count', $count, $redirect_to);

        return $redirect_to;
    }

    // ========== Hilfsmethoden ==========

    /**
     * Setzt Admin-Notice
     *
     * @param string $type Typ (success, error, warning, info)
     * @param string $message Nachricht
     * @return void
     * @since 1.0.0
     */
    private function set_admin_notice(string $type, string $message): void {
        set_transient('chatbot_admin_notice', [
            'type' => $type,
            'message' => $message,
        ], 30);
    }

    /**
     * Zeigt Admin-Notices
     *
     * @return void
     * @since 1.0.0
     */
    public function show_admin_notices(): void {
        $notice = get_transient('chatbot_admin_notice');

        if ($notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
            delete_transient('chatbot_admin_notice');
        }

        // Bulk-Action-Notices
        if (isset($_GET['bulk_action']) && isset($_GET['bulk_count'])) {
            $action = sanitize_text_field($_GET['bulk_action']);
            $count = (int) $_GET['bulk_count'];

            $messages = [
                'activate' => sprintf(_n('%d FAQ aktiviert.', '%d FAQs aktiviert.', $count, 'questify'), $count),
                'deactivate' => sprintf(_n('%d FAQ deaktiviert.', '%d FAQs deaktiviert.', $count, 'questify'), $count),
                'delete' => sprintf(_n('%d FAQ gelöscht.', '%d FAQs gelöscht.', $count, 'questify'), $count),
            ];

            if (isset($messages[$action])) {
                printf(
                    '<div class="notice notice-success is-dismissible"><p>%s</p></div>',
                    esc_html($messages[$action])
                );
            }
        }
    }
}
