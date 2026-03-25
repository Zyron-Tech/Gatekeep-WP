=== Zyron Login Redirect ===
Contributors:      zyrontech
Tags:              login, redirect, user-role, login-redirect, woocommerce
Requires at least: 5.8
Tested up to:      6.5
Requires PHP:      7.4
Stable tag:        2.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Configure where each WordPress user role is redirected after a successful login — regardless of which login form they used.

== Description ==

**Zyron Login Redirect** lets you control exactly where users land after they log in, based on their role.

= Key Features =
* Set a **per-role redirect URL** for any WordPress user role (Admin, Editor, Author, Subscriber, Shop Manager, or custom roles)
* **Fallback (default) redirect** for roles without a specific rule
* Works with **every login pathway**:
  * WordPress default login form (`wp-login.php`)
  * Programmatic logins via `wp_signon()`
  * WooCommerce My Account login form
  * BuddyPress / BuddyBoss login
  * Ultimate Member login forms
  * Profile Builder login forms
  * Custom theme login forms (via `login_redirect` filter)
  * REST API logins
* **Clean admin dashboard** under Settings → Login Redirect
* Add / remove rules dynamically — no page reload needed
* Duplicate role detection to keep rules conflict-free
* Zero configuration required out of the box

= Usage =
1. Go to **Settings → Login Redirect**
2. Set a **Fallback Redirect URL** (used when no rule matches)
3. Click **+ Add Rule**, select a user role, and enter its redirect URL
4. Click **Save Settings** — changes are instant

== Installation ==

1. Upload the `zyron-login-redirect` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Navigate to **Settings → Login Redirect** and configure your rules

== Frequently Asked Questions ==

= Does this work with WooCommerce? =
Yes. The plugin hooks into the `woocommerce_login_redirect` filter specifically, so the My Account login form is fully covered.

= What if a user has multiple roles? =
The plugin checks the user's roles in order. The first role that has a matching rule wins. If no role matches, the fallback URL is used.

= Will this break my existing login flow? =
No. If no rules are configured, the plugin falls back to WordPress's default behaviour.

= Does it work with custom login pages? =
Yes — any login that eventually calls `wp_signon()` or sets WordPress authentication cookies goes through the `login_redirect` filter, which this plugin hooks into at high priority.

== Screenshots ==
1. Settings page — role-based redirect rules table
2. Adding a new redirect rule

== Changelog ==

= 2.0.0 =
* Added Access Guard feature: redirect any user role away from specific URLs.
* Added wildcard URL pattern matching (/path/*)
* Added support for logged-out visitors and "Any" visitor type in Access Guard.
* Added row drag-to-reorder for Access Guard rules.
* Admin UI redesigned with tabbed layout.

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
* Added Access Guard feature: redirect any user role away from specific URLs.
* Added wildcard URL pattern matching (/path/*)
* Added support for logged-out visitors and "Any" visitor type in Access Guard.
* Added row drag-to-reorder for Access Guard rules.
* Admin UI redesigned with tabbed layout.

= 1.0.0 =
First public release.
