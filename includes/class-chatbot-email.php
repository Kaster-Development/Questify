<?php
/**
 * E-Mail-Versand-Klasse
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Klasse für E-Mail-Versand
 */
class Chatbot_Email {

    /**
     * Singleton-Instanz
     */
    private static ?Chatbot_Email $instance = null;

    /**
     * Database-Instanz
     */
    private Chatbot_Database $db;

    /**
     * Singleton-Methode
     */
    public static function get_instance(): Chatbot_Email {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Konstruktor
     */
    private function __construct() {
        $this->db = Chatbot_Database::get_instance();
    }

    /**
     * Sendet Benachrichtigung bei neuer Anfrage
     *
     * @param int $inquiry_id Inquiry-ID
     * @return bool
     * @since 1.0.0
     */
    public function send_inquiry_notification(int $inquiry_id): bool {
        // Inquiry-Daten abrufen
        $inquiry = $this->db->get_inquiry($inquiry_id);

        if (!$inquiry) {
            $this->log_error('Inquiry nicht gefunden', ['inquiry_id' => $inquiry_id]);
            return false;
        }

        // Einstellungen laden
        $admin_emails = get_option('chatbot_notification_emails', get_option('admin_email'));
        $email_prefix = get_option('chatbot_email_prefix', '[Chatbot]');

        // Validierung
        if (empty($admin_emails)) {
            $this->log_error('Keine E-Mail-Adresse konfiguriert');
            return false;
        }

        // E-Mail-Empfänger
        $to = array_map('trim', explode(',', $admin_emails));

        // Leere Einträge entfernen
        $to = array_filter($to);

        if (empty($to)) {
            $this->log_error('Keine gültigen E-Mail-Adressen gefunden');
            return false;
        }

        // Betreff
        $subject = $email_prefix . ' ' . sprintf(
            __('Neue Anfrage von %s', 'questify'),
            $inquiry->user_name
        );

        // Headers (immer HTML, da Plain-Text als HTML mit <pre> gesendet wird)
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        // HTML-Nachricht
        $message = $this->get_inquiry_email_template($inquiry);

        // Debug-Log vor dem Senden
        $this->log_debug('Versuche E-Mail zu senden', [
            'inquiry_id' => $inquiry_id,
            'to' => implode(', ', $to),
            'subject' => $subject,
        ]);

        // E-Mail senden
        $sent = wp_mail($to, $subject, $message, $headers);

        // Status aktualisieren
        if ($sent) {
            $this->db->update_inquiry($inquiry_id, ['email_sent' => 1]);
            $this->log_debug('Benachrichtigungs-E-Mail gesendet', [
                'inquiry_id' => $inquiry_id,
                'to' => implode(', ', $to),
            ]);
        } else {
            $this->log_error('E-Mail konnte nicht gesendet werden', [
                'inquiry_id' => $inquiry_id,
                'to' => implode(', ', $to),
            ]);
        }

        return $sent;
    }

    /**
     * E-Mail-Template für Inquiry-Benachrichtigung
     *
     * @param object $inquiry Inquiry-Objekt
     * @return string
     * @since 1.0.0
     */
    private function get_inquiry_email_template(object $inquiry): string {
        $admin_url = admin_url('admin.php?page=chatbot-inquiries&action=view&inquiry=' . $inquiry->id);

        // Einstellungen laden
        $email_format = get_option('chatbot_email_format', 'html');
        $include_ip = get_option('chatbot_email_include_ip', true);
        $include_faq = get_option('chatbot_email_include_faq', true);
        $include_user_agent = get_option('chatbot_email_include_user_agent', false);
        $header_color = get_option('chatbot_email_header_color', '#0073aa');
        $footer_color = get_option('chatbot_email_footer_color', '#f4f4f4');

        // Symbole immer anzeigen
        $show_icons = true;

        // FAQ-Details laden wenn vorhanden und aktiviert
        $faq = null;
        if ($inquiry->matched_faq_id && $include_faq) {
            $faq = $this->db->get_faq($inquiry->matched_faq_id);
        }

        // Plain Text Format (aber als HTML mit <pre> für bessere Darstellung in E-Mail-Clients)
        if ($email_format === 'plain') {
            $plain_text = $this->get_plain_text_email($inquiry, $faq, $include_ip, $include_user_agent, $admin_url);
            // Umschließe mit <pre> für korrekte Darstellung in allen E-Mail-Clients
            return '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><pre style="font-family: monospace; font-size: 13px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">' . esc_html($plain_text) . '</pre></body></html>';
        }

        // HTML Format
        ob_start();
        ?>
        <!DOCTYPE html>
        <html lang="de">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                    line-height: 1.6;
                    color: #333;
                    margin: 0;
                    padding: 20px;
                    background-color: #ffffff;
                }
                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }
                .header {
                    background: <?php echo esc_attr($header_color); ?>;
                    padding: 30px 20px;
                    text-align: center;
                }
                .header h2 {
                    margin: 0;
                    font-size: 26px;
                    font-weight: 600;
                    color: #ffffff;
                    text-shadow: 0 1px 2px rgba(0,0,0,0.1);
                }
                .content {
                    padding: 30px 20px;
                }
                .info-row {
                    margin: 12px 0;
                    padding: 8px 0;
                }
                .label {
                    font-weight: 600;
                    color: #666;
                    display: inline-block;
                    min-width: 100px;
                }
                .question-box {
                    background: #f8f9fa;
                    padding: 20px;
                    border-left: 4px solid #0073aa;
                    margin: 25px 0;
                    border-radius: 4px;
                }
                .question-box strong {
                    display: block;
                    margin-bottom: 12px;
                    color: #0073aa;
                    font-size: 16px;
                }
                .answer-box {
                    background: #e8f5e9;
                    padding: 20px;
                    border-left: 4px solid #46b450;
                    margin: 25px 0;
                    border-radius: 4px;
                }
                .answer-box strong {
                    display: block;
                    margin-bottom: 12px;
                    color: #46b450;
                    font-size: 16px;
                }
                .status-badge {
                    display: inline-block;
                    padding: 6px 12px;
                    border-radius: 4px;
                    font-weight: 600;
                    font-size: 14px;
                }
                .status-success {
                    background: #e8f5e9;
                    color: #2e7d32;
                }
                .status-warning {
                    background: #fff3e0;
                    color: #e65100;
                }
                .tip-box {
                    background: #e3f2fd;
                    padding: 15px;
                    border-radius: 4px;
                    margin: 25px 0;
                    border-left: 4px solid #2196f3;
                }
                .button {
                    display: inline-block;
                    background: <?php echo esc_attr($header_color); ?>;
                    color: white !important;
                    padding: 16px 40px;
                    text-decoration: none;
                    border-radius: 8px;
                    margin: 25px 0;
                    font-weight: 600;
                    font-size: 15px;
                    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
                    transition: all 0.3s ease;
                }
                .button:hover {
                    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                    transform: translateY(-1px);
                }
                .footer {
                    background: <?php echo esc_attr($footer_color); ?>;
                    padding: 30px 20px;
                    text-align: center;
                    color: #666;
                    font-size: 13px;
                    line-height: 1.8;
                    border-top: 1px solid #e0e0e0;
                }
                .footer p {
                    margin: 5px 0;
                }
                .footer a {
                    color: #0073aa;
                    text-decoration: none;
                    font-weight: 500;
                }
                .footer a:hover {
                    text-decoration: underline;
                }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>Neue Chatbot-Anfrage</h2>
                </div>

                <div class="content">
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span><?php echo esc_html($inquiry->user_name); ?></span>
                    </div>

                    <div class="info-row">
                        <span class="label">E-Mail:</span>
                        <a href="mailto:<?php echo esc_attr($inquiry->user_email); ?>" style="color: #0073aa; text-decoration: none;">
                            <?php echo esc_html($inquiry->user_email); ?>
                        </a>
                    </div>

                    <div class="info-row">
                        <span class="label">Datum:</span>
                        <span><?php echo date_i18n('d.m.Y H:i', strtotime($inquiry->timestamp)); ?> Uhr</span>
                    </div>

                    <div class="info-row">
                        <span class="label">Status:</span>
                        <?php if ($inquiry->matched_faq_id): ?>
                            <span class="status-badge status-success">
                                <?php if ($show_icons): ?>[✓]<?php endif; ?> Antwort gefunden (FAQ #<?php echo $inquiry->matched_faq_id; ?>)
                            </span>
                        <?php else: ?>
                            <span class="status-badge status-warning">
                                <?php if ($show_icons): ?>[!]<?php endif; ?> Keine passende Antwort gefunden
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($include_ip && $inquiry->ip_address): ?>
                    <div class="info-row">
                        <span class="label">IP-Adresse:</span>
                        <span><?php echo esc_html($inquiry->ip_address); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($include_user_agent && $inquiry->user_agent): ?>
                    <div class="info-row">
                        <span class="label">Browser:</span>
                        <span style="font-size: 11px; color: #999;"><?php echo esc_html($inquiry->user_agent); ?></span>
                    </div>
                    <?php endif; ?>

                    <div class="question-box">
                        <strong>Frage des Kunden:</strong>
                        <div><?php echo nl2br(esc_html($inquiry->user_question)); ?></div>
                    </div>

                    <?php if ($faq): ?>
                    <div class="answer-box">
                        <strong>Gefundene FAQ-Antwort:</strong>
                        <div style="margin-bottom: 15px;">
                            <strong style="color: #333; display: inline;">Frage:</strong>
                            <?php echo esc_html($faq->question); ?>
                        </div>
                        <div>
                            <strong style="color: #333; display: inline;">Antwort:</strong>
                            <?php echo wp_kses_post($faq->answer); ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div class="tip-box">
                        <strong><?php if ($show_icons): ?>[i]<?php endif; ?> Tipp:</strong>
                        Sie können direkt auf diese E-Mail antworten, um dem Kunden zu helfen.
                    </div>

                    <center>
                        <a href="<?php echo esc_url($admin_url); ?>" class="button">
                            Im Backend ansehen →
                        </a>
                    </center>
                </div>

                <div class="footer">
                    <p>
                        Diese E-Mail wurde automatisch vom Questify Plugin generiert.<br>
                        <a href="<?php echo esc_url(home_url()); ?>"><?php echo esc_html(get_bloginfo('name')); ?></a>
                    </p>
                </div>
            </div>
        </body>
        </html>
        <?php
        return ob_get_clean();
    }

    /**
     * Plain-Text E-Mail Template
     *
     * @param object $inquiry Inquiry-Objekt
     * @param object|null $faq FAQ-Objekt
     * @param bool $include_ip IP-Adresse anzeigen
     * @param bool $include_user_agent User-Agent anzeigen
     * @param string $admin_url Link zum Backend
     * @return string
     * @since 1.0.0
     */
    private function get_plain_text_email(object $inquiry, ?object $faq, bool $include_ip, bool $include_user_agent, string $admin_url): string {
        $text = "NEUE CHATBOT-ANFRAGE\n";
        $text .= str_repeat("=", 50) . "\n\n";

        $text .= "Name: " . $inquiry->user_name . "\n";
        $text .= "E-Mail: " . $inquiry->user_email . "\n";
        $text .= "Datum: " . date_i18n('d.m.Y H:i', strtotime($inquiry->timestamp)) . " Uhr\n";

        if ($include_ip && $inquiry->ip_address) {
            $text .= "IP-Adresse: " . $inquiry->ip_address . "\n";
        }

        if ($include_user_agent && $inquiry->user_agent) {
            $text .= "Browser: " . $inquiry->user_agent . "\n";
        }

        $text .= "\nStatus: ";
        if ($inquiry->matched_faq_id) {
            $text .= "Antwort gefunden (FAQ #" . $inquiry->matched_faq_id . ")\n";
        } else {
            $text .= "Keine passende Antwort gefunden\n";
        }

        $text .= "\n" . str_repeat("-", 50) . "\n";
        $text .= "FRAGE DES KUNDEN:\n";
        $text .= str_repeat("-", 50) . "\n";
        $text .= $this->wrap_text($inquiry->user_question, 70) . "\n";

        if ($faq) {
            $text .= "\n" . str_repeat("-", 50) . "\n";
            $text .= "GEFUNDENE FAQ-ANTWORT:\n";
            $text .= str_repeat("-", 50) . "\n";
            $text .= "Frage: " . $this->wrap_text($faq->question, 70) . "\n\n";
            $text .= "Antwort:\n" . $this->wrap_text(wp_strip_all_tags($faq->answer), 70) . "\n";
        }

        $text .= "\n" . str_repeat("-", 50) . "\n";
        $text .= "TIPP:\n";
        $text .= $this->wrap_text("Sie können direkt auf diese E-Mail antworten, um dem Kunden zu helfen.", 70) . "\n";
        $text .= "\nIm Backend ansehen:\n" . $admin_url . "\n";
        $text .= "\n" . str_repeat("=", 50) . "\n";
        $text .= "Diese E-Mail wurde automatisch vom Questify Plugin generiert.\n";
        $text .= get_bloginfo('name') . " - " . home_url() . "\n";

        return $text;
    }

    /**
     * Wickelt Text in Zeilen mit maximal $width Zeichen um
     *
     * @param string $text Text zum Umbrechen
     * @param int $width Maximale Zeilenlänge
     * @return string Umgebrochener Text
     * @since 1.0.0
     */
    private function wrap_text(string $text, int $width = 70): string {
        // Entferne HTML-Tags
        $text = strip_tags($text);

        // Ersetze <br> und ähnliche durch Zeilenumbrüche
        $text = str_replace(['<br>', '<br/>', '<br />'], "\n", $text);

        // Umbrechen
        return wordwrap($text, $width, "\n", false);
    }

    /**
     * Sendet Test-E-Mail
     *
     * @return bool
     * @since 1.0.0
     */
    public function send_test_email(): bool {
        $test_inquiry = (object) [
            'id' => 0,
            'user_name' => 'Test Benutzer',
            'user_email' => 'test@example.com',
            'user_question' => 'Dies ist eine Test-Anfrage zur Überprüfung der E-Mail-Funktion des Questify Plugins.',
            'timestamp' => current_time('mysql'),
            'matched_faq_id' => null,
            'ip_address' => '127.0.0.1',
        ];

        // Einstellungen laden
        $admin_emails = get_option('chatbot_notification_emails', get_option('admin_email'));
        $email_prefix = get_option('chatbot_email_prefix', '[Chatbot]');

        // E-Mail-Empfänger
        $to = array_map('trim', explode(',', $admin_emails));

        // Betreff
        $subject = $email_prefix . ' ' . __('Test-E-Mail', 'questify');

        // Headers
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        // HTML-Nachricht
        $message = $this->get_inquiry_email_template($test_inquiry);

        // E-Mail senden
        $sent = wp_mail($to, $subject, $message, $headers);

        if ($sent) {
            $this->log_debug('Test-E-Mail gesendet', ['to' => implode(', ', $to)]);
        } else {
            $this->log_error('Test-E-Mail konnte nicht gesendet werden', ['to' => implode(', ', $to)]);
        }

        return $sent;
    }

    /**
     * Loggt Fehler
     *
     * @param string $message Fehlermeldung
     * @param array $context Kontext-Daten
     * @return void
     * @since 1.0.0
     */
    private function log_error(string $message, array $context = []): void {
        if (get_option('chatbot_debug_mode')) {
            error_log(sprintf(
                '[Questify - Email] ERROR: %s - Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }

    /**
     * Loggt Debug-Informationen
     *
     * @param string $message Debug-Meldung
     * @param array $context Kontext-Daten
     * @return void
     * @since 1.0.0
     */
    private function log_debug(string $message, array $context = []): void {
        if (get_option('chatbot_debug_mode')) {
            error_log(sprintf(
                '[Questify - Email] DEBUG: %s - Context: %s',
                $message,
                json_encode($context)
            ));
        }
    }
}
