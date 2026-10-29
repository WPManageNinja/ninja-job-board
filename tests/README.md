# Ninja Job Board tests

## Integration tier (PHPUnit, real WordPress)

The tests run against a **dedicated** local WordPress with this repository symlinked in as the plugin. They never use mocks for the database: every bug that ships from plugins like this one is a runtime or SQL bug, and a mocked database hides it.

- Real MySQL in strict mode (`STRICT_TRANS_TABLES`, `ONLY_FULL_GROUP_BY`), as on MySQL 8 hosts
- Non-default table prefix (`njbt_`), so unprefixed SQL fails
- Non-UTC timezone (`Asia/Dhaka`), so site-time vs UTC mistakes show
- Every test in `tests/php/Integration` runs in a transaction that is rolled back; `tearDown()` fails the test if rows survive
- `tests/php/Destructive` (uninstall) drops tables, so it runs outside a transaction, after the other suite, and rebuilds the schema
- Outbound mail and HTTP are blocked; tests that need a third party (Cloudflare Turnstile) answer it at the HTTP layer

### One-time setup

```bash
W=~/Sites/njb-tests
wp core download --path=$W
cd $W
wp config create --dbname=ninja_job_board_tests --dbuser=root --dbpass='' --dbhost=127.0.0.1 --dbprefix=njbt_
wp db create
wp core install --url=http://njb-tests.test --title="Ninja Job Board Tests" --admin_user=admin \
  --admin_password="$(openssl rand -hex 16)" --admin_email=admin@example.com --skip-email
ln -s /path/to/ninja-job-board $W/wp-content/plugins/ninja-job-board
wp plugin activate ninja-job-board
wp option update timezone_string Asia/Dhaka
```

Then, in the repository: `composer install`.

Never point the suite at a site with real content. The bootstrap refuses to run without `NJB_INTEGRATION=1`, refuses a `wp_` table prefix, and refuses a plugin copy that is not this repository. Use `NJB_WP_PATH` for a test site in another folder.

### Run

```bash
composer test:integration     # or: npm run test:php
```

### What is covered (security regression suite)

| Test | Protects |
|---|---|
| `RestPermissionMatrixTest` | Every admin REST route: 401 anonymous, 403 for users without plugin permissions, "own forms" limited to own forms and their applications, no leak through prev/next, route list drift |
| `StoredXssTest` | The reported stored XSS (WordPress.org 2026) through file upload values, the upload record (form-bound, single use), old stored payloads, HTML in text fields on save and on display |
| `FileAccessTest` | CV download decisions (permission, ownership, file belongs to the application, path traversal, name pattern) and the upload folder guards |
| `SpamProtectionTest` | Honeypot, Turnstile without token / failed / unreachable (fails closed) / confirmed, secret never returned and kept when left empty |
| `PrivacyTest` | Personal data exporter and eraser (only that email; rows, activity and files), file deletion with the application, IP logging setting respected |
| `BlocksTest` | The three Gutenberg blocks render in PHP (category filter, filter toggle, published jobs only, chosen detail fields, escaped title), a job page never shows the form or details twice, job fields in the block editor are sanitized and need edit rights |
| `UninstallTest` | Uninstall keeps everything without the opt-in and removes tables, options, jobs and files with it |

### Proof of catch

Every test was watched failing against injected faults (break, run, restore). Mutations run and caught: 36.

Equivalent mutants (no behavior change, so no test can catch them; the code stays as defense in depth):
- Removing `esc_url()` on the protected download link: the link is built from a name that already passed the strict file-name pattern and `rawurlencode()`.
- Removing `sanitize_text_field()` on the Job list block title: the list template also runs it through `wp_kses_post()`.
- Making the job meta `auth_callback` always true: the REST post endpoint already refuses users who can not edit the post, and the default meta check for these keys is the same `edit_post` capability.

When you add a test: break the code it protects, watch it go red for the right reason, restore, watch it go green. A test that can not fail asserts nothing.

### Findings

A test that finds a real bug is parked as a skipped `KNOWN-FAILURE` and recorded in `tests/FIX-PLAN.md`, then fixed in a separate pass. Current findings: none.
