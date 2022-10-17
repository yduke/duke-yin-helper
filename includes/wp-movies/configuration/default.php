<?php

//------------------------------------------------------------------------------
// Default Configuration
//------------------------------------------------------------------------------

// Global Configuration
$dukeyin_options = get_site_option( 'options-page', true, true);

$cnf['apikey'] = $dukeyin_options['tmdb-key'];
$cnf['lang'] = $dukeyin_options['tmdb-lang'] ?? 'en-US';
$cnf['timezone'] = 'Asia/China';
$cnf['adult'] = false;
$cnf['debug'] = false;

// Data Return Configuration - Manipulate if you want to tune your results
$cnf['appender']['movie'] = array( 'images', 'credits');
$cnf['appender']['tvshow'] = array( 'images', 'credits');
$cnf['appender']['season'] = array( 'images', 'credits');
$cnf['appender']['episode'] = array( 'images', 'credits');
$cnf['appender']['person'] = array('movie_credits', 'tv_credits', 'images');
$cnf['appender']['collection'] = array('images');
$cnf['appender']['company'] = array('movies');

?>