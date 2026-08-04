=== Bare Bones SEO ===
Contributors: charltondigital
Tags: seo, metadata, sitemap, noindex, redirects
Requires at least: 6.0
Tested up to: 6.7
Stable tag: 0.1.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

A minimal SEO plugin for developers and agencies who want direct technical control without the overhead.

== Description ==

Bare Bones SEO is built on the premise that 95% of ranking-moving SEO work happens outside plugin settings. So the plugin stays out of your way.

No telemetry. No upsells. No dashboard widgets. Just clean control over the things a plugin should actually handle: indexing, sitemaps, titles, descriptions, schema, and redirects.

Built for developers and agencies managing sites where precision matters more than wizard-style automation.

= Key Features =

* **Global Indexation Controls:** Set indexing and sitemap behavior by post type across your entire site from a single screen.
* **Page-Level Overrides:** Title, description, JSON-LD schema, and indexing controls on every post and page. Page-level settings can only be more restrictive than site-level — never more permissive.
* **Bulk Page Meta Editor:** Audit and edit titles, descriptions, and indexing across your entire site in a paginated AJAX grid without opening individual editors.
* **301 Redirect Manager:** Handle moved pages and external redirects. Runs only on 404s to avoid any frontend overhead.
* **404 Monitor:** A read-only log of 404 errors by path, paginated and capability-checked.
* **Tracking Snippet Manager:** Store GA4 or GTM snippets with lazy-loaded panels.
* **Health Notices:** Detects common configuration problems (site set to discourage search engines, entire post types noindexed) with one-click fixes.

== Installation ==

1. Upload the `bare-bones-seo` folder to the `/wp-content/plugins/` directory. The folder name must be `bare-bones-seo` exactly.
2. Activate the plugin through the Plugins screen in WordPress.
3. Navigate to **Bare Bones SEO** in the admin menu to configure global indexation settings.

**Note:** If you are running Wordfence or another WAF, you may need to allowlist the plugin's settings pages. POST bodies containing `<script>` tags or `gtag(` strings (used in the tracking snippet and schema fields) can trigger false-positive 403 blocks. See the FAQ for details.

== Frequently Asked Questions ==

= Will this conflict with Yoast SEO or RankMath? =

Running two SEO plugins simultaneously will cause conflicts — both plugins will attempt to output title tags and schema markup. Bare Bones SEO is intended to be your only active SEO plugin.

= Does this work with custom post types? =

Yes. Global indexation controls apply to any registered post type. Page-level meta boxes appear on all public post types.

= Wordfence is blocking my settings from saving. =

Wordfence's WAF can block POST requests containing `<script>` tags or `gtag(` strings, which appear in the tracking snippet and JSON-LD schema fields. To fix this, add the plugin's settings pages to your Wordfence allowlist under **Wordfence > Firewall > Allowlisted URLs**.

= What does "bare bones" mean for long-term development? =

It's a deliberate product philosophy, not a development phase. Features that belong outside a plugin (content analysis, keyword tracking, rank monitoring) won't be added. The plugin will stay focused on what a plugin should control.

== Screenshots ==

1. Global indexation controls organized by post type.
2. Bulk page meta editor grid with inline AJAX saving.
3. Page-level meta box with title, description, schema, and indexing controls.
4. 404 monitor log.
5. Health notice with one-click fix.

== Changelog ==

= 0.1.3 =
* Bulk page meta editor: paginated at 50 per page with delegated row expand/collapse.
* Schema field: invalid JSON warning fires after AJAX save and holds the row open.
* Health notice system: detects discouraged indexing and fully noindexed post types.

= 0.1.0 =
* Initial beta release.
* Global indexation controls with three-state ladder model (Index / Remove from Sitemap / Noindex).
* Page-level title, description, and JSON-LD schema fields.
* 301 redirect manager.
* 404 monitor.
* GA4/GTM tracking snippet storage.

== Upgrade Notice ==

= 0.1.3 =
Beta release. Adds bulk editor pagination, schema JSON validation, and health notices.
