/**
 * Analytics Page Scripts
 *
 * @package Questify
 * @since 1.0.0
 */

/* global jQuery, QuestifyCharts, questiAnalytics */

(function($) {
    'use strict';

    $(document).ready(function() {
        $('#period-filter').on('change', function() {
            window.location.href = questiAnalytics.analyticsUrl + '&period=' + $(this).val();
        });

        if (window.QuestifyCharts) {
            // Timeline
            QuestifyCharts.line(
                'timeline-chart',
                questiAnalytics.timelineLabels,
                questiAnalytics.timelineValues,
                {
                    borderColor: '#0073aa',
                    backgroundColor: 'rgba(0, 115, 170, 0.1)'
                }
            );

            // Success
            QuestifyCharts.doughnut(
                'success-chart',
                questiAnalytics.successCounts,
                ['#46b450', '#dc3232']
            );

            // Top FAQs
            QuestifyCharts.barHorizontal(
                'top-faqs-chart',
                questiAnalytics.topFaqLabels,
                questiAnalytics.topFaqViews,
                { backgroundColor: '#0073aa' }
            );
        }
    });
})(jQuery);
