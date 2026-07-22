<?php
/**
 * Bare Bones SEO Helper Functions
 * 
 * Path: includes/helpers.php
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detect plugins that override the WordPress core sitemap. Lives here rather
 * than in the admin screen because the front-end noindex filter needs it too.
 *
 * @return array { conflict: bool, plugin_name: string }
 */
function bare_bones_seo_detect_sitemap_conflict() {
    $plugins = array(
        array(
            'name'  => 'Yoast SEO',
            'check' => function() {
                return class_exists('WPSEO_Sitemaps') ||
                       (function_exists('wpseo_init') && get_option('wpseo_xml') !== false);
            },
        ),
        array(
            'name'  => 'Rank Math',
            'check' => function() {
                return class_exists('RankMath\Sitemap\Sitemap');
            },
        ),
        array(
            'name'  => 'All in One SEO',
            'check' => function() {
                return class_exists('AIOSEO\Plugin\Common\Sitemap\Sitemap');
            },
        ),
        array(
            'name'  => 'Google XML Sitemaps',
            'check' => function() {
                return function_exists('sm_init') || class_exists('GoogleSitemapGeneratorLoader');
            },
        ),
        array(
            'name'  => 'Simple Sitemap',
            'check' => function() {
                return class_exists('Simple_Sitemap');
            },
        ),
        array(
            'name'  => 'Slim SEO',
            'check' => function() {
                return class_exists('SlimSEO\Sitemap\Sitemap');
            },
        ),
    );

    foreach ($plugins as $plugin) {
        if (call_user_func($plugin['check'])) {
            return array('conflict' => true, 'plugin_name' => $plugin['name']);
        }
    }

    return array('conflict' => false, 'plugin_name' => '');
}

/**
 * Actual on-disk size of the plugin, cached for a day. Previously this screen
 * read an option nothing ever wrote, so it always showed a hardcoded number.
 *
 * @return string e.g. "96 KB"
 */
function bare_bones_seo_get_disk_size() {
	$cached = get_transient( 'bbseo_disk_size' );
	if ( false !== $cached ) {
		return $cached;
	}

	$bytes = 0;

	try {
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( BARE_BONES_SEO_PATH, FilesystemIterator::SKIP_DOTS )
		);
		foreach ( $iterator as $file ) {
			if ( $file->isFile() ) {
				$bytes += $file->getSize();
			}
		}
	} catch ( Exception $e ) {
		return 'n/a';
	}

	$size = ( $bytes >= 1048576 )
		? round( $bytes / 1048576, 1 ) . ' MB'
		: round( $bytes / 1024 ) . ' KB';

	set_transient( 'bbseo_disk_size', $size, DAY_IN_SECONDS );
	return $size;
}

/**
 * Get SEO metadata for a specific post with sensible defaults.
 *
 * @param int $post_id The ID of the post.
 * @return array Sanitized SEO metadata.
 */
function bare_bones_seo_get_page_meta( $post_id ) {
	$title  = get_post_meta( $post_id, BARE_BONES_SEO_META_TITLE, true );
	$desc   = get_post_meta( $post_id, BARE_BONES_SEO_META_DESC, true );
	$schema = get_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, true );
	$index  = get_post_meta( $post_id, BARE_BONES_SEO_META_INDEX, true );

	// If the indexing option is empty (never set), default it to 'index' or 'yes'
	if ( '' === $index ) {
		$index = 'yes';
	}

	return array(
		'title'  => sanitize_text_field( $title ),
		'desc'   => sanitize_text_field( $desc ),
		'schema' => $schema, // Raw text area storage for JSON schema
		'index'  => sanitize_key( $index ),
	);
}

/**
 * Save SEO metadata for a specific post.
 *
 * @param int   $post_id The ID of the post.
 * @param array $data    The array of data to save.
 */
function bare_bones_seo_update_page_meta( $post_id, $data ) {
	if ( isset( $data['title'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_TITLE, sanitize_text_field( $data['title'] ) );
	}
	if ( isset( $data['desc'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_DESC, sanitize_text_field( $data['desc'] ) );
	}
	if ( isset( $data['schema'] ) ) {
		// Store raw JSON. wp_kses_post is for HTML and would corrupt it; safety
		// is enforced on output, where it's validated and re-encoded with tag
		// escaping. update_post_meta unslashes for us.
		update_post_meta( $post_id, BARE_BONES_SEO_META_SCHEMA, trim( (string) $data['schema'] ) );
	}
	if ( isset( $data['should_index'] ) ) {
		update_post_meta( $post_id, BARE_BONES_SEO_META_INDEX, sanitize_key( $data['should_index'] ) );
	}
}

/**
 * True when schema text is present but won't parse, meaning the output layer
 * will silently skip it. Used to warn in the editor instead of failing quietly.
 */
function bare_bones_seo_schema_is_invalid( $schema ) {
	$schema = trim( (string) $schema );
	if ( '' === $schema ) {
		return false;
	}
	json_decode( $schema );
	return JSON_ERROR_NONE !== json_last_error();
}

/**
 * Creates the 404 log table. Safe to call repeatedly — dbDelta only applies
 * differences. Uses $wpdb->prefix, so each site on a network gets its own.
 */
function bare_bones_seo_install() {
	global $wpdb;

	$table_name      = $wpdb->prefix . 'bbseo_404_logs';
	$charset_collate = $wpdb->get_charset_collate();

	// dbDelta is whitespace-sensitive: two spaces after PRIMARY KEY, one field per line.
	// url is indexed at 191 chars to stay under the InnoDB key limit on utf8mb4.
	$sql = "CREATE TABLE $table_name (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		url varchar(255) NOT NULL DEFAULT '',
		referer varchar(255) NOT NULL DEFAULT '',
		hits bigint(20) unsigned NOT NULL DEFAULT 1,
		last_accessed datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY url (url(191))
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	// Pages and Posts are no longer switchable in the Indexation screen. Any value
	// stored before that change would still be honoured by the resolver with no UI
	// left to undo it, so clear it out.
	$map = get_option( BARE_BONES_SEO_OPTION_GLOBAL_MAP, array() );
	if ( is_array( $map ) && ( isset( $map['page'] ) || isset( $map['post'] ) ) ) {
		unset( $map['page'], $map['post'] );
		update_option( BARE_BONES_SEO_OPTION_GLOBAL_MAP, $map );
	}

	update_option( BARE_BONES_SEO_DB_VERSION_OPTION, BARE_BONES_SEO_DB_VERSION );
}

/**
 * Log 404s. Stores the request path only (no host — it's always this site,
 * and it's rebuilt with home_url() on display); skips common bot/scanner probes.
 */
function bbseo_log_404_error() {
	if ( ! is_404() ) {
		return;
	}

	global $wpdb;
	$table_name = $wpdb->prefix . 'bbseo_404_logs';

	$path    = esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) );
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';

	// Skip bot/scanner probe noise so the table stays lean.
	$bot_patterns = array(
		'.env', 'wp-config', 'xmlrpc.php', 'wp-admin', '.git', 'eval-stdin.php',
		'phpunit', 'autodiscover.xml', '.well-known', 'wp-login.php', '.php',
	);
	foreach ( $bot_patterns as $pattern ) {
		if ( false !== stripos( $path, $pattern ) ) {
			return;
		}
	}

	$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, hits FROM `$table_name` WHERE url = %s", $path ) );

	if ( $existing ) {
		$wpdb->update(
			$table_name,
			array(
				'hits'          => $existing->hits + 1,
				'last_accessed' => current_time( 'mysql' ),
				'referer'       => $referer,
			),
			array( 'id' => $existing->id ),
			array( '%d', '%s', '%s' ),
			array( '%d' )
		);
	} else {
		$wpdb->insert(
			$table_name,
			array(
				'url'           => $path,
				'hits'          => 1,
				'last_accessed' => current_time( 'mysql' ),
				'referer'       => $referer,
			),
			array( '%s', '%d', '%s', '%s' )
		);
	}
}
add_action( 'template_redirect', 'bbseo_log_404_error' );
