<?php

class Moviee {
    
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
            return Movies::tmdb_image($this->poster_path_alt, $size, $force_copy);
        }else{
            if(!$this->poster_path) return false;
            if(!$size) $size = self::$POSTER_WIDTH;
            return Movies::tmdb_image($this->poster_path, $size, $force_copy);
        }
    }
    
    public function backdrop( $size=false, $force_copy=false ) {
        if(!$this->backdrop_path) return false;
        if(!$size) $size = self::$BACKDROP_WIDTH;
        return Movies::tmdb_image($this->backdrop_path, $size, $force_copy);
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

?>