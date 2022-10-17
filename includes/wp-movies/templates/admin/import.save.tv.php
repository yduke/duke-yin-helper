<?php

$data_saved = false;


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
        update_post_meta( $post_id, 'tmdb_id', $tmdb_id );
        
        // Copy images and store JSON
        $movie = Tvs::TMDb($tmdb_id);

        if(has_post_thumbnail($post_id)){ //if has thumbnail, then don't import images from tmdb
            $json = $movie->json($copy_images=false,$copy_poster=true);
        }else{
            $json = $movie->json($copy_images=true,$copy_poster=true);

        }
        
        update_post_meta( $post_id, '_zmovies_json', $json );
        $date = strtotime($movie->date);
        update_post_meta( $post_id, '_r_rdate', $date );
		
		update_post_meta( $post_id, '_r_t_number_seasons', $movie -> number_seasons );  //save seasons count data
        if($movie->seasons){
            update_post_meta( $post_id, '_r_t_seasons', $movie->seasons);  //save seasons data
        }

        $original_title = $movie->original_title;
        $slug = sanitize_title($original_title);
        wp_update_post(array ('ID'=> $post_id,'post_title' => $original_title, 'post_name' => $slug));
        if($original_title !== $movie->title){
            update_post_meta( $post_id, '_r_f_original_title', $original_title );
        }

        $title = $movie->title;
        update_post_meta( $post_id, '_headline', $title );

        $year = $movie->year;
        update_post_meta( $post_id, '_r_f_year', $year );

        $imdbid = $movie-> imdb_id;
        update_post_meta( $post_id, '_r_f_imdb_id', $imdbid );

        // $language = $movie-> languages[0];
        // update_post_meta( $post_id, '_r_f_language', $language );

        $runtime = $movie-> runtime[0];
        update_post_meta($post_id,'_r_f_runtime',$runtime);

        $overview = $movie-> overview;
        update_post_meta( $post_id, '_r_f_overview', $overview );
        

//Creators
$move_data = Movies::$TMDB->getTVShow($tmdb_id);

$creators = $move_data->get($item = 'created_by');
foreach($creators as $creator){
    $term_id = term_exists( $creator['name'], 'creators' );
    if($term_id){
        wp_set_object_terms( $post_id, $creator['name'], 'creators', true );
    }else{
        wp_insert_term(
            $creators,
        'creators',
        array(
            'description' => '',
            'slug'        => $creators,
            'parent'      => '',
        ));
        $term_id = term_exists( $creator['name'], 'creators' );
        wp_set_object_terms( $post_id, $creator['name'], 'creators', true );
    }
}


//CAST
        $cast = $move_data->get($item = 'credits')['cast'];
        foreach($cast as $cas){
            $term_id = term_exists( $cas['name'], 'cast' );
            if($term_id){
                wp_set_object_terms( $post_id, $cas['name'], 'cast', true );
            }else{
                wp_insert_term(
                    $cas['name'],
                'cast',
                array(
                    'description' => '',
                    'slug'        => $cas['name'],
                    'parent'      => '',
                ));
                $term_id = term_exists( $cas['name'], 'cast' );
                wp_set_object_terms( $post_id, $cas['name'], 'cast', true );
            }
        }

//Languages
        $languages = $move_data->get($item ='spoken_languages');
        foreach($languages as $language){
            $term_id = term_exists( $language['name'], 'languages' );
            if($term_id){
                wp_set_object_terms( $post_id, $language['name'], 'languages', true );
            }else{
                wp_insert_term(
                    $language['name'],
                'languages',
                array(
                    'description' => '',
                    'slug'        => $language['name'],
                    'parent'      => '',
                ));
                $term_id = term_exists( $language['name'], 'languages' );
                wp_set_object_terms( $post_id, $language['name'], 'languages', true );
            }
        }

//Genres
        $genres = $move_data->getGenresName();
        foreach($genres as $genre){
            $term_id = term_exists( $genre['name'], 'film_review_categories' );
            if($term_id){
                wp_set_post_terms( $post_id, array( $term_id['term_id'] ), 'film_review_categories', true );
            }else{
                wp_insert_term(
                $genre['name'],
                'film_review_categories',
                array(
                    'description' => '',
                    'slug'        => $genre['name'],
                    'parent'      => '',
                ));
                $term_id = term_exists( $genre['name'], 'film_review_categories' );
                wp_set_post_terms( $post_id, array( $term_id['term_id'] ), 'film_review_categories', true );
            }
        }

        // Fetch movie data back from WP
        $movie = new Moviee( $post_id );

        

        // Attach media
            $attach_ids = get_attach_ids_for_post( $post_id );
            if($movie->backdrop_path) {
                $attach_id = attach_media_to_post( $post_id, $movie->backdrop_path, true, $movie->title );
                $attach_ids[] = $attach_id;
            }
            if($movie->poster_path) {
                $attach_id = attach_media_to_post( $post_id, $movie->poster_path, false, $movie->title );
                $attach_ids[] = $attach_id;
                update_post_meta( $post_id, '_r_f_poster',  $movie->poster_path );
            }
            update_post_meta( $post_id, '_zmovies_attach_ids', implode(",", $attach_ids) );


    }
    $data_saved = true;

}

?>