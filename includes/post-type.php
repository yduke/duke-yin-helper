<?php
/**
 * Add some post types to the theme.
 *
 * @package DUKE_YIN
 */

/** SlideShow */
add_action('init', 'slideshow_register');

	function slideshow_register() {
		  $labels = array(
		    'name' => _x('Slidshow', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Slidshow post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All Slidshows', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add new', 'slideshow','duke-yin-helper'),
		    'add_new_item' => __('Add New slider','duke-yin-helper'),
		    'edit_item' => __('Edit','duke-yin-helper'),
		    'new_item' => __('New Slider','duke-yin-helper'),
		    'view_item' => __('View this slider','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('Not found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );

	
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> false,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 3,
			'menu_icon' => 'dashicons-slides',
        	'supports' => array('title','author','thumbnail')
        );

    	register_post_type( 'slideshow' , $args );
	}

/** portfolio */
	function portfolio_register() {
		  $labels = array(
		    'name' => _x('Portfolios', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Work', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All Portfolios', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'portfolio','duke-yin-helper'),
		    'add_new_item' => __('Add new','duke-yin-helper'),
		    'edit_item' => __('Edit work','duke-yin-helper'),
		    'new_item' => __('New work','duke-yin-helper'),
		    'view_item' => __('View this work','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('Not found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );

		$slugRule = 'portfolio';
		
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => array('slug' => 'portfolios','with_front' => true),
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 4,
			'menu_icon' => 'dashicons-grid-view',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments'),
			'taxonomies' => array('post_tag')
        );

    	register_post_type( 'portfolio' , $args ); 
	}
	
	function create_portfolio_taxonomies() {
    $labels = array(
        'name'              => _x( 'Categories', 'taxonomy general name','duke-yin-helper' ),
        'singular_name'     => _x( 'Category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search Categories','duke-yin-helper' ),
        'all_items'         => __( 'All Categories', 'duke-yin-helper'),
        'parent_item'       => __( 'Parent Category','duke-yin-helper'),
        'parent_item_colon' => __( 'Parent Category:','duke-yin-helper' ),
        'edit_item'         => __( 'Edit Category','duke-yin-helper' ),
        'update_item'       => __( 'Update Category','duke-yin-helper' ),
        'add_new_item'      => __( 'Add New Category','duke-yin-helper' ),
        'new_item_name'     => __( 'New Category Name','duke-yin-helper' ),
        'menu_name'         => __( 'Categories','duke-yin-helper' ),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'categories' ),
    );

    register_taxonomy( 'portfolio_categories', array( 'portfolio' ), $args );

}
add_action('init', 'portfolio_register');
add_action( 'init', 'create_portfolio_taxonomies', 0 );

/** Photography */
	function photo_register() {
		  $labels = array(
		    'name' => _x('Photography', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Photo post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All photos', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'photo','duke-yin-helper'),
		    'add_new_item' => __('Add Photo','duke-yin-helper'),
		    'edit_item' => __('Edit post','duke-yin-helper'),
		    'new_item' => __('New Photo','duke-yin-helper'),
		    'view_item' => __('View this Photo','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No photo found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'photos';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 5,
			'menu_icon' => 'dashicons-format-image',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments'),
			'taxonomies' => array('post_tag')
        );
    	register_post_type( 'photo' , $args );
	}
	
function create_photo_taxonomies() {
    $labels = array(
        'name'              => _x( 'Categories', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search Categories','duke-yin-helper'),
        'all_items'         => __( 'All Categories' ,'duke-yin-helper'),
        'parent_item'       => __( 'Parent Category' ,'duke-yin-helper'),
        'parent_item_colon' => __( 'Parent Category:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit Category' ,'duke-yin-helper'),
        'update_item'       => __( 'Update Category' ,'duke-yin-helper'),
        'add_new_item'      => __( 'Add New Category' ,'duke-yin-helper'),
        'new_item_name'     => __( 'New Category Name' ,'duke-yin-helper'),
        'menu_name'         => __( 'Categories' ,'duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'categories' ),
    );

    register_taxonomy( 'photo_categories', array( 'photo' ), $args );
}
add_action('init', 'photo_register');
add_action( 'init', 'create_photo_taxonomies', 0 );
	
/** Selfie */

	function selfie_register() {
		  $labels = array(
		    'name' => _x('Selfie', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Selfie post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All Selfies', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add new', 'selfie','duke-yin-helper'),
		    'add_new_item' => __('Add selfie','duke-yin-helper'),
		    'edit_item' => __('Edit post','duke-yin-helper'),
		    'new_item' => __('New selfie','duke-yin-helper'),
		    'view_item' => __('View this selfie','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('Selfie not found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'selfies';
		
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 6,
			'menu_icon' => 'dashicons-universal-access-alt',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments'),
			'taxonomies' => array('post_tag')
        );

    	register_post_type( 'selfie' , $args );
	}
function create_selfie_taxonomies() {
    $labels = array(
        'name'              => _x( 'Categories', 'taxonomy general name','duke-yin-helper' ),
        'singular_name'     => _x( 'Category', 'taxonomy singular name','duke-yin-helper' ),
        'search_items'      => __( 'Search Categories','duke-yin-helper' ),
        'all_items'         => __( 'All Categories','duke-yin-helper' ),
        'parent_item'       => __( 'Parent Category','duke-yin-helper' ),
        'parent_item_colon' => __( 'Parent Category:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit Category','duke-yin-helper' ),
        'update_item'       => __( 'Update Category','duke-yin-helper' ),
        'add_new_item'      => __( 'Add New Category','duke-yin-helper' ),
        'new_item_name'     => __( 'New Category Name','duke-yin-helper' ),
        'menu_name'         => __( 'Categories','duke-yin-helper' ),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'categories' ),
    );

    register_taxonomy( 'selfie_categories', array( 'selfie' ), $args );
}
add_action('init', 'selfie_register');
add_action( 'init', 'create_selfie_taxonomies', 0 );
	
/**Porducts*/
	function product_register() {
		  $labels = array(
		    'name' => _x('Products', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Product', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All Products', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add new', 'product','duke-yin-helper'),
		    'add_new_item' => __('Add a product for sale','duke-yin-helper'),
		    'edit_item' => __('Edit','duke-yin-helper'),
		    'new_item' => __('New product','duke-yin-helper'),
		    'view_item' => __('View this product','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('Not found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );

		$slugRule = 'product';
		
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 7,
			'menu_icon' => 'dashicons-cart',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments'),
			'taxonomies' => array('post_tag')
        );

    	register_post_type( 'product' , $args );
	}
	function create_product_taxonomies() {
    $labels = array(
        'name'              => _x( 'Categories', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search Categories','duke-yin-helper'),
        'all_items'         => __( 'All Categories','duke-yin-helper'),
        'parent_item'       => __( 'Parent Category','duke-yin-helper'),
        'parent_item_colon' => __( 'Parent Category:','duke-yin-helper'),
        'edit_item'         => __( 'Edit Category','duke-yin-helper'),
        'update_item'       => __( 'Update Category','duke-yin-helper'),
        'add_new_item'      => __( 'Add New Category','duke-yin-helper'),
        'new_item_name'     => __( 'New Category Name','duke-yin-helper'),
        'menu_name'         => __( 'Categories','duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'categories' ),
    );

    register_taxonomy( 'product_categories', array( 'product' ), $args );
}
add_action('init', 'product_register');
add_action( 'init', 'create_product_taxonomies', 0 );

/** Reviews */

//
add_action( 'admin_menu', 'register_review_menu_page' );
function register_review_menu_page() {
  // add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
  add_menu_page( null, __( 'Reviews','duke-yin-helper'), 'manage_options', 'edit.php?post_type=film_review', '', 'dashicons-edit-page', 9 );
}

////
/** Reviews - Films */
	function film_review_register() {
		  $labels = array(
		    'name' => _x('Film Reviews', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Film Review Post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All film reviews', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'review','duke-yin-helper'),
		    'add_new_item' => __('Add film review','duke-yin-helper'),
		    'edit_item' => __('Edit film review','duke-yin-helper'),
		    'new_item' => __('New film review','duke-yin-helper'),
		    'view_item' => __('View this film review','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No film review found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'film_review';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'film_review','with_front' => true),
        	'query_var' => true,
			'show_in_menu' => false,
        	'show_in_nav_menus'=> false,
			'menu_icon' => 'dashicons-format-video',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments','custom-fields'),
			'taxonomies' => array('post_tag')
        );
    	register_post_type( 'film_review' , $args );
	}
add_action('init', 'film_review_register');

function create_film_review_taxonomies() {
    $labels = array(
        'name'              => _x( 'Film&TV review categories', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Film&TV review category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search film review categories','duke-yin-helper'),
        'all_items'         => __( 'All film review categories' ,'duke-yin-helper'),
        'parent_item'       => __( 'Parent film review category' ,'duke-yin-helper'),
        'parent_item_colon' => __( 'Parent film review category:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit film review category' ,'duke-yin-helper'),
        'update_item'       => __( 'Update film review category' ,'duke-yin-helper'),
        'add_new_item'      => __( 'Add New film review category' ,'duke-yin-helper'),
        'new_item_name'     => __( 'New film review category name' ,'duke-yin-helper'),
        'menu_name'         => __( 'Film review categories' ,'duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_tagcloud'		=> false,
        'show_admin_column' => true,
        'query_var'         => true,
        'meta_box_cb'		=> true,
        'rewrite'           => array( 'slug' => 'film_review_categories' ),
    );

    register_taxonomy( 'film_review_categories', array('film_review','tvshow_review'), $args );
	
// CAST
	$label_cast = array(
	'name'              => _x( 'Cast', 'taxonomy general name','duke-yin-helper'),
	'singular_name'     => _x( 'Cast', 'taxonomy singular name','duke-yin-helper'),
	'search_items'      => __( 'Search Cast','duke-yin-helper'),
	'all_items'         => __( 'All Cast' ,'duke-yin-helper'),
	'popular_items' 	=> __( 'Popular Cast','duke-yin-helper' ),
	'parent_item' 		=> null,
    'parent_item_colon' => null,
	'edit_item'         => __( 'Edit Cast' ,'duke-yin-helper'),
	'update_item'       => __( 'Update Cast' ,'duke-yin-helper'),
	'add_new_item'      => __( 'Add New Cast' ,'duke-yin-helper'),
	'new_item_name'     => __( 'New Cast Name' ,'duke-yin-helper'),
	'separate_items_with_commas' => __( 'Separate cast with commas.','duke-yin-helper' ),
	'add_or_remove_items' => __( 'Add or remove cast','duke-yin-helper' ),
	'choose_from_most_used' => __( 'Choose from popular cast','duke-yin-helper' ),
	'menu_name'         => __( 'Cast' ,'duke-yin-helper'),
    );
	
	$arr_cast = array(
	'hierarchical'      => false, 
	'labels'            => $label_cast,
	'show_ui'           => true,
	'show_admin_column' => true,
	'show_in_rest' => true,
	'update_count_callback' => '_update_post_term_count',
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'cast' ),
    );

    register_taxonomy( 'cast', array( 'film_review','tvshow_review' ), $arr_cast );

//Director
	$label_dir = array(
	'name'              => _x( 'Directors', 'taxonomy general name','duke-yin-helper'),
	'singular_name'     => _x( 'Director', 'taxonomy singular name','duke-yin-helper'),
	'search_items'      => __( 'Search director','duke-yin-helper'),
	'all_items'         => __( 'All director' ,'duke-yin-helper'),
	'popular_items' 	=> __( 'Popular director','duke-yin-helper' ),
	'parent_item' 		=> null,
    'parent_item_colon' => null,
	'edit_item'         => __( 'Edit director' ,'duke-yin-helper'),
	'update_item'       => __( 'Update director' ,'duke-yin-helper'),
	'add_new_item'      => __( 'Add New Director' ,'duke-yin-helper'),
	'new_item_name'     => __( 'New Director Name' ,'duke-yin-helper'),
	'separate_items_with_commas' => __( 'Separate director with commas.','duke-yin-helper' ),
	'add_or_remove_items' => __( 'Add or remove director','duke-yin-helper' ),
	'choose_from_most_used' => __( 'Choose from popular director','duke-yin-helper' ),
	'menu_name'         => __( 'Director' ,'duke-yin-helper'),
    );
	
	$arr_dir = array(
	'hierarchical'      => false, 
	'labels'            => $label_dir,
	'show_ui'           => true,
	'show_admin_column' => true,
	'show_in_rest' => true,
	'update_count_callback' => '_update_post_term_count',
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'directors' ),
    );

    register_taxonomy( 'directors', array( 'film_review','tvshow_review' ), $arr_dir );
	
//Screenplay Writer
	$label_writer = array(
	'name'              => _x( 'Screenplay', 'taxonomy general name','duke-yin-helper'),
	'singular_name'     => _x( 'Screenplay', 'taxonomy singular name','duke-yin-helper'),
	'search_items'      => __( 'Search screenplay','duke-yin-helper'),
	'all_items'         => __( 'All screenplay' ,'duke-yin-helper'),
	'popular_items' 	=> __( 'Popular screenplay','duke-yin-helper' ),
	'parent_item' 		=> null,
    'parent_item_colon' => null,
	'edit_item'         => __( 'Edit screenplay' ,'duke-yin-helper'),
	'update_item'       => __( 'Update screenplay' ,'duke-yin-helper'),
	'add_new_item'      => __( 'Add New Screenplay' ,'duke-yin-helper'),
	'new_item_name'     => __( 'New Screenplay Name' ,'duke-yin-helper'),
	'separate_items_with_commas' => __( 'Separate screenplay with commas.','duke-yin-helper' ),
	'add_or_remove_items' => __( 'Add or remove screenplay','duke-yin-helper' ),
	'choose_from_most_used' => __( 'Choose from popular screenplay','duke-yin-helper' ),
	'menu_name'         => __( 'Screenplay' ,'duke-yin-helper'),
    );
	
	$arr_writer = array(
	'hierarchical'      => false, 
	'labels'            => $label_writer,
	'show_ui'           => true,
	'show_admin_column' => true,
	'show_in_rest' => true,
	'update_count_callback' => '_update_post_term_count',
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'screenplay' ),
    );

    register_taxonomy( 'screenplay', array( 'film_review','tvshow_review' ), $arr_writer );
	
//
//Language
	$label_language = array(
	'name'              => _x( 'Languages', 'taxonomy general name','duke-yin-helper'),
	'singular_name'     => _x( 'Languages', 'taxonomy singular name','duke-yin-helper'),
	'search_items'      => __( 'Search Language','duke-yin-helper'),
	'all_items'         => __( 'All languages' ,'duke-yin-helper'),
	'popular_items' 	=> __( 'Popular languages','duke-yin-helper' ),
	'parent_item' 		=> null,
    'parent_item_colon' => null,
	'edit_item'         => __( 'Edit language' ,'duke-yin-helper'),
	'update_item'       => __( 'Update language' ,'duke-yin-helper'),
	'add_new_item'      => __( 'Add New Language' ,'duke-yin-helper'),
	'new_item_name'     => __( 'New Language Name' ,'duke-yin-helper'),
	'separate_items_with_commas' => __( 'Separate languages with commas.','duke-yin-helper' ),
	'add_or_remove_items' => __( 'Add or remove Language','duke-yin-helper' ),
	'choose_from_most_used' => __( 'Choose from popular Language','duke-yin-helper' ),
	'menu_name'         => __( 'Languages' ,'duke-yin-helper'),
    );
	
	$arr_language = array(
	'hierarchical'      => false, 
	'labels'            => $label_language,
	'show_ui'           => true,
	'show_admin_column' => true,
	'show_in_rest' => true,
	'update_count_callback' => '_update_post_term_count',
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'languages' ),
    );

    register_taxonomy( 'languages', array( 'film_review','tvshow_review' ), $arr_language );
//
}
add_action( 'init', 'create_film_review_taxonomies', 0 );


function film_review_admin_menu() {
	add_submenu_page( 
		'edit.php?post_type=film_review',
		NULL,
		__( 'All Film Reviews','duke-yin-helper'),
		'manage_options',
		'edit.php?post_type=film_review',
		NULL,
		0
	);
	
	
    add_submenu_page(
		'edit.php?post_type=film_review',
		NULL,
		__( 'Film&TV categories','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=film_review_categories&post_type=film_review',
		NULL,
		7
	);
	
	add_submenu_page(
		'edit.php?post_type=film_review',
		null, __( 'Game categories','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=game_review_categories&post_type=film_review',
		null,
		8
	);
	
	add_submenu_page(
		'edit.php?post_type=film_review',
		null,
		__( 'Book categories', 'duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=book_review_categories&post_type=film_review',
		null,
		9
	);
	
	add_submenu_page(
		'edit.php?post_type=film_review',
		null, __( 'Product categories','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=product_review_categories&post_type=film_review',
		null,
		10
	);
	
	add_submenu_page(
		'edit.php?post_type=film_review',
		NULL,
		__( 'Cast','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=cast&post_type=film_review',
		NULL,
		11
	);

	add_submenu_page(
		'edit.php?post_type=film_review',
		NULL,
		__( 'Directors','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=directors&post_type=film_review',
		NULL,
		12
	);
	
	add_submenu_page(
		'edit.php?post_type=film_review',
		NULL,
		__( 'Screenplay','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=screenplay&post_type=film_review',
		NULL,
		13
	);

	add_submenu_page(
		'edit.php?post_type=film_review',
		NULL,
		__( 'Languages','duke-yin-helper'),
		'manage_options',
		'edit-tags.php?taxonomy=languages&post_type=film_review',
		NULL,
		14
	);
	
}
add_action('admin_menu', 'film_review_admin_menu'); 



/** Reviews - TVshows */
	function tvshow_review_register() {
		  $labels = array(
		    'name' => _x('TVshow Reviews', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('TVshow Review Post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All TVshow reviews', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'review','duke-yin-helper'),
		    'add_new_item' => __('Add TVshow review','duke-yin-helper'),
		    'edit_item' => __('Edit TVshow review','duke-yin-helper'),
		    'new_item' => __('New TVshow review','duke-yin-helper'),
		    'view_item' => __('View this TVshow review','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No TVshow review found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'tvshow_review';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'tvshow_review','with_front' => true),
        	'query_var' => true,
			'show_in_menu' => 'edit.php?post_type=film_review',
			// 'menu_position' => 40,
        	'show_in_nav_menus'=> false,
			'menu_icon' => 'dashicons-cover-image',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments','custom-fields'),
			'taxonomies' => array('post_tag')
        );
    	register_post_type( 'tvshow_review' , $args );
	}
add_action('init', 'tvshow_review_register');


/** Reviews - Games */
	function game_review_register() {
		  $labels = array(
		    'name' => _x('Game Reviews', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Game Review Post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All game reviews', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'review','duke-yin-helper'),
		    'add_new_item' => __('Add game review','duke-yin-helper'),
		    'edit_item' => __('Edit game review','duke-yin-helper'),
		    'new_item' => __('New game review','duke-yin-helper'),
		    'view_item' => __('View this game review','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No game review found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'game_review';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'game_review','with_front' => true),
        	'query_var' => true,
			'show_in_menu' => 'edit.php?post_type=film_review',
			// 'menu_position' => 80,
        	'show_in_nav_menus'=> false,
			'menu_icon' => 'dashicons-games',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments','custom-fields'),
			'taxonomies' => array('post_tag')
        );
    	register_post_type( 'game_review' , $args );
	}
add_action('init', 'game_review_register');

function create_game_review_taxonomies() {
    $labels = array(
        'name'              => _x( 'Game review categories', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Game review category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search game review categories','duke-yin-helper'),
        'all_items'         => __( 'All game review categories' ,'duke-yin-helper'),
        'parent_item'       => __( 'Parent game review category' ,'duke-yin-helper'),
        'parent_item_colon' => __( 'Parent game review category:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit game review category' ,'duke-yin-helper'),
        'update_item'       => __( 'Update game review category' ,'duke-yin-helper'),
        'add_new_item'      => __( 'Add New game review category' ,'duke-yin-helper'),
        'new_item_name'     => __( 'New game review category name' ,'duke-yin-helper'),
        'menu_name'         => __( 'Game review categories' ,'duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_tagcloud'		=> false,
        'show_admin_column' => true,
        'query_var'         => true,
        'meta_box_cb'		=> true,
        'rewrite'           => array( 'slug' => 'game_review_categories' ),
    );

    register_taxonomy( 'game_review_categories', array('game_review'), $args );
}
add_action( 'init', 'create_game_review_taxonomies', 0 );

function game_review_admin_menu() { 

}
add_action('admin_menu', 'game_review_admin_menu'); 



/** Reviews - Books */
	function book_review_register() {
		  $labels = array(
		    'name' => _x('Book Reviews', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Book Review Post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All book reviews', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'review','duke-yin-helper'),
		    'add_new_item' => __('Add book review','duke-yin-helper'),
		    'edit_item' => __('Edit book review','duke-yin-helper'),
		    'new_item' => __('New book review','duke-yin-helper'),
		    'view_item' => __('View this book review','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No book review found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'book_review';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'book_review','with_front' => true),
        	'query_var' => true,
			'show_in_menu' => 'edit.php?post_type=film_review',
        	'show_in_nav_menus'=> false,
        	'menu_position' => 20,
			'menu_icon' => 'dashicons-book',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments','custom-fields'),
			'taxonomies' => array('post_tag')
        );
    	register_post_type( 'book_review' , $args );
	}
add_action('init', 'book_review_register');

function create_book_review_taxonomies() {
    $labels = array(
        'name'              => _x( 'Book review categories', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Book review category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search book review categories','duke-yin-helper'),
        'all_items'         => __( 'All book review categories' ,'duke-yin-helper'),
        'parent_item'       => __( 'Parent book review category' ,'duke-yin-helper'),
        'parent_item_colon' => __( 'Parent book review category:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit book review category' ,'duke-yin-helper'),
        'update_item'       => __( 'Update book review category' ,'duke-yin-helper'),
        'add_new_item'      => __( 'Add New book review category' ,'duke-yin-helper'),
        'new_item_name'     => __( 'New book review category name' ,'duke-yin-helper'),
        'menu_name'         => __( 'Book review categories' ,'duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_tagcloud'		=> false,
        'show_admin_column' => true,
        'query_var'         => true,
        'meta_box_cb'		=> true,
        'rewrite'           => array( 'slug' => 'book_review_categories' ),
    );

    register_taxonomy( 'book_review_categories', array('book_review'), $args );
}
add_action( 'init', 'create_book_review_taxonomies', 0 );

function book_review_admin_menu() { 

}
add_action('admin_menu', 'book_review_admin_menu'); 

/** Reviews - Product */
	function product_review_register() {
		  $labels = array(
		    'name' => _x('Product Reviews', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Product Review Post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All product reviews', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'review','duke-yin-helper'),
		    'add_new_item' => __('Add product review','duke-yin-helper'),
		    'edit_item' => __('Edit product review','duke-yin-helper'),
		    'new_item' => __('New product review','duke-yin-helper'),
		    'view_item' => __('View this product review','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No product review found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'product_review';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'product_review','with_front' => true),
        	'query_var' => true,
			'show_in_menu' => 'edit.php?post_type=film_review',
			// 'menu_position' => 50,
        	'show_in_nav_menus'=> false,
			'menu_icon' => 'dashicons-products',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments','custom-fields'),
			'taxonomies' => array('post_tag')
        );
    	register_post_type( 'product_review' , $args );
	}
add_action('init', 'product_review_register');

function create_product_review_taxonomies() {
    $labels = array(
        'name'              => _x( 'Product review categories', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Product review category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search product review categories','duke-yin-helper'),
        'all_items'         => __( 'All product review categories' ,'duke-yin-helper'),
        'parent_item'       => __( 'Parent product review category' ,'duke-yin-helper'),
        'parent_item_colon' => __( 'Parent product review category:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit product review category' ,'duke-yin-helper'),
        'update_item'       => __( 'Update product review category' ,'duke-yin-helper'),
        'add_new_item'      => __( 'Add New product review category' ,'duke-yin-helper'),
        'new_item_name'     => __( 'New product review category name' ,'duke-yin-helper'),
        'menu_name'         => __( 'Product review categories' ,'duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_tagcloud'		=> false,
        'show_admin_column' => true,
        'query_var'         => true,
        'meta_box_cb'		=> true,
        'rewrite'           => array( 'slug' => 'product_review_categories' ),
    );

    register_taxonomy( 'product_review_categories', array('product_review'), $args );
}
add_action( 'init', 'create_product_review_taxonomies', 0 );

function product_review_admin_menu() { 

}
add_action('admin_menu', 'product_review_admin_menu'); 


/* testimonial */
	function testimonial_register() {
		  $labels = array(
		    'name' => _x('Testimonials', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Testimonial post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All testimonials', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'testimonial','duke-yin-helper'),
		    'add_new_item' => __('Add Testimonial','duke-yin-helper'),
		    'edit_item' => __('Edit Testimonial','duke-yin-helper'),
		    'new_item' => __('New Testimonial','duke-yin-helper'),
		    'view_item' => __('View this Testimonials','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No testimonial found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'testimonial';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> false,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'testimonial','with_front' => true),
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 9,
			'menu_icon' => 'dashicons-format-status',
        	'supports' => array('title','thumbnail','excerpt','custom-fields'),
			'exclude_from_search'   => true,

        );
    	register_post_type( 'testimonial' , $args );
	}
	
add_action('init', 'testimonial_register');


/* Service type */
	function service_type_register() {
		  $labels = array(
		    'name' => _x('Service type', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Service type post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All service type', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'service-type','duke-yin-helper'),
		    'add_new_item' => __('Add Service type','duke-yin-helper'),
		    'edit_item' => __('Edit Service type','duke-yin-helper'),
		    'new_item' => __('New Service type','duke-yin-helper'),
		    'view_item' => __('View this service type','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No service type found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'service-type';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> false,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'service-type','with_front' => true),
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 10,
			'menu_icon' => 'dashicons-coffee',
        	'supports' => array('title','thumbnail','excerpt','custom-fields'),
			'exclude_from_search'   => true,

        );
    	register_post_type( 'service-type' , $args );
	}
	
add_action('init', 'service_type_register');

/* Music type */

	function music_type_register() {
		  $labels = array(
		    'name' => _x('Music', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Music post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All music', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'music','duke-yin-helper'),
		    'add_new_item' => __('Add Music','duke-yin-helper'),
		    'edit_item' => __('Edit Music','duke-yin-helper'),
		    'new_item' => __('New Music','duke-yin-helper'),
		    'view_item' => __('View this music type','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No music found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'music';
    	$args = array(
        	'labels' => $labels,
        	'public' => false,
        	'show_ui' => true,
			'show_in_rest'=> false,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => false,
			'rewrite' => array('slug' => 'music','with_front' => true,'feeds' => false),
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 11,
			'menu_icon' => 'dashicons-format-audio',
        	'supports' => array('title'),
			'exclude_from_search'   => true
        );
    	register_post_type( 'music' , $args );
	}
add_action('init', 'music_type_register');

function create_music_taxonomies() {
//Create Artists "tags"
	$labelst = array(
	'name'              => _x( 'Artists', 'taxonomy general name','duke-yin-helper'),
	'singular_name'     => _x( 'Artist', 'taxonomy singular name','duke-yin-helper'),
	'search_items'      => __( 'Search Artists','duke-yin-helper'),
	'all_items'         => __( 'All Artists' ,'duke-yin-helper'),
	'popular_items' 	=> __( 'Popular Artists','duke-yin-helper' ),
	'parent_item' 		=> null,
    'parent_item_colon' => null,
	'edit_item'         => __( 'Edit Artist' ,'duke-yin-helper'),
	'update_item'       => __( 'Update Artist' ,'duke-yin-helper'),
	'add_new_item'      => __( 'Add New Artist' ,'duke-yin-helper'),
	'new_item_name'     => __( 'New Artist Name' ,'duke-yin-helper'),
	'separate_items_with_commas' => __( 'Separate artists with commas.','duke-yin-helper' ),
	'add_or_remove_items' => __( 'Add or remove artists','duke-yin-helper' ),
	'choose_from_most_used' => __( 'Choose from popular artists','duke-yin-helper' ),
	'menu_name'         => __( 'Artists' ,'duke-yin-helper'),
    );
	
	$argst = array(
	'hierarchical'      => false, 
	'labels'            => $labelst,
	'show_ui'           => true,
	'show_admin_column' => true,
	'update_count_callback' => '_update_post_term_count',
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'music-artist' ),
    );

    register_taxonomy( 'music_artists', array( 'music' ), $argst );
	
//Create Genre "tags"
	$labelsg = array(
	'name'              => _x( 'Genres', 'taxonomy general name','duke-yin-helper'),
	'singular_name'     => _x( 'Genre', 'taxonomy singular name','duke-yin-helper'),
	'search_items'      => __( 'Search Genres','duke-yin-helper'),
	'all_items'         => __( 'All Genres' ,'duke-yin-helper'),
	'popular_items' 	=> __( 'Popular Genres','duke-yin-helper' ),
	'parent_item' 		=> null,
    'parent_item_colon' => null,
	'edit_item'         => __( 'Edit Genre' ,'duke-yin-helper'),
	'update_item'       => __( 'Update Genre' ,'duke-yin-helper'),
	'add_new_item'      => __( 'Add New Genre' ,'duke-yin-helper'),
	'new_item_name'     => __( 'New Genre Name' ,'duke-yin-helper'),
	'separate_items_with_commas' => __( 'Separate genres with commas.','duke-yin-helper' ),
	'add_or_remove_items' => __( 'Add or remove genres','duke-yin-helper' ),
	'choose_from_most_used' => __( 'Choose from popular genres','duke-yin-helper' ),
	'menu_name'         => __( 'Genres' ,'duke-yin-helper'),
    );
	
	$argsg = array(
	'hierarchical'      => false, 
	'labels'            => $labelsg,
	'show_ui'           => true,
	'show_admin_column' => true,
	'update_count_callback' => '_update_post_term_count',
	'query_var'         => true,
	'rewrite'           => array( 'slug' => 'music-genres' ),
    );

    register_taxonomy( 'music_genres', array( 'music' ), $argsg );
	
//Create Music lists "Categories"
    $labels = array(
        'name'              => _x( 'Music lists', 'taxonomy general name','duke-yin-helper'),
        'singular_name'     => _x( 'Music list', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search Music lists','duke-yin-helper'),
        'all_items'         => __( 'All Music lists' ,'duke-yin-helper'),
        'parent_item'       => __( 'Parent Music List' ,'duke-yin-helper'),
        'parent_item_colon' => __( 'Parent Music List:' ,'duke-yin-helper'),
        'edit_item'         => __( 'Edit Music List' ,'duke-yin-helper'),
        'update_item'       => __( 'Update Music List' ,'duke-yin-helper'),
        'add_new_item'      => __( 'Add New Music List' ,'duke-yin-helper'),
        'new_item_name'     => __( 'New Music List Name' ,'duke-yin-helper'),
        'menu_name'         => __( 'Music lists' ,'duke-yin-helper'),
    );

    $args = array(
        'hierarchical'      => true, 
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'music-lists' ),
    );
	register_taxonomy( 'music_taxonomies', array( 'music' ), $args );



}
add_action( 'init', 'create_music_taxonomies', 0 );

//allow category has html tags
remove_filter( 'pre_term_description', 'wp_filter_kses' );
remove_filter( 'pre_link_description', 'wp_filter_kses' );
remove_filter( 'pre_link_notes', 'wp_filter_kses' );
remove_filter( 'term_description', 'wp_kses_data' );

//Music categories image
add_action('music_taxonomies_add_form_fields', 'add_term_image', 10, 2);
add_action('music_artists_add_form_fields', 'add_term_image', 10, 2);
add_action('music_genres_add_form_fields', 'add_term_image', 10, 2);
function add_term_image($taxonomy){
    ?>
    <div class="form-field term-image-wrap">
        <label for=""><?php _e('Cover Image','duke-yin-helper') ?></label>
        <input type="text" name="txt_upload_image" id="txt_upload_image" value="">
        <input type="button" id="upload_image_btn" class="button" value="<?php _e('Upload an Image','duke-yin-helper') ?>" />
		<p><?php _e('The cover image similar as "featured image" but for a music list, a genre or an artist.','duke-yin-helper') ?></p>
    </div>
    <?php
}

add_action('created_music_taxonomies', 'save_term_image', 10, 2);
add_action('created_music_artists', 'save_term_image', 10, 2);
add_action('created_music_genres', 'save_term_image', 10, 2);
function save_term_image($term_id, $tt_id) {
    if (isset($_POST['txt_upload_image']) && '' !== $_POST['txt_upload_image']){
        $group = esc_url($_POST['txt_upload_image']);
        add_term_meta($term_id, 'term_image', $group, true);
    }
}

add_action('music_taxonomies_edit_form_fields', 'edit_image_upload', 10, 2);
add_action('music_artists_edit_form_fields', 'edit_image_upload', 10, 2);
add_action('music_genres_edit_form_fields', 'edit_image_upload', 10, 2);
function edit_image_upload($term, $taxonomy) {
    // get current group
    $txt_upload_image = get_term_meta($term->term_id, 'term_image', true);
?>
    <table class="form-table" role="presentation"><tbody><tr class="form-field term-image-wrap">
        <th scope="row"><label for=""><?php _e('Cover Image','duke-yin-helper') ?></label></th>
        <td>
		<img src="<?php echo esc_url($txt_upload_image) ?>" style="max-width:150px"><br>
		<input type="text" name="txt_upload_image" id="txt_upload_image" value="<?php echo esc_url($txt_upload_image) ?>" style="width: 60%">
        <input type="button" id="upload_image_btn" class="button" value="<?php _e('Upload an Image','duke-yin-helper') ?>" />
		<p><?php _e('The cover image similar as "featured image" but for a music list, a genre or an artist.','duke-yin-helper') ?></p></td>
    </tr></tbody></table>
	
<?php
}

add_action('edited_music_taxonomies', 'update_image_upload', 10, 2);
add_action('edited_music_artists', 'update_image_upload', 10, 2);
add_action('edited_music_genres', 'update_image_upload', 10, 2);
function update_image_upload($term_id, $tt_id) {
    if (isset($_POST['txt_upload_image']) && '' !== $_POST['txt_upload_image']){
        $group = esc_url($_POST['txt_upload_image']);
        update_term_meta($term_id, 'term_image', $group);
    }
}


/** Videos */
	function videos_register() {
		  $labels = array(
		    'name' => _x('Videos', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Video Post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All Videos', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'video','duke-yin-helper'),
		    'add_new_item' => __('Add new Video','duke-yin-helper'),
		    'edit_item' => __('Edit video','duke-yin-helper'),
		    'new_item' => __('New video','duke-yin-helper'),
		    'view_item' => __('View this video','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('Not found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );

		$slugRule = 'videos';
		
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => array('slug' => 'videos','with_front' => true),
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 12,
			'menu_icon' => 'dashicons-video-alt2',
        	'supports' => array('title','author','thumbnail','excerpt','comments'),
			'taxonomies' => array('post_tag')
        );

    	register_post_type( 'videos' , $args ); 
	}
	
	function create_video_taxonomies() {
    $labels = array(
        'name'              => _x( 'Categories', 'taxonomy general name','duke-yin-helper' ),
        'singular_name'     => _x( 'Category', 'taxonomy singular name','duke-yin-helper'),
        'search_items'      => __( 'Search Categories','duke-yin-helper' ),
        'all_items'         => __( 'All Categories', 'duke-yin-helper'),
        'parent_item'       => __( 'Parent Category','duke-yin-helper'),
        'parent_item_colon' => __( 'Parent Category:','duke-yin-helper' ),
        'edit_item'         => __( 'Edit Category','duke-yin-helper' ),
        'update_item'       => __( 'Update Category','duke-yin-helper' ),
        'add_new_item'      => __( 'Add New Category','duke-yin-helper' ),
        'new_item_name'     => __( 'New Category Name','duke-yin-helper' ),
        'menu_name'         => __( 'Categories','duke-yin-helper' ),
    );

    $args = array(
        'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array( 'slug' => 'categories' ),
    );

    register_taxonomy( 'video_categories', array( 'videos' ), $args );

}
add_action('init', 'videos_register');
add_action( 'init', 'create_video_taxonomies', 0 );


/** Magnet links */
function link_register() {
	$labels = array(
	  'name' => _x('Magnet Links', 'post type general name','duke-yin-helper'),
	  'singular_name' => _x('Link', 'post type singular name','duke-yin-helper'),
	  'all_items'=> _x('All Magnet Links', 'String for the submenu','duke-yin-helper'),
	  'add_new' => _x('Add New', 'links','duke-yin-helper'),
	  'add_new_item' => __('Add new','duke-yin-helper'),
	  'edit_item' => __('Edit Magnet link','duke-yin-helper'),
	  'new_item' => __('New link','duke-yin-helper'),
	  'view_item' => __('View this link','duke-yin-helper'),
	  'search_items' => __('Search for','duke-yin-helper'),
	  'not_found' =>  __('Not found','duke-yin-helper'),
	  'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
	  'parent_item_colon' => ''
	);

  $slugRule = 'links';
  
  $args = array(
	  'labels' => $labels,
	  'public' => true,
	  'show_ui' => true,
	  'show_in_rest'=> false,
	  'capability_type' => 'post',
	  'hierarchical' => false,
	  'rewrite' => array('slug' => 'links','with_front' => true),
	  'query_var' => true,
	  'show_in_nav_menus'=> false,
	  'menu_position' => 13,
	  'menu_icon' => 'dashicons-download',
	  'supports' => array('title','author')
  );

  register_post_type( 'links' , $args ); 
}

function create_link_taxonomies() {
$labels = array(
  'name'              => _x( 'Categories', 'taxonomy general name','duke-yin-helper' ),
  'singular_name'     => _x( 'Category', 'taxonomy singular name','duke-yin-helper'),
  'search_items'      => __( 'Search Categories','duke-yin-helper' ),
  'all_items'         => __( 'All Categories', 'duke-yin-helper'),
  'parent_item'       => __( 'Parent Category','duke-yin-helper'),
  'parent_item_colon' => __( 'Parent Category:','duke-yin-helper' ),
  'edit_item'         => __( 'Edit Category','duke-yin-helper' ),
  'update_item'       => __( 'Update Category','duke-yin-helper' ),
  'add_new_item'      => __( 'Add New Category','duke-yin-helper' ),
  'new_item_name'     => __( 'New Category Name','duke-yin-helper' ),
  'menu_name'         => __( 'Categories','duke-yin-helper' ),
);

$args = array(
  'hierarchical'      => true, // Set this to 'false' for non-hierarchical taxonomy (like tags)
  'labels'            => $labels,
  'show_ui'           => true,
  'show_admin_column' => true,
  'query_var'         => true,
  'rewrite'           => array( 'slug' => 'categories' ),
);

register_taxonomy( 'link_categories', array( 'links' ), $args );

}
add_action('init', 'link_register');
add_action( 'init', 'create_link_taxonomies', 0 );