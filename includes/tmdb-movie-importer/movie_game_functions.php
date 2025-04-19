<?php

//MOVIES AND TV-SHOWS FUNCTIONS
// This file contains functions to clear movie and tv-show data from posts, including removing post meta, terms, and attachments.
function clear_movie_data_for_post( $post_id ) {
    $tmdb_id = get_post_meta( $post_id, 'tmdb_id', true ); 
//remove post meta
    delete_post_meta( $post_id, 'tmdb_id' );
    delete_post_meta( $post_id, '_zmovies_json' );
    delete_post_meta( $post_id, '_r_rdate' );
    delete_post_meta( $post_id, '_r_f_original_title' );
    delete_post_meta( $post_id, '_headline' );
    delete_post_meta( $post_id, '_r_f_year' );
    delete_post_meta( $post_id, '_r_f_imdb_id' );
    delete_post_meta( $post_id, '_r_f_runtime' );
    delete_post_meta( $post_id, '_r_f_overview' );
    delete_post_meta( $post_id, '_r_f_poster' );
    delete_post_meta( $post_id, '_r_t_number_seasons' );
    delete_post_meta( $post_id, '_r_t_seasons' );
//remove terms
    dk_remove_all_terms($post_id, 'directors' );
    dk_remove_all_terms($post_id, 'screenplay' );
    dk_remove_all_terms($post_id, 'screenplay' );
    dk_remove_all_terms($post_id, 'cast' );
    dk_remove_all_terms($post_id, 'languages' );
    dk_remove_all_terms($post_id, 'film_review_categories' );

    /* Delete imported media if it's not attached to other posts.
    TODO: Remove attachment metadata from this post without
    deleting image file (unless image file isn't needed anymore). */
    $other_posts =  posts_with_tmdb_id( $tmdb_id );
    if( count($other_posts) < 1 ) {
        $attach_ids = get_attach_ids_for_post( $post_id );
        foreach( $attach_ids as $attach_id ) {
            wp_delete_attachment( $attach_id );
        }
        delete_post_meta( $post_id, '_zmovies_attach_ids' );
    }
    /*delete the folder with $tmdb_id name*/
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
    require_once ( ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php' );
    $fileSystemDirect = new WP_Filesystem_Direct(false);
    $folder_path = wp_upload_dir()['basedir'] .'/tmdb/'.$tmdb_id;
    $fileSystemDirect->rmdir( $folder_path,true, 'd' );
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

function clear_data_for_tmdb_id( $tmdb_id ) {
    $posts = posts_with_tmdb_id( $tmdb_id );
    foreach($posts as $post) {
        clear_movie_data_for_post( $post->ID );
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
        clear_movie_data_for_post( $post->ID );
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