<?php

//MOVIES AND TV-SHOWS FUNCTIONS
// This file contains functions to clear movie and tv-show data from posts, including removing post meta, terms, and attachments.
function clear_movie_for_post_id( $post_id ) {
    if(!$post_id){
        cid_admin_notice_redirect(__('Post ID is empty.','duke-yin-helper'));
        return;
    }
    $tmdb_id = get_post_meta( $post_id, 'tmdb_id', true ); 


//remove post meta
    delete_post_meta( $post_id, 'tmdb_id' );
    delete_post_meta( $post_id, '_r_rdate' );
    delete_post_meta( $post_id, '_r_f_original_title' );
    delete_post_meta( $post_id, '_headline' );
    delete_post_meta( $post_id, '_r_f_year' );
    delete_post_meta( $post_id, '_r_f_imdb_id' );
    delete_post_meta( $post_id, '_r_f_runtime' );
    delete_post_meta( $post_id, '_r_f_overview' );
    delete_post_meta( $post_id, '_r_f_poster' );
    delete_post_meta( $post_id, '_r_f_logo' );
    delete_post_meta( $post_id, '_r_t_number_seasons' );
    delete_post_meta( $post_id, '_r_t_seasons' );
//remove terms
    dk_remove_all_terms($post_id, 'directors' );
    dk_remove_all_terms($post_id, 'screenplay' );
    dk_remove_all_terms($post_id, 'genres' );
    dk_remove_all_terms($post_id, 'cast' );
    dk_remove_all_terms($post_id, 'languages' );
    dk_remove_all_terms($post_id, 'languages' );
    dk_remove_all_terms($post_id, 'film_review_categories' );
    
    $other_posts =  posts_with_tmdb_id( $tmdb_id );
    if( count($other_posts) < 1 ) {
        $attached_images = get_attached_media('image', $post_id );
        foreach( $attached_images as $attachment ) {
            @wp_delete_attachment( $attachment->ID, true );
        }
    }
    delete_post_thumbnail($post_id);
    /*delete the folder with $tmdb_id name*/
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
    $fileSystemDirect = new WP_Filesystem_Direct(false);
    $folder_path = wp_upload_dir()['basedir'] .'/tmdb'.'/'.$tmdb_id;
    $fileSystemDirect->rmdir( $folder_path,true, 'd' );
}

add_filter( 'wp_delete_file', 'delete_webp' );

function delete_webp($file) {
    if (file_exists(str_replace(".png" ,".webp", $file))) {
        @unlink(str_replace(".png" ,".webp", $file));
    }
    return $file;
}

function dk_remove_all_terms($post_id, $taxonomy ) {
	//An empty array to set 
	$new_terms = array();
	return wp_set_object_terms( $post_id, $new_terms, $taxonomy );
}

function posts_with_tmdb_id( $tmdb_id ) {
    $args = array(
    'posts_per_page' => 9999,
        'meta_key'         => 'tmdb_id',
        'meta_value'       => $tmdb_id,
        'post_type'        => array('film_review','tvshow_review'),
        'suppress_filters' => true
    );
    return get_posts( $args );
}

function clear_movie_for_tmdb_id( $tmdb_id ) {
    $posts = posts_with_tmdb_id( $tmdb_id );
    foreach($posts as $post) {
        clear_movie_for_post_id( $post->ID );
    }
}
function posts_with_movie_data() {
    $args = array(
        'posts_per_page' => 9999,
            'meta_key'         => 'tmdb_id',
            'post_type'        => array('film_review','tvshow_review'),
            'suppress_filters' => true
        );
        return get_posts( $args );
}

function clear_all_movie_data() {
    $posts = posts_with_movie_data();
    foreach($posts as $post) {
        clear_movie_for_post_id( $post->ID );
    }
}

function clear_movie_zero(){
    clear_zero_tax('cast');
    clear_zero_tax('screenplay');
    clear_zero_tax('languages');
    clear_zero_tax('directors');
    clear_zero_tax('film_review_categories');
}

function clear_zero_tax( $tax ){
    $terms = get_terms( [
        'taxonomy'                  => $tax,
        'hide_empty'                => false,
        'update_term_meta_cache'    => false,
        'hierarchical'              => false,
    ] );
    foreach ( $terms as $term ) {
        if ( 0 === $term->count ) {
            wp_delete_term( $term->term_id, $tax );
        }
    }
}