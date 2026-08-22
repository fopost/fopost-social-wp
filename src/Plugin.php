<?php

declare(strict_types=1);

namespace Fopost\Social\Wp;

defined( 'ABSPATH' ) || exit;

use Fopost\Social\Config\FopostConfig;
use Fopost\Social\Events\Contracts\EventDispatcherInterface;
use Fopost\Social\Formatting\CharacterTruncator;
use Fopost\Social\Formatting\HashtagExtractor;
use Fopost\Social\Http\Contracts\HttpClientInterface;
use Fopost\Social\Platforms\Discord\DiscordFormatter;
use Fopost\Social\Platforms\Discord\DiscordPlatform;
use Fopost\Social\Platforms\Facebook\FacebookFormatter;
use Fopost\Social\Platforms\Facebook\FacebookPlatform;
use Fopost\Social\Platforms\Instagram\InstagramPlatform;
use Fopost\Social\Platforms\LinkedIn\LinkedInFormatter;
use Fopost\Social\Platforms\LinkedIn\LinkedInPlatform;
use Fopost\Social\Platforms\Pinterest\PinterestPlatform;
use Fopost\Social\Platforms\PlatformRegistry;
use Fopost\Social\Platforms\Reddit\RedditFormatter;
use Fopost\Social\Platforms\Reddit\RedditPlatform;
use Fopost\Social\Platforms\Slack\SlackPlatform;
use Fopost\Social\Platforms\Telegram\TelegramFormatter;
use Fopost\Social\Platforms\Telegram\TelegramPlatform;
use Fopost\Social\Platforms\Tumblr\TumblrPlatform;
use Fopost\Social\Platforms\Twitter\TwitterFormatter;
use Fopost\Social\Platforms\Twitter\TwitterPlatform;
use Fopost\Social\Platforms\WhatsApp\WhatsAppPlatform;
use Fopost\Social\Publishing\Publisher;
use Fopost\Social\Wp\Admin\CloudPromo;
use Fopost\Social\Wp\Admin\DeliveryLogsPage;
use Fopost\Social\Wp\Admin\MetaBox;
use Fopost\Social\Wp\Admin\OptionsManager;
use Fopost\Social\Wp\Admin\SettingsPage;
use Fopost\Social\Wp\Auth\WpTokenStore;
use Fopost\Social\Wp\Events\WpEventDispatcher;
use Fopost\Social\Wp\Http\WpHttpClient;
use Fopost\Social\Wp\Publishing\PostPublisher;
use Fopost\Social\Wp\Publishing\SendTo;
use Fopost\Social\Wp\Rest\FopostRestController;

/**
 * Main plugin class — wires all services and registers WordPress hooks.
 */
class Plugin
{
    private static ?self $instance = null;

    private ?FopostConfig $config = null;
    private ?HttpClientInterface $httpClient = null;
    private ?EventDispatcherInterface $eventDispatcher = null;
    private ?PlatformRegistry $registry = null;
    private ?Publisher $publisher = null;
    private ?SendTo $sendTo = null;
    private ?OptionsManager $optionsManager = null;
    private ?WpTokenStore $tokenStore = null;

    private bool $booted = false;

    private function __construct()
    {
        // Singleton — use Plugin::instance().
    }

    /**
     * Get the singleton plugin instance.
     */
    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Boot the plugin — register all hooks and initialize services.
     */
    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        // Carry data forward from the plugin's previous `owlstack` prefix.
        LegacyDataMigrator::run();

        // Build services.
        $this->buildServices();

        // Admin hooks.
        if (is_admin()) {
            $this->registerAdminHooks();
        }

        // Show activation notices.
        add_action('admin_notices', [$this, 'showActivationNotice']);

        // REST API.
        add_action('rest_api_init', [FopostRestController::class, 'register']);

        // Post publishing hook.
        add_action('transition_post_status', [PostPublisher::class, 'handle'], 10, 3);
    }

    // ── Service accessors ────────────────────────────────────────────────

    public function config(): FopostConfig
    {
        if ($this->config === null) {
            $this->buildServices();
        }

        return $this->config;
    }

    public function httpClient(): HttpClientInterface
    {
        if ($this->httpClient === null) {
            $this->httpClient = new WpHttpClient();
        }

        return $this->httpClient;
    }

    public function eventDispatcher(): EventDispatcherInterface
    {
        if ($this->eventDispatcher === null) {
            $this->eventDispatcher = new WpEventDispatcher();
        }

        return $this->eventDispatcher;
    }

    public function optionsManager(): OptionsManager
    {
        if ($this->optionsManager === null) {
            $this->optionsManager = new OptionsManager();
        }

        return $this->optionsManager;
    }

    public function tokenStore(): WpTokenStore
    {
        if ($this->tokenStore === null) {
            $this->tokenStore = new WpTokenStore();
        }

        return $this->tokenStore;
    }

    public function registry(): PlatformRegistry
    {
        if ($this->registry === null) {
            $this->buildPlatforms();
        }

        return $this->registry;
    }

    public function publisher(): Publisher
    {
        if ($this->publisher === null) {
            $this->publisher = new Publisher(
                platforms: $this->registry(),
                eventDispatcher: $this->eventDispatcher(),
            );
        }

        return $this->publisher;
    }

    public function sendTo(): SendTo
    {
        if ($this->sendTo === null) {
            $this->sendTo = new SendTo(
                publisher: $this->publisher(),
                config: $this->config(),
                registry: $this->registry(),
            );
        }

        return $this->sendTo;
    }

    // ── Internal setup ───────────────────────────────────────────────────

    private function buildServices(): void
    {
        $manager = $this->optionsManager();
        $this->config = $manager->buildConfig();
    }

    private function buildPlatforms(): void
    {
        $this->registry = new PlatformRegistry();
        $config = $this->config();
        $httpClient = $this->httpClient();

        $hashtagExtractor = new HashtagExtractor();
        $truncator = new CharacterTruncator();

        if ($config->hasPlatform('telegram')) {
            $formatter = new TelegramFormatter($hashtagExtractor, $truncator);
            $platform = new TelegramPlatform(
                credentials: $config->credentials('telegram'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('twitter')) {
            $formatter = new TwitterFormatter($hashtagExtractor, $truncator);
            $platform = new TwitterPlatform(
                credentials: $config->credentials('twitter'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('facebook')) {
            $formatter = new FacebookFormatter($hashtagExtractor, $truncator);
            $graphVersion = $config->credentials('facebook')?->get('default_graph_version', 'v21.0') ?? 'v21.0';
            $platform = new FacebookPlatform(
                credentials: $config->credentials('facebook'),
                httpClient: $httpClient,
                formatter: $formatter,
                graphVersion: $graphVersion,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('instagram')) {
            $graphVersion = $config->credentials('instagram')?->get('default_graph_version', 'v19.0') ?? 'v19.0';
            $platform = new InstagramPlatform(
                credentials: $config->credentials('instagram'),
                httpClient: $httpClient,
                graphVersion: $graphVersion,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('linkedin')) {
            $formatter = new LinkedInFormatter($hashtagExtractor, $truncator);
            $platform = new LinkedInPlatform(
                credentials: $config->credentials('linkedin'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('discord')) {
            $formatter = new DiscordFormatter($hashtagExtractor, $truncator);
            $platform = new DiscordPlatform(
                credentials: $config->credentials('discord'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('pinterest')) {
            $platform = new PinterestPlatform(
                credentials: $config->credentials('pinterest'),
                httpClient: $httpClient,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('reddit')) {
            $formatter = new RedditFormatter($hashtagExtractor, $truncator);
            $platform = new RedditPlatform(
                credentials: $config->credentials('reddit'),
                httpClient: $httpClient,
                formatter: $formatter,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('slack')) {
            $platform = new SlackPlatform(
                credentials: $config->credentials('slack'),
                httpClient: $httpClient,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('tumblr')) {
            $platform = new TumblrPlatform(
                credentials: $config->credentials('tumblr'),
                httpClient: $httpClient,
            );
            $this->registry->register($platform);
        }

        if ($config->hasPlatform('whatsapp')) {
            $graphVersion = $config->credentials('whatsapp')?->get('default_graph_version', 'v19.0') ?? 'v19.0';
            $platform = new WhatsAppPlatform(
                credentials: $config->credentials('whatsapp'),
                httpClient: $httpClient,
                graphVersion: $graphVersion,
            );
            $this->registry->register($platform);
        }
    }

    private function registerAdminHooks(): void
    {
        $settingsPage = new SettingsPage($this->optionsManager());
        add_action('admin_menu', [$settingsPage, 'register']);
        add_action('admin_init', [$settingsPage, 'registerSettings']);

        $metaBox = new MetaBox();
        add_action('add_meta_boxes', [$metaBox, 'register']);
        add_action('save_post', [$metaBox, 'save'], 10, 2);

        $logsPage = new DeliveryLogsPage();
        add_action('admin_menu', [$logsPage, 'register']);

        CloudPromo::registerActions();

        add_action('admin_enqueue_scripts', [$this, 'enqueueAdminAssets']);
    }

    /**
     * Show one-time activation notices.
     */
    public function showActivationNotice(): void
    {
        $notice = get_transient('fopost_social_activation_notice');

        if ($notice === false || ! is_array($notice)) {
            return;
        }

        $type = $notice['type'] ?? 'info';
        $message = $notice['message'] ?? '';

        printf(
            '<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
            esc_attr($type),
            esc_html($message),
        );

        delete_transient('fopost_social_activation_notice');
    }

    /**
     * Enqueue admin CSS and JS on Owlstack admin pages.
     */
    public function enqueueAdminAssets(string $hook): void
    {
        $fopostSocialPages = [
            'toplevel_page_fopost-social',
            'fopost-social_page_fopost-social-logs',
            'fopost-social_page_fopost-social-telegram',
            'fopost-social_page_fopost-social-twitter',
            'fopost-social_page_fopost-social-facebook',
            'fopost-social_page_fopost-social-instagram',
            'fopost-social_page_fopost-social-linkedin',
            'fopost-social_page_fopost-social-discord',
            'fopost-social_page_fopost-social-pinterest',
            'fopost-social_page_fopost-social-reddit',
            'fopost-social_page_fopost-social-slack',
            'fopost-social_page_fopost-social-tumblr',
            'fopost-social_page_fopost-social-whatsapp',
        ];

        // Also load on post edit screens for the meta box.
        $postPages = ['post.php', 'post-new.php'];

        if (! in_array($hook, array_merge($fopostSocialPages, $postPages), true)) {
            return;
        }

        wp_enqueue_style(
            'fopost-social-admin',
            FOPOST_SOCIAL_URL . 'assets/css/admin.css',
            [],
            FOPOST_SOCIAL_VERSION,
        );

        wp_enqueue_script(
            'fopost-social-admin',
            FOPOST_SOCIAL_URL . 'assets/js/admin.js',
            ['jquery'],
            FOPOST_SOCIAL_VERSION,
            true,
        );

        wp_localize_script('fopost-social-admin', 'fopostSocialAdmin', [
            'restUrl' => rest_url('fopost-social/v1/'),
            'nonce'   => wp_create_nonce('wp_rest'),
            'i18n'    => [
                'connectionFailed'    => __('Connection test failed. Please check your credentials.', 'fopost-social'),
                'testMessageFailed'   => __('Failed to send test message. Please check your credentials.', 'fopost-social'),
                'noPlatformsSelected' => __('Please select at least one platform.', 'fopost-social'),
                'viewPost'            => __('View Post', 'fopost-social'),
                'publishFailed'       => __('Publishing failed. Please try again.', 'fopost-social'),
                'unknownError'        => __('An unknown error occurred.', 'fopost-social'),
            ],
        ]);
    }

    /**
     * Prevent cloning.
     */
    private function __clone()
    {
    }
}
