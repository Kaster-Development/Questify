/**
 * Settings Page Scripts
 *
 * @package Questify
 * @since 1.0.0
 */

/* global jQuery, questiAdmin, questiSettings */

(function($) {
    'use strict';

    $(document).ready(function() {
        $('.chatbot-color-picker').wpColorPicker();

        $('#min-score-range').on('input', function() {
            $('#min-score-value').text($(this).val());
        });

        $('#confident-score-range').on('input', function() {
            $('#confident-score-value').text($(this).val());
        });

        $('#send-test-email').on('click', function() {
            var button = $(this);
            button.prop('disabled', true).text(questiSettings.strings.sending);

            $.post(questiAdmin.ajaxurl, {
                action: 'questi_send_test_email',
                nonce: questiAdmin.nonce
            }, function(response) {
                button.prop('disabled', false).text(questiSettings.strings.sendTestEmail);
                alert(response.success ? response.data.message : response.data.message);
            });
        });

        $('#restore-defaults').on('click', function() {
            if (!confirm(questiSettings.strings.confirmRestore)) {
                return;
            }

            var button = $(this);
            var originalText = button.text();
            button.prop('disabled', true).text(questiSettings.strings.restoring);

            $.post(questiAdmin.ajaxurl, {
                action: 'questi_restore_defaults',
                nonce: questiAdmin.nonce
            }, function(response) {
                button.prop('disabled', false).text(originalText);
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert(response.data.message || questiSettings.strings.restoreError);
                }
            }).fail(function() {
                button.prop('disabled', false).text(originalText);
                alert(questiSettings.strings.restoreError);
            });
        });
    });
})(jQuery);
