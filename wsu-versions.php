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
	 * @var string The meta key used to track the template assigned to a piece of content.
	 */
	var $template_meta_key = '_wsu_versions_template';

	/**
	 * @var string Meta key used to determine if this version is a fork.
	 */
	var $is_fork_meta_key = '_wsu_versions_is_fork';

	/**
	 * Setup the hooks used by the plugin.
	 */
	public function __construct() {
		add_action( 'transition_post_status', array( $this, 'transition_post_status' ), 10, 3 );
		add_action( 'add_meta_boxes',         array( $this, 'add_meta_boxes'         ), 10, 2 );
		add_action( 'admin_enqueue_scripts',  array( $this, 'admin_enqueue_scripts'  )        );
		add_action( 'wp_ajax_create_fork',    array( $this, 'ajax_create_fork'       )        );
	}

	/**
	 * Add meta boxes provided by the plugin.
	 *
	 * @param string  $post_type Current post's post type.
	 * @param WP_post $post      Contains properties of a piece of content.
	 */
	public function add_meta_boxes( $post_type, $post ) {
		add_meta_box( 'wsu-versions-meta', 'Versions', array( $this, 'display_versions_box' ), null, 'side', 'default' );
	}

	/**
	 * Enqueue scripts used in the admin.
	 */
	public function admin_enqueue_scripts() {
		if ( 'post' === get_current_screen()->base ) {
			wp_enqueue_script( 'wsu-versions-admin', plugins_url( '/js/wsu-versions-admin.js', __FILE__ ), array( 'jquery' ), false, true );
		}
	}

	/**
	 * Handle an AJAX request to create a fork from an original piece of content.
	 */
	public function ajax_create_fork() {
		check_ajax_referer( 'wsu-versions-fork' );
		$original_unique_id = $_POST['version_id'];

		$post = $this->get_post_by_version( $original_unique_id );

		$fork_post = $post;
		unset( $fork_post->ID );
		$fork_post->post_name   = 'wsu-fork-' . $fork_post->post_name;
		$fork_post->post_status = 'private';

		$fork_post_id = wp_insert_post( $fork_post );

		if ( is_wp_error( $fork_post_id ) ) {
			$response = array( 'error' => $fork_post_id->get_error_message() );
		} else {
			update_post_meta( $fork_post_id, $this->is_fork_meta_key, absint( $fork_post_id ) );
			$response = array( 'success' => $fork_post_id );
		}

		echo json_encode( $response );
		die();
	}

	/**
	 * Get a post object based on a unique ID.
	 *
	 * @param string $unique_id The hashed unique ID of a piece of content.
	 *
	 * @return bool|WP_Post False if a post does not exist. A WP_Post object if it does.
	 */
	public function get_post_by_version( $unique_id ) {
		global $wpdb;

		if ( empty( $unique_id ) ) {
			return false;
		}

		$post = $wpdb->get_row( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s", $this->unique_id_meta_key, $unique_id ) );

		if ( isset( $post->post_id ) ) {
			$post = get_post( $post->post_id );

			if ( null !== $post ) {
				return $post;
			}
		}

		return false;
	}

	/**
	 * Display a meta box containing WSU Versions information.
	 *
	 * @param WP_Post $post Current post's object=.
	 */
	public function display_versions_box( $post ) {
		$unique_id = $this->get_unique_id( $post );
		echo 'Unique ID: <input id="wsu-version-id" readonly type="text" value="' . esc_attr( $unique_id ) . '" />';

		if ( $this->is_fork( $post ) ) {
			$template  = $this->get_template(  $post );
			echo 'Template: <select name="wsu_versions_fork_template">
				<option value="' . esc_attr( $template ) . '">' . esc_html( $template ) . '</option>
				</select>';
		} else {
			$ajax_nonce = wp_create_nonce( 'wsu-versions-fork' );
			?><p class="description">This is an original piece of content.</p>
			<label for="wsu_versions_fork_location">Fork Location:</label>
			<select id="wsu-fork-location" name="wsu_versions_fork_location">
				<option value="production">Current Site</option>
			</select>
			<input type="hidden" id="wsu-versions-fork-nonce" value="<?php echo esc_attr( $ajax_nonce ); ?>" />
			<span id="wsu-create-fork" class="button-secondary">Fork</span><?php
		}
	}

	/**
	 * Fires when updating or saving a piece of content.
	 *
	 * @param string  $new_status Contains new status of content.
	 * @param string  $old_status Contains old status of content.
	 * @param WP_Post $post       A post object of the content's main properties.
	 */
	public function transition_post_status( $new_status, $old_status, $post ) {
		if ( in_array( $post->post_status, array( 'inherit', 'auto-draft' ) ) ) {
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
	 * Generate a unique version ID for a piece of content and store the current
	 * template in use when the content version was created.
	 *
	 * @param int|object $post Post ID or post object.
	 */
	public function generate_unique_id( $post ) {
		if ( ! is_object( $post ) ) {
			$post = get_post( $post );
		}

		$unique_id    = md5( $post->post_name . time() . $post->ID );
		$current_form = sanitize_key( get_template() );

		update_post_meta( $post->ID, $this->unique_id_meta_key, $unique_id    );
		update_post_meta( $post->ID, $this->template_meta_key,  $current_form );
	}

	/**
	 * @param int|WP_Post $post Post ID or object representing a piece of content.
	 *
	 * @return string|bool String representing template assigned to content. False if not found.
	 */
	public function get_template( $post ) {
		if ( is_object( $post ) ) {
			$post = $post->ID;
		}

		$current_form = get_post_meta( $post, $this->template_meta_key, true );

		return $current_form;
	}

	/**
	 * Determine if a piece of content is a fork.
	 *
	 * @param int|WP_Post $post Post ID or object.
	 *
	 * @return bool True if a fork. False if not.
	 */
	private function is_fork( $post ) {
		if ( is_object( $post ) ) {
			$post = $post->ID;
		}

		$is_fork = get_post_meta( $post, $this->is_fork_meta_key, true );

		if ( empty( $is_fork ) ) {
			return false;
		}

		return true;
	}
}
$wsu_versions = new WSU_Versions();