<?php
/**
 * Site-wide indexation health check — Bare Bones SEO
 *
 * Only flags states nobody sets deliberately on a live site, so the warning keeps
 * its weight. Individual noindexed pages are routine and are never flagged here.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns keyed problem descriptions, empty when the site is healthy.
 * Post types beyond page/post are excluded on purpose: noindexing a whole CPT
 * or taxonomy is a normal, deliberate choice.
 */
function bare_bones_seo_get_critical_issues() {
	$issues = array();

	if ( ! get_option( 'blog_public' ) ) {
		$issues['blog_public'] = 'WordPress is set to discourage search engines from indexing this site. Nothing on it can rank while this is on.';
	}

	$types = array(
		'page' => 'Pages',
		'post' => 'Posts',
	);

	foreach ( $types as $type => $label ) {
		if ( bare_bones_seo_state_is_noindex( bare_bones_seo_get_site_state( $type ) ) ) {
			$issues[ 'noindex_' . $type ] = 'Every entry under ' . $label . ' is set to noindex, so none of them can appear in search results.';
		}
	}

	return $issues;
}

// Re-enables indexing from the notice itself, so fixing is quicker than ignoring.
add_action( 'admin_init', function() {
	if ( ! isset( $_POST['bb_seo_allow_indexing'] ) ) {
		return;
	}
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'bb_seo_allow_indexing' ) ) {
		return;
	}

	update_option( 'blog_public', 1 );

	$back = wp_get_referer() ? wp_get_referer() : admin_url();
	wp_safe_redirect( add_query_arg( 'bbseo_indexing_restored', '1', $back ) );
	exit;
} );

/**
 * Rendered on in_admin_header so it sits above every other plugin's notices, and
 * styled deliberately unlike a core notice so it doesn't read as more chrome.
 * Styles are inline because the plugin stylesheet only loads on its own screens.
 */
add_action( 'in_admin_header', function() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_GET['bbseo_indexing_restored'] ) ) {
		echo '<div class="notice notice-success" style="margin:16px 20px 0 2px;"><p>Search engine indexing is back on for this site.</p></div>';
	}

	$issues = bare_bones_seo_get_critical_issues();
	if ( empty( $issues ) ) {
		return;
	}

	$indexation_url = admin_url( 'admin.php?page=bare-bones-seo&tab=indexation' );
	?>
	<div style="margin:16px 20px 8px 2px; background:#1d2327; border-left:6px solid #d63638; border-radius:4px; padding:20px 22px; display:flex; gap:18px; align-items:flex-start; box-shadow:0 1px 4px rgba(0,0,0,.25);">
		<div style="flex:0 0 auto; color:#f86368; line-height:0;">
			<?php echo bare_bones_seo_skull_icon( 46, '#f86368' ); ?>
		</div>
		<div style="flex:1 1 auto; min-width:0;">
			<h2 style="margin:0 0 8px; padding:0; color:#fff; font-size:18px; line-height:1.3; font-weight:600;">
				<?php echo count( $issues ) > 1 ? 'This site is hidden from search engines' : 'Search engine problem detected'; ?>
			</h2>
			<ul style="margin:0 0 14px; padding:0; list-style:none; color:#dcdcde; font-size:14px; line-height:1.6;">
				<?php foreach ( $issues as $message ) : ?>
					<li style="margin:0 0 4px;">&bull; <?php echo esc_html( $message ); ?></li>
				<?php endforeach; ?>
			</ul>
			<div style="display:flex; gap:10px; align-items:center; flex-wrap:wrap;">
				<?php if ( isset( $issues['blog_public'] ) ) : ?>
					<form method="post" action="" style="margin:0;">
						<?php wp_nonce_field( 'bb_seo_allow_indexing' ); ?>
						<button type="submit" name="bb_seo_allow_indexing" value="1" style="background:#d63638; color:#fff; border:none; border-radius:3px; padding:8px 16px; font-size:14px; font-weight:600; cursor:pointer;">
							Allow search engines
						</button>
					</form>
				<?php endif; ?>
				<?php if ( isset( $issues['noindex_page'] ) || isset( $issues['noindex_post'] ) ) : ?>
					<a href="<?php echo esc_url( $indexation_url ); ?>" style="color:#f0b849; font-size:14px; font-weight:600; text-decoration:underline;">Review indexation settings</a>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php
} );
