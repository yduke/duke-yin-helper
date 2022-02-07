<?php
    function duke_blocks_enqueue() {

    wp_register_script(
        'duke-blocks-js',
        plugin_dir_url( __FILE__ )  . '/meta-block.js',
        array( 'wp-blocks', 'wp-editor', 'wp-element','wp-components', 'wp-i18n' )
    );
// Audio box


// subtitle
	register_block_type( 'dukeyin/headline', array(
        'editor_script' => 'duke-blocks-js',
    ) );
//	
}
add_action( 'init', 'duke_blocks_enqueue' );


function duke_register_block_meta() {
// Audio box

// subtitle
    register_post_meta( 'post', '_headline', array(
        'show_in_rest' => true,
        'single' => true,
        'type' => 'string',
        'description' => esc_html__('Subtitle of posts.','duke-yin-helper' ),
		'auth_callback' => function() {
        return current_user_can( 'edit_posts' );
    }
    ) );
}

add_action( 'init', 'duke_register_block_meta' );

// always show on editor
function dukeyin_register_template() {
	//default post
    $post_type_object = get_post_type_object( 'post' );
    $post_type_object->template = array(
        array( 'dukeyin/headline' ),
    );
	
}
add_action( 'init', 'dukeyin_register_template' );

//translate
function dukeyin_set_script_translations() {
        wp_set_script_translations( 'duke-blocks-js', 'duke-yin-helper' );
    }
    add_action( 'init', 'dukeyin_set_script_translations' );
