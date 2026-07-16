<?php
/**
 * Admin Screen: Other Tools
 *
 * @package BareBonesSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Render the Other Tools informational screen.
 */
function bare_bones_seo_render_other_tools_screen() {
	?>
	<div class="card" style="max-width: 800px; margin-top: 20px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #ccd0d4; background: #fff;">
		<h2 style="margin-top: 0;">🛠️ <?php _e( 'More SEO Utilities', 'bare-bones-seo' ); ?></h2>
		<p class="description" style="font-size: 14px; line-height: 1.5; margin-bottom: 20px;">
			<?php _e( 'Bare Bones SEO is built to stay fast and unbloated. If you need advanced features beyond clean indexing controls and 404 monitoring, here are some lightweight, recommended tools and resources:', 'bare-bones-seo' ); ?>
		</p>

		<hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

		<h3 style="margin-top: 0;"><?php _e( '1. Schema Markup & Structured Data', 'bare-bones-seo' ); ?></h3>
		<p><?php _e( 'While you can paste custom schema JSON directly into Bare Bones SEO\'s page editor, you can test and validate your structured data using Google\'s official testing tool.', 'bare-bones-seo' ); ?></p>
		<p><a href="https://search.google.com/test/rich-results" target="_blank" class="button button-secondary"><?php _e( 'Test Rich Results', 'bare-bones-seo' ); ?></a></p>

		<hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">

		<h3><?php _e( '2. Robots.txt and Sitemap Validation', 'bare-bones-seo' ); ?></h3>
		<p><?php _e( 'Make sure search engines can discover your sitemap correctly. Submit your sitemap directly in Google Search Console to track indexing status and find crawl errors.', 'bare-bones-seo' ); ?></p>
		<p><a href="https://search.google.com/search-console" target="_blank" class="button button-secondary"><?php _e( 'Open Google Search Console', 'bare-bones-seo' ); ?></a></p>
	</div>
	<?php
}
