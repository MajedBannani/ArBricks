<?php
/**
 * Uninstall ArBricks Plugin
 *
 * Handles plugin uninstallation based on user-configured settings.
 *
 * @package ArBricks
 * @since 2.1.0
 */

// Exit if accessed directly or not via WordPress uninstall
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user wants to delete all plugin data
$options = get_option( 'arbricks_options', array() );
$delete_data = isset( $options['delete_on_uninstall'] ) && $options['delete_on_uninstall'];

if ( $delete_data ) {
	// 1. Delete all plugin options
	delete_option( 'arbricks_options' );
	delete_option( 'arbricks_enabled_features' );
	delete_option( 'arbricks_feature_settings' );
	delete_option( 'arbricks_version' );
	
	// 2. Delete site options (for multisite)
	delete_site_option( 'arbricks_options' );
	delete_site_option( 'arbricks_enabled_features' );
	delete_site_option( 'arbricks_feature_settings' );
	delete_site_option( 'arbricks_version' );
	
	// 3. Delete user metadata (2FA secrets and status)
	delete_metadata( 'user', 0, 'arbricks_2fa_enabled', '', true );
	delete_metadata( 'user', 0, 'arbricks_2fa_secret', '', true );
	delete_metadata( 'user', 0, 'arbricks_2fa_temp_secret', '', true );
	delete_metadata( 'user', 0, 'arbricks_last_activity', '', true );

	// 4. Delete all transients
	global $wpdb;
	
	// Single site
	$wpdb->query(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE '_transient_arbricks_%' 
		OR option_name LIKE '_transient_timeout_arbricks_%'"
	);
	
	// Multisite
	if ( is_multisite() ) {
		$wpdb->query(
			"DELETE FROM {$wpdb->sitemeta} 
			WHERE meta_key LIKE '_site_transient_arbricks_%' 
			OR meta_key LIKE '_site_transient_timeout_arbricks_%'"
		);
	}
	
	// 4. Delete temporary files created by the plugin
	$upload_dir = wp_upload_dir();
	$temp_dirs = array(
		$upload_dir['basedir'] . '/arbricks-temp',
		$upload_dir['basedir'] . '/arbricks-webp',
	);
	
	foreach ( $temp_dirs as $dir ) {
		if ( is_dir( $dir ) ) {
			// Recursively delete directory
			$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $dir, RecursiveDirectoryIterator::SKIP_DOTS ),
				RecursiveIteratorIterator::CHILD_FIRST
			);
			
			foreach ( $files as $file ) {
				if ( $file->isDir() ) {
					rmdir( $file->getRealPath() );
				} else {
					unlink( $file->getRealPath() );
				}
			}
			
			rmdir( $dir );
		}
	}
	
	// 5. Clear any cached data
	wp_cache_flush();
}

// If delete_data is false, settings and data are preserved for reinstallation
