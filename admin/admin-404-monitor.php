<?php
/**
 * 404 Monitor screen.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bare_bones_seo_render_404_monitor_screen() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'bbseo_404_logs';

	// 1. Handle actions
	if ( isset( $_GET['action'] ) && 'clear_all' === $_GET['action']
		&& current_user_can( 'manage_options' ) && check_admin_referer( 'bb_clear_all_404' ) ) {
		$wpdb->query( "TRUNCATE TABLE `$table_name`" );
		echo '<div class="notice notice-success is-dismissible"><p>All 404 logs successfully cleared.</p></div>';
	}

	// 2. Query logs
	$logs = $wpdb->get_results( "SELECT * FROM `$table_name` ORDER BY hits DESC LIMIT 150" );
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
					<th style="width: 50%;">Requested URL</th>
					<th style="width: 25%;">Referer</th>
					<th style="width: 10%; text-align: center;">Hits</th>
					<th style="width: 15%;">Last Accessed</th>
				</tr>
			</thead>
			<tbody>
				<?php if ( ! empty( $logs ) ) : ?>
					<?php foreach ( $logs as $log ) : ?>
						<tr>
							<td style="word-wrap: break-word;">
								<a href="<?php echo esc_url( home_url( $log->url ) ); ?>" target="_blank" style="text-decoration: none; font-weight: 600;">
									<?php echo esc_html( $log->url ); ?>
								</a>
							</td>
							<td style="color: #646970; word-wrap: break-word; font-size: 12px;">
								<?php echo esc_html( ! empty( $log->referer ) ? $log->referer : 'Direct' ); ?>
							</td>
							<td style="text-align: center; font-weight: bold;"><?php echo esc_html( $log->hits ); ?></td>
							<td style="font-size: 12px; color: #646970;">
								<?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $log->last_accessed ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php else : ?>
					<tr>
						<td colspan="4" style="text-align: center; padding: 30px; color: #646970;">
							No 404 errors recorded yet. Your visitors are finding everything they need!
						</td>
					</tr>
				<?php endif; ?>
			</tbody>
		</table>
	</div>
	<?php
}
