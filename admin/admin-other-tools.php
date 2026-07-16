<?php
/**
 * Bare Bones SEO - Other Tools Recommendations Screen
 * 
 * Path: admin/admin-other-tools.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bare_bones_seo_render_other_tools_screen() {
	?>
	<style>
		.bbseo-tools-grid {
			display: grid;
			grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
			gap: 20px;
			margin-top: 15px;
		}
		.bbseo-tool-card {
			background: #fff;
			border: 1px solid #ccd0d4;
			border-radius: 4px;
			padding: 24px;
			display: flex;
			flex-direction: column;
			justify-content: space-between;
			box-shadow: 0 1px 3px rgba(0,0,0,0.04);
			box-sizing: border-box;
		}
		.bbseo-tool-card h2 {
			margin-top: 0;
			margin-bottom: 12px;
			font-size: 16px;
			font-weight: 600;
			color: #1d2327;
			display: flex;
			align-items: center;
			gap: 10px;
		}
		.bbseo-tool-card h2 .dashicons {
			font-size: 20px;
			width: 20px;
			height: 20px;
		}
		.bbseo-tool-card p {
			color: #50575e;
			font-size: 13px;
			line-height: 1.6;
			margin-top: 0;
			margin-bottom: 24px;
			flex-grow: 1;
		}
		.bbseo-card-footer {
			border-top: 1px solid #f0f0f1;
			padding-top: 16px;
			display: flex;
			justify-content: space-between;
			align-items: center;
		}
		.bbseo-tag {
			background: #f0f0f1;
			color: #50575e;
			padding: 3px 8px;
			border-radius: 3px;
			font-size: 11px;
			font-weight: 600;
			text-transform: uppercase;
			letter-spacing: 0.5px;
		}
		.bbseo-coming-soon {
			background: #d9ecff;
			color: #0056b3;
		}
	</style>

	<div style="max-width: 1200px; margin-top: 15px;">
		<p style="font-size: 14px; color: #50575e; margin-bottom: 20px; margin-top: 0;">
			Bare Bones SEO is built to be fast, lightweight, and focused purely on indexation control. For advanced tasks, we recommend pairing our plugin with these best-in-class, specialized external utilities.
		</p>

		<div class="bbseo-tools-grid">
			
			<!-- On-Page Keyword Optimization -->
			<div class="bbseo-tool-card">
				<div>
					<h2>
						<span class="dashicons dashicons-editor-bold" style="color: #2271b1;"></span>
						On-Page Keyword Optimization
					</h2>
					<p>
						Writing content is only half the battle. Tools like <strong>Surfer SEO</strong> or <strong>PageOptimizer Pro (POP)</strong> analyze top-ranking pages for your target keywords. They tell you exactly how many times to use primary terms, variations, and headings to match search engine expectations.
					</p>
				</div>
				<div class="bbseo-card-footer">
					<span class="bbseo-tag">Content</span>
					<a href="https://surferseo.com/" target="_blank" rel="noopener" class="button button-secondary">Explore Tools</a>
				</div>
			</div>

			<!-- Redirection -->
			<div class="bbseo-tool-card">
				<div>
					<h2>
						<span class="dashicons dashicons-randomize" style="color: #46b450;"></span>
						301 Redirection
					</h2>
					<p>
						Managing redirections is critical for SEO preservation. For absolute speed, always write redirections at the server level (such as Cloudflare, Nginx, or .htaccess). If you must manage them directly inside your WordPress dashboard, we highly recommend using the <strong>Redirection</strong> plugin by John Godley.
					</p>
				</div>
				<div class="bbseo-card-footer">
					<span class="bbseo-tag">Infrastructure</span>
					<a href="https://wordpress.org/plugins/redirection/" target="_blank" rel="noopener" class="button button-secondary">Get Plugin</a>
				</div>
			</div>

			<!-- Broken Link Checker -->
			<div class="bbseo-tool-card">
				<div>
					<h2>
						<span class="dashicons dashicons-warning" style="color: #dba617;"></span>
						Broken Link Detection
					</h2>
					<p>
						Broken internal and external links frustrate users and block search spiders from properly crawling your site. We are currently developing our own lightning-fast, server-friendly broken link detection utility that won't bog down your database.
					</p>
				</div>
				<div class="bbseo-card-footer">
					<span class="bbseo-tag bbseo-coming-soon">Coming Soon</span>
					<span style="font-size: 12px; color: #646970; font-style: italic;">In Development</span>
				</div>
			</div>

		</div>
	</div>
	<?php
}
