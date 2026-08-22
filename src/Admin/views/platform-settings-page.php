<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array{label: string, description: string, docs_url: string, fields: array} $platform */
/** @var string $platformSlug */
/** @var \Fopost\Social\Wp\Admin\OptionsManager $optionsManager */
?>
<div class="wrap fopost-social-settings fopost-social-platform-settings">
    <h1>
        <?php
        printf(
            /* translators: %s: platform name */
            esc_html__('Owlstack — %s Settings', 'fopost-social'),
            esc_html($platform['label']),
        );
        ?>
    </h1>

    <?php
    if (! empty($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        add_settings_error('fopost_social_settings', 'fopost_social_updated', __('Settings saved.', 'fopost-social'), 'updated');
    }
    settings_errors('fopost_social_settings');
    ?>

    <div class="fopost-social-platform-docs-notice" style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px 16px; margin: 16px 0;">
        <p style="margin: 0;">
            <?php
            printf(
                /* translators: %1$s: platform name, %2$s: opening link tag, %3$s: closing link tag */
                esc_html__('Need help setting up %1$s? See the %2$sconfiguration guide%3$s for step-by-step instructions on obtaining API credentials and configuring this platform for your WordPress site.', 'fopost-social'),
                esc_html($platform['label']),
                '<a href="' . esc_url($platform['docs_url']) . '" target="_blank" rel="noopener noreferrer">',
                '</a>',
            );
            ?>
        </p>
    </div>

    <form method="post" action="options.php">
        <?php
        settings_fields('fopost_social_settings_group');
        do_settings_sections("fopost-social-{$platformSlug}");
        submit_button(__('Save Settings', 'fopost-social'));
        ?>
    </form>

    <hr />

    <!-- ── Testing Section ────────────────────────────────────────────── -->
    <h2><?php esc_html_e('Testing', 'fopost-social'); ?></h2>
    <p><?php esc_html_e('Save your settings first, then use the actions below to verify your integration.', 'fopost-social'); ?></p>

    <table class="wp-list-table widefat fixed striped fopost-social-test-actions-table">
        <thead>
            <tr>
                <th class="fopost-social-test-col-type"><?php esc_html_e('Test', 'fopost-social'); ?></th>
                <th class="fopost-social-test-col-desc"><?php esc_html_e('Description', 'fopost-social'); ?></th>
                <th class="fopost-social-test-col-action"><?php esc_html_e('Action', 'fopost-social'); ?></th>
            </tr>
        </thead>
        <tbody>
            <!-- Connection Test -->
            <tr>
                <td><strong><?php esc_html_e('Connection', 'fopost-social'); ?></strong></td>
                <td><?php esc_html_e('Validates that your API credentials are correct and the platform is reachable.', 'fopost-social'); ?></td>
                <td>
                    <button type="button" class="button fopost-social-test-btn" data-platform="<?php echo esc_attr($platformSlug); ?>">
                        <?php esc_html_e('Test Connection', 'fopost-social'); ?>
                    </button>
                    <span class="spinner"></span>
                </td>
            </tr>

            <!-- Text Message Test -->
            <tr>
                <td><strong><?php esc_html_e('Text Message', 'fopost-social'); ?></strong></td>
                <td><?php esc_html_e('Sends a sample text post to verify publishing works end-to-end. The message will include your site name and a timestamp.', 'fopost-social'); ?></td>
                <td>
                    <button type="button" class="button fopost-social-test-message-btn" data-platform="<?php echo esc_attr($platformSlug); ?>" data-type="text">
                        <?php esc_html_e('Send Test Message', 'fopost-social'); ?>
                    </button>
                    <span class="spinner"></span>
                </td>
            </tr>

            <!-- Image / Media (Guidance) -->
            <tr>
                <td><strong><?php esc_html_e('Image / Media', 'fopost-social'); ?></strong></td>
                <td>
                    <?php esc_html_e('To test image or media publishing:', 'fopost-social'); ?>
                    <ol class="fopost-social-test-steps">
                        <li><?php esc_html_e('Create a new post in WordPress and add an image via the Featured Image or content editor.', 'fopost-social'); ?></li>
                        <li>
                            <?php
                            printf(
                                /* translators: %s: platform name */
                                esc_html__('In the Owlstack meta box on the post editor, select "%s" as a target platform.', 'fopost-social'),
                                esc_html($platform['label']),
                            );
                            ?>
                        </li>
                        <li><?php esc_html_e('Click "Publish Now" in the meta box to send the post with its media attachments.', 'fopost-social'); ?></li>
                    </ol>
                </td>
                <td>
                    <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="button">
                        <?php esc_html_e('Create Test Post', 'fopost-social'); ?>
                    </a>
                </td>
            </tr>

            <!-- Video (Guidance) -->
            <tr>
                <td><strong><?php esc_html_e('Video', 'fopost-social'); ?></strong></td>
                <td>
                    <?php esc_html_e('To test video publishing, follow the same steps as image testing above, but upload a video file instead. Supported formats depend on the platform (typically MP4).', 'fopost-social'); ?>
                </td>
                <td>
                    <a href="<?php echo esc_url(admin_url('post-new.php')); ?>" class="button">
                        <?php esc_html_e('Create Test Post', 'fopost-social'); ?>
                    </a>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="fopost-social-test-result" class="fopost-social-test-result" style="margin-top: 12px;"></div>

    <p style="margin-top: 24px;">
        <a href="<?php echo esc_url(admin_url('admin.php?page=fopost-social')); ?>">&larr; <?php esc_html_e('Back to Settings Overview', 'fopost-social'); ?></a>
    </p>
</div>
