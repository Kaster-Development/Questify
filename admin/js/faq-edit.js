/**
 * FAQ Edit Page Scripts
 *
 * @package Questify
 * @since 1.0.0
 */

/* global jQuery, tinymce, questiAdmin, questiFaqEdit */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Keywords automatisch generieren
        $('#generate-keywords-btn').on('click', function() {
            var button = $(this);
            var originalText = button.html();

            // Frage und Antwort holen
            var question = $('#question').val().trim();
            var answer = '';

            // WordPress-Editor-Content holen (verschiedene Methoden je nach Editor-Typ)
            if (typeof tinymce !== 'undefined' && tinymce.get('answer')) {
                answer = tinymce.get('answer').getContent();
            } else if ($('#answer').length) {
                answer = $('#answer').val();
            }

            if (!question) {
                alert(questiFaqEdit.strings.enterQuestionFirst);
                return;
            }

            // Button deaktivieren
            button.prop('disabled', true).html('<span class="dashicons dashicons-update-alt" style="margin-top: 3px; animation: spin 1s linear infinite;"></span> ' + questiFaqEdit.strings.generating);

            // AJAX-Request
            $.post(questiAdmin.ajaxurl, {
                action: 'questi_generate_keywords',
                nonce: questiAdmin.nonce,
                question: question,
                answer: answer
            }, function(response) {
                button.prop('disabled', false).html(originalText);

                if (response.success) {
                    // Keywords in Textfeld einfügen
                    var currentKeywords = $('#keywords').val().trim();
                    var newKeywords = response.data.keywords;

                    if (currentKeywords) {
                        // Bestehende Keywords mit neuen zusammenführen
                        var combined = currentKeywords + ', ' + newKeywords;
                        // Duplikate entfernen
                        var keywordArray = combined.split(',').map(function(k) { return k.trim(); });
                        keywordArray = keywordArray.filter(function(item, pos, self) {
                            return item && self.indexOf(item) === pos;
                        });
                        $('#keywords').val(keywordArray.join(', '));
                    } else {
                        $('#keywords').val(newKeywords);
                    }

                    // Erfolgsmeldung
                    var successMsg = $('<div class="notice notice-success is-dismissible" style="margin: 10px 0;"><p>' + response.data.message + '</p></div>');
                    $('#keywords').after(successMsg);
                    setTimeout(function() {
                        successMsg.fadeOut(function() { $(this).remove(); });
                    }, 3000);
                } else {
                    alert(response.data.message || questiAdmin.strings.error);
                }
            }).fail(function() {
                button.prop('disabled', false).html(originalText);
                alert(questiAdmin.strings.error);
            });
        });
    });
})(jQuery);
