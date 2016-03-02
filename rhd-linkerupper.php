<?php
/**
 * Plugin Name: RHD Linkerupper
 * Description: Automatically creates, updates, and deletes taxonomy terms to match linked posts (including pages and custom post types). Set CPT and taxonomy definitions by editing plugin.
 * Author: Roundhouse Designs
 * Author URI: https://roundhouse-designs.com
 * Version: 0.1
**/

define( 'RHD_LU_TAX', 'location' );
define( 'RHD_LU_POST_TYPE', 'store' );


/**
 * rhd_add_update_store function.
 *
 * Adds a RHD_LU_TAX taxonomy term to match a new RHD_LU_POST_TYPE post,
 *  or updates the term to match the updated store.
 *
 * @access public
 * @param mixed $post_id
 * @param mixed $post_after
 * @param mixed $post_before
 * @return void
 */
function rhd_add_update_store( $post_id, $post_after, $post_before )
{
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
add_action( 'post_updated', 'rhd_add_update_store', 10, 3 );


/**
 * rhd_delete_store function.
 *
 * @access public
 * @param mixed $post_id
 * @return void
 */
function rhd_delete_store( $post_id )
{
	if ( get_post_type( $post_id ) == RHD_LU_POST_TYPE ) {
		$term_id = get_post_meta( $post_id, '_lu_' . RHD_LU_TAX . '_id', true );

		if ( $term_id ) {
			wp_delete_term( $term_id, RHD_LU_TAX );
		}
	}
}
add_action( 'before_delete_post', 'rhd_delete_store' );
add_action( 'delete_post', 'rhd_delete_store' );


/**
 * rhd_force_slug_update function.
 *
 * Forces recalculation of post slug on update if type RHD_LU_POST_TYPE
 *
 * @access public
 * @param mixed $data
 * @param mixed $postarr
 * @return void
 */
function rhd_force_slug_update( $data, $postarr )
{
	if ( ! in_array( $data['post_status'], array( 'draft', 'pending', 'auto-draft' ) ) && $data['post_type'] == RHD_LU_POST_TYPE ) {
		$data['post_name'] = sanitize_title( $data['post_title'] );
	}

	return $data;
}
add_filter( 'wp_insert_post_data', 'rhd_force_slug_update', 99, 2 );