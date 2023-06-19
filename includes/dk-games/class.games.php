<?php

require "class.game.php";

class Games {

    public static function is_configured() {
        $dukeyin_options = get_site_option( 'options-page', true, true);
        $key = $dukeyin_options['sgdb-key']??'';
	    if(!$key) {
	        return false;
	    }
	    return true;
	}

	public static function render_template( $template_name, $context ) {
	    $file_path = GAMES__PLUGIN_DIR . '/templates/' . $template_name;
	    extract($context);
	    require($file_path);
	}


}