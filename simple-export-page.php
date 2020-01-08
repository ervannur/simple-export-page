<?php
/*
Plugin Name: Simple Export Page
Plugin URI: http://wordpress.org/plugins/hello-dolly/
Description: Easily export one or more pages.
Author: Ervan Nur Adhitiya
Version: 1.0.0
Author URI: https://duosweb.com/about/
*/

function duosep_register_export_bulk_action( $bulk_actions ) {
	$bulk_actions['duosep_export'] = __( 'Export', 'simple_export_page' );
	return $bulk_actions;
}
add_filter( 'bulk_actions-edit-page', 'duosep_register_export_bulk_action' );

function duosep_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
	if ( $doaction !== 'duosep_export' ) {
		return $redirect_to;
	}

	if ( ! is_user_logged_in() || ! current_user_can( 'export' ) ) {
		wp_die( __( 'Sorry, you are not allowed to export the content of this site.' ) );
	}

	if( !defined( 'WXR_VERSION' ) ) {
		define( 'WXR_VERSION', '1.2' );
	}

	require_once( dirname( __FILE__ ) . '/class-duosep-export.php' );

	$duosep_export = new Duosep_Export();
	echo $duosep_export->export_page( $post_ids );
	die();

	return $redirect_to;
}
add_filter( 'handle_bulk_actions-edit-page', 'duosep_bulk_action_handler', 10, 3 );
