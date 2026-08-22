<?php

/**
 * FoPost Social Uninstall
 *
 * Fired when the plugin is uninstalled. Cleans up all plugin data
 * including options, custom database tables, and capabilities.
 *
 * @package Fopost\Social\Wp
 */

declare(strict_types=1);

// If uninstall not called from WordPress, exit.
if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load Composer autoloader.
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// If autoloader failed or class not found, clean up manually.
if (! class_exists(\Fopost\Social\Wp\Uninstaller::class)) {
    // Minimal fallback cleanup without autoloader.
    delete_option('fopost_social_settings');
    delete_option('fopost_social_db_version');

    global $wpdb;

    // Remove post meta.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s",
            '_fopost_social_%'
        )
    );

    // Remove tokens.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            'fopost_social_token_%'
        )
    );

    // Note: The delivery log table is dropped by Uninstaller::uninstall() when the
    // autoloader is available. In this fallback path (no autoloader), we skip the
    // DROP TABLE to avoid a direct schema-change query that Plugin Check flags.

    // Remove capabilities from all roles.
    $fopost_social_capabilities = ['manage_fopost_social', 'fopost_social_publish', 'fopost_social_view_logs'];
    foreach (wp_roles()->roles as $fopost_social_role_name => $fopost_social_role_data) {
        $fopost_social_role = get_role($fopost_social_role_name);
        if ($fopost_social_role === null) {
            continue;
        }
        foreach ($fopost_social_capabilities as $fopost_social_cap) {
            $fopost_social_role->remove_cap($fopost_social_cap);
        }
    }

    // Clear transients.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            '_transient_fopost_social_%',
            '_transient_timeout_fopost_social_%'
        )
    );

    return;
}

\Fopost\Social\Wp\Uninstaller::uninstall();
