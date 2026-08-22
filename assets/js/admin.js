/**
 * FoPost Social WordPress Admin Scripts
 *
 * @package Fopost\Social\Wp
 */

(function ($) {
    'use strict';

    /**
     * Test Connection handler for the Settings page.
     */
    function initTestConnection() {
        $('.fopost-social-test-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var platform = $btn.data('platform');
            var $spinner = $btn.closest('td, .fopost-social-test-buttons').find('.spinner');
            var $result = $('#fopost-social-test-result');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.empty();

            $.ajax({
                url: fopostSocialAdmin.restUrl + 'test-connection',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': fopostSocialAdmin.nonce,
                },
                data: JSON.stringify({ platform: platform }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var cls = response.success ? 'success' : 'error';
                    $result.html(
                        '<span class="fopost-social-test-result ' +
                            cls +
                            '">' +
                            escapeHtml(response.message) +
                            '</span>'
                    );
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : fopostSocialAdmin.i18n.connectionFailed;

                    $result.html(
                        '<span class="fopost-social-test-result error">' +
                            escapeHtml(msg) +
                            '</span>'
                    );
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

    /**
     * Test Message handler — sends a sample text message to a platform.
     */
    function initTestMessage() {
        $('.fopost-social-test-message-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var platform = $btn.data('platform');
            var type = $btn.data('type');
            var $spinner = $btn.closest('td').find('.spinner');
            var $result = $('#fopost-social-test-result');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.empty();

            $.ajax({
                url: fopostSocialAdmin.restUrl + 'test-message',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': fopostSocialAdmin.nonce,
                },
                data: JSON.stringify({ platform: platform, type: type }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var cls = response.success ? 'success' : 'error';
                    var html = escapeHtml(response.message);

                    if (response.success && response.external_url) {
                        html +=
                            ' <a href="' +
                            escapeHtml(response.external_url) +
                            '" target="_blank" rel="noopener">' +
                            escapeHtml(fopostSocialAdmin.i18n.viewPost || 'View Post') +
                            '</a>';
                    }

                    $result.html(
                        '<span class="fopost-social-test-result ' + cls + '">' + html + '</span>'
                    );
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : fopostSocialAdmin.i18n.testMessageFailed ||
                              'Failed to send test message.';

                    $result.html(
                        '<span class="fopost-social-test-result error">' +
                            escapeHtml(msg) +
                            '</span>'
                    );
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

    /**
     * Per-platform Publish handler for the Meta Box.
     */
    function initPublishSingle() {
        $('.fopost-social-publish-single-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $row = $btn.closest('.fopost-social-platform-row');
            var $spinner = $row.find('.spinner');
            var $result = $row.find('.fopost-social-platform-result');
            var postId = $btn.data('post-id');
            var platform = $btn.data('platform');

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $result.empty().removeClass('success error');

            $.ajax({
                url: fopostSocialAdmin.restUrl + 'publish',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': fopostSocialAdmin.nonce,
                },
                data: JSON.stringify({
                    post_id: postId,
                    platforms: [platform],
                }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var r = response.results && response.results[platform];
                    if (r && r.success) {
                        var html = '✓';
                        if (r.external_url) {
                            html =
                                '✓ <a href="' +
                                escapeHtml(r.external_url) +
                                '" target="_blank" rel="noopener">' +
                                escapeHtml(fopostSocialAdmin.i18n.viewPost) +
                                '</a>';
                        }
                        $result.html(html).addClass('success');
                    } else {
                        var err = r ? r.error : fopostSocialAdmin.i18n.unknownError;
                        $result
                            .html('✗ ' + escapeHtml(err || fopostSocialAdmin.i18n.unknownError))
                            .addClass('error');
                    }
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : fopostSocialAdmin.i18n.publishFailed;

                    $result.html('✗ ' + escapeHtml(msg)).addClass('error');
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

    /**
     * Publish All Selected handler for the Meta Box.
     */
    function initPublishNow() {
        $('.fopost-social-publish-all-btn').on('click', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var $spinner = $btn.siblings('.fopost-social-publish-all-spinner');
            var $resultContainer = $('.fopost-social-publish-status');
            var postId = $btn.data('post-id');

            // Gather selected platforms.
            var platforms = [];
            $('input[name="fopost_social_platforms[]"]:checked').each(function () {
                platforms.push($(this).val());
            });

            if (platforms.length === 0) {
                $resultContainer
                    .html(fopostSocialAdmin.i18n.noPlatformsSelected)
                    .attr('class', 'fopost-social-publish-result error')
                    .show();
                return;
            }

            $btn.prop('disabled', true);
            $spinner.addClass('is-active');
            $resultContainer.hide();

            $.ajax({
                url: fopostSocialAdmin.restUrl + 'publish',
                method: 'POST',
                headers: {
                    'X-WP-Nonce': fopostSocialAdmin.nonce,
                },
                data: JSON.stringify({
                    post_id: postId,
                    platforms: platforms,
                }),
                contentType: 'application/json',
                dataType: 'json',
            })
                .done(function (response) {
                    var messages = [];
                    var hasFailure = false;

                    $.each(response.results, function (platform, result) {
                        // Update per-platform row indicators.
                        var $row = $('.fopost-social-platform-row[data-platform="' + platform + '"]');
                        var $rowResult = $row.find('.fopost-social-platform-result');

                        if (result.success) {
                            var link = result.external_url
                                ? ' (<a href="' +
                                  escapeHtml(result.external_url) +
                                  '" target="_blank">' +
                                  fopostSocialAdmin.i18n.viewPost +
                                  '</a>)'
                                : '';
                            messages.push(
                                '<strong>' +
                                    escapeHtml(platform) +
                                    '</strong>: ✓' +
                                    link
                            );
                            $rowResult.html('✓').removeClass('error').addClass('success');
                        } else {
                            hasFailure = true;
                            messages.push(
                                '<strong>' +
                                    escapeHtml(platform) +
                                    '</strong>: ✗ ' +
                                    escapeHtml(result.error || fopostSocialAdmin.i18n.unknownError)
                            );
                            $rowResult.html('✗').removeClass('success').addClass('error');
                        }
                    });

                    var cls = response.success
                        ? 'success'
                        : hasFailure
                        ? 'partial'
                        : 'error';

                    $resultContainer
                        .html(messages.join('<br>'))
                        .attr('class', 'fopost-social-publish-result ' + cls)
                        .show();
                })
                .fail(function (xhr) {
                    var msg =
                        xhr.responseJSON && xhr.responseJSON.message
                            ? xhr.responseJSON.message
                            : fopostSocialAdmin.i18n.publishFailed;

                    $resultContainer
                        .html(escapeHtml(msg))
                        .attr('class', 'fopost-social-publish-result error')
                        .show();
                })
                .always(function () {
                    $btn.prop('disabled', false);
                    $spinner.removeClass('is-active');
                });
        });
    }

    /**
     * Select-all checkbox for delivery logs.
     */
    function initSelectAll() {
        $('#cb-select-all-1').on('change', function () {
            var checked = $(this).prop('checked');
            $('input[name="log_ids[]"]').prop('checked', checked);
        });
    }

    /**
     * Escape HTML to prevent XSS.
     */
    function escapeHtml(str) {
        if (!str) return '';

        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));

        return div.innerHTML;
    }

    /**
     * Initialize on DOM ready.
     */
    $(function () {
        if (typeof fopostSocialAdmin === 'undefined') {
            return;
        }

        initTestConnection();
        initTestMessage();
        initPublishSingle();
        initPublishNow();
        initSelectAll();
    });
})(jQuery);
