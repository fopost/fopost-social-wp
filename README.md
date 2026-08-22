# FoPost Social for WordPress

WordPress plugin for [FoPost](https://fopost.com): publish content to multiple social media platforms directly from WordPress, using your own platform credentials. No FoPost account required.

Integrates `fopost/social-core` into WordPress sites: admin settings, post meta boxes, delivery logs, REST API endpoints, and more.

## Supported Platforms

| Platform | Status |
|----------|--------|
| Telegram | ✅ Supported |
| X (Twitter) | ✅ Supported |
| Facebook | ✅ Supported |
| Instagram | ✅ Supported |
| LinkedIn | ✅ Supported |
| Discord | ✅ Supported |
| Pinterest | ✅ Supported |
| Reddit | ✅ Supported |
| Slack | ✅ Supported |
| Tumblr | ✅ Supported |
| WhatsApp | ✅ Supported |

## Requirements

- PHP 8.1+
- WordPress 6.4+

## Installation

1. Download the plugin zip or clone this repository into `wp-content/plugins/fopost-social`.
2. For development, run `composer install` in the plugin directory.
3. Activate the plugin from WP Admin → Plugins.
4. Go to **FoPost Social > Settings** to configure your platform credentials.

## Features

- **Admin Settings Page** — Configure platform credentials and proxy settings from a clean admin UI
- **Publish Meta Box** — Select platforms and publish directly from the post editor
- **Auto-Publish** — Automatically share posts when they're published
- **Delivery Logs** — Track all publishing activity with status, errors, and external links
- **REST API** — AJAX endpoints for connection testing, manual publishing, and log management
- **WP HTTP API** — Uses native WordPress HTTP functions instead of cURL
- **WordPress Events** — Full hook support with `fopost_social_post_published` and `fopost_social_post_failed` actions
- **Token Storage** — Encrypted OAuth token storage via `wp_options`

## Usage

### From PHP (theme or plugin code)

```php
// Publish to Telegram
fopost_social()->telegram('Hello from WordPress!');

// Publish to Twitter/X
fopost_social()->twitter('Hello from WordPress!');
// or
fopost_social()->x('Hello from WordPress!');

// Publish to Facebook
fopost_social()->facebook('Hello from WordPress!', 'link', ['link' => 'https://example.com']);

// Publish to a specific platform by name
fopost_social()->publish($post, 'linkedin');

// Publish to all configured platforms
$post = new \Fopost\Social\Content\Post(
    title: 'My Post',
    body: 'Hello world!',
    url: 'https://example.com/my-post',
);
fopost_social()->toAll($post);
```

### Auto-publish on post publish

Enable via the FoPost Social meta box in the post editor. Select platforms and the post will be published automatically when you hit "Publish".

## Hooks

### Actions

- `fopost_social_post_published` — Fired after successful publishing. Receives `PostPublished` event.
- `fopost_social_post_failed` — Fired after a publishing failure. Receives `PostFailed` event.
- `fopost_social_before_publish` — Fired before publishing starts. Receives `WP_Post` and platform names.
- `fopost_social_after_publish` — Fired after publishing completes. Receives `WP_Post` and results.

### Filters

- `fopost_social_supported_post_types` — Filter which post types show the meta box. Default: `['post']`.
- `fopost_social_post_data` — Filter the `Post` object before publishing.
- `fopost_social_publish_options` — Filter platform-specific options before publishing.

## Dependencies

- [`fopost/social-core`](https://github.com/fopost/fopost-social-core) (bundled via Composer)

## Roadmap

Planned features for upcoming releases:

- [ ] **Message Template Builder** — Define per-platform message templates with placeholders (`{title}`, `{body:100}`, `{url}`, `{tags}`) to control message format, content length, and tag rendering
- [ ] **Per-Post Custom Message** — Override the auto-generated message with a custom text field in the post editor meta box
- [ ] **Configurable body word limit** — Allow users to set how many words of the post body are included in the message (currently hardcoded at 55 words)
- [ ] **Tag selection control** — Choose which taxonomy to use for tags, limit the number of tags, or exclude specific tags

## License

GPL-2.0-or-later
