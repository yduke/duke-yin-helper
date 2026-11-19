<?php
/**
 * Registers the alert / progress blocks.
 *
 * The blocks are server rendered: their HTML comes from includes/blocks/render.php,
 * the very same builders the [alert] and [progress] shortcodes use, so a block
 * and its shortcode always output an identical Bootstrap component.
 *
 * @package Duke_Yin_Helper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/render.php';

/**
 * Script / style handle shared by both blocks.
 */
if ( ! defined( 'DUKEYIN_BLOCKS_EDITOR_HANDLE' ) ) {
	define( 'DUKEYIN_BLOCKS_EDITOR_HANDLE', 'dukeyin-blocks-editor' );
}

if ( ! function_exists( 'dukeyin_blocks_register_assets' ) ) {
	/**
	 * Registers the editor bundle.
	 *
	 * The script is registered by hand (instead of using "file:" in block.json)
	 * so it keeps working without a build step and without an .asset.php file.
	 */
	function dukeyin_blocks_register_assets() {
		$url  = plugin_dir_url( __FILE__ ) . 'assets/';
		$path = __DIR__ . '/assets/';

		wp_register_script(
			DUKEYIN_BLOCKS_EDITOR_HANDLE,
			$url . 'blocks.js',
			array( 'wp-blocks', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
			file_exists( $path . 'blocks.js' ) ? filemtime( $path . 'blocks.js' ) : DUKE_YIN_HELPER_VERSION,
			true
		);

		wp_register_style(
			DUKEYIN_BLOCKS_EDITOR_HANDLE,
			$url . 'blocks-editor.css',
			array(),
			file_exists( $path . 'blocks-editor.css' ) ? filemtime( $path . 'blocks-editor.css' ) : DUKE_YIN_HELPER_VERSION
		);
	}
}

if ( ! function_exists( 'dukeyin_blocks_register' ) ) {
	/**
	 * Registers both block types from their block.json metadata.
	 */
	function dukeyin_blocks_register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		dukeyin_blocks_register_assets();

		wp_set_script_translations( DUKEYIN_BLOCKS_EDITOR_HANDLE, 'duke-yin-helper', dirname( __DIR__, 2 ) . '/languages' );

		register_block_type(
			__DIR__ . '/alert',
			array( 'render_callback' => 'dukeyin_alert_block_render' )
		);

		register_block_type(
			__DIR__ . '/progress',
			array( 'render_callback' => 'dukeyin_progress_block_render' )
		);

		// Subtitle is editor-only: the value lives in the _headline post meta and
		// the theme prints it next to the title, so the block renders nothing in
		// the content. It is not dynamic (no render_callback) on purpose.
		register_block_type( __DIR__ . '/subtitle' );
	}
}
add_action( 'init', 'dukeyin_blocks_register' );

if ( ! function_exists( 'dukeyin_alert_block_render' ) ) {
	/**
	 * Renders the alert block, reusing the [alert] markup builder.
	 *
	 * @param array  $attributes Block attributes.
	 * @param string $content    Rendered inner blocks.
	 * @return string
	 */
	function dukeyin_alert_block_render( $attributes, $content = '' ) {
		$attributes = wp_parse_args(
			(array) $attributes,
			array(
				'type'    => 'danger',
				'close'   => true,
				'content' => '',
			)
		);

		// The message lives in a RichText attribute, so it is inline HTML and is
		// emitted as-is: no <p> wrapper (that would add paragraph margins inside
		// the alert and make it taller than the equivalent shortcode).
		// Legacy alerts saved the message as inner blocks; fall back to that.
		$message = (string) $attributes['content'];
		if ( '' === trim( $message ) && '' !== trim( (string) $content ) ) {
			$message = dukeyin_alert_inner_blocks_to_text( (string) $content );
		}

		return dukeyin_render_alert(
			array(
				'type'               => $attributes['type'],
				'close'              => $attributes['close'],
				'extra_class'        => 'wp-block-dukeyin-alert',
				'use_block_supports' => true,
			),
			wp_kses_post( $message )
		);
	}
}

if ( ! function_exists( 'dukeyin_alert_inner_blocks_to_text' ) ) {
	/**
	 * Unwraps the single-paragraph markup produced by the first version of the
	 * alert block, so migrated alerts keep their text without the <p> wrapper.
	 *
	 * @param string $html Rendered inner blocks.
	 * @return string
	 */
	function dukeyin_alert_inner_blocks_to_text( $html ) {
		$html = trim( $html );

		// A lone <p>…</p> (optionally with attributes) becomes its inner HTML.
		if ( preg_match( '~^<p(?:\s[^>]*)?>(.*)</p>$~s', $html, $matches ) ) {
			return trim( $matches[1] );
		}

		return $html;
	}
}

if ( ! function_exists( 'dukeyin_progress_block_render' ) ) {
	/**
	 * Renders the progress block, reusing the [progress] markup builder.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	function dukeyin_progress_block_render( $attributes ) {
		$attributes = wp_parse_args(
			(array) $attributes,
			array(
				'title'      => '',
				'percentage' => 50,
				'height'     => '',
				'color'      => '',
				'striped'    => false,
				'animated'   => false,
				'showLabel'  => true,
			)
		);

		return dukeyin_render_progress(
			array(
				'title'              => $attributes['title'],
				'percentage'         => $attributes['percentage'],
				'height'             => $attributes['height'],
				'color'              => $attributes['color'],
				'striped'            => $attributes['striped'],
				'animated'           => $attributes['animated'],
				'show_label'         => $attributes['showLabel'],
				'wrapper_class'      => 'wp-block-dukeyin-progress mb-3',
				'use_block_supports' => true,
			)
		);
	}
}

if ( ! function_exists( 'dukeyin_blocks_category' ) ) {
	/**
	 * Adds a dedicated inserter category so both blocks sit together.
	 *
	 * @param array $categories Existing block categories.
	 * @return array
	 */
	function dukeyin_blocks_category( $categories ) {
		foreach ( $categories as $category ) {
			if ( isset( $category['slug'] ) && 'dukeyin' === $category['slug'] ) {
				return $categories;
			}
		}

		array_unshift(
			$categories,
			array(
				'slug'  => 'dukeyin',
				'title' => __( 'Duke Yin Helper', 'duke-yin-helper' ),
				'icon'  => null,
			)
		);

		return $categories;
	}
}
add_filter( 'block_categories_all', 'dukeyin_blocks_category' );
