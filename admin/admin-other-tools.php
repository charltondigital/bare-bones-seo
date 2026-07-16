<?php
/**
 * Renders the "Other Tools" tab for Bare Bones SEO.
 *
 * @package BareBonesSEO
 * @subpackage Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the Other Tools screen.
 *
 * @since 1.0.11
 */
function bare_bones_seo_render_other_tools_screen() {
    ?>
    <div class="wrap bare-bones-seo-wrap" style="padding: 0; margin-top: 10px;">
        
        <p style="color: #2c3338; font-size: 13px; line-height: 1.5; margin-bottom: 20px; max-width: 1200px;">
            Bare Bones SEO is built to be fast, lightweight, and focused purely on indexation control. For advanced tasks, we recommend pairing our plugin with these best-in-class, specialized external utilities.
        </p>

        <!-- Other Tools Grid Container -->
        <div class="bb-other-tools-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 20px; max-width: 1200px;">
            
            <!-- On-Page Keyword Optimization Card -->
            <div class="card" style="margin: 0; padding: 24px; border: 1px solid #c3c4c7; box-shadow: none; background: #fff; border-radius: 4px; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
                <div>
                    <h2 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-editor-bold" style="color: #2271b1; font-size: 20px; width: 20px; height: 20px;"></span>
                        On-Page Keyword Optimization
                    </h2>
                    <p style="color: #2c3338; font-size: 13px; line-height: 1.6; margin-bottom: 20px;">
                        Writing content is only half the battle. Tools like <strong>Surfer SEO</strong> or <strong>PageOptimizer Pro (POP)</strong> analyze top-ranking pages for your target keywords. They tell you exactly how many times to use primary terms, variations, and headings to match search engine expectations.
                    </p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f1; padding-top: 15px; margin-top: auto;">
                    <span style="font-size: 10px; font-weight: bold; letter-spacing: 0.5px; color: #646970; background: #f0f0f1; padding: 3px 8px; border-radius: 3px;">CONTENT</span>
                    <a href="https://surferseo.com" target="_blank" class="button button-secondary" style="border-color: #2271b1; color: #2271b1;">Explore Tools</a>
                </div>
            </div>

            <!-- Broken Link Detection Card -->
            <div class="card" style="margin: 0; padding: 24px; border: 1px solid #c3c4c7; box-shadow: none; background: #fff; border-radius: 4px; display: flex; flex-direction: column; justify-content: space-between; min-height: 280px;">
                <div>
                    <h2 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 600; display: flex; align-items: center; gap: 10px;">
                        <span class="dashicons dashicons-warning" style="color: #dba617; font-size: 20px; width: 20px; height: 20px;"></span>
                        Broken Link Detection
                    </h2>
                    <p style="color: #2c3338; font-size: 13px; line-height: 1.6; margin-bottom: 20px;">
                        Broken internal and external links frustrate users and block search spiders from properly crawling your site. We are currently developing our own lightning-fast, server-friendly broken link detection utility that won't bog down your database.
                    </p>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f0f0f1; padding-top: 15px; margin-top: auto;">
                    <span style="font-size: 10px; font-weight: bold; letter-spacing: 0.5px; color: #2271b1; background: #f0f6fc; padding: 3px 8px; border-radius: 3px;">COMING SOON</span>
                    <span style="color: #646970; font-style: italic; font-size: 12px;">In Development</span>
                </div>
            </div>

        </div>
    </div>
    <?php
}
