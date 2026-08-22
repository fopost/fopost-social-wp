<?php

declare(strict_types=1);

namespace Fopost\Social\Wp;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin deactivation tasks.
 */
class Deactivator
{
    /**
     * Run on plugin deactivation.
     */
    public static function deactivate(): void
    {
        self::clearScheduledEvents();
        self::removeCapabilities();

        flush_rewrite_rules();
    }

    /**
     * Clear any scheduled WP-Cron events.
     */
    private static function clearScheduledEvents(): void
    {
        wp_clear_scheduled_hook('fopost_social_scheduled_publish');
    }

    /**
     * Remove custom capabilities from administrator role.
     */
    private static function removeCapabilities(): void
    {
        $role = get_role('administrator');

        if ($role === null) {
            return;
        }

        $role->remove_cap('manage_fopost_social');
        $role->remove_cap('fopost_social_publish');
        $role->remove_cap('fopost_social_view_logs');
    }
}
