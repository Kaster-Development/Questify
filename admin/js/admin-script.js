/**
 * Admin JavaScript
 *
 * @package WP_FAQ_Chat
 * @since 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        // ========== Color Picker ==========
        if ($.fn.wpColorPicker) {
            $('.chatbot-color-picker').wpColorPicker();
        }

        // ========== Range Slider ==========
        $('#min-score-range').on('input', function() {
            $('#min-score-value').text($(this).val());
        });

        // ========== Checkbox Select All ==========
        $('#cb-select-all').on('change', function() {
            $('input[name="faq[]"]').prop('checked', $(this).prop('checked'));
        });

        // ========== AJAX: Delete FAQ ==========
        $(document).on('click', '.chatbot-delete-faq', function(e) {
            e.preventDefault();

            if (!confirm(chatbotAdmin.strings.delete_confirm)) {
                return;
            }

            const faqId = $(this).data('faq-id');
            const row = $(this).closest('tr');

            $.post(chatbotAdmin.ajaxurl, {
                action: 'chatbot_delete_faq',
                nonce: chatbotAdmin.nonce,
                faq_id: faqId
            }, function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                } else {
                    alert(response.data.message || chatbotAdmin.strings.error);
                }
            }).fail(function() {
                alert(chatbotAdmin.strings.error);
            });
        });

        // ========== AJAX: Toggle FAQ Status ==========
        $(document).on('click', '.chatbot-toggle-status', function(e) {
            e.preventDefault();

            const faqId = $(this).data('faq-id');
            const active = $(this).data('active');
            const badge = $(this).closest('td').find('.chatbot-status-badge');

            $.post(chatbotAdmin.ajaxurl, {
                action: 'chatbot_toggle_faq_status',
                nonce: chatbotAdmin.nonce,
                faq_id: faqId,
                active: active ? 0 : 1
            }, function(response) {
                if (response.success) {
                    if (active) {
                        badge.removeClass('status-answered').addClass('status-new').text('Inaktiv');
                        $(this).data('active', 0);
                    } else {
                        badge.removeClass('status-new').addClass('status-answered').text('Aktiv');
                        $(this).data('active', 1);
                    }
                } else {
                    alert(response.data.message || chatbotAdmin.strings.error);
                }
            }.bind(this));
        });

        // ========== AJAX: Delete Inquiry ==========
        $(document).on('click', '.chatbot-delete-inquiry', function(e) {
            e.preventDefault();

            if (!confirm(chatbotAdmin.strings.delete_confirm)) {
                return;
            }

            const inquiryId = $(this).data('inquiry-id');

            $.post(chatbotAdmin.ajaxurl, {
                action: 'chatbot_delete_inquiry',
                nonce: chatbotAdmin.nonce,
                inquiry_id: inquiryId
            }, function(response) {
                if (response.success) {
                    window.location.href = window.location.pathname + '?page=chatbot-inquiries';
                } else {
                    alert(response.data.message || chatbotAdmin.strings.error);
                }
            });
        });

        // ========== AJAX: Update Inquiry Status ==========
        $(document).on('change', '#inquiry-status', function() {
            const inquiryId = $(this).data('inquiry-id');
            const status = $(this).val();

            $.post(chatbotAdmin.ajaxurl, {
                action: 'chatbot_update_inquiry_status',
                nonce: chatbotAdmin.nonce,
                inquiry_id: inquiryId,
                status: status
            }, function(response) {
                if (response.success) {
                    // Zeige kurze Erfolgsmeldung
                    const successMsg = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                    $('.wrap h1').after(successMsg);
                    setTimeout(function() {
                        successMsg.fadeOut(function() {
                            $(this).remove();
                        });
                    }, 3000);
                } else {
                    alert(response.data.message || chatbotAdmin.strings.error);
                }
            });
        });

        // ========== AJAX: Send Test Email ==========
        $(document).on('click', '#send-test-email', function(e) {
            e.preventDefault();

            const button = $(this);
            const originalText = button.text();

            button.prop('disabled', true).text('Sende...');

            $.post(chatbotAdmin.ajaxurl, {
                action: 'chatbot_send_test_email',
                nonce: chatbotAdmin.nonce
            }, function(response) {
                button.prop('disabled', false).text(originalText);

                if (response.success) {
                    alert(response.data.message);
                } else {
                    alert(response.data.message || chatbotAdmin.strings.error);
                }
            }).fail(function() {
                button.prop('disabled', false).text(originalText);
                alert(chatbotAdmin.strings.error);
            });
        });

        // ========== Auto-Dismiss Notices ==========
        setTimeout(function() {
            $('.notice.is-dismissible').each(function() {
                $(this).fadeOut(function() {
                    $(this).remove();
                });
            });
        }, 5000);

    });

})(jQuery);
