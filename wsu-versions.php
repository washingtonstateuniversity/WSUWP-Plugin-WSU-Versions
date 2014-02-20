<?php
/**
Plugin Name: WSU Versions
Description: A method of versioning content, location, and form in WordPress.
Author:      washingtonstateuniversity, jeremyfelt
Version:     0.1
Plugin URI:  http://github.com/washingtonstateuniversity/wsuwp-plugin-wsu-versions/
License:     GPLv2 or Later
 */

/* WSU Versions
 *
 * A method of versioning content, location, and form in WordPress
 *
 * Copyright (C) 2014 Washington State University
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

class WSU_Versions {

	/**
	 * @var string Stores the meta key used for a piece of content's unique ID.
	 */
	var $unique_id_meta_key = '_wsu_versions_uid';

	/**
	 * Setup the hooks used by the plugin.
	 */
	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
	}

	/**
	 * Fires when updating or saving a post.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( 'inherit' === $post->post_status ) {
			return;
		}

		if ( $this->get_unique_id( $post ) ) {
			return;
		}

		$this->generate_unique_id( $post );
	}

	/**
	 * Get the unique ID generated for a piece of content.
	 *
	 * @param int|object $post Post ID or post object.
	 *
	 * @return bool|string False if no unique ID is available or a string representing the unique ID.
	 */
	public function get_unique_id( $post ) {
		if ( is_object( $post ) ) {
			$post = $post->ID;
		}

		$unique_id = get_post_meta( $post, $this->unique_id_meta_key, true );

		return $unique_id;
	}

	/**
	 * Generate a unique version ID for a piece of content.
	 *
	 * @param int|object $post Post ID or post object.
	 */
	public function generate_unique_id( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		$unique_id = md5( $post->post_name . time() . $post->ID );

		update_post_meta( $post->ID, $this->unique_id_meta_key, $unique_id );
	}

}
$wsu_versions = new WSU_Versions();