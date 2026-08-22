# CLAUDE.md

> **Read and follow ALL rules in `AGENTS.md` first.** This file contains Claude-specific instructions only.
> Do NOT treat this file as standalone, `AGENTS.md` is the source of truth for all rules.

---

## Context Loading Priority

When starting a new session, read files in this order:

1. `AGENTS.md` — mandatory rules (text domain, architecture, code style, git workflow)
2. `fopost-social.php` — plugin header (verify Text Domain is `fopost-social`)
3. The specific files related to the current task

---

## Critical Reminder

**Text domain = `'fopost-social'`**, matching the plugin folder name inside `wp-content/plugins/` and the WP.org slug. The same string is used for admin menu and settings-page slugs.

Any other second argument to `__()`, `_e()`, `esc_html__()`, `esc_html_e()`, `_n()` is a bug. Fix it to `'fopost-social'`.

**This plugin never calls a FoPost service.** It publishes to the social platforms with the site owner's own credentials. Anything that talks to `api.fopost.com` belongs in the separate `fopost/wordpress` plugin.

---

## Tool Usage

- **Prefer grep and find** to locate code, never guess file locations or class names
- **Run PHPCS after every change**: `php -d xdebug.mode=off vendor/bin/phpcs` (uses `phpcs.xml.dist`: WordPress security/I18n/DB/PHP sniffs on the repo's PSR-style code; do NOT run the raw `--standard=WordPress` ruleset)
- **Run I18n check specifically**: `php -d xdebug.mode=off vendor/bin/phpcs --sniffs=WordPress.WP.I18n`
- **Run the tests**: `./vendor/bin/phpunit`
- **Use `git diff`** to verify changes before committing
