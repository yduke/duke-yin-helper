<?php
/**
 * Post subtitle: the _headline post meta, the post template, and the migration
 * that gives posts written before the block existed the same subtitle block.
 *
 * The block itself is registered in includes/blocks/blocks.php together with the
 * alert and progress blocks, so all three live under the same "Duke Yin Helper"
 * inserter category and share one editor bundle.
 *
 * What this file owns:
 *
 * 1. the _headline post meta, which the theme reads with
 *    get_post_meta( get_the_ID(), '_headline', true ) — the storage format must
 *    not change, and the theme has to print the subtitle, not the block;
 * 2. the post template that puts the subtitle block at the top of every new post;
 * 3. the one-time migration that adds the block to posts written before it
 *    existed, plus the filter that keeps those older posts rendering exactly as
 *    they did before (see dukeyin_restore_legacy_subtitle_content()).
 *
 * @package Duke_Yin_Helper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The subtitle block's saved markup.
 *
 * The block stores nothing in the post body (its save() returns null), so this
 * delimiter is all that reaches post_content. It exists only to tell the editor
 * where the subtitle field belongs.
 */
define( 'DUKEYIN_SUBTITLE_BLOCK', '<!-- wp:dukeyin/headline /-->' );

if ( ! function_exists( 'dukeyin_subtitle_post_types' ) ) {
	/**
	 * Post types that get the subtitle block.
	 *
	 * Every post type whose editor is the block editor is included, so the
	 * subtitle is available on the built-in post and page types as well as on
	 * the custom types registered by includes/post-type.php.
	 *
	 * Post types that use the classic editor (videos, links, music, ...) cannot
	 * host a block. They keep the CMB2 subtitle metabox the theme registers, so
	 * the subtitle stays editable there — that is the fallback path.
	 *
	 * @return string[] Post type names.
	 */
	function dukeyin_subtitle_post_types() {
		// Containers and internals that are not editorial content.
		$excluded = array(
			'attachment',
			'revision',
			'nav_menu_item',
			'custom_css',
			'customize_changeset',
			'oembed_cache',
			'user_request',
			'wp_block',
			'wp_template',
			'wp_template_part',
			'wp_navigation',
			'wp_global_styles',
			'wp_font_family',
			'wp_font_face',
		);

		$post_types = array();

		foreach ( get_post_types( array(), 'names' ) as $post_type ) {
			if ( in_array( $post_type, $excluded, true ) ) {
				continue;
			}

			if ( ! use_block_editor_for_post_type( $post_type ) ) {
				continue;
			}

			$post_types[] = $post_type;
		}

		/**
		 * Filters the post types that get the subtitle block.
		 *
		 * @param string[] $post_types Post type names.
		 */
		return (array) apply_filters( 'dukeyin_subtitle_post_types', $post_types );
	}
}

if ( ! function_exists( 'duke_register_block_meta' ) ) {
	/**
	 * Registers the _headline post meta for every post type that has the block.
	 *
	 * show_in_rest is required: the block attribute uses source: 'meta', which
	 * syncs through the REST API. The meta key itself (a single string) is what
	 * the theme reads, so it stays exactly as it was.
	 */
	function duke_register_block_meta() {
		foreach ( dukeyin_subtitle_post_types() as $post_type ) {
			register_post_meta(
				$post_type,
				'_headline',
				array(
					'show_in_rest'  => true,
					'single'        => true,
					'type'          => 'string',
					'description'   => esc_html__( 'Subtitle of posts.', 'duke-yin-helper' ),
					'auth_callback' => function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
}
add_action( 'init', 'duke_register_block_meta' );

if ( ! function_exists( 'dukeyin_enable_custom_fields_support' ) ) {
	/**
	 * Makes the REST API expose _headline on every post type that has the block.
	 *
	 * The block attribute uses source: 'meta', which means the editor reads and
	 * writes the subtitle through the REST API. WordPress only adds the `meta`
	 * field to a post type's REST schema when that post type supports
	 * `custom-fields` (see WP_REST_Posts_Controller::get_item_schema()). The
	 * built-in post and page types support it, but most custom types registered
	 * by includes/post-type.php do not, so without this the subtitle block would
	 * silently do nothing on poem, portfolio, photo and the review types.
	 *
	 * This adds the support flag only; it does not register any meta boxes and
	 * does not expose arbitrary custom fields beyond the ones registered with
	 * show_in_rest.
	 */
	function dukeyin_enable_custom_fields_support() {
		foreach ( dukeyin_subtitle_post_types() as $post_type ) {
			if ( post_type_supports( $post_type, 'custom-fields' ) ) {
				continue;
			}

			add_post_type_support( $post_type, 'custom-fields' );
		}
	}
}
add_action( 'init', 'dukeyin_enable_custom_fields_support', 15 );

if ( ! function_exists( 'dukeyin_register_template' ) ) {
	/**
	 * Puts the subtitle block at the top of every new post.
	 *
	 * Runs late on init so the post types registered by includes/post-type.php
	 * are already in place. An existing template is preserved; the subtitle
	 * block is only prepended when it is not there yet.
	 */
	function dukeyin_register_template() {
		foreach ( dukeyin_subtitle_post_types() as $post_type ) {
			$post_type_object = get_post_type_object( $post_type );

			if ( ! $post_type_object ) {
				continue;
			}

			$template = is_array( $post_type_object->template ) ? $post_type_object->template : array();

			$already_present = false;
			foreach ( $template as $block ) {
				if ( isset( $block[0] ) && 'dukeyin/headline' === $block[0] ) {
					$already_present = true;
					break;
				}
			}

			if ( ! $already_present ) {
				array_unshift( $template, array( 'dukeyin/headline' ) );
			}

			$post_type_object->template = $template;
		}
	}
}
add_action( 'init', 'dukeyin_register_template', 20 );

if ( ! function_exists( 'dukeyin_restore_legacy_subtitle_content' ) ) {
	/**
	 * Keeps pre-block posts rendering exactly as they did before the migration.
	 *
	 * Adding any block delimiter to a post turns has_blocks() true, and do_blocks()
	 * then removes wpautop() from the_content. Posts written before the block
	 * editor store bare text with no <p> tags and rely on wpautop() to create
	 * paragraphs, so leaving the delimiter in place would collapse their layout.
	 *
	 * The migration always prepends the delimiter followed by exactly two
	 * newlines. When that is the only block in the post, this filter removes
	 * exactly what the migration added and hands the original string, byte for
	 * byte, to the rest of the content pipeline. Rendering therefore cannot
	 * change. As soon as the post contains any other block, the filter steps
	 * aside and normal block rendering takes over.
	 *
	 * Runs at priority 8, before do_blocks() at 9.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	function dukeyin_restore_legacy_subtitle_content( $content ) {
		if ( ! is_string( $content ) ) {
			return $content;
		}

		$prefix = DUKEYIN_SUBTITLE_BLOCK . "\n\n";

		if ( 0 === strpos( $content, $prefix ) ) {
			$rest = substr( $content, strlen( $prefix ) );

			// Only when the subtitle block is the sole block in the post.
			if ( false === strpos( $rest, '<!-- wp:' ) ) {
				return $rest;
			}

			return $content;
		}

		// An empty post was migrated to the bare delimiter.
		if ( DUKEYIN_SUBTITLE_BLOCK === $content ) {
			return '';
		}

		return $content;
	}
}
add_filter( 'the_content', 'dukeyin_restore_legacy_subtitle_content', 8 );

/*
 * Existing posts are handled by the tool in includes/subtitle-migration.php, not
 * automatically. A site may run this plugin on more than one install, and each one
 * has to be able to inspect what would change and run the migration when it suits
 * them — see Tools → Subtitle Block Migration.
 */

if ( ! function_exists( 'dukeyin_hide_cmb2_subtitle_box' ) ) {
	/**
	 * Hides the CMB2 subtitle metabox where the block is available.
	 *
	 * The theme registers the _headline text field with CMB2 for eleven post
	 * types. Every one of them except "videos" now has the subtitle block, and
	 * two controls writing the same meta key is confusing and can overwrite each
	 * other. Removing the metabox for those types leaves CMB2 as the fallback
	 * for post types without a block editor, which is what "videos" still uses.
	 *
	 * The theme itself is untouched and the removal is reversible: dropping this
	 * hook brings the metabox back.
	 */
	function dukeyin_hide_cmb2_subtitle_box() {
		if ( ! function_exists( 'remove_meta_box' ) ) {
			return;
		}

		foreach ( dukeyin_subtitle_post_types() as $post_type ) {
			remove_meta_box( 'extra_option', $post_type, 'normal' );
		}
	}
}
add_action( 'add_meta_boxes', 'dukeyin_hide_cmb2_subtitle_box', 99 );
