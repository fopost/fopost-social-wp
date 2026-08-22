<?php

declare(strict_types=1);

namespace Fopost\Social\Wp;

defined( 'ABSPATH' ) || exit;

use Fopost\Social\Wp\Database\DeliveryLogTable;

/**
 * Copies data written under the plugin's previous `owlstack` prefix.
 *
 * Every copy is additive: the old option, meta row, capability, and table are
 * left in place so a rollback to the previous plugin keeps working.
 */
class LegacyDataMigrator
{
    /** Set once the copy has run, so it never repeats. */
    public const FLAG_OPTION = 'fopost_social_legacy_import_done';

    private const OPTIONS = [
        'owlstack_settings'   => 'fopost_social_settings',
        'owlstack_db_version' => 'fopost_social_db_version',
    ];

    private const POST_META = [
        '_owlstack_auto_publish' => '_fopost_social_auto_publish',
        '_owlstack_platforms'    => '_fopost_social_platforms',
        '_owlstack_published'    => '_fopost_social_published',
    ];

    private const USER_META = [
        'owlstack_cloud_promo_dismissed' => 'fopost_social_cloud_promo_dismissed',
    ];

    private const CAPABILITIES = [
        'manage_owlstack'    => 'manage_fopost_social',
        'owlstack_publish'   => 'fopost_social_publish',
        'owlstack_view_logs' => 'fopost_social_view_logs',
    ];

    private const LEGACY_TOKEN_PREFIX = 'owlstack_token_';

    private const TOKEN_PREFIX = 'fopost_social_token_';

    private const LEGACY_TABLE = 'owlstack_delivery_logs';

    /**
     * Run the copy once.
     */
    public static function run(): void
    {
        if (get_option(self::FLAG_OPTION) !== false) {
            return;
        }

        self::copyOptions();
        self::copyTokens();
        self::copyPostMeta();
        self::copyUserMeta();
        self::copyCapabilities();
        self::copyDeliveryLogs();

        add_option(self::FLAG_OPTION, '1');
    }

    private static function copyOptions(): void
    {
        foreach (self::OPTIONS as $old => $new) {
            $value = get_option($old);

            if ($value !== false && get_option($new) === false) {
                add_option($new, $value);
            }
        }
    }

    private static function copyTokens(): void
    {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $names = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
                $wpdb->esc_like(self::LEGACY_TOKEN_PREFIX) . '%'
            )
        );

        foreach ((array) $names as $name) {
            $platform = substr((string) $name, strlen(self::LEGACY_TOKEN_PREFIX));
            $new = self::TOKEN_PREFIX . $platform;

            if (get_option($new) === false) {
                add_option($new, get_option((string) $name), '', false);
            }
        }
    }

    private static function copyPostMeta(): void
    {
        global $wpdb;

        foreach (self::POST_META as $old => $new) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value)
                     SELECT legacy.post_id, %s, legacy.meta_value
                     FROM {$wpdb->postmeta} legacy
                     LEFT JOIN {$wpdb->postmeta} renamed
                            ON renamed.post_id = legacy.post_id AND renamed.meta_key = %s
                     WHERE legacy.meta_key = %s AND renamed.meta_id IS NULL",
                    $new,
                    $new,
                    $old
                )
            );
        }
    }

    private static function copyUserMeta(): void
    {
        global $wpdb;

        foreach (self::USER_META as $old => $new) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->query(
                $wpdb->prepare(
                    "INSERT INTO {$wpdb->usermeta} (user_id, meta_key, meta_value)
                     SELECT legacy.user_id, %s, legacy.meta_value
                     FROM {$wpdb->usermeta} legacy
                     LEFT JOIN {$wpdb->usermeta} renamed
                            ON renamed.user_id = legacy.user_id AND renamed.meta_key = %s
                     WHERE legacy.meta_key = %s AND renamed.umeta_id IS NULL",
                    $new,
                    $new,
                    $old
                )
            );
        }
    }

    private static function copyCapabilities(): void
    {
        if (! function_exists('wp_roles')) {
            return;
        }

        foreach (array_keys(wp_roles()->roles) as $roleName) {
            $role = get_role((string) $roleName);

            if ($role === null) {
                continue;
            }

            foreach (self::CAPABILITIES as $old => $new) {
                if ($role->has_cap($old) && ! $role->has_cap($new)) {
                    $role->add_cap($new);
                }
            }
        }
    }

    private static function copyDeliveryLogs(): void
    {
        global $wpdb;

        $legacy = $wpdb->prefix . self::LEGACY_TABLE;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $legacy ) );

        if ($exists !== $legacy) {
            return;
        }

        DeliveryLogTable::create();

        $target = DeliveryLogTable::tableName();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $alreadyCopied = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $target ) );

        if ($alreadyCopied > 0) {
            return;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query(
            $wpdb->prepare(
                'INSERT INTO %i
                 (id, post_id, platform, status, external_id, external_url, error, payload, created_at)
                 SELECT id, post_id, platform, status, external_id, external_url, error, payload, created_at
                 FROM %i',
                $target,
                $legacy
            )
        );
    }
}
