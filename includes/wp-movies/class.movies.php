<?php

require "class.movie.php";

class Movies {
    
    public static $TMDB = false;
    private static $TMDB_API_KEY = false;

	public static function init() {
		if(Movies::is_configured()) {
		    self::connect_to_tmdb();
		}
	}

    public static function connect_to_tmdb() {
        if(self::$TMDB_API_KEY) {
		    require("tmdb.v3.php");
		    self::$TMDB = new TMDBv3(self::$TMDB_API_KEY);
		}
    }

    public static function TMDb( $tmdb_id ) {
        $data = self::data_from_tmdb_basic_search(
            array('id' => $tmdb_id),
            $get_detail=true
        );
        return new Movie($data);
    }

	public static function render_template( $template_name, $context ) {
	    $file_path = MOVIES__PLUGIN_DIR . '/templates/' . $template_name;
	    extract($context);
	    require($file_path);
	}

	public static function is_configured() {
	    self::$TMDB_API_KEY = get_option('zmovies_tmdb_key');
	    if(!self::$TMDB_API_KEY) {
	        return false;
	    }
	    return true;
	}

    public static function webpImage($source, $quality = 75, $removeOld = false)
    {
        $dir = pathinfo($source, PATHINFO_DIRNAME);
        $name = pathinfo($source, PATHINFO_FILENAME);
        $destination = $dir . DIRECTORY_SEPARATOR . $name . '.webp';
        $info = getimagesize($source);
        $isAlpha = false;
        if ($info['mime'] == 'image/jpeg')
            $image = imagecreatefromjpeg($source);
        elseif ($isAlpha = $info['mime'] == 'image/gif') {
            $image = imagecreatefromgif($source);
        } elseif ($isAlpha = $info['mime'] == 'image/png') {
            $image = imagecreatefrompng($source);
        } else {
            return $source;
        }
        if ($isAlpha) {
            imagepalettetotruecolor($image);
            imagealphablending($image, true);
            imagesavealpha($image, true);
        }
        imagewebp($image, $destination, $quality);

        if ($removeOld)
            unlink($source);

        return $destination;
    }

	public static function tmdb_image( $file_path, &$size='original', $force_copy=false ) {
	    if($size == 'poster') { $size = Movie::POSTER_WIDTH; }
	    if($size == 'backdrop') { $size = Movie::BACKDROP_WIDTH; }
        $wp_upload_dir = wp_upload_dir();
        
        $file_destination = '/tmdb/' . $size . $file_path;
        if( !file_exists($wp_upload_dir['basedir'] . $file_destination) || $force_copy ) {
            self::copy_tmdb_image( $file_path, $size ); 
        }
        $img_path = $wp_upload_dir['basedir'] . '/tmdb/' . $size . $file_path;

        // $image_url = $wp_upload_dir['baseurl'] . $file_destination;
        $webp_url = self:: webpImage( $img_path, 75, true );
        $image_url = $wp_upload_dir['baseurl']  . '/tmdb/' . $size .'/'. basename($webp_url);
        return $image_url;
	}
	
	public static function copy_tmdb_image( $file_path, &$size='original' ) {
	    if($size == 'poster') { $size = Movie::POSTER_WIDTH; }
	    if($size == 'backdrop') { $size = Movie::BACKDROP_WIDTH; }
	    $image_url = self::$TMDB->getImageURL($size) . $file_path;
	    $image_to_upload = file_get_contents( $image_url );
            $wp_upload_dir = wp_upload_dir();
            $tmdb_upload_dir = $wp_upload_dir['basedir'] . '/tmdb';
            if( !file_exists( $tmdb_upload_dir ) ) {
                mkdir( $tmdb_upload_dir );
            }
            $size_upload_dir = $tmdb_upload_dir . '/' . $size;
            if( !file_exists( $size_upload_dir ) ) {
                mkdir( $size_upload_dir );
            }
            $image_path = $size_upload_dir . $file_path;
            $fh = fopen( $image_path, 'w' );
            fwrite( $fh, $image_to_upload );
            fclose( $fh );
	}

	public static function data_from_tmdb_basic_search( $result, $get_detail=false ) {
	    if($get_detail) {
	        $result = self::$TMDB->movieDetail($result['id']);
	    }
	    if($result['release_date']) {
	        if(strlen($result['release_date']) < 4) {
	            $release_year = false;
	        } else {
	            $release_year = substr($result['release_date'], 0, 4);
	            $release_date = $result['release_date'];
	        }
	    }
	    $data = array(
                'tmdb_id' => $result['id'],
                'year' => $release_year,
                'date' => $release_date,
                'backdrop_path' => $result['backdrop_path'],
                'poster_path' => $result['poster_path'],
                'title' => $result['title'],
                'original_title' => $result['original_title'],
                'genres' => array(),
                'imdb_id' => false,
                'runtime' => false,
                'languages' => array(),
                'overview' => false
            );
	    if($get_detail) {
	        if(isset($result['genres'])) {
                    foreach($result['genres'] as $genre) {
                        $data['genres'][] = $genre['name'];
                    }
                }
            if(isset($result['spoken_languages'])) {
                    foreach($result['spoken_languages'] as $language) {
                        $data['languages'][] = $language['name'];
                    }
                }
	        $data['imdb_id'] = $result['imdb_id'];
	        $data['runtime'] = $result['runtime'];
	        $data['overview'] = $result['overview'];
	    }
	    return $data;
	}
	
	public static function plugin_activation() {
	    self::restore_defaults();
	}
	
	public static function plugin_deactivation() {
	    
	}
	
	public static function restore_defaults($exclude=array('zmovies_tmdb_key')) {
	    foreach(self::$settings as $opt_name => $opt) {
	        if(!in_array($opt_name, $exclude)) {
	            update_option( $opt_name, $opt['default'] );
	        }
	    }
	}
	
	public static function clear_all_data() {
	    $posts = self::posts_with_movie_data();
	    foreach($posts as $post) {
	        self::clear_data_for_post( $post->ID );
	    }
	}
    public static function clear_zero(){
        self::clear_zero_tax('cast');
        self::clear_zero_tax('screenplay');
        self::clear_zero_tax('languages');
        self::clear_zero_tax('directors');
        self::clear_zero_tax('film_review_categories');
    }

    public static function clear_zero_tax( $tax ){
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
	
	public static function clear_data_for_tmdb_id( $tmdb_id ) {
	    $posts = self::posts_with_tmdb_id( $tmdb_id );
	    foreach($posts as $post) {
	        self::clear_data_for_post( $post->ID );
	    }
	}
	
	public static function clear_data_for_post( $post_id ) {
	    $tmdb_id = get_post_meta( $post_id, 'tmdb_id', true ); 
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


	    /* Delete imported media if it's not attached to other posts.
	    TODO: Remove attachment metadata from this post without
	    deleting image file (unless image file isn't needed anymore). */
	    $other_posts = self::posts_with_tmdb_id( $tmdb_id );
	    if( count($other_posts) < 1 ) {
            $attach_ids = get_attach_ids_for_post( $post_id );
            foreach( $attach_ids as $attach_id ) {
                wp_delete_attachment( $attach_id );
            }
            delete_post_meta( $post_id, '_zmovies_attach_ids' );
        }
	}

    public static function posts_with_tmdb_id( $tmdb_id ) {
        $args = array(
	    'posts_per_page' => 9999,
            'meta_key'         => 'tmdb_id',
            'meta_value'       => $tmdb_id,
            'post_type'        => get_option('zmovies_post_type'),
            'suppress_filters' => true
        );
        return get_posts( $args );
    }

	public static function posts_with_movie_data() {
	    $args = array(
	        'posts_per_page' => 9999,
                'meta_key'         => 'tmdb_id',
                'post_type'        => get_option('zmovies_post_type'),
                'suppress_filters' => true
            );
            return get_posts( $args );
	}
    
    public static $settings = array(
        'zmovies_tmdb_key' => array(
            'default' => '',
            'label' => 'TMDB API Key',
            'description' => 'Your TMDb API key'
        ),
        'zmovies_language' => array(
            'default' => 'en-US',
            'label' => 'Language to download',
            'description' => 'e.g. `zh-CN` `en-US`.'
        ),
        'zmovies_poster_width' => array(
            'default' => 'w500',
            'label' => 'Default poster width',
            'description' => 'e.g. `original` `w780` `w500` `w342` `w185`. Must be supported by TMDb.'
        ),
        'zmovies_backdrop_width' => array(
            'default' => 'w1280',
            'label' => 'Default backdrop width',
            'description' => 'e.g. `original` `w1280` `w780` `w300`. Must be supported by TMDb.'
        ),
        'zmovies_post_type' => array(
            'default' => 'film_review',
            'label' => 'Post type (optional)',
            'description' => 'set to custom post type if needed'
        ),
        'zmovies_attach_media' => array(
            'default' => 'y',
            'label' => 'Attach imported media to posts',
            'description' => '`y` for yes, anything else for no'
        ),
        'zmovies_featured_image' => array(
            'default' => 'backdrop then poster',
            'label' => 'Make featured image',
            'description' => '`backdrop then poster` or `poster then backdrop`; anything else for none'
        ),
    );

}

?>
