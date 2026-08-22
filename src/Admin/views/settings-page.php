<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/** @var array $platforms */
/** @var \Fopost\Social\Wp\Admin\OptionsManager $optionsManager */
/** @var string[] $configuredNames */
?>
<div class="wrap fopost-social-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php
    if (! empty($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        add_settings_error('fopost_social_settings', 'fopost_social_updated', __('Settings saved.', 'fopost-social'), 'updated');
    }
    settings_errors('fopost_social_settings');
    ?>

    <?php if (! \Fopost\Social\Wp\Admin\CloudPromo::isDismissed()): ?>
        <div class="fopost-social-cloud-promo notice notice-info">
            <h2 class="fopost-social-cloud-promo__title">
                <?php esc_html_e('Publish beyond this site with hosted FoPost', 'fopost-social'); ?>
            </h2>
            <p>
                <?php
                printf(
                    /* translators: 1: platforms this plugin supports, 2: platforms hosted FoPost supports */
                    esc_html__('This plugin publishes to %1$d platforms straight from WordPress using your own credentials, and it always will. Hosted FoPost reaches %2$d platforms and adds scheduling, a content calendar, analytics, and AI drafting, with this site as one of its destinations.', 'fopost-social'),
                    count($platforms),
                    (int) \Fopost\Social\Wp\Admin\CloudPromo::CLOUD_PLATFORM_COUNT,
                );
                ?>
            </p>
            <p>
                <a class="button button-primary" href="<?php echo esc_url(\Fopost\Social\Wp\Admin\CloudPromo::url('/', 'settings-card')); ?>" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e('Start a free trial', 'fopost-social'); ?>
                </a>
                <a class="fopost-social-cloud-promo__dismiss" href="<?php echo esc_url(\Fopost\Social\Wp\Admin\CloudPromo::dismissUrl()); ?>">
                    <?php esc_html_e('Dismiss', 'fopost-social'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <!-- Platform overview -->
    <h2><?php esc_html_e('Platforms', 'fopost-social'); ?></h2>
    <p><?php esc_html_e('Configure each platform from its own settings page. Platforms with valid credentials are marked as connected.', 'fopost-social'); ?></p>

    <table class="wp-list-table widefat fixed striped fopost-social-platform-overview">
        <thead>
            <tr>
                <th><?php esc_html_e('Platform', 'fopost-social'); ?></th>
                <th><?php esc_html_e('Status', 'fopost-social'); ?></th>
                <th><?php esc_html_e('Actions', 'fopost-social'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($platforms as $fopost_social_key => $fopost_social_platform): ?>
                <?php $fopost_social_is_configured = in_array($fopost_social_key, $configuredNames, true); ?>
                <tr>
                    <td>
                        <strong><?php echo esc_html($fopost_social_platform['label']); ?></strong>
                    </td>
                    <td>
                        <?php if ($fopost_social_is_configured): ?>
                            <span class="fopost-social-badge fopost-social-badge--success"><?php esc_html_e('Connected', 'fopost-social'); ?></span>
                        <?php else: ?>
                            <span class="fopost-social-badge fopost-social-badge--pending"><?php esc_html_e('Not configured', 'fopost-social'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="<?php echo esc_url(admin_url("admin.php?page=fopost-social-{$fopost_social_key}")); ?>" class="button button-small">
                            <?php esc_html_e('Configure', 'fopost-social'); ?>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Proxy settings -->
    <form method="post" action="options.php">
        <?php
        settings_fields('fopost_social_settings_group');
        do_settings_sections('fopost-social');
        submit_button(__('Save Settings', 'fopost-social'));
        ?>
    </form>

    <!-- Support section -->
    <div class="fopost-social-support-section">
        <h2><?php esc_html_e('Need Help?', 'fopost-social'); ?></h2>
        <p>
            <?php
            printf(
                /* translators: %s: link to FoPost documentation */
                esc_html__('Need help setting up your social media platforms such as Telegram, Twitter, Facebook, Instagram, LinkedIn, or others? Our documentation covers step-by-step guides for configuring each platform with the WordPress plugin. Visit the %s to get started.', 'fopost-social'),
                '<a href="https://fopost.com/docs/sdks/wordpress/installation" target="_blank" rel="noopener noreferrer">' . esc_html__('FoPost Documentation', 'fopost-social') . '</a>'
            );
            ?>
        </p>
    </div>
</div>
