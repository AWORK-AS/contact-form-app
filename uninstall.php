<?php
/**
 * Formular af CitizenOne journalsystem
 *
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * @package   mzaworkdk\Citizenone
 * @author    Mindell Zamora <mz@awork.dk>
 * @copyright 2025 AWORK A/S
 * @license   GPL 2.0+
 * @link      https://awork.dk
 */

// If uninstall is not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Important: Check that the user has permission to uninstall plugins.
if ( ! current_user_can( 'delete_plugins' ) ) {
	exit;
}

/**
 * Main uninstall logic for a single site.
 * Deletes all plugin options and transients.
 */
function facioj_uninstall_single_site(): void {
	// Define the option names and transients used by your plugin.
	// ✅ MAKE THIS A SINGLE PLACE FOR EASY UPDATING IN THE FUTURE.
	$options_to_delete = array(
		'formular-af-citizenone-journalsystem-settings', // The main option for your settings.
	);

	$transients_to_delete = array(
		'facioj_autoloader_not_optimized',
	);

	// Delete all options.
	foreach ( $options_to_delete as $option_name ) {
		delete_option( $option_name );
	}

	// Delete all transients.
	foreach ( $transients_to_delete as $transient_name ) {
		delete_transient( $transient_name );
	}
}


// --- Main Execution Logic ---

// Check if it's a multisite uninstall.
if ( is_multisite() ) {

	// Get all blogs in the network and loop through them.
	$blog_ids = get_sites(
		array(
			'fields'     => 'ids',
			'network_id' => get_current_network_id(),
		)
	);

	foreach ( $blog_ids as $blg_id ) {
		switch_to_blog( $blg_id );
		facioj_uninstall_single_site();
		restore_current_blog();
	}
} else {
	// This is a single site, so just run the uninstall function once.
	facioj_uninstall_single_site();
}
