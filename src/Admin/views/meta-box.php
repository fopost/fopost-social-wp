<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var string[] $configuredPlatforms */
/** @var string[] $selectedPlatforms */
/** @var bool $autoPublish */
/** @var WP_Post $post */

wp_nonce_field('fopost_social_meta_box', 'fopost_social_meta_box_nonce');

$fopost_social_platform_labels = \Fopost\Social\Wp\Admin\SettingsPage::platforms();
?>

<div class="fopost-social-meta-box">
    <?php if (empty($configuredPlatforms)) : ?>
        <p class="fopost-social-no-platforms">
            <?php
            printf(
                /* translators: %s: link open tag, %s: link close tag */
                esc_html__('No platforms configured. %1$sConfigure platforms%2$s in FoPost Social settings.', 'fopost-social'),
                '<a href="' . esc_url(admin_url('admin.php?page=fopost-social')) . '">',
                '</a>',
            );
            ?>
        </p>
    <?php else : ?>
        <p class="fopost-social-meta-label"><strong><?php esc_html_e('Publish to:', 'fopost-social'); ?></strong></p>

        <table class="fopost-social-platform-list">
            <tbody>
            <?php foreach ($configuredPlatforms as $fopost_social_platform) :
                $fopost_social_label = $fopost_social_platform_labels[$fopost_social_platform]['label'] ?? ucfirst($fopost_social_platform);
            ?>
                <tr class="fopost-social-platform-row" data-platform="<?php echo esc_attr($fopost_social_platform); ?>">
                    <td class="fopost-social-platform-checkbox-col">
                        <input
                            type="checkbox"
                            name="fopost_social_platforms[]"
                            value="<?php echo esc_attr($fopost_social_platform); ?>"
                            <?php checked(in_array($fopost_social_platform, $selectedPlatforms, true)); ?>
                        />
                    </td>
                    <td class="fopost-social-platform-name-col">
                        <span class="fopost-social-badge fopost-social-badge--<?php echo esc_attr($fopost_social_platform); ?>"><?php echo esc_html($fopost_social_label); ?></span>
                    </td>
                    <td class="fopost-social-platform-action-col">
                        <button type="button"
                                class="button button-small fopost-social-publish-single-btn"
                                data-post-id="<?php echo esc_attr((string) $post->ID); ?>"
                                data-platform="<?php echo esc_attr($fopost_social_platform); ?>"
                                title="<?php echo esc_attr(sprintf(
                                    /* translators: %s: platform name */
                                    __('Publish to %s', 'fopost-social'),
                                    $fopost_social_label
                                )); ?>">
                            <?php esc_html_e('Publish', 'fopost-social'); ?>
                        </button>
                        <span class="spinner"></span>
                    </td>
                    <td class="fopost-social-platform-status-col">
                        <span class="fopost-social-platform-result"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <hr />

        <label class="fopost-social-auto-publish">
            <input
                type="checkbox"
                name="fopost_social_auto_publish"
                value="1"
                <?php checked($autoPublish); ?>
            />
            <?php esc_html_e('Auto-publish when post is published', 'fopost-social'); ?>
        </label>

        <hr />

        <button type="button" class="button button-primary fopost-social-publish-all-btn" data-post-id="<?php echo esc_attr((string) $post->ID); ?>">
            <?php esc_html_e('Publish All Selected', 'fopost-social'); ?>
        </button>
        <span class="spinner fopost-social-publish-all-spinner"></span>
        <div class="fopost-social-publish-status"></div>
    <?php endif; ?>
</div>
