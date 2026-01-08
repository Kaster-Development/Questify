<?php
/**
 * Admin Area Class
 *
 * @package Questify
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class for Admin Area
 */
class Questi_Admin {

    /**
     * Database instance
     */
    private Questi_Database $db;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = Questi_Database::get_instance();

        // Register hooks
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_questi_save_faq', [$this, 'handle_save_faq']);
        add_action('admin_notices', [$this, 'show_admin_notices']);

        // Bulk actions for FAQs
        add_filter('bulk_actions-toplevel_page_questi-faqs', [$this, 'add_bulk_actions']);
        add_filter('handle_bulk_actions-toplevel_page_questi-faqs', [$this, 'handle_bulk_actions'], 10, 3);
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
            'questi-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-format-chat',
            30
        );

        // Dashboard (Untermenü mit gleichem Slug wie Hauptmenü)
        add_submenu_page(
            'questi-dashboard',
            __('Dashboard', 'questify'),
            __('Dashboard', 'questify'),
            'manage_options',
            'questi-dashboard',
            [$this, 'render_dashboard']
        );

        // Fragen & Antworten
        add_submenu_page(
            'questi-dashboard',
            __('Fragen & Antworten', 'questify'),
            __('Fragen & Antworten', 'questify'),
            'manage_options',
            'questi-faqs',
            [$this, 'render_faqs']
        );

        // Anfragen
        add_submenu_page(
            'questi-dashboard',
            __('Anfragen', 'questify'),
            __('Anfragen', 'questify'),
            'manage_options',
            'questi-inquiries',
            [$this, 'render_inquiries']
        );

        // Auswertung
        add_submenu_page(
            'questi-dashboard',
            __('Auswertung', 'questify'),
            __('Auswertung', 'questify'),
            'manage_options',
            'questi-analytics',
            [$this, 'render_analytics']
        );

        // Einstellungen
        add_submenu_page(
            'questi-dashboard',
            __('Einstellungen', 'questify'),
            __('Einstellungen', 'questify'),
            'manage_options',
            'questi-settings',
            [$this, 'render_settings']
        );

        // Über den Entwickler
        add_submenu_page(
            'questi-dashboard',
            __('Über den Entwickler', 'questify'),
            __('Über den Entwickler', 'questify'),
            'manage_options',
            'questi-about',
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
            'questi-admin-style',
            QUESTIFY_PLUGIN_URL . 'admin/css/admin-style.css',
            [],
            QUESTIFY_VERSION
        );

        // JavaScript
        wp_enqueue_script(
            'questi-admin-script',
            QUESTIFY_PLUGIN_URL . 'admin/js/admin-script.js',
            ['jquery', 'wp-color-picker'],
            QUESTIFY_VERSION,
            true
        );

        // Chart.js für Analytics
        if ($hook === 'questi-dashboard_page_questi-analytics') {
            wp_enqueue_script(
                'questify-simple-charts',
                QUESTIFY_PLUGIN_URL . 'admin/js/simple-charts.js',
                [],
                QUESTIFY_VERSION,
                true
            );
        }

        // Lokalisierung
        wp_localize_script('questi-admin-script', 'questiAdmin', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('Questi_Admin_ajax'),
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
            'questi_enabled',
            'questi_welcome_message',
            'questi_placeholder_text',
            'questi_no_answer_message',
            'questi_thank_you_message',
            'questi_position',
            'questi_primary_color',
            'questi_button_text',
            'questi_size',
            'questi_notification_emails',
            'questi_email_prefix',
            'questi_rate_limiting_enabled',
            'questi_rate_limit_requests',
            'questi_rate_limit_window',
            'questi_gdpr_checkbox',
            'questi_gdpr_text',
            'questi_ip_anonymize_days',
            'questi_auto_embed',
            'questi_exclude_pages',
            'questi_debug_mode',
            'questi_min_score',
            'questi_fuzzy_matching',
            'questi_levenshtein_threshold',
            'questi_stopwords',
        ];

        foreach ($settings as $setting) {
            register_setting(
                'questi_settings',
                $setting,
                [
                    'sanitize_callback' => function ($value) use ($setting) {
                        return $this->sanitize_setting_value($setting, $value);
                    },
                ]
            );
        }
    }

    /**
     * Sanitization callback for plugin settings.
     *
     * @param string $setting Setting key.
     * @param mixed  $value   Raw value.
     * @return mixed
     */
    private function sanitize_setting_value(string $setting, mixed $value): mixed {
        switch ($setting) {
            case 'questi_enabled':
            case 'questi_rate_limiting_enabled':
            case 'questi_gdpr_checkbox':
            case 'questi_auto_embed':
            case 'questi_debug_mode':
            case 'questi_fuzzy_matching':
                return absint($value) ? 1 : 0;

            case 'questi_rate_limit_requests':
            case 'questi_rate_limit_window':
            case 'questi_ip_anonymize_days':
            case 'questi_min_score':
            case 'questi_levenshtein_threshold':
                return absint($value);

            case 'questi_position':
                $pos = sanitize_key((string) $value);
                return in_array($pos, ['left', 'right'], true) ? $pos : 'right';

            case 'questi_size':
                $size = sanitize_key((string) $value);
                return in_array($size, ['small', 'medium', 'large'], true) ? $size : 'medium';

            case 'questi_primary_color':
                return sanitize_hex_color((string) $value) ?: '';

            case 'questi_notification_emails':
                $emails_raw = is_string($value) ? $value : '';
                $emails = array_filter(array_map('trim', explode(',', $emails_raw)));
                $emails = array_values(array_filter(array_map('sanitize_email', $emails)));
                return implode(', ', $emails);

            case 'questi_stopwords':
            case 'questi_gdpr_text':
                return sanitize_textarea_field((string) $value);

            case 'questi_welcome_message':
            case 'questi_placeholder_text':
            case 'questi_no_answer_message':
            case 'questi_thank_you_message':
            case 'questi_button_text':
            case 'questi_email_prefix':
            case 'questi_exclude_pages':
            default:
                return sanitize_text_field((string) $value);
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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : 'list';
        $action = in_array($action, ['list', 'edit', 'add'], true) ? $action : 'list';

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
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only routing parameter.
        $action = isset($_GET['action']) ? sanitize_key(wp_unslash($_GET['action'])) : 'list';
        $action = in_array($action, ['list', 'view'], true) ? $action : 'list';

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
        $nonce = isset($_POST['questi_faq_nonce']) ? sanitize_text_field(wp_unslash($_POST['questi_faq_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'questi_save_faq')) {
            wp_die(esc_html__('Sicherheitsprüfung fehlgeschlagen.', 'questify'));
        }

        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Keine Berechtigung.', 'questify'));
        }

        // Daten holen
        $faq_id = isset($_POST['faq_id']) ? absint(wp_unslash($_POST['faq_id'])) : 0;
        $question = sanitize_textarea_field(isset($_POST['question']) ? wp_unslash($_POST['question']) : '');
        $answer = wp_kses_post(isset($_POST['answer']) ? wp_unslash($_POST['answer']) : '');
        $keywords = sanitize_textarea_field(isset($_POST['keywords']) ? wp_unslash($_POST['keywords']) : '');
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
            $generator = Questi_Keyword_Generator::get_instance();
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
            wp_safe_redirect(admin_url('admin.php?page=questi-faqs&action=add'));
        } else {
            wp_safe_redirect(admin_url('admin.php?page=questi-faqs'));
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

        // Nonce prüfen
        $nonce = isset($_POST['questi_bulk_nonce']) ? sanitize_text_field(wp_unslash($_POST['questi_bulk_nonce'])) : '';
        if ($nonce === '' || !wp_verify_nonce($nonce, 'questi_bulk_action')) {
            return $redirect_to;
        }

        // Berechtigung prüfen
        if (!current_user_can('manage_options')) {
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
        set_transient('Questi_Admin_notice', [
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
        $notice = get_transient('Questi_Admin_notice');

        if ($notice) {
            printf(
                '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
                esc_attr($notice['type']),
                esc_html($notice['message'])
            );
            delete_transient('Questi_Admin_notice');
        }

        // Bulk-Action-Notices
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice.
        if (isset($_GET['bulk_action']) && isset($_GET['bulk_count'])) {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice.
            $action = sanitize_text_field(wp_unslash($_GET['bulk_action']));
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only admin notice.
            $count = absint(wp_unslash($_GET['bulk_count']));

            $messages = [
                'activate' => sprintf(
                    /* translators: %d: number of FAQs */
                    _n('%d FAQ aktiviert.', '%d FAQs aktiviert.', $count, 'questify'),
                    $count
                ),
                'deactivate' => sprintf(
                    /* translators: %d: number of FAQs */
                    _n('%d FAQ deaktiviert.', '%d FAQs deaktiviert.', $count, 'questify'),
                    $count
                ),
                'delete' => sprintf(
                    /* translators: %d: number of FAQs */
                    _n('%d FAQ gelöscht.', '%d FAQs gelöscht.', $count, 'questify'),
                    $count
                ),
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
