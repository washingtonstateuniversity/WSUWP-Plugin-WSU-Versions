<?php
/**
Plugin Name: WSU Versions - Template Filter
Description: Used in combination with WSU Versions, filters the template and stylesheet on demand.
Author:      washingtonstateuniversity, jeremyfelt
Version:     0.1
Plugin URI:  http://github.com/washingtonstateuniversity/wsuwp-plugin-wsu-versions/
License:     GPLv2 or Later
 */

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