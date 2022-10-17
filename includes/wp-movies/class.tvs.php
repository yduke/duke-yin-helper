<?php

class TV {
    
    public $tmdb_id;
    public $title;
    public $year;
    public $backdrop_path;
    public $poster_path;
    
    public $genres;
    public $imdb_id;
    public $runtime;
    public $languages;
    public $overview;
    
    private static $POSTER_WIDTH;
    private static $BACKDROP_WIDTH;
    
    function __construct($data) {
        self::$POSTER_WIDTH = 'w500';
        self::$BACKDROP_WIDTH = 'w1280';

        if ( is_numeric($data) ) { // post ID *not* a TMDb ID
            $data = get_post_meta( $data, '_zmovies_json', true );
        }

        if( is_string($data) ) { // JSON
            $data = json_decode(base64_decode($data), true);
        }
    
        if(!empty($data)) { // array
            foreach($data as $key => $value) {
                $this->{$key} = $value;
            }
        }
    }

    public function poster( $size=false, $force_copy=false ) {
        $dukeyin_options = get_site_option( 'options-page', true, true);
        if($dukeyin_options['tmdb-en-poster']){
            if(!$this->poster_path_alt) return false;
            if(!$size) $size = self::$POSTER_WIDTH;
            return Tvs::tmdb_image($this->poster_path_alt, $size, $force_copy);
        }else{
            if(!$this->poster_path) return false;
            if(!$size) $size = self::$POSTER_WIDTH;
            return Tvs::tmdb_image($this->poster_path, $size, $force_copy);
        }
    }
    
    public function backdrop( $size=false, $force_copy=false ) {
        if(!$this->backdrop_path) return false;
        if(!$size) $size = self::$BACKDROP_WIDTH;
        return Tvs::tmdb_image($this->backdrop_path, $size, $force_copy);
    }
    
    public function json($copy_images=false, $copy_poster=false) {
        $data = array(
            'tmdb_id' => $this->tmdb_id,
            'title' => $this->title,
            'year' => $this->year,
            'backdrop_path' => false,
            'poster_path' => false,
            'genres' => $this->genres,
            'imdb_id' => $this->imdb_id,
            'runtime' => $this->runtime,
            'languages' => $this->languages,
            // 'overview' => $this->overview
        );
        if($copy_images) {
            $data['backdrop_path'] = self::backdrop();
        }
        if($copy_poster) {
            $data['poster_path'] = self::poster();
        }
        return base64_encode(json_encode($data));
    }

}

class Tvs {
    
    public static $TMDB = false;

	public static function init() {
		if(Tvs::is_configured()) {
		    self::connect_to_tmdb();
		}
	}

    public static function connect_to_tmdb() {
        if(Tvs::is_configured()) {
		    require("tmdb-api.php");
		    self::$TMDB =new TMDB();
		}
    }

    public static function TMDb( $tmdb_id ) {
        $data = self::data_from_tmdb_basic_search(
            array('id' => $tmdb_id),
            $get_detail=true
        );
        return new TV($data);
    }

	public static function render_template( $template_name, $context ) {
	    $file_path = MOVIES__PLUGIN_DIR . '/templates/' . $template_name;
	    extract($context);
	    require($file_path);
	}

	public static function is_configured() {
        $dukeyin_options = get_site_option( 'options-page', true, true);
        $key = $dukeyin_options['tmdb-key'];
	    if(!isset($key)) {
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
	    if($size == 'poster') { $size = TV::POSTER_WIDTH; }
	    if($size == 'backdrop') { $size = TV::BACKDROP_WIDTH; }
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
	    if($size == 'poster') { $size = TV::POSTER_WIDTH; }
	    if($size == 'backdrop') { $size = TV::BACKDROP_WIDTH; }
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
	        $result = self::$TMDB->getTVShow($result['id'],'images');
	    }
	    if($result->get($item = 'first_air_date')) {
	        if(strlen($result->get($item = 'first_air_date')) < 4) {
	            $release_year = false;
	        } else {
	            $release_year = substr($result->get($item = 'first_air_date'), 0, 4);
	            $release_date = $result->get($item = 'first_air_date');
	        }
	    }
	    $data = array(
                'tmdb_id' => $result->get($item = 'id'),
                'year' => $release_year,
                'date' => $release_date,
                'backdrop_path' => $result->get($item = 'backdrop_path'),
                'poster_path' => $result->get($item = 'poster_path'),
                'logo_path' => $result->get($item = 'images')['logos'][0]['file_path'],
                'poster_path_alt' => $result->get($item = 'images')['posters'][0]['file_path'],
                'title' => $result->get($item = 'name'),
                'original_title' => $result->get($item = 'original_name'),
                'genres' => array(),
                'imdb_id' => false,
                'runtime' => false,
                'languages' => array(),
                'overview' => false
            );
	    if($get_detail) {
            $ge = $result->get($item = 'genres');
	        if(isset($ge)) {
                    foreach($ge as $genre) {
                        $data['genres'][] = $genre['name'];
                    }
                }
            $spl = $result->get($item = 'spoken_languages');
            if(isset($spl )) {
                    foreach($spl as $language) {
                        $data['languages'][] = $language['name'];
                    }
                }
	        $data['imdb_id'] = $result->get($item = 'imdb_id');
	        $data['runtime'] = $result->get($item = 'episode_run_time');
	        $data['overview'] = $result->get($item = 'overview');
	    }
	    return $data;
	}

}

?>
