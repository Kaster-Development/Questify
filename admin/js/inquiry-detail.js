/**
 * Inquiry Detail Page Scripts
 *
 * @package Questify
 * @since 1.0.0
 */

/* global jQuery, questiAdmin, questiInquiryDetail */

(function($) {
    'use strict';

    $(document).ready(function() {
        $('#inquiry-status').on('change', function() {
            var inquiryId = $(this).data('inquiry-id');
            var status = $(this).val();

            $.post(questiAdmin.ajaxurl, {
                action: 'questi_update_inquiry_status',
                nonce: questiAdmin.nonce,
                inquiry_id: inquiryId,
                status: status
            }, function(response) {
                if (response.success) {
                    alert(response.data.message);
                }
            });
        });

        $('.chatbot-delete-inquiry').on('click', function() {
            if (!confirm(questiInquiryDetail.strings.confirmDelete)) return;

            var inquiryId = $(this).data('inquiry-id');

            $.post(questiAdmin.ajaxurl, {
                action: 'questi_delete_inquiry',
                nonce: questiAdmin.nonce,
                inquiry_id: inquiryId
            }, function(response) {
                if (response.success) {
                    window.location.href = questiInquiryDetail.inquiriesUrl;
                }
            });
        });
    });
})(jQuery);
