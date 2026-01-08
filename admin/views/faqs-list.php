<?php
/**
 * FAQs List View
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

// Direkten Zugriff verhindern
if (!defined('ABSPATH')) {
    exit;
}

$questify_db = Chatbot_Database::get_instance();

// Pagination
$per_page = 20;
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pagination.
$questify_current_page = isset($_GET['paged']) ? max(1, absint(wp_unslash($_GET['paged']))) : 1;
$questify_offset = ($questify_current_page - 1) * $per_page;

// Filter
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only filtering.
$questify_filter_active = null;
if (isset($_GET['filter_active'])) {
    $questify_filter_active_raw = sanitize_text_field(wp_unslash($_GET['filter_active']));
    if ($questify_filter_active_raw === '0' || $questify_filter_active_raw === '1') {
        $questify_filter_active = (int) $questify_filter_active_raw;
    }
}
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only search.
$questify_search = isset($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';

// FAQs holen
$questify_faq_args = [
    'limit' => $per_page,
    'offset' => $questify_offset,
    'orderby' => 'created_at',
    'order' => 'DESC',
];

if ($questify_filter_active !== null) {
    $questify_faq_args['active'] = $questify_filter_active;
}

if (!empty($questify_search)) {
    $questify_faq_args['search'] = $questify_search;
}

$questify_faqs = $questify_db->get_all_faqs($questify_faq_args);
$questify_total_faqs = $questify_db->count_faqs($questify_faq_args);
$questify_total_pages = ceil($questify_total_faqs / $per_page);

// Löschaktion verarbeiten
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['faq'])) {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce is verified via check_admin_referer().
    $questify_faq_id = absint(wp_unslash($_GET['faq']));
    check_admin_referer('delete-faq-' . $questify_faq_id);
    if ($questify_db->delete_faq($questify_faq_id)) {
        echo '<div class="notice notice-success"><p>' . esc_html__('FAQ erfolgreich gelöscht.', 'questify') . '</p></div>';
    }
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html(get_admin_page_title()); ?></h1>
    <a href="<?php echo esc_url(admin_url('admin.php?page=chatbot-faqs&action=add')); ?>" class="page-title-action">
        <?php esc_html_e('Neu hinzufügen', 'questify'); ?>
    </a>
    <hr class="wp-header-end">

    <!-- Filter und Suche -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select name="filter_active" id="filter-active">
                <option value=""><?php esc_html_e('Alle Status', 'questify'); ?></option>
                <option value="1" <?php selected($questify_filter_active, 1); ?>><?php esc_html_e('Aktiv', 'questify'); ?></option>
                <option value="0" <?php selected($questify_filter_active, 0); ?>><?php esc_html_e('Inaktiv', 'questify'); ?></option>
            </select>
            <input type="button" class="button" value="<?php echo esc_attr__('Filtern', 'questify'); ?>" onclick="location.href='<?php echo esc_js(admin_url('admin.php?page=chatbot-faqs')); ?>&filter_active=' + document.getElementById('filter-active').value">

            <!-- Export -->
            <div class="chatbot-export-dropdown" style="display: inline-block; margin-left: 10px;">
                <button type="button" class="button" id="export-btn">
                    <span class="dashicons dashicons-download" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Exportieren', 'questify'); ?>
                </button>
                <div id="export-menu" class="chatbot-dropdown-menu" style="display: none;">
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=chatbot_export_faqs&format=json&nonce=' . wp_create_nonce('chatbot_admin_ajax'))); ?>">
                        <span class="dashicons dashicons-media-code"></span> <?php esc_html_e('Als JSON exportieren', 'questify'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=chatbot_export_faqs&format=csv&nonce=' . wp_create_nonce('chatbot_admin_ajax'))); ?>">
                        <span class="dashicons dashicons-media-spreadsheet"></span> <?php esc_html_e('Als CSV exportieren', 'questify'); ?>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin-ajax.php?action=chatbot_export_faqs&format=json&include_inactive=1&nonce=' . wp_create_nonce('chatbot_admin_ajax'))); ?>">
                        <span class="dashicons dashicons-media-code"></span> <?php esc_html_e('JSON (inkl. inaktive)', 'questify'); ?>
                    </a>
                </div>
            </div>

            <!-- Import -->
            <button type="button" class="button" id="import-btn" style="margin-left: 5px;">
                <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
                <?php esc_html_e('Importieren', 'questify'); ?>
            </button>
        </div>

        <div class="alignright">
            <form method="get">
                <input type="hidden" name="page" value="chatbot-faqs">
                <?php if ($questify_filter_active !== null): ?>
                <input type="hidden" name="filter_active" value="<?php echo esc_attr($questify_filter_active); ?>">
                <?php endif; ?>
                <input type="search" name="s" value="<?php echo esc_attr($questify_search); ?>" placeholder="<?php echo esc_attr__('Suchen...', 'questify'); ?>">
                <input type="submit" class="button" value="<?php echo esc_attr__('Suchen', 'questify'); ?>">
            </form>
        </div>
    </div>

    <?php if (empty($questify_faqs)): ?>
        <div class="chatbot-empty-state-large">
            <span class="dashicons dashicons-format-chat"></span>
            <h2><?php esc_html_e('Noch keine FAQs vorhanden', 'questify'); ?></h2>
            <p><?php esc_html_e('Erstellen Sie Ihre erste FAQ, um loszulegen.', 'questify'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=chatbot-faqs&action=add')); ?>" class="button button-primary button-hero">
                <?php esc_html_e('Erste FAQ erstellen', 'questify'); ?>
            </a>
        </div>
    <?php else: ?>
        <form method="post">
            <?php wp_nonce_field('chatbot_bulk_action', 'chatbot_bulk_nonce'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="cb-select-all">
                        </td>
                        <th><?php esc_html_e('ID', 'questify'); ?></th>
                        <th><?php esc_html_e('Frage', 'questify'); ?></th>
                        <th><?php esc_html_e('Keywords', 'questify'); ?></th>
                        <th><?php esc_html_e('Status', 'questify'); ?></th>
                        <th><?php esc_html_e('Aufrufe', 'questify'); ?></th>
                        <th><?php esc_html_e('Erstellt', 'questify'); ?></th>
                        <th><?php esc_html_e('Aktionen', 'questify'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($questify_faqs as $questify_faq): ?>
                    <tr>
                        <th class="check-column">
                            <input type="checkbox" name="faq[]" value="<?php echo esc_attr((string) $questify_faq->id); ?>">
                        </th>
                        <td><?php echo esc_html((string) $questify_faq->id); ?></td>
                        <td>
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=chatbot-faqs&action=edit&faq=' . $questify_faq->id)); ?>">
                                    <?php echo esc_html(wp_trim_words($questify_faq->question, 15)); ?>
                                </a>
                            </strong>
                        </td>
                        <td><?php echo esc_html(wp_trim_words($questify_faq->keywords, 10)); ?></td>
                        <td>
                            <?php if ($questify_faq->active): ?>
                                <span class="chatbot-status-badge status-answered"><?php esc_html_e('Aktiv', 'questify'); ?></span>
                            <?php else: ?>
                                <span class="chatbot-status-badge status-new"><?php esc_html_e('Inaktiv', 'questify'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html(number_format_i18n($questify_faq->view_count)); ?></td>
                        <td><?php echo esc_html(date_i18n('d.m.Y', strtotime($questify_faq->created_at))); ?></td>
                        <td class="chatbot-actions">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=chatbot-faqs&action=edit&faq=' . $questify_faq->id)); ?>" title="<?php echo esc_attr__('Bearbeiten', 'questify'); ?>">
                                <span class="dashicons dashicons-edit"></span>
                            </a>
                            <a href="<?php echo esc_url(wp_nonce_url(admin_url('admin.php?page=chatbot-faqs&action=delete&faq=' . $questify_faq->id), 'delete-faq-' . $questify_faq->id)); ?>"
                               onclick="return confirm('<?php echo esc_js(__('Sind Sie sicher?', 'questify')); ?>');"
                               title="<?php echo esc_attr__('Löschen', 'questify'); ?>"
                               class="chatbot-delete-link">
                                <span class="dashicons dashicons-trash"></span>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <!-- Pagination -->
        <?php if ($questify_total_pages > 1): ?>
        <div class="tablenav bottom">
            <div class="tablenav-pages">
                <?php
                echo wp_kses_post(paginate_links([
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'prev_text' => '&laquo;',
                    'next_text' => '&raquo;',
                    'total' => $questify_total_pages,
                    'current' => $questify_current_page,
                ]));
                ?>
            </div>
        </div>
        <?php endif; ?>

        <p class="chatbot-info-text">
            <?php printf(
                /* translators: %d: number of FAQs */
                esc_html(_n('%d FAQ gefunden', '%d FAQs gefunden', $questify_total_faqs, 'questify')),
                absint($questify_total_faqs)
            ); ?>
        </p>
    <?php endif; ?>
</div>

<!-- Import Modal -->
<div id="import-modal" class="chatbot-modal" style="display: none;">
    <div class="chatbot-modal-content" style="max-width: 700px;">
        <span class="chatbot-modal-close">&times;</span>
        <h2><?php esc_html_e('FAQs importieren', 'questify'); ?></h2>

        <!-- Import-Methode wählen -->
        <div id="import-method-selector" style="margin: 20px 0;">
            <h3><?php esc_html_e('Import-Methode wählen:', 'questify'); ?></h3>
            <div style="display: flex; gap: 15px; margin: 15px 0;">
                <label style="flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer; transition: all 0.3s;">
                    <input type="radio" name="import_method" value="file" checked style="margin-right: 8px;">
                    <strong><?php esc_html_e('📁 Datei-Upload', 'questify'); ?></strong><br>
                       <small><?php esc_html_e('JSON oder CSV-Datei hochladen', 'questify'); ?></small>
                </label>
                <label style="flex: 1; padding: 15px; border: 2px solid #ddd; border-radius: 5px; cursor: pointer; transition: all 0.3s;">
                    <input type="radio" name="import_method" value="paste" style="margin-right: 8px;">
                    <strong><?php esc_html_e('📋 Copy & Paste', 'questify'); ?></strong><br>
                       <small><?php esc_html_e('Text direkt einfügen', 'questify'); ?></small>
                </label>
            </div>
        </div>

        <form id="import-form" enctype="multipart/form-data">

            <!-- Datei-Upload Methode -->
            <div id="import-file-section">
                <h3><?php esc_html_e('Datei auswählen', 'questify'); ?></h3>
                <div style="margin: 15px 0;">
                    <input type="file" id="import-file" name="import_file" accept=".json,.csv,.txt">
                </div>

                <div class="notice notice-info inline" style="margin: 15px 0;">
                    <p><strong><?php esc_html_e('Unterstützte Formate:', 'questify'); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><strong>JSON:</strong> <?php esc_html_e('Exportierte Datei von diesem Plugin', 'questify'); ?></li>
                        <li><strong>CSV (Standard):</strong> <?php esc_html_e('Spalten: Frage, Antwort, Keywords (optional)', 'questify'); ?></li>
                        <li><strong>CSV (Einfach):</strong> <?php esc_html_e('Nur: Frage, Antwort (Keywords werden automatisch generiert)', 'questify'); ?></li>
                    </ul>
                </div>
            </div>

            <!-- Copy & Paste Methode -->
            <div id="import-paste-section" style="display: none;">
                <h3><?php esc_html_e('Text einfügen', 'questify'); ?></h3>

                <div style="margin: 15px 0;">
                       <label><strong><?php esc_html_e('Format wählen:', 'questify'); ?></strong></label>
                    <select id="paste-format" style="width: 100%; margin: 10px 0; padding: 8px;">
                        <option value="tab"><?php esc_html_e('Tab-getrennt (aus Excel/Google Sheets kopiert)', 'questify'); ?></option>
                        <option value="comma"><?php esc_html_e('Komma-getrennt (CSV)', 'questify'); ?></option>
                        <option value="semicolon"><?php esc_html_e('Semikolon-getrennt (CSV)', 'questify'); ?></option>
                        <option value="pipe"><?php esc_html_e('Pipe-getrennt (|)', 'questify'); ?></option>
                    </select>
                </div>

                <div style="margin: 15px 0;">
                    <label>
                        <input type="checkbox" id="has-headers" checked>
                        <?php esc_html_e('Erste Zeile enthält Überschriften', 'questify'); ?>
                    </label>
                </div>

                <div style="margin: 15px 0;">
                       <textarea id="paste-content" placeholder="<?php echo esc_attr__('Fügen Sie hier Ihre Daten ein...\n\nBeispiel (Tab-getrennt):\nFrage	Antwort	Keywords\nWas kostet...?	Der Preis beträgt...	preis, kosten\nWie lange...?	Die Dauer ist...	dauer, zeit', 'questify'); ?>"
                                 style="width: 100%; height: 250px; font-family: monospace; padding: 10px; border: 1px solid #ddd; border-radius: 4px;"></textarea>
                </div>

                <div class="notice notice-info inline">
                    <p><strong><?php esc_html_e('Hinweise:', 'questify'); ?></strong></p>
                    <ul style="margin-left: 20px;">
                        <li><?php esc_html_e('Mindestens 2 Spalten: Frage und Antwort', 'questify'); ?></li>
                        <li><?php esc_html_e('Optional: 3. Spalte für Keywords (kommasepariert)', 'questify'); ?></li>
                        <li><?php esc_html_e('Wenn keine Keywords angegeben, werden sie automatisch generiert', 'questify'); ?></li>
                        <li><?php esc_html_e('Aus Excel/Sheets: Markieren → Kopieren → Hier einfügen', 'questify'); ?></li>
                    </ul>
                </div>

                <button type="button" id="preview-btn" class="button" style="margin-top: 10px;">
                    <span class="dashicons dashicons-visibility" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Vorschau anzeigen', 'questify'); ?>
                </button>
            </div>

            <!-- Vorschau -->
            <div id="import-preview" style="display: none; margin: 20px 0; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
                <h3><?php esc_html_e('Vorschau', 'questify'); ?></h3>
                <div id="preview-content"></div>
            </div>

            <!-- Aktionen -->
            <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
                <button type="submit" class="button button-primary" id="import-submit-btn">
                    <span class="dashicons dashicons-upload" style="margin-top: 3px;"></span>
                    <?php esc_html_e('Jetzt importieren', 'questify'); ?>
                </button>
                <button type="button" class="button chatbot-modal-close">
                    <?php esc_html_e('Abbrechen', 'questify'); ?>
                </button>
                <span id="import-count" style="margin-left: 15px; color: #666;"></span>
            </div>

            <div id="import-progress" style="display: none; margin-top: 20px;">
                    <p><?php esc_html_e('Importiere...', 'questify'); ?></p>
                <div class="chatbot-progress-bar">
                    <div class="chatbot-progress-bar-fill"></div>
                </div>
            </div>

            <div id="import-result" style="display: none; margin-top: 20px;"></div>
        </form>
    </div>
</div>

<style>
/* Dropdown Menu */
.chatbot-dropdown-menu {
    position: absolute;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    border-radius: 4px;
    margin-top: 5px;
    z-index: 1000;
    min-width: 200px;
}

.chatbot-dropdown-menu a {
    display: block;
    padding: 10px 15px;
    color: #333;
    text-decoration: none;
    border-bottom: 1px solid #eee;
}

.chatbot-dropdown-menu a:last-child {
    border-bottom: none;
}

.chatbot-dropdown-menu a:hover {
    background: #f5f5f5;
}

.chatbot-dropdown-menu .dashicons {
    margin-right: 8px;
    color: #0073aa;
}

/* Modal */
.chatbot-modal {
    position: fixed;
    z-index: 100000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
    overflow-y: auto;
}

.chatbot-modal-content {
    background-color: white;
    margin: 20px auto;
    padding: 30px;
    border: 1px solid #888;
    border-radius: 8px;
    width: 90%;
    max-width: 700px;
    max-height: calc(100vh - 40px);
    overflow-y: auto;
    box-shadow: 0 4px 20px rgba(0,0,0,0.2);
    position: relative;
}

.chatbot-modal-close {
    color: #aaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 20px;
}

.chatbot-modal-close:hover {
    color: #000;
}

.chatbot-progress-bar {
    width: 100%;
    height: 20px;
    background: #f0f0f0;
    border-radius: 10px;
    overflow: hidden;
}

.chatbot-progress-bar-fill {
    height: 100%;
    background: #0073aa;
    width: 0;
    transition: width 0.3s;
    animation: progress-indeterminate 1.5s infinite;
}

@keyframes progress-indeterminate {
    0% { width: 0; margin-left: 0; }
    50% { width: 50%; margin-left: 25%; }
    100% { width: 0; margin-left: 100%; }
}
</style>

<script>
jQuery(document).ready(function($) {
    var parsedData = [];

    // Export Dropdown
    $('#export-btn').on('click', function(e) {
        e.stopPropagation();
        $('#export-menu').toggle();
    });

    $(document).on('click', function() {
        $('#export-menu').hide();
    });

    $('#export-menu').on('click', function(e) {
        e.stopPropagation();
    });

    // Import Modal
    $('#import-btn').on('click', function() {
        $('#import-modal').show();
        parsedData = [];
        $('#import-preview').hide();
        $('#import-result').hide();
    });

    $('.chatbot-modal-close').on('click', function() {
        $('#import-modal').hide();
        $('#import-form')[0].reset();
        $('#import-progress').hide();
        $('#import-preview').hide();
        $('#import-result').hide();
        $('#paste-content').val('');
        parsedData = [];
    });

    // Import-Methode Wechsel
    $('input[name="import_method"]').on('change', function() {
        var method = $(this).val();

        if (method === 'file') {
            $('#import-file-section').show();
            $('#import-paste-section').hide();
            $('#import-preview').hide();
        } else if (method === 'paste') {
            $('#import-file-section').hide();
            $('#import-paste-section').show();
        }

        // Reset
        parsedData = [];
        $('#import-preview').hide();
        $('#import-count').text('');
    });

    // Visual Feedback für Methoden-Auswahl
    $('input[name="import_method"]').on('change', function() {
        $('input[name="import_method"]').parent().css({
            'border-color': '#ddd',
            'background-color': 'transparent'
        });

        $(this).parent().css({
            'border-color': '#0073aa',
            'background-color': '#f0f6fc'
        });
    });

    // Initiales Styling
    $('input[name="import_method"]:checked').trigger('change');

    // CSV Parser Funktion
    function parseCSV(text, delimiter) {
        var lines = text.trim().split(/\r?\n/);
        var result = [];

        for (var i = 0; i < lines.length; i++) {
            if (!lines[i].trim()) continue;

            var values = [];
            var current = '';
            var inQuotes = false;

            for (var j = 0; j < lines[i].length; j++) {
                var char = lines[i][j];

                if (char === '"') {
                    inQuotes = !inQuotes;
                } else if (char === delimiter && !inQuotes) {
                    values.push(current.trim());
                    current = '';
                } else {
                    current += char;
                }
            }
            values.push(current.trim());

            result.push(values);
        }

        return result;
    }

    // Vorschau anzeigen
    $('#preview-btn').on('click', function() {
        var content = $('#paste-content').val().trim();

        if (!content) {
            alert('<?php echo esc_js(__('Bitte fügen Sie zuerst Text ein.', 'questify')); ?>');
            return;
        }

        var format = $('#paste-format').val();
        var hasHeaders = $('#has-headers').is(':checked');
        var delimiter;

        switch(format) {
            case 'tab': delimiter = '\t'; break;
            case 'comma': delimiter = ','; break;
            case 'semicolon': delimiter = ';'; break;
            case 'pipe': delimiter = '|'; break;
        }

        var rows = parseCSV(content, delimiter);

        if (rows.length === 0) {
            alert('<?php echo esc_js(__('Keine Daten gefunden.', 'questify')); ?>');
            return;
        }

        // Erste Zeile überspringen wenn Headers
        var startIndex = hasHeaders ? 1 : 0;
        parsedData = [];

        for (var i = startIndex; i < rows.length; i++) {
            if (rows[i].length < 2) continue; // Mindestens Frage + Antwort

            parsedData.push({
                question: rows[i][0] || '',
                answer: rows[i][1] || '',
                keywords: rows[i][2] || ''
            });
        }

        if (parsedData.length === 0) {
            alert('<?php echo esc_js(__('Keine gültigen Daten gefunden. Mindestens 2 Spalten erforderlich.', 'questify')); ?>');
            return;
        }

        // Vorschau generieren
        var html = '<p><strong><?php echo esc_js(__('Gefundene FAQs:', 'questify')); ?> ' + parsedData.length + '</strong></p>';
        html += '<div style="max-height: 300px; overflow-y: auto;">';
        html += '<table class="wp-list-table widefat striped" style="margin-top: 10px;">';
        html += '<thead><tr>';
        html += '<th style="width: 40px;">#</th>';
        html += '<th><?php echo esc_js(__('Frage', 'questify')); ?></th>';
        html += '<th><?php echo esc_js(__('Antwort', 'questify')); ?></th>';
        html += '<th><?php echo esc_js(__('Keywords', 'questify')); ?></th>';
        html += '</tr></thead><tbody>';

        for (var i = 0; i < Math.min(parsedData.length, 10); i++) {
            var item = parsedData[i];
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td>' + $('<div>').text(item.question.substring(0, 50) + (item.question.length > 50 ? '...' : '')).html() + '</td>';
            html += '<td>' + $('<div>').text(item.answer.substring(0, 50) + (item.answer.length > 50 ? '...' : '')).html() + '</td>';
            html += '<td>' + (item.keywords ? $('<div>').text(item.keywords).html() : '<em style="color:#999;"><?php echo esc_js(__('Auto', 'questify')); ?></em>') + '</td>';
            html += '</tr>';
        }

        if (parsedData.length > 10) {
            html += '<tr><td colspan="4" style="text-align: center; color: #666;"><em><?php echo esc_js(__('...und', 'questify')); ?> ' + (parsedData.length - 10) + ' <?php echo esc_js(__('weitere', 'questify')); ?></em></td></tr>';
        }

        html += '</tbody></table></div>';

        $('#preview-content').html(html);
        $('#import-preview').show();
        $('#import-count').text('<?php echo esc_js(__('Bereit zum Import:', 'questify')); ?> ' + parsedData.length + ' FAQs');
    });

    // Import Form Submit
    $('#import-form').on('submit', function(e) {
        e.preventDefault();

        var method = $('input[name="import_method"]:checked').val();
        var ajaxSettings = {
            url: chatbotAdmin.ajaxurl,
            type: 'POST'
        };

        if (method === 'file') {
            var file = $('#import-file')[0].files[0];

            if (!file) {
                alert('<?php echo esc_js(__('Bitte wählen Sie eine Datei aus.', 'questify')); ?>');
                return;
            }

            var formData = new FormData();
            formData.append('import_file', file);
            formData.append('import_method', 'file');
            formData.append('action', 'chatbot_import_faqs');
            formData.append('nonce', chatbotAdmin.nonce);

            ajaxSettings.data = formData;
            ajaxSettings.processData = false;
            ajaxSettings.contentType = false;

        } else {
            // Copy & Paste Methode
            if (parsedData.length === 0) {
                alert('<?php echo esc_js(__('Bitte klicken Sie zuerst auf "Vorschau anzeigen".', 'questify')); ?>');
                return;
            }

            // Console-Log für Debugging
            console.log('Import-Daten:', parsedData);
            console.log('Anzahl FAQs:', parsedData.length);

            // Normales POST-Request ohne FormData für bessere Kompatibilität
            ajaxSettings.data = {
                action: 'chatbot_import_faqs',
                nonce: chatbotAdmin.nonce,
                import_method: 'paste',
                import_data: JSON.stringify(parsedData)
            };
        }

        $('#import-progress').show();
        $('#import-submit-btn').prop('disabled', true);
        $('#import-result').hide();

        ajaxSettings.success = function(response) {
            $('#import-progress').hide();
            $('#import-submit-btn').prop('disabled', false);

            if (response.success) {
                var resultHtml = '<div class="notice notice-success inline"><p>';
                resultHtml += '<strong>' + response.data.message + '</strong></p>';

                if (response.data.imported) {
                    resultHtml += '<p><?php echo esc_js(__('Erfolgreich importiert:', 'questify')); ?> ' + response.data.imported + '</p>';
                }

                if (response.data.errors && response.data.errors.length > 0) {
                    resultHtml += '<p><?php echo esc_js(__('Fehler:', 'questify')); ?> ' + response.data.errors.length + '</p>';
                    resultHtml += '<details><summary><?php echo esc_js(__('Details anzeigen', 'questify')); ?></summary><ul>';
                    response.data.errors.forEach(function(err) {
                        resultHtml += '<li>' + err + '</li>';
                    });
                    resultHtml += '</ul></details>';
                }

                resultHtml += '</div>';

                $('#import-result').html(resultHtml).show();

                // Seite nach 2 Sekunden neu laden
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                var errorHtml = '<div class="notice notice-error inline"><p><strong><?php echo esc_js(__('Import fehlgeschlagen:', 'questify')); ?></strong><br>';
                errorHtml += (response.data.message || chatbotAdmin.strings.error) + '</p>';

                // Debug-Informationen wenn vorhanden
                if (response.data.debug) {
                    errorHtml += '<details><summary><?php echo esc_js(__('Debug-Informationen', 'questify')); ?></summary><pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;">' + response.data.debug + '</pre></details>';
                }

                errorHtml += '</div>';
                $('#import-result').html(errorHtml).show();
            }
        };

        ajaxSettings.error = function(xhr, status, error) {
            $('#import-progress').hide();
            $('#import-submit-btn').prop('disabled', false);

            console.error('AJAX Error:', status, error);
            console.error('Response:', xhr.responseText);

            var errorHtml = '<div class="notice notice-error inline"><p><strong><?php echo esc_js(__('Fehler beim Import:', 'questify')); ?></strong><br>' + error + '</p>';

            // Response Text wenn vorhanden
            if (xhr.responseText) {
                errorHtml += '<details><summary><?php echo esc_js(__('Server-Antwort anzeigen', 'questify')); ?></summary><pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;">' + xhr.responseText.substring(0, 1000) + '</pre></details>';
            }

            errorHtml += '<p><small><?php echo esc_js(__('Tipp: Aktivieren Sie den Debug-Modus in den Einstellungen für detaillierte Fehlerinformationen.', 'questify')); ?></small></p></div>';
            $('#import-result').html(errorHtml).show();
        };

        $.ajax(ajaxSettings);
    });
});
</script>
