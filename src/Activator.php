<?php

declare(strict_types=1);

namespace Fopost\Social\Wp;

defined( 'ABSPATH' ) || exit;

use Fopost\Social\Wp\Database\DeliveryLogTable;

/**
 * Handles plugin activation tasks.
 */
class Activator
{
    /**
     * Run on plugin activation.
     */
    public static function activate(): void
    {
        self::checkRequirements();
        self::createTables();
        LegacyDataMigrator::run();
        self::setDefaultOptions();
        self::addCapabilities();

        flush_rewrite_rules();
    }

    /**
     * Check system requirements.
     */
    private static function checkRequirements(): void
    {
        if (! extension_loaded('openssl')) {
            set_transient('fopost_social_activation_notice', [
                'type'    => 'warning',
                'message' => __('FoPost Social: The OpenSSL PHP extension is not installed. OAuth tokens will be stored with base64 encoding only (not encrypted). Install the OpenSSL extension for production use.', 'fopost-social'),
            ], 60);
        }
    }

    /**
     * Create custom database tables.
     */
    private static function createTables(): void
    {
        DeliveryLogTable::create();
    }

    /**
     * Set default plugin options if not already set.
     */
    private static function setDefaultOptions(): void
    {
        if (get_option('fopost_social_settings') === false) {
            $defaults = [
                'platforms' => [
                    'telegram' => [
                        'api_token'         => '',
                        'bot_username'      => '',
                        'channel_username'  => '',
                        'channel_signature' => '',
                        'parse_mode'        => 'HTML',
                    ],
                    'twitter' => [
                        'consumer_key'        => '',
                        'consumer_secret'     => '',
                        'access_token'        => '',
                        'access_token_secret' => '',
                    ],
                    'facebook' => [
                        'app_id'                => '',
                        'app_secret'            => '',
                        'page_access_token'     => '',
                        'page_id'               => '',
                        'default_graph_version' => 'v21.0',
                    ],
                ],
                'proxy' => [
                    'type'     => '',
                    'hostname' => '',
                    'port'     => '',
                    'username' => '',
                    'password' => '',
                ],
            ];

            add_option('fopost_social_settings', $defaults);
        }

        if (get_option('fopost_social_db_version') === false) {
            add_option('fopost_social_db_version', '1.0.0');
        }
    }

    /**
     * Add custom capabilities to administrator role.
     */
    private static function addCapabilities(): void
    {
        $role = get_role('administrator');

        if ($role === null) {
            return;
        }

        $role->add_cap('manage_fopost_social');
        $role->add_cap('fopost_social_publish');
        $role->add_cap('fopost_social_view_logs');
    }
}
