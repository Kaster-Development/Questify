/**
 * FAQs List Page Scripts
 *
 * @package Questify
 * @since 1.0.0
 */

/* global jQuery, questiAdmin, questiFaqsList */

(function($) {
    'use strict';

    $(document).ready(function() {
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
                alert(questiFaqsList.strings.pasteFirst);
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
                alert(questiFaqsList.strings.noDataFound);
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
                alert(questiFaqsList.strings.noValidData);
                return;
            }

            // Vorschau generieren
            var html = '<p><strong>' + questiFaqsList.strings.foundFaqs + ' ' + parsedData.length + '</strong></p>';
            html += '<div style="max-height: 300px; overflow-y: auto;">';
            html += '<table class="wp-list-table widefat striped" style="margin-top: 10px;">';
            html += '<thead><tr>';
            html += '<th style="width: 40px;">#</th>';
            html += '<th>' + questiFaqsList.strings.question + '</th>';
            html += '<th>' + questiFaqsList.strings.answer + '</th>';
            html += '<th>' + questiFaqsList.strings.keywords + '</th>';
            html += '</tr></thead><tbody>';

            for (var j = 0; j < Math.min(parsedData.length, 10); j++) {
                var item = parsedData[j];
                html += '<tr>';
                html += '<td>' + (j + 1) + '</td>';
                html += '<td>' + $('<div>').text(item.question.substring(0, 50) + (item.question.length > 50 ? '...' : '')).html() + '</td>';
                html += '<td>' + $('<div>').text(item.answer.substring(0, 50) + (item.answer.length > 50 ? '...' : '')).html() + '</td>';
                html += '<td>' + (item.keywords ? $('<div>').text(item.keywords).html() : '<em style="color:#999;">' + questiFaqsList.strings.auto + '</em>') + '</td>';
                html += '</tr>';
            }

            if (parsedData.length > 10) {
                html += '<tr><td colspan="4" style="text-align: center; color: #666;"><em>' + questiFaqsList.strings.andMore + ' ' + (parsedData.length - 10) + ' ' + questiFaqsList.strings.more + '</em></td></tr>';
            }

            html += '</tbody></table></div>';

            $('#preview-content').html(html);
            $('#import-preview').show();
            $('#import-count').text(questiFaqsList.strings.readyToImport + ' ' + parsedData.length + ' FAQs');
        });

        // Import Form Submit
        $('#import-form').on('submit', function(e) {
            e.preventDefault();

            var method = $('input[name="import_method"]:checked').val();
            var ajaxSettings = {
                url: questiAdmin.ajaxurl,
                type: 'POST'
            };

            if (method === 'file') {
                var file = $('#import-file')[0].files[0];

                if (!file) {
                    alert(questiFaqsList.strings.selectFile);
                    return;
                }

                var formData = new FormData();
                formData.append('import_file', file);
                formData.append('import_method', 'file');
                formData.append('action', 'questi_import_faqs');
                formData.append('nonce', questiAdmin.nonce);

                ajaxSettings.data = formData;
                ajaxSettings.processData = false;
                ajaxSettings.contentType = false;

            } else {
                // Copy & Paste Methode
                if (parsedData.length === 0) {
                    alert(questiFaqsList.strings.clickPreviewFirst);
                    return;
                }

                // Console-Log für Debugging
                console.log('Import-Daten:', parsedData);
                console.log('Anzahl FAQs:', parsedData.length);

                // Normales POST-Request ohne FormData für bessere Kompatibilität
                ajaxSettings.data = {
                    action: 'questi_import_faqs',
                    nonce: questiAdmin.nonce,
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
                        resultHtml += '<p>' + questiFaqsList.strings.successfullyImported + ' ' + response.data.imported + '</p>';
                    }

                    if (response.data.errors && response.data.errors.length > 0) {
                        resultHtml += '<p>' + questiFaqsList.strings.errors + ' ' + response.data.errors.length + '</p>';
                        resultHtml += '<details><summary>' + questiFaqsList.strings.showDetails + '</summary><ul>';
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
                    var errorHtml = '<div class="notice notice-error inline"><p><strong>' + questiFaqsList.strings.importFailed + '</strong><br>';
                    errorHtml += (response.data.message || questiAdmin.strings.error) + '</p>';

                    // Debug-Informationen wenn vorhanden
                    if (response.data.debug) {
                        errorHtml += '<details><summary>' + questiFaqsList.strings.debugInfo + '</summary><pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;">' + response.data.debug + '</pre></details>';
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

                var errorHtml = '<div class="notice notice-error inline"><p><strong>' + questiFaqsList.strings.importError + '</strong><br>' + error + '</p>';

                // Response Text wenn vorhanden
                if (xhr.responseText) {
                    errorHtml += '<details><summary>' + questiFaqsList.strings.showServerResponse + '</summary><pre style="background:#f5f5f5;padding:10px;overflow:auto;max-height:200px;">' + xhr.responseText.substring(0, 1000) + '</pre></details>';
                }

                errorHtml += '<p><small>' + questiFaqsList.strings.debugTip + '</small></p></div>';
                $('#import-result').html(errorHtml).show();
            };

            $.ajax(ajaxSettings);
        });
    });
})(jQuery);
