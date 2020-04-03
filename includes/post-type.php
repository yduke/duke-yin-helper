<?php
/**
 * Add some post types to the theme.
 *
 * @package DUKE_YIN
 */
 
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
			'show_in_rest'=> true,
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
		    'singular_name' => _x('Porduct', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All Products', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add new', 'product','duke-yin-helper'),
		    'add_new_item' => __('Add a product for sale','duke-yin-helper'),
		    'edit_item' => __('Edit','duke-yin-helper'),
		    'new_item' => __('New porduct','duke-yin-helper'),
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


	function review_register() {
		  $labels = array(
		    'name' => _x('Reviews', 'post type general name','duke-yin-helper'),
		    'singular_name' => _x('Review post', 'post type singular name','duke-yin-helper'),
			'all_items'=> _x('All review', 'String for the submenu','duke-yin-helper'),
		    'add_new' => _x('Add New', 'review','duke-yin-helper'),
		    'add_new_item' => __('Add review','duke-yin-helper'),
		    'edit_item' => __('Edit review','duke-yin-helper'),
		    'new_item' => __('New review','duke-yin-helper'),
		    'view_item' => __('View this review','duke-yin-helper'),
		    'search_items' => __('Search for','duke-yin-helper'),
		    'not_found' =>  __('No review found','duke-yin-helper'),
		    'not_found_in_trash' => __('Not found in trash','duke-yin-helper'), 
		    'parent_item_colon' => ''
		  );
		$slugRule = 'review';
    	$args = array(
        	'labels' => $labels,
        	'public' => true,
        	'show_ui' => true,
			'show_in_rest'=> true,
        	'capability_type' => 'post',
        	'hierarchical' => false,
        	'rewrite' => true,
			'rewrite' => array('slug' => 'review','with_front' => true),
        	'query_var' => true,
        	'show_in_nav_menus'=> false,
        	'menu_position' => 8,
			'menu_icon' => 'dashicons-editor-ol',
        	'supports' => array('title','author','thumbnail','excerpt','editor','comments','custom-fields'),
			'taxonomies' => array('post_tag',)
        );
    	register_post_type( 'review' , $args );
	}
	
function create_review_taxonomies() {
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
        'rewrite'           => array( 'slug' => 'reviews' ),
    );

    register_taxonomy( 'review_categories', array( 'review' ), $args );
}
add_action('init', 'review_register');
add_action( 'init', 'create_review_taxonomies', 0 );