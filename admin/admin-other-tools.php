<?php
/**
 * "Other Tools" tab — Bare Bones SEO
 *
 * Explains what this plugin deliberately does not do, and points at off-server
 * tools for the jobs that would otherwise cost the host real processing time.
 */

if (!defined('ABSPATH')) exit;

function bare_bones_seo_render_other_tools_screen() {
    ?>
    <div style="background:#fff; border:1px solid #c3c4c7; padding:24px 28px; border-radius:4px; margin-top:20px; max-width:900px;">

        <h2 style="margin:0 0 12px; font-size:18px; font-weight:600; color:#1d2327;">
            The Bare Bones Philosophy on "Extra" Tools
        </h2>

        <p style="margin:0 0 12px; font-size:14px; line-height:1.6; color:#3c434a;">
            We're not here to stuff your WordPress database with heavy algorithms, background crawlers, or $100/mo upsell widgets that slow down your site.
        </p>

        <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#3c434a;">
            For specialized tasks that require heavy processing, we recommend using off-server utilities that keep your host fast and clean:
        </p>

        <div style="border-top:1px solid #f0f0f1; padding-top:20px; margin-bottom:24px;">
            <h3 style="margin:0 0 8px; font-size:15px; font-weight:600; color:#1d2327;">
                On-Page Content Optimization
            </h3>
            <p style="margin:0 0 10px; font-size:14px; line-height:1.6; color:#3c434a;">
                Rather than bloating your editor with arbitrary "green light" algorithms, focus on core on-page targets (Title, H1, Meta, and URL).
            </p>
            <p style="margin:0; font-size:14px; line-height:1.6; color:#3c434a;">
                <strong>For heavy semantic &amp; entity research:</strong> We recommend using external cloud tools like
                <strong>Surfer SEO</strong> or <strong>PageOptimizer Pro</strong>. They handle the heavy lifting on their servers &mdash; not yours.
            </p>
        </div>

        <div style="border-top:1px solid #f0f0f1; padding-top:20px;">
            <h3 style="margin:0 0 8px; font-size:15px; font-weight:600; color:#1d2327;">
                Broken Link Audits
            </h3>
            <p style="margin:0 0 10px; font-size:14px; line-height:1.6; color:#3c434a;">
                Background link checkers are the #1 cause of server strain in traditional SEO plugins.
            </p>
            <p style="margin:0 0 10px; font-size:14px; line-height:1.6; color:#3c434a;">
                <strong>Passive Detection:</strong> Our built-in
                <a href="<?php echo esc_url(admin_url('admin.php?page=bare-bones-seo&tab=404-monitor')); ?>">404 Monitor</a>
                automatically captures broken links as real visitors hit them.
            </p>
            <p style="margin:0; font-size:14px; line-height:1.6; color:#3c434a;">
                <strong>Full Site Audits:</strong> For deep scans, run <strong>Screaming Frog SEO Spider</strong> (Free Version) locally from your desktop. It scans your site in seconds without using a single byte of server memory.
            </p>
        </div>

    </div>
    <?php
}
