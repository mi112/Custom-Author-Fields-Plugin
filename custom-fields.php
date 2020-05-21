<?php
/**
 * Custom Fields
 *
 * @package     Custom_Fields
 *
 * Plugin Name: Custom Fields
 * Plugin URI:  https://github.com/mi112/custom-fields
 * Description: This plugin provides functionality to add custom fields in post
 * Version:     1.0.1
 * Requires at least: 5.2
 * Tested Up to: 5.4.1
 * Requires PHP: 7.0
 * Author:      Mittala Nandle
 * Author URI:  https://www.mittala.com/
 * License:     GPLv2 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * Text Domain: custom-fields
 * Domain Path: /languages
 */

// Exit if accessed directly.

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}

/**
 * INCLUDE SCRIPTS/STYLES FOR FRONTEND.
 */
function mcf_front_scripts() {

	// css for slideshow.

	wp_enqueue_style( 'mcf-stylesheet', plugins_url( 'lib/public/css/styles.css', __FILE__ ), array(), '1.0' );

}

add_action( 'wp_enqueue_scripts', 'mcf_front_scripts' );

/**
 * Load the text domain for i18n.
 */
function mcf_load_textdomain() {
	load_plugin_textdomain( 'custom-fields', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'mcf_load_textdomain' );

/**
 * CREATE 'CONTRIBUTORS' METABOX.
 */
function mcf_add_metabox() {

	$post_types = array( 'post', 'page' );

	foreach ( $post_types as $post_type ) {

		add_meta_box(
			'mcf_contrributors_box', // metabox id.
			__( 'Contributors', 'custom-fields' ),  // metabox title.
			'mcf_contributors_box_html',  // function to display content.
			$post_type    // post type.
		);

	}

}

add_action( 'add_meta_boxes', 'mcf_add_metabox' );

/**
 * CONTENT OF 'CONTRIBUTORS' METABOX.
 *
 * @param array $post contains an array of post content.
 */
function mcf_contributors_box_html( $post ) {

	wp_nonce_field( basename( __FILE__ ), 'mcf_nonce' );

	$mcf_all_users = get_users( array( 'fields' => array( 'ID', 'display_name' ) ) );

	$post_contributors = get_post_meta( $post->ID, '_contributors', true );

	?>
	<?php

	foreach ( $mcf_all_users as $mcf_user ) {
		// phpcs:ignore
		if ( is_array( $post_contributors ) && in_array( $mcf_user->ID, $post_contributors ) ) {

			$checked = 'checked="checked"';

		} else {

			$checked = null;

		}

		?>

		<p>        	 

			<input  type="checkbox" name="contributors[]" value="<?php echo esc_attr( $mcf_user->ID ); ?>" 

			<?php echo esc_attr( $checked ); ?> 

			/>

			<?php echo esc_html( $mcf_user->display_name ); ?>

		</p>

	<?php }

}

/**
 * SAVING 'CONTRIBUTORS' METABOX.
 *
 * @param int $post_id gives current post id.
 */
function mcf_save_postdata( $post_id ) {

	$is_autosave = wp_is_post_autosave( $post_id );

	$is_revision = wp_is_post_revision( $post_id );

	// phpcs:ignore
	$is_valid_nonce = ( isset( $_POST['mcf_nonce'] ) && wp_verify_nonce( $_POST['mcf_nonce'], basename( __FILE__ ) ) ) ? 'true' : 'false'; // WPCS: Input var okay.

	if ( $is_autosave || $is_revision || ! $is_valid_nonce ) {

		return;

	}

	// If the checkbox was not empty, save it as array in post meta.

	if ( ! empty( $_POST['contributors'] ) ) {

		// phpcs:ignore
		update_post_meta( $post_id, '_contributors', $_POST['contributors'] ); 

	} else {

		delete_post_meta( $post_id, '_contributors' );

	}

}

add_action( 'save_post', 'mcf_save_postdata' );

/**
 * DISPLAY CONTRIBUTORS FIELD CONTENT ON FRONT (ON POST).
 *
 * @param String $content represents current post's content.
 */
function filter_the_content_in_the_main_loop( $content ) {

	// Check if we're inside the main loop in a single post page.

	if ( is_single() && in_the_loop() && is_main_query() ) {

		global $post;

		$return = '';

		$post_contributors = get_post_meta( $post->ID, '_contributors', true );

		if ( $post_contributors ) {

			$return = "<fieldset class='contributor_profile'><legend>" . __( 'Contributors', 'custom-fields' ) . '</legend>

        <ul>';

			foreach ( $post_contributors as $contributor_id ) {

				$user_info = get_userdata( $contributor_id );

				$user_display_name = $user_info->display_name;

				$user_gravatar = get_avatar( $user_info->ID, 32 );

				$user_profile_url = get_author_posts_url( $user_info->ID, $user_info->nicename );

				$return = $return . "<li><a href='" . esc_url( $user_profile_url ) . "'>" . $user_gravatar . esc_html( $user_display_name ) . '</a></li>';

			}

			$return = $return . '</ul></fieldset>';

		}

		return $content . $return;

	}

	return $content;

}

add_filter( 'the_content', 'filter_the_content_in_the_main_loop' );

/**
 * Filter to print Author Contributions.
 *
 * @param array $query represents main query for archive page.
 */
function mcf_display_author_contributions( $query ) {
	if ( ! is_admin() && $query->is_main_query() && $query->is_author() ) {

		$author  = get_user_by( 'slug', get_query_var( 'author_name' ) );
		$user_id = $author->ID;

		if ( $user_id ) {

			global $post;

			$posts_by_author = get_posts(
				array(
					'author' => $user_id,
				)
			);

			$contributions_by_author = get_posts(
				array(
					'meta_key'     => '_contributors', // phpcs:ignore
					'meta_compare' => 'LIKE',
					'meta_value'   => '"' . $user_id . '"', // phpcs:ignore
				)
			);

			$all_posts = array_unique( array_merge( $posts_by_author, $contributions_by_author ), SORT_REGULAR );

			$post_ids = array();
			foreach ( $all_posts as $single_post ) {
				$post_ids[] = $single_post->ID;
			}

			set_query_var( 'current_author', get_query_var( 'author_name' ) );

			$query->set( 'author_name', null );
			$query->set( 'post__in', $post_ids );

		}
	}//if it is author archive.

}
add_action( 'pre_get_posts', 'mcf_display_author_contributions', 9999 );

/**
 * Change 'Author' archive title.
 *
 * @param String $title represents original title of archive page.
 */
function mcf_custom_archive_title( $title ) {
	if ( is_author() ) {

		$author = get_user_by( 'slug', get_query_var( 'current_author' ) );

		/* translators: %s: new title */
		$title = sprintf( __( 'ALL POSTS/CONTRIBUTIONS BY: %s', 'custom-fields' ), '<span class="vcard highlight">' . $author->display_name . '</span>' );

	}
	return $title;
}

add_filter( 'get_the_archive_title', 'mcf_custom_archive_title', 100 );

?>
