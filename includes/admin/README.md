# Admin code

`class-admin.php` is the entry point for `EMCP_Tools_Admin`. It owns hook
registration, shared constants, navigation, assets, notices, and tool counts.
It explicitly loads its traits, so including this file still works without an
autoloader or changes to the plugin bootstrap.

The traits are internal parts of that class, preserving its existing public
methods, static helpers, private helpers, and WordPress callback identities.
Views and the Pro overlay can continue calling `EMCP_Tools_Admin` as before.

| File | Responsibility |
| --- | --- |
| `trait-admin-catalog.php` | Curated tool labels, descriptions, badges, and integration categories |
| `trait-admin-tool-groups.php` | Tool slug families, including legacy slugs used by migrations |
| `trait-admin-integrations.php` | Platform grouping, availability checks, and requirement labels |
| `trait-admin-settings.php` | Settings registration, sanitization, and versioned defaults |
| `trait-admin-connection.php` | Client registry, app passwords, OAuth, diagnostics, and MCPB downloads |
| `trait-admin-cloud.php` | Cloud backups, imports, marketplace state, and settings sync |
| `trait-admin-sandbox.php` | Widget, block, and snippet handlers; portable bundle import/export |
| `trait-admin-history.php` | History URLs, rollback, deletion, and clearing |
| `trait-admin-redirects.php` | Redirect Manager forms and action URLs |
| `views/page-shell.php` | Shared page chrome and tab view selection |

Add behavior to the relevant concern file and register new hooks in `init()`.
Traits share the admin instance and class scope; they are not standalone
services. The page shell is included by `render_page()` after its capability
check and receives `$active_tab`, `$this`, and the existing class scope.

Public tests: `vendor/bin/phpunit -c tests/phpunit.xml`.
With the Pro checkout, admin tests: `vendor/bin/phpunit --testsuite Admin`.
