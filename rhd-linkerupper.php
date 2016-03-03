<?php
/**
 * Plugin Name: RHD Linkerupper
 * Description: Automatically creates, updates, and deletes taxonomy terms to match linked posts (including pages and custom post types). Set CPT and taxonomy definitions by editing plugin.
 * Author: Roundhouse Designs
 * Author URI: https://roundhouse-designs.com
 * Version: 1.12
**/

define( 'RHD_LU_TAX', 'taxonomy' );
define( 'RHD_LU_CUSTOM_TYPE', 'custom_post_type' );
define( 'RHD_LU_MAIN_POST_TYPE', 'main_post_type' );


/**
 * rhd_register_lu_tax function.
 *
 * @access public
 * @return void
 */
function rhd_register_lu_tax()
{
	register_taxonomy( RHD_LU_TAX, RHD_LU_MAIN_POST_TYPE, array(
		'label' => 'Some Terms',
		'labels' => array(
			'name' => 'Some Terms',
		),
		'public' => true,
		'show_admin_column' => true,
		'show_in_nav_menus' => false,
		'show_tagcloud' => false,
		'show_ui' => true,
		'hierarchical' => true
	));
}
add_action( 'init', 'rhd_register_lu_tax' );

/**
 * rhd_add_update_cpt_post function.
 *
 * Adds a RHD_LU_TAX taxonomy term to match a new `RHD_LU_CUSTOM_TYPE` post,
 *  or updates the term to match the updated `RHD_LU_CUSTOM_TYPE` post.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post_after
 * @param mixed $post_before
 * @return void
 */
function rhd_add_update_cpt_post( $post_id, $post_after, $post_before )
{
	if ( get_post_type( $post_id ) != RHD_LU_CUSTOM_TYPE )
		return;

	$title_after = $post_after->post_title;
	$slug_after = $post_after->post_name;
	$title_before = $post_before->post_title;
	$slug_before = $post_before->post_name;

	// Check if meta already set
	$term_id = get_post_meta( $post_id, '_lu_' . RHD_LU_TAX . '_id', true );

	if ( $post_after->post_status != 'trash' ) {
		if ( ! $term_id ) {
			$term = term_exists( $title_after, RHD_LU_TAX );

			if ( $term == 0 || $term == null ) {
				$args = array(
					'slug' => $slug_after
				);
				wp_insert_term( $title_after, RHD_LU_TAX, $args );

				$new_term = get_term_by( 'slug', $slug_after, RHD_LU_TAX );
				$new_term_id = intval( $new_term->term_id );

				add_post_meta( $post_id, '_lu_' . RHD_LU_TAX . '_id', $new_term_id, true );
			}
		} else {
			$args = array();

			if ( $title_before != $title_after )
				$args['name'] = $title_after;

			if ( $slug_before != $slug_after )
				$args['slug'] = $slug_after;

			if ( ! empty( $args ) )
				wp_update_term( $term_id, RHD_LU_TAX, $args );
		}
	}
}
add_action( 'post_updated', 'rhd_add_update_cpt_post', 10, 3 );


/**
 * rhd_delete_cpt_post function.
 *
 * @access public
 * @param mixed $post_id
 * @return void
 */
function rhd_delete_cpt_post( $post_id )
{
	if ( get_post_type( $post_id ) != RHD_LU_CUSTOM_TYPE )
		return;

	$term_id = get_post_meta( $post_id, '_lu_' . RHD_LU_TAX . '_id', true );

	if ( $term_id ) {
		wp_delete_term( $term_id, RHD_LU_TAX );
	}
}
add_action( 'before_delete_post', 'rhd_delete_cpt_post' );
add_action( 'delete_post', 'rhd_delete_cpt_post' );


/* ==========================================================================
	DISABLED FUNCTIONALITY
   ========================================================================== */

/**
 * rhd_lu_tax_hide_ui function.
 *
 * Sets RHD_LU_TAX 'show_ui' to false.
 *
 * @access public
 * @return void
 */
function rhd_lu_tax_hide_ui()
{
	$tax_args =& get_taxonomy( RHD_LU_TAX );

	$tax_args->show_ui = false;
}
// add_action( 'init', 'rhd_lu_tax_hide_ui', 11 );


/**
 * rhd_add_lu_tax_box function.
 *
 * Displays the LU_TAX meta box on the edit screen, since show_ui has been disabled.
 *
 * @access public
 * @return void
 */
function rhd_add_lu_tax_box()
{
	$tax = get_taxonomy( RHD_LU_TAX );
	add_meta_box( RHD_LU_TAX . '_box', $tax->labels->name, 'post_categories_meta_box', RHD_LU_MAIN_POST_TYPE, 'side', null, array( 'taxonomy' => RHD_LU_TAX ) );
}
// add_action( 'add_meta_boxes', 'rhd_add_lu_tax_box' );