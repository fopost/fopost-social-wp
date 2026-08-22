# AGENTS.md

> **This is the single source of truth for all AI coding agents** working in this repository.
> All agents (GitHub Copilot, Claude, ChatGPT, Cursor, Windsurf, Codex, etc.) MUST follow these rules.
> Agent-specific files (`CLAUDE.md`, `.github/copilot-instructions.md`) extend this file, they do NOT replace it.

---

## Project Overview

**FoPost Social** is a WordPress plugin that integrates [FoPost Social Core](https://github.com/fopost/fopost-social-core) with WordPress, publishing WordPress posts out to social media platforms.

> **Brand name:** "FoPost" (capital F, capital P). The domain is `fopost.com`.

It is the free, offline toolkit: it talks straight to each platform with the site owner's own app credentials. It has no FoPost account, no FoPost API key, and makes no outbound calls to any FoPost service. The separate `fopost/wordpress` plugin is the one that connects a site to the hosted product; that code does not belong here.

| Property | Value |
|----------|-------|
| **Type** | WordPress plugin |
| **Plugin slug** | `fopost-social` |
| **Text Domain** | `fopost-social` |
| **Admin menu slug** | `fopost-social` |
| **PHP namespace** | `Fopost\Social\Wp\` |
| **PHP Version** | 8.1+ (strict types required) |
| **Dependencies** | `fopost/social-core` (Composer) |
| **License** | GPL-2.0-or-later |

---

## CRITICAL: Text Domain Rules

The WordPress text domain **MUST** be `fopost-social`, matching the plugin folder name inside `wp-content/plugins/` and the WP.org slug. This is a WordPress requirement for Plugin Check compatibility.

The same string `'fopost-social'` is also the admin menu and settings-page slug.

```php
// CORRECT
__('Settings', 'fopost-social')
_e('Save', 'fopost-social')
esc_html__('Platform', 'fopost-social')
esc_html_e('Status', 'fopost-social')
_n('%s item', '%s items', $count, 'fopost-social')

menu_slug: 'fopost-social',
parent_slug: 'fopost-social',
do_settings_sections('fopost-social');
```

Anything else as a second argument to `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `_n()` is a bug. The plugin header in `fopost-social.php` MUST say `Text Domain: fopost-social`.

---

## Directory Structure

```
fopost-social.php          # Main plugin file (plugin header, bootstrap)
src/
├── Plugin.php             # Core plugin class (hooks, assets, localization)
├── Activator.php          # Plugin activation logic
├── Deactivator.php        # Plugin deactivation logic
├── Uninstaller.php        # Plugin uninstall logic
├── LegacyDataMigrator.php # One-time copy of pre-rebrand `owlstack` data
├── helpers.php            # Helper functions (fopost_social() singleton)
├── Admin/                 # Admin UI (settings pages, meta box, promo card)
│   └── views/             # PHP view templates
├── Auth/                  # WordPress token storage
├── Database/              # Custom DB tables (delivery logs)
├── Events/                # Core event dispatcher bridge
├── Http/                  # WP HTTP API client
├── Publishing/            # WordPress-specific publishing logic
└── Rest/                  # REST API endpoints
```

---

## Coding Standards

- All PHP files must have `declare(strict_types=1);`
- All translatable strings use the `'fopost-social'` text domain
- Option, transient, hook, and capability names use the `fopost_social_` prefix; slugs and CSS classes use `fopost-social-`
- Use `phpcs:ignore` or `phpcs:disable` comments ONLY when a violation is intentional and unavoidable
- Run PHPCS with the repo's own ruleset: `php -d xdebug.mode=off vendor/bin/phpcs`. Do NOT run the raw `--standard=WordPress` ruleset; the codebase intentionally does not follow its formatting rules

---

## Stored Data Is User Data

Never rename an option, transient, post meta, user meta, capability, or table name in place. If a key has to change, add a copy-forward step to `src/LegacyDataMigrator.php` that writes the new key and leaves the old one intact.

---

## Git Workflow

1. Create a feature branch from `main`
2. Make changes, then run `./vendor/bin/phpunit` and `php -d xdebug.mode=off vendor/bin/phpcs`. Both must pass before each commit
3. Commit with conventional commit messages (`fix:`, `feat:`, `docs:`, etc.), one logical change per commit
4. Push and open a PR against `main`

---

## Testing Checklist

Before submitting a PR:

- [ ] `./vendor/bin/phpunit` passes
- [ ] PHPCS passes on `src/`, `fopost-social.php`, and `uninstall.php`
- [ ] Text domain is `'fopost-social'` in ALL translation functions
- [ ] Plugin header `Text Domain:` matches `fopost-social`
- [ ] No new outbound call to any FoPost service
