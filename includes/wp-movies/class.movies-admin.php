<?php

class Movies_Admin {

	public static function init() {
	    add_action('admin_menu', array( 'Movies_Admin', 'menu' ));
		if(!Movies::is_configured()) {
		    add_action( 'admin_notices', array( 'Movies_Admin', 'display_notices' ) );
		    return;
		}
	}

	public static function menu() {
        add_menu_page(__('Movies Import Tool','duke-yin-helper'), __('Movies Tool','duke-yin-helper') , 'publish_posts', 'movies', array( 'Movies_Admin', 'import_page' ),'dashicons-editor-video',9 );
        add_submenu_page('movies', 'Movies Import Tool',__( 'Import','duke-yin-helper'), 'publish_posts', 'movies', array( 'Movies_Admin', 'import_page' ) );
	    $hook_suffix = add_submenu_page('movies', 'Movies Plugin Configuration', __('Settings','duke-yin-helper'), 'manage_options', 'movies-settings', array( 'Movies_Admin', 'settings_page' ) );
        add_action( 'load-' . $hook_suffix , array( 'Movies_Admin', 'override_display_notices' ) );
        add_submenu_page('movies', 'Movies Plugin Tools', __('Tools','duke-yin-helper'), 'publish_posts', 'movies-tools', array( 'Movies_Admin', 'tools_page' ) );
	}

	public static function override_display_notices() {
	    remove_action( 'admin_notices', array( 'Movies_Admin', 'display_notices' ) );
	}

	public static function display_notices() {
	    $context = array(
	        'notice' => __('The Movies plugin has not been configured.','duke-yin-helper')
	    );
	    Movies::render_template('admin/notice.php', $context);
	}
	
	public static function import_page() {
	    Movies::render_template('admin/import.php', array());
	}

	public static function settings_page() {
	    Movies::render_template('admin/settings.php', array());
	}
	
	public static function tools_page() {
	    Movies::render_template('admin/tools.php', array());
	}

}

?>