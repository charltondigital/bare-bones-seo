<?php
/**
 * Bare Bones SEO 404 Monitor
 * 
 * Path: admin/admin-404-monitor.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bb_seo_render_404_monitor_page() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bb_seo_404_logs';

	// 1. Handle actions (Delete individual / Clear all)
	if ( isset( $_GET['action'] ) ) {
		if ( 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$entry_id = absint( $_GET['id'] );
			if ( check_admin_referer( 'bb_delete_404_' . $entry_id ) ) {
				$wpdb->delete( $table_name, array( 'id' => $entry_id ), array( '%d' ) );
				echo '<div class="notice notice-success is-dismissible"><p>Log entry removed.</p></div>';
			}
		}

		if ( 'clear_all' === $_GET['action'] ) {
			if ( check_admin_referer( 'bb_clear_all_404' ) ) {
				$wpdb->query( "TRUNCATE TABLE $table_name" );
				echo '<div class="notice notice-success is-dismissible"><p>All 404 logs cleared successfully.</p></div>';
			}
		}
	}

	// 2. Fetch log data
	$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY hits DESC LIMIT 250" );
	?>
	<div class="wrap">
		<h1 style="display: inline-block; margin-right: 15px;">404 Monitor</h1>
		
		<?php if ( ! empty( $logs ) ) : ?>
			<a href="<?php echo esc_url( wp_nonce_url( '?page=bare-bones-seo-404&action=clear_all', 'bb_clear_all_404' ) ); ?>" class="button button-secondary" style="vertical-align: super;" onclick="return confirm('Are you sure you want to delete all logged 404 errors?');">Clear All Logs</a>
		<?php endif; ?>

		<p>Track broken URLs on your site so you can identify dead links, crawl errors, or bad assets.</p>

		<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px; margin-top: 15px;">
			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th style="width: 50%;">Broken URI Path</th>
						<th style="width: 15%; text-align: center;">Hits</th>
						<th style="width: 20%;">Last Accessed</th>
						<th style="width: 15%; text-align: right;">Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! empty( $logs ) ) : ?>
						<?php foreach ( $logs as $log ) : ?>
							<tr>
								<td style="word-wrap: break-word;"><strong><?php echo esc_html( $log->url ); ?></strong></td>
								<td style="text-align: center; font-weight: bold;"><?php echo esc_html( $log->hits ); ?></td>
								<td style="color: #646970;"><?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->last_accessed ) ); ?></td>
								<td style="text-align: right;">
									<a href="<?php echo esc_url( wp_nonce_url( '?page=bare-bones-seo-404&action=delete&id=' . $log->id, 'bb_delete_404_' . $log->id ) ); ?>" class="button button-small button-link-delete" onclick="return confirm('Delete this log entry?');">Remove</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php else : ?>
						<tr>
							<td colspan="4" style="text-align: center; padding: 20px; color: #646970;">No 404 errors recorded yet. You're in the clear!</td>
						</tr>
					<?php endif; ?>
				</tbody>
			</table>
		</div>
	</div>
	<?php
}
