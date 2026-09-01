<?php

/**
 * Plugin Name:       FoPost Social
 * Plugin URI:        https://github.com/fopost/fopost-social-wp
 * Description:       Publish content to Telegram, X (Twitter), Facebook, Instagram, LinkedIn, Discord, and more, directly from WordPress with your own platform credentials.
 * Version:           1.1.2
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            FoPost
 * Author URI:        https://fopost.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       fopost-social
 * Domain Path:       /languages
 */

declare(strict_types=1);

// Prevent direct access.
if (! defined('ABSPATH')) {
    exit;
}

// Plugin constants.
define('FOPOST_SOCIAL_VERSION', '1.1.2');
define('FOPOST_SOCIAL_FILE', __FILE__);
define('FOPOST_SOCIAL_DIR', plugin_dir_path(__FILE__));
define('FOPOST_SOCIAL_URL', plugin_dir_url(__FILE__));
define('FOPOST_SOCIAL_BASENAME', plugin_basename(__FILE__));

// Require Composer autoloader.
if (! file_exists(FOPOST_SOCIAL_DIR . 'vendor/autoload.php')) {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>';
        echo esc_html__('FoPost Social requires Composer dependencies. Please run "composer install" in the plugin directory.', 'fopost-social');
        echo '</p></div>';
    });

    return;
}

require_once FOPOST_SOCIAL_DIR . 'vendor/autoload.php';

// Boot the plugin.
$fopostSocialPlugin = \Fopost\Social\Wp\Plugin::instance();

// Activation / Deactivation hooks.
register_activation_hook(__FILE__, [\Fopost\Social\Wp\Activator::class, 'activate']);
register_deactivation_hook(__FILE__, [\Fopost\Social\Wp\Deactivator::class, 'deactivate']);

// Initialize on plugins_loaded (ensures all plugins are available).
add_action('plugins_loaded', [$fopostSocialPlugin, 'boot']);
