<?php

define( 'GAMES__PLUGIN_DIR', plugin_dir_path( __FILE__ )  );

require_once( GAMES__PLUGIN_DIR . 'class.games.php' );

if ( is_admin() ) {
	require_once( GAMES__PLUGIN_DIR . 'class.games-admin.php' );
	add_action( 'init', array( 'Games_Admin', 'init' ) );
}