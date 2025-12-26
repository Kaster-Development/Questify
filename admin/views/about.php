<?php
/**
 * Über den Entwickler View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

if (!defined('ABSPATH')) exit;

// Helper-Funktion: Gibt Option-Wert oder Standardwert zurück
function get_chatbot_option($option_name, $default = '') {
    $value = get_option($option_name, $default);

    if ($value === '' || $value === false) {
        return $default;
    }

    return $value;
}

// Standardwerte
$company_name = get_chatbot_option('chatbot_company_name', 'Steffen Kaster - Kaster Development');
$company_address = get_chatbot_option('chatbot_company_address', '');
$company_phone = get_chatbot_option('chatbot_company_phone', '');
$company_website = get_chatbot_option('chatbot_company_website', 'https://kaster-development.de');
$feedback_email = get_chatbot_option('chatbot_feedback_email', 'info@kaster-development.de');

// GitHub (Support/Feedback)
$github_repo_url = 'https://github.com/Kaster-Development/Questify';
$github_issues_url = $github_repo_url . '/issues';
?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div style="max-width: 800px;">
        <!-- Plugin Info Card -->
        <div style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,0.04); padding: 0; margin: 20px 0;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px;">
                <h2 style="margin: 0 0 10px 0; color: white; font-size: 28px;">
                    <span class="dashicons dashicons-format-chat" style="font-size: 32px; vertical-align: middle; margin-right: 10px;"></span>
                    Questify
                </h2>
                <p style="margin: 0; font-size: 16px; opacity: 0.95;">
                    <?php _e('Intelligenter FAQ-Chatbot mit Backend-Verwaltung', 'questify'); ?>
                </p>
            </div>

            <div style="padding: 30px;">
                <h3 style="margin-top: 0; color: #23282d; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                    <span class="dashicons dashicons-admin-users" style="color: #0073aa;"></span>
                    <?php _e('Über den Entwickler', 'questify'); ?>
                </h3>

                <table class="form-table" style="margin-top: 20px;">
                    <tr>
                        <th scope="row" style="width: 200px;">
                            <span class="dashicons dashicons-businessman" style="color: #0073aa;"></span>
                            <?php _e('Entwickler / Firma:', 'questify'); ?>
                        </th>
                        <td>
                            <strong style="font-size: 16px;"><?php echo esc_html($company_name); ?></strong>
                        </td>
                    </tr>

                    <?php if (!empty($company_address)): ?>
                    <tr>
                        <th scope="row">
                            <span class="dashicons dashicons-location" style="color: #0073aa;"></span>
                            <?php _e('Adresse:', 'questify'); ?>
                        </th>
                        <td>
                            <?php echo nl2br(esc_html($company_address)); ?>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <?php if (!empty($company_phone)): ?>
                    <tr>
                        <th scope="row">
                            <span class="dashicons dashicons-phone" style="color: #0073aa;"></span>
                            <?php _e('Telefon:', 'questify'); ?>
                        </th>
                        <td>
                            <a href="tel:<?php echo esc_attr(str_replace(' ', '', $company_phone)); ?>" style="text-decoration: none;">
                                <?php echo esc_html($company_phone); ?>
                            </a>
                        </td>
                    </tr>
                    <?php endif; ?>

                    <tr>
                        <th scope="row">
                            <span class="dashicons dashicons-admin-site" style="color: #0073aa;"></span>
                            <?php _e('Website:', 'questify'); ?>
                        </th>
                        <td>
                            <a href="<?php echo esc_url($company_website); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
                                <?php echo esc_html($company_website); ?> <span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: middle;"></span>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row">
                            <span class="dashicons dashicons-email" style="color: #0073aa;"></span>
                            <?php _e('E-Mail:', 'questify'); ?>
                        </th>
                        <td>
                            <a href="mailto:<?php echo esc_attr($feedback_email); ?>" style="text-decoration: none;">
                                <?php echo esc_html($feedback_email); ?>
                            </a>
                        </td>
                    </tr>
                </table>

                <div style="background: #f0f6fc; border-left: 4px solid #0073aa; padding: 15px; margin-top: 30px;">
                    <p style="margin: 0;">
                        <span class="dashicons dashicons-info" style="color: #0073aa;"></span>
                        <strong><?php _e('Hinweis:', 'questify'); ?></strong>
                        <?php _e('Die angegebene E-Mail-Adresse wird als Reply-To-Adresse in den Benachrichtigungs-E-Mails verwendet.', 'questify'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Plugin Features -->
        <div style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,0.04); padding: 30px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #23282d; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                <span class="dashicons dashicons-star-filled" style="color: #0073aa;"></span>
                <?php _e('Plugin-Features', 'questify'); ?>
            </h3>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-top: 20px;">
                <div>
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('FAQ-Verwaltung', 'questify'); ?>
                    </h4>
                    <p style="margin: 0; color: #646970;">
                        <?php _e('Einfaches Erstellen, Bearbeiten und Verwalten von FAQ-Einträgen mit Keywords.', 'questify'); ?>
                    </p>
                </div>

                <div>
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <span class="dashicons dashicons-search"></span>
                        <?php _e('Intelligentes Matching', 'questify'); ?>
                    </h4>
                    <p style="margin: 0; color: #646970;">
                        <?php _e('Fortgeschrittener Algorithmus mit Fuzzy-Matching und Levenshtein-Distanz.', 'questify'); ?>
                    </p>
                </div>

                <div>
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <span class="dashicons dashicons-email-alt"></span>
                        <?php _e('E-Mail-Benachrichtigungen', 'questify'); ?>
                    </h4>
                    <p style="margin: 0; color: #646970;">
                        <?php _e('Automatische Benachrichtigungen bei neuen Anfragen mit anpassbarem Design.', 'questify'); ?>
                    </p>
                </div>

                <div>
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <?php _e('Analytics & Statistiken', 'questify'); ?>
                    </h4>
                    <p style="margin: 0; color: #646970;">
                        <?php _e('Detaillierte Auswertungen mit grafischen Darstellungen und Zeitverläufen.', 'questify'); ?>
                    </p>
                </div>

                <div>
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <span class="dashicons dashicons-admin-appearance"></span>
                        <?php _e('Anpassbares Design', 'questify'); ?>
                    </h4>
                    <p style="margin: 0; color: #646970;">
                        <?php _e('Vollständig anpassbare Farben, Schriften und Größen für den Chat-Widget.', 'questify'); ?>
                    </p>
                </div>

                <div>
                    <h4 style="margin: 0 0 10px 0; color: #0073aa;">
                        <span class="dashicons dashicons-shield"></span>
                        <?php _e('DSGVO-konform', 'questify'); ?>
                    </h4>
                    <p style="margin: 0; color: #646970;">
                        <?php _e('IP-Anonymisierung, DSGVO-Checkbox und automatische Datenlöschung.', 'questify'); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div style="background: #fff; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,0.04); padding: 30px; margin: 20px 0;">
            <h3 style="margin-top: 0; color: #23282d; border-bottom: 2px solid #0073aa; padding-bottom: 10px;">
                <span class="dashicons dashicons-sos" style="color: #0073aa;"></span>
                <?php _e('Support & Feedback', 'questify'); ?>
            </h3>

            <p style="font-size: 15px; line-height: 1.7;">
                <?php _e('Bei Fragen, Problemen oder Feedback zum Plugin können Sie uns jederzeit kontaktieren:', 'questify'); ?>
            </p>

            <div style="margin: 20px 0;">
                <a href="mailto:<?php echo esc_attr($feedback_email); ?>" class="button button-primary" style="margin-right: 10px;">
                    <span class="dashicons dashicons-email" style="vertical-align: middle; margin-top: 3px; margin-right: 6px;"></span>
                    <?php _e('E-Mail senden', 'questify'); ?>
                </a>
                <a href="<?php echo esc_url($github_issues_url); ?>" target="_blank" rel="noopener noreferrer" class="button">
                    <span class="dashicons dashicons-bug" style="vertical-align: middle; margin-top: 3px; margin-right: 6px;"></span>
                    <?php _e('GitHub Issues', 'questify'); ?>
                </a>
            </div>

            <p style="margin: 0; color: #646970;">
                <?php _e('Code & Mitwirken:', 'questify'); ?>
                <a href="<?php echo esc_url($github_repo_url); ?>" target="_blank" rel="noopener noreferrer" style="text-decoration: none;">
                    <span class="dashicons dashicons-admin-links" style="vertical-align: middle; margin-top: -2px; margin-right: 6px;"></span>
                    <?php _e('GitHub Repository', 'questify'); ?>
                </a>
            </p>

            <div style="background: #fff9e6; border-left: 4px solid #f0b849; padding: 15px; margin-top: 20px;">
                <p style="margin: 0;">
                    <span class="dashicons dashicons-heart" style="color: #f0b849;"></span>
                    <?php _e('Gefällt Ihnen das Plugin? Wir freuen uns über Ihr Feedback und Ihre Bewertung!', 'questify'); ?>
                </p>
            </div>
        </div>

        <!-- Version Info -->
        <div style="text-align: center; color: #646970; padding: 20px 0;">
            <p style="margin: 0;">
                <?php printf(__('Questify Version %s', 'questify'), QUESTIFY_VERSION); ?> |
                <?php printf(__('Entwickelt von %s', 'questify'), '<strong>' . esc_html($company_name) . '</strong>'); ?>
            </p>
        </div>
    </div>
</div>
