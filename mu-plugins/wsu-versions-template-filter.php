<?php
/**
 * Hackity hack, don't look back.
 */

// Filter stylesheet and template to force design.
if ( isset( $_GET['wsu-versions-template'] ) ) {
	add_filter( 'stylesheet', 'wsu_versions_stylesheet', 10 );
	add_filter( 'template',   'wsu_versions_template',   10 );
}

function wsu_versions_stylesheet( $stylesheet ) {
	if (  ! isset( $_GET['wsu-versions-template'] ) ) {
		return $stylesheet;
	}

	$stylesheet = sanitize_key( $_GET['wsu-versions-template'] );

	return $stylesheet;
}

function wsu_versions_template( $template ) {
	if ( ! isset( $_GET['wsu-versions-template'] ) ) {
		return $template;
	}

	$template = sanitize_key( $_GET['wsu-versions-template'] );

	return $template;
}