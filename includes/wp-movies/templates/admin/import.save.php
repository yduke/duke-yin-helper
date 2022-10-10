<?php

$data_saved = false;

function add_or_update_post_meta( $post_id, $key, $value ) {
    if ( ! update_post_meta ($post_id, $key, $value, false) ) { 
        add_post_meta($post_id, $key, $value, false);	
    }; 
}

function is_featured_image( $poster_or_backdrop, $movie ) {
    $setting = get_option('zmovies_featured_image');
    if($setting == 'backdrop then poster') {
        if(($poster_or_backdrop=='backdrop' && $movie->backdrop_path) || ($poster_or_backdrop=='poster' && !$movie->backdrop_path)) {
            return true;
        }
        return false;
    } else if($setting == 'poster then backdrop') {
        if(($poster_or_backdrop=='poster' && $movie->poster_path) || ($poster_or_backdrop=='backdrop' && !$movie->poster_path)) {
            return true;
        }
        return false;
    } else {
        return false;
    }
}

if( isset($_POST['posts']) ) { ?>

<div class="updated" id="importing-message">
    <p><strong><?php _e('Importing... Please do not navigate away from this page.', 'duke-yin-helper' ); ?></strong></p>
</div>

<?php

    foreach($_POST['posts'] as $post) {
    
        if(empty($post)) {
            continue;
        }
    
        $parts = explode("|", $post);
        $post_id = $parts[0];
        $tmdb_id = $parts[1];

        
        // Store TMDb ID
        add_or_update_post_meta( $post_id, 'tmdb_id', $tmdb_id );
        
        // Copy images and store JSON
        $movie = Movies::TMDb($tmdb_id);
        $json = $movie->json($copy_images=true);
        add_or_update_post_meta( $post_id, '_zmovies_json', $json );
        $date = strtotime($movie->date);
        add_or_update_post_meta( $post_id, '_r_rdate', $date );

        $title = $movie->title;
        $slug = sanitize_title($title);
        wp_update_post(array ('ID'=> $post_id,'post_title' => $title, 'post_name' => $slug));

        $original_title = $movie->original_title;
        add_or_update_post_meta( $post_id, '_r_f_original_title', $original_title );

        $year = $movie->year;
        add_or_update_post_meta( $post_id, '_r_f_year', $year );

        $imdbid = $movie-> imdb_id;
        add_or_update_post_meta( $post_id, '_r_f_imdb_id', $imdbid );

        $language = $movie-> languages[0];
        add_or_update_post_meta( $post_id, '_r_f_language', $language );

        $overview = $movie-> overview;
        add_or_update_post_meta( $post_id, '_r_f_overview', $overview );
        

        $genres = $movie->genres;
        foreach($genres as $genre){
            $term_id = term_exists( $genre, 'film_review_categories' );
            if($term_id){
                wp_set_post_terms( $post_id, array( $term_id['term_id'] ), 'film_review_categories', true );
            }else{
                wp_insert_term(
                $genre,
                'film_review_categories',
                array(
                    'description' => '',
                    'slug'        => $genre,
                    'parent'      => '',
                ));
                $term_id = term_exists( $genre, 'film_review_categories' );
                wp_set_post_terms( $post_id, array( $term_id['term_id'] ), 'film_review_categories', true );
            }
        }

        // Fetch movie data back from WP
        $movie = new Movie( $post_id );
        
        // Attach media
        if( trim(get_option('zmovies_attach_media')) == 'y' ) {
            $attach_ids = get_attach_ids_for_post( $post_id );
            if($movie->backdrop_path) {
                $attach_id = attach_media_to_post( $post_id, $movie->backdrop_path, is_featured_image('backdrop', $movie), $movie->title );
                $attach_ids[] = $attach_id;
            }
            if($movie->poster_path) {
                $attach_id = attach_media_to_post( $post_id, $movie->poster_path, is_featured_image('poster', $movie), $movie->title );
                $attach_ids[] = $attach_id;
            }
            add_or_update_post_meta( $post_id, '_zmovies_attach_ids', implode(",", $attach_ids) );
        }

    }

    $data_saved = true;

}

?>