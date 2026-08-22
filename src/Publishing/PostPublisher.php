<?php

declare(strict_types=1);

namespace Fopost\Social\Wp\Publishing;

defined( 'ABSPATH' ) || exit;

use Fopost\Social\Wp\Admin\MetaBox;
use Fopost\Social\Wp\Plugin;
use WP_Post;

/**
 * Handles automatic publishing when a WordPress post transitions to "publish" status.
 */
class PostPublisher
{
    /**
     * Called by the `transition_post_status` hook.
     */
    public static function handle(string $newStatus, string $oldStatus, WP_Post $post): void
    {
        // Only act on new publications.
        if ($newStatus !== 'publish' || $oldStatus === 'publish') {
            return;
        }

        // Only act on supported post types.
        $supportedTypes = apply_filters('fopost_social_supported_post_types', ['post']);
        if (! in_array($post->post_type, $supportedTypes, true)) {
            return;
        }

        // Check that the current user has permission to auto-publish.
        if (! current_user_can('fopost_social_publish')) {
            return;
        }

        // Check auto-publish flag.
        if (! MetaBox::isAutoPublishEnabled($post->ID)) {
            return;
        }

        // Prevent duplicate publishing (e.g. from rapid saves or race conditions).
        $publishedFlag = get_post_meta($post->ID, '_fopost_social_published', true);
        if ($publishedFlag === '1') {
            return;
        }

        $platforms = MetaBox::getSelectedPlatforms($post->ID);
        if (empty($platforms)) {
            return;
        }

        // Mark as published to prevent duplicates.
        update_post_meta($post->ID, '_fopost_social_published', '1');

        $sendTo = Plugin::instance()->sendTo();
        $corePost = $sendTo->buildPostFromWpPost($post);

        /** @var array $options */
        $options = apply_filters('fopost_social_publish_options', [], $post);

        do_action('fopost_social_before_publish', $corePost, $platforms, $post);

        $results = [];
        foreach ($platforms as $platform) {
            $results[$platform] = $sendTo->publish($corePost, $platform, $options, $post->ID);
        }

        do_action('fopost_social_after_publish', $results, $post);

        // Fire per-result actions.
        foreach ($results as $platform => $result) {
            if ($result->success) {
                do_action('fopost_social_post_published', $result, $post, $platform);
            } else {
                do_action('fopost_social_post_failed', $result, $post, $platform);
            }
        }
    }
}
