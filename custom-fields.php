<?php
/**
 * Custom Fields
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


// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) {
  die( 'Invalid request.' );
}

/*-------------------------------------------------------------------

INCLUDE SCRIPTS/STYLES FOR FRONTEND

---------------------------------------------------------------------*/

add_action( 'wp_enqueue_scripts', 'mcf_front_scripts' );

function mcf_front_scripts() {

 	//css for slideshow

 	wp_enqueue_style( 'mcf-stylesheet', plugins_url('lib/public/css/styles.css', __FILE__) );

}

/*-------------------------------------------------------------------

CREATE 'CONTRIBUTORS' METABOX

---------------------------------------------------------------------*/

function mcf_add_metabox()

{

    $post_types = ['post','page'];

    foreach ($post_types as $post_type) {

        add_meta_box(

            'mcf_contrributors_box', //metabox id 

            'Contributors',  //metabox title

            'mcf_contributors_box_html',  // function to display content 

            $post_type    //post type              

        );

    }

}

add_action('add_meta_boxes', 'mcf_add_metabox');

/*-------------------------------------------------------------------

CONTENT OF 'CONTRIBUTORS' METABOX

---------------------------------------------------------------------*/

function mcf_contributors_box_html($post)

{

	wp_nonce_field( basename(__FILE__), 'mcf_nonce' );



	$mcf_all_users = get_users( array( 'fields' => array( 'ID','display_name' ) ) );



  	$post_contributors=get_post_meta($post->ID,'_contributors', true);

    ?>

  

    <?php

    foreach($mcf_all_users as $mcf_user){

		

		if ( is_array( $post_contributors ) && in_array( $mcf_user->ID, $post_contributors ) ) {

            $checked = 'checked="checked"';

        } else {

            $checked = null;

        }

        ?>



        <p>        	 

            <input  type="checkbox" name="contributors[]" value="<?php echo $mcf_user->ID;?>" 

            <?php echo $checked; ?> 

            />

            <?php echo $mcf_user->display_name;?>

        </p>

   <?php }

   

}

/*-------------------------------------------------------------------

SAVING 'CONTRIBUTORS' METABOX

---------------------------------------------------------------------*/



add_action('save_post', 'mcf_save_postdata');

function mcf_save_postdata($post_id){

    $is_autosave = wp_is_post_autosave( $post_id );

    $is_revision = wp_is_post_revision( $post_id );



    $is_valid_nonce = ( isset( $_POST[ 'mcf_nonce' ] ) && wp_verify_nonce( $_POST[ 'mcf_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';



    if ( $is_autosave || $is_revision || !$is_valid_nonce ) {

        return;

    }



    // If the checkbox was not empty, save it as array in post meta

    if ( ! empty( $_POST['contributors'] ) ) {

        update_post_meta( $post_id, '_contributors', $_POST['contributors'] );

    } else {

        delete_post_meta( $post_id, '_contributors' );

    }

}



/*-------------------------------------------------------------------

DISPLAY CONTRIBUTORS FIELD CONTENT ON FRONT

---------------------------------------------------------------------*/

add_filter( 'the_content', 'filter_the_content_in_the_main_loop' );

 

function filter_the_content_in_the_main_loop( $content ) {

 

    // Check if we're inside the main loop in a single post page.

    if ( is_single() && in_the_loop() && is_main_query() ) {



    	global $post;



    	$return="";



		$post_contributors = get_post_meta($post->ID,"_contributors",true);


        if($post_contributors){

		$return="<fieldset class='contributor_profile'><legend>Contributors</legend>

        <ul>";



		foreach ($post_contributors as $contributor_id) {

			

			$user_info = get_userdata($contributor_id);

			$user_display_name=$user_info->display_name;

			$user_gravatar=get_avatar($user_info->ID, 32);

			$user_profile_url=get_author_posts_url( $user_info->ID, $user_info->nicename );



			$return=$return."<li><a href='".$user_profile_url."'>".$user_gravatar.$user_display_name."</a></li>";



		}

		$return=$return."</ul></fieldset>";

    }
        return $content . $return;

    }

 

    return $content;

}



?>
