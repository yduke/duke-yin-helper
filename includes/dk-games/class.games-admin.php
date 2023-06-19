<?php
//  add_action('admin_menu', 'register_add_game_menu');

// function register_add_game_menu(){
//     add_menu_page(__('Games Import Tool','duke-yin-helper'), __('Game Tool','duke-yin-helper') , 'publish_posts', 'games', 'import_game_page','dashicons-games',9 );

// }

// function import_game_page(){

    
// }


class Games_Admin {

	public static function init() {
	    add_action('admin_menu', array( 'Games_Admin', 'menu' ));
		if(!Games::is_configured()) {
		    // add_action( 'admin_notices', array( 'Games_Admin', 'display_notices' ) );
		    return;
		}
	}

	public static function menu() {
        add_menu_page(__('Games Import Tool','duke-yin-helper'), __('Games Tool','duke-yin-helper') , 'publish_posts', 'games', array( 'Games_Admin', 'import_page' ),'dashicons-games',9 );
        add_submenu_page('games', 'Games Import Tool',__( 'Import Games','duke-yin-helper'), 'publish_posts', 'games', array( 'Games_Admin', 'import_page' ) );
        add_submenu_page('games', 'Games Plugin Tools', __('Tools','duke-yin-helper'), 'publish_posts', 'games-tools', array( 'Games_Admin', 'tools_page' ) );
	}

	public static function override_display_notices() {
	    remove_action( 'admin_notices', array( 'Games_Admin', 'display_notices' ) );
	}

	public static function display_notices() {
	    $context = array(
	        'notice' => __('The Games plugin has not been configured, enter your API key in Appearance, DukeYin theme options.','duke-yin-helper')
	    );
	    Games::render_template('admin/notice.php', $context);
	}
	
	public static function import_page() {
	    Games::render_template('admin/import.php', array());
	}
	
	public static function tools_page() {
	    Games::render_template('admin/tools.php', array());
	}

}