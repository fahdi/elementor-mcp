# Security audit — EMCP Tools (Elementor MCP Server) 3.18.1

Date: 2026-10-05. Scope: the MCP tool surface (`includes/abilities/*`), the
WP-CLI, filesystem and database tool backends, the OAuth 2.1 server, the
public REST routes, admin AJAX, outbound HTTP (SSRF), request logging, and
the PHP 8.x behaviour of the public test suite. Issues are disabled on this
fork, so findings are recorded here and in the linked PRs.

Overall: the codebase is unusually security-conscious for a WordPress plugin.
Every ability has a `permission_callback`; destructive classes require
`manage_options`, code-writing tools additionally require `unfiltered_html`
and/or `edit_files`; a tokenizer-based SQL policy, a DNS-resolving URL guard,
HMAC-signed public routes and hashed OAuth tokens are all present. The real
findings are gaps in denylists and in "protected by `.htaccess`" assumptions.

## Findings

| # | Sev | Title | Status |
|---|-----|-------|--------|
| 1 | High | WP-CLI validator still allows arbitrary-PHP paths (`config create --extra-php`, install-from-URL/zip, `@alias` retargeting) | Fixed — PR #1 |
| 2 | Medium | FS backups and WP-CLI job logs sit under `uploads/` at predictable paths, protected only by an Apache-only `.htaccess` | Fixed — PR #2 |
| 3 | Medium | Raw DB write tools refuse only `wp_users`/`wp_usermeta`; `wp_options` and network tables writable (privilege escalation) | Fixed — PR #3 |
| 4 | Low | WP-CLI tool is denylist-based; `scaffold plugin` + `plugin activate`, `core update --version` etc. remain. No allowlist mode for production | Open |
| 5 | Low | `write-file`/`edit-file` may create `.php` anywhere under `ABSPATH` (mu-plugins, uploads) when `edit_files` is granted; `DISALLOW_FILE_MODS` is not honoured | Open |
| 6 | Low | PHP snippet validator is a name-based function denylist; variable functions / callbacks are only stopped by the human approval gate | Open (document) |
| 7 | Info | Anonymous OAuth dynamic client registration is capped but not rate-limited per IP; token endpoint relies on 256-bit random tokens rather than throttling | Open (accepted) |
| 8 | Info | OAuth metadata/registration endpoints send `Access-Control-Allow-Origin: *` — correct per RFC 8414/9728; noted so it is not "fixed" later | Accepted |

### 1. WP-CLI validator gaps (High) — fixed in PR #1

`includes/wpcli/class-wpcli-validator.php`. Blocked `eval`, `--exec`,
`--require`, `config set/edit/delete`, but not:

- `config create --force --extra-php` — rewrites `wp-config.php` with
  caller-supplied PHP; defeats the FS tools' `wp-config.php` protection and
  `DISALLOW_FILE_EDIT`.
- `plugin install <url|.zip>`, `theme install <url|.zip>`, `core update <zip>`,
  `core download <zip>` — code from an arbitrary source.
- `cli alias add @x --set-ssh=…` then `wp @x …` — bypasses the `--ssh`/`--http`
  flag block.

Impact: a prompt-injected agent or a stolen admin credential gets RCE.
Fix: block `config create`/`shuffle-salts`/`cli alias`, refuse URL/archive/
path positionals on install subcommands, refuse `@alias` command words.

### 2. Web-exposed backup and job directories (Medium) — fixed in PR #2

`includes/class-filesystem-guard.php::backup()` and
`includes/wpcli/class-wpcli-jobs.php::dir()`. Backups of edited files (plugin
configs with API keys) and job stdout (`wp option get`, `wp user list`) went to
`uploads/emcp-fs-backups/<timestamp>-<path>` and `uploads/emcp-wpcli-jobs/<id>/`
with a `.htaccess` that only Apache reads (the backup one 2.4-syntax only).
On Nginx/LiteSpeed/Caddy/IIS the files were downloadable unauthenticated.
Fix: per-site random 32-hex directory token (option
`emcp_tools_private_dir_token`), plus `.htaccess` (2.2 + 2.4), `web.config`,
`index.php`, `index.html`.

### 3. Raw DB writes to `wp_options` (Medium) — fixed in PR #3

`includes/abilities/class-database-abilities.php`. `update-rows` on
`wp_options` (`default_role=administrator` + `users_can_register=1`,
`active_plugins`, `siteurl`) or on `wp_sitemeta`/`wp_signups` is a takeover
path around the dedicated option/user tools. Fix: new write-protected list
(users, usermeta, options, sitemeta, signups, blogs, site; filter
`emcp_tools_db_write_protected_tables`); reads unchanged.

### 4. Denylist model for WP-CLI (Low) — open

Recommend an opt-in allowlist (`emcp_tools_wpcli_allowed_commands`) for
production sites and documenting that `run-wp-cli` is admin-equivalent.

### 5. Filesystem write scope (Low) — open

`write-file`/`edit-file` honour `DISALLOW_FILE_EDIT` and `edit_files` but
not `DISALLOW_FILE_MODS`, and can create `.php` under `wp-content/uploads`
or `wp-content/mu-plugins`. Recommend honouring `DISALLOW_FILE_MODS` and
refusing new `.php`/`.phtml`/`.phar` in uploads unless a filter opts in.

### 6. PHP snippet validator (Low) — open

`includes/class-php-snippet-validator.php` denies by function name
(`shell_exec`, `proc_open`, …). `('sys'.'tem')()`, `call_user_func`,
`include` of an uploaded file are not caught by name matching; the
manage_options + unfiltered_html + human approval gate is the effective
control and should be documented as such.

## Reviewed and found sound

- SQL policy (`includes/sql/class-sql-policy.php`): tokenizer with
  backslash/ANSI-quote variants, blocks DML/DDL, `OUTFILE/DUMPFILE/INFILE`,
  `LOAD_FILE`, system schemas; `LIMIT` bounded in SQL; statement timeout.
- URL guard: RFC1918, loopback, link-local/cloud-metadata, CGNAT, multicast,
  IPv4-mapped IPv6, DNS resolution, per-hop redirect re-check; used by
  content, SVG icon and stock-image sideloads. Frontend page fetcher is
  same-origin only; `wp_safe_remote_*` used for connection tests.
- OAuth: S256 PKCE enforced, exact `redirect_uri` match, SHA-256-hashed
  tokens, resource binding, refresh rotation with grace, revocation,
  anonymous-registration capacity cap.
- Public loop REST route (`permission_callback => __return_true`): HMAC-signed
  config, cookie-without-nonce demotion guard, page/limit ceilings.
- Admin AJAX dismiss handlers: `check_ajax_referer` + `manage_options`.
- Request log stores tool name/status/timing only — no inputs or tokens.
- User tools: refuse admin-grade roles on create/update, never return passwords.
- Custom code / snippet / themer PHP tools: `unfiltered_html` gated.
- PHP 8.5: public suite (`vendor/bin/phpunit -c tests/phpunit.xml`) runs clean
  with no deprecation notices.

## How to run the tests

```
composer install
vendor/bin/phpunit -c tests/phpunit.xml
```
