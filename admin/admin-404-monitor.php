<?php
/**
 * Bare Bones SEO 404 Monitor View
 * 
 * Path: admin/admin-404-monitor.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bare_bones_seo_render_404_monitor_screen() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bbseo_404_logs';

	// 1. Handle individual log delete or clear all actions
	if ( isset( $_GET['action'] ) ) {
		if ( 'delete' === $_GET['action'] && isset( $_GET['id'] ) ) {
			$entry_id = absint( $_GET['id'] );
			if ( check_admin_referer( 'bb_delete_404_' . $entry_id ) ) {
				$wpdb->delete( $table_name, array( 'id' => $entry_id ), array( '%d' ) );
				echo '<div class="notice notice-success is-dismissible"><p>404 log entry deleted.</p></div>';
			}
		}

		if ( 'clear_all' === $_GET['action'] ) {
			if ( check_admin_referer( 'bb_clear_all_404' ) ) {
				$wpdb->query( "TRUNCATE TABLE $table_name" );
				echo '<div class="notice notice-success is-dismissible"><p>All 404 logs successfully cleared.</p></div>';
			}
		}
	}

	// 2. Query logs
	$logs = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY hits DESC LIMIT 150" );
	?>
	<div style="background: #fff; padding: 20px; border: 1px solid #ccd0d4; border-radius: 4px;">
		<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
			<h2 style="margin: 0;">Recorded 404 Errors</h2>
			<?php if ( ! empty( $logs ) ) : ?>
				<a href="<?php echo esc_url( wp_nonce_url( '?page=bare-bones-seo&tab=404-monitor&action=clear_all', 'bb_clear_all_404' ) ); ?>" 
				   class="button button-secondary" 
				   onclick="return confirm('Are you sure you want to delete all logged 404 errors?');">
					Clear All Logs
				</a>
			<?php endif; ?>
		</div>

		<p style="color: #646970; margin-bottom: 20px;">Track broken URLs on your site. This list ignores common background bot scan requests to keep your database lean.</p>

		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th style="width: 45%;">Requested URL</th>
					<th style="width: 25%;">Referer</th>
					<th style="width: 10%; text-align: center;">Hits</th>
					<th style="width: 12%;">Last Accessed</th>
					<th style="width: 8%; text-align: right;">Action</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $logs ) ) : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td style="word-wrap: break-word;">
								<a href="<?php echo esc_url( $log->url ); ?>" target="_blank" style="text-decoration: none; font-weight: 600;">
									<?php echo esc_html( $log->url ); ?>
								</a>
							</td>
							<td style="color: #646970; word-wrap: break-word; font-size: 12px;">
								<?php echo esc_html( $log->referer ); ?>
							</td>
							<td style="text-align: center; font-weight: bold;"><?php echo esc_html( $log->hits ); ?></td>
							<td style="font-size: 12px; color: #646970;">
								<?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->last_accessed ) ); ?>
							</td>
							<td style="text-align: right;">
								<a href="<?php echo esc_url( wp_nonce_url( '?page=bare-bones-seo&tab=404-monitor&action=delete&id=' . $log->id, 'bb_delete_404_' . $log->id ) ); ?>" 
								   class="button button-small button-link-delete" 
								   style="color: #d63638;"
								   onclick="return confirm('Delete this log entry?');">
									Delete
								</a>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="5" style="text-align: center; padding: 30px; color: #646970;">
							🎉 No 404 errors recorded yet. Your visitors are finding everything they need!
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
