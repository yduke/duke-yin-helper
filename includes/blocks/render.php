<?php
/**
 * Markup builders for the alert / progress blocks.
 *
 * These build the same Bootstrap components as the [alert] and [progress]
 * shortcodes in includes/short-code.php, so a post can mix blocks and
 * shortcodes without looking inconsistent.
 *
 * The shortcodes are deliberately NOT routed through this file: they are
 * frozen exactly as they were so old posts keep rendering byte-for-byte
 * identical HTML. Keeping the two paths separate means block-side hardening
 * (type whitelisting, class sanitising) can never change legacy output.
 *
 * @package Duke_Yin_Helper
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( ! function_exists( 'dukeyin_alert_types' ) ) {
	/**
	 * Alert variants supported by Bootstrap 5.
	 *
	 * @return string[]
	 */
	function dukeyin_alert_types() {
		return array( 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark' );
	}
}

if ( ! function_exists( 'dukeyin_render_alert' ) ) {
	/**
	 * Builds a Bootstrap alert box.
	 *
	 * @param array  $args    {
	 *     @type string $type               Bootstrap variant, see dukeyin_alert_types(). Default 'danger'.
	 *     @type bool   $close              Whether to print the dismiss button. Default true.
	 *     @type string $extra_class        Extra class for the wrapper (blocks use 'wp-block-dukeyin-alert').
	 *     @type bool   $use_block_supports Merge the block's own className / anchor / style into the wrapper.
	 *                                      Only valid while a block is being rendered.
	 * }
	 * @param string $content Inner HTML. Passed through untouched, so it must be escaped by the caller.
	 * @return string
	 */
	function dukeyin_render_alert( $args = array(), $content = '' ) {
		$args = wp_parse_args(
			$args,
			array(
				'type'               => 'danger',
				'close'              => true,
				'extra_class'        => '',
				'use_block_supports' => false,
			)
		);

		$type = sanitize_html_class( (string) $args['type'] );
		if ( ! in_array( $type, dukeyin_alert_types(), true ) ) {
			$type = 'danger';
		}

		$classes = array( 'alert', 'alert-dismissible', 'fade', 'show', 'alert-' . $type );

		$extra_class = trim( (string) $args['extra_class'] );
		if ( '' !== $extra_class ) {
			array_unshift( $classes, $extra_class );
		}

		if ( $args['use_block_supports'] && function_exists( 'get_block_wrapper_attributes' ) ) {
			// Lets the block's className, anchor and style attributes reach the front end.
			$attributes = get_block_wrapper_attributes(
				array(
					'class'      => implode( ' ', $classes ),
					'data-alert' => '',
				)
			);
		} else {
			$attributes = 'data-alert="" class="' . esc_attr( implode( ' ', $classes ) ) . '"';
		}

		$html  = '<div ' . $attributes . '>';
		$html .= $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Inner HTML, escaped by the caller.
		if ( filter_var( $args['close'], FILTER_VALIDATE_BOOLEAN ) ) {
			$html .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="' . esc_attr__( 'Close', 'duke-yin-helper' ) . '"></button>';
		}
		$html .= '</div>';

		return $html;
	}
}

if ( ! function_exists( 'dukeyin_render_progress' ) ) {
	/**
	 * Builds a Bootstrap progress bar.
	 *
	 * @param array $args {
	 *     @type string $title         Optional label above the bar.
	 *     @type int    $percentage    Fill amount, clamped to 0-100.
	 *     @type string $height        Optional inline height for the fill, e.g. '1rem'.
	 *     @type string $color         Bootstrap variant for the fill, e.g. 'success'.
	 *     @type bool   $striped       Add progress-bar-striped.
	 *     @type bool   $animated      Add progress-bar-animated.
	 *     @type bool   $show_label    Print the percentage inside the fill. Default true.
	 *     @type string $wrapper_class Extra classes for the wrapper. Default 'mb-3'.
	 *     @type bool   $use_block_supports Merge the block's own className / anchor / style into the
	 *                                      wrapper. Only valid while a block is being rendered.
	 * }
	 * @return string
	 */
	function dukeyin_render_progress( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'title'              => '',
				'percentage'         => 0,
				'height'             => '',
				'color'              => '',
				'striped'            => false,
				'animated'           => false,
				'show_label'         => true,
				'wrapper_class'      => 'mb-3',
				'use_block_supports' => false,
			)
		);

		$percentage = (int) $args['percentage'];
		$percentage = max( 0, min( 100, $percentage ) );

		$bar_classes = array( 'progress-bar' );
		$color       = sanitize_html_class( (string) $args['color'] );
		if ( '' !== $color ) {
			$bar_classes[] = 'bg-' . $color;
		}
		if ( filter_var( $args['striped'], FILTER_VALIDATE_BOOLEAN ) ) {
			$bar_classes[] = 'progress-bar-striped';
		}
		if ( filter_var( $args['animated'], FILTER_VALIDATE_BOOLEAN ) ) {
			$bar_classes[] = 'progress-bar-animated';
		}

		$style = 'width: ' . $percentage . '%;';

		// Height belongs to the .progress track, not to the .progress-bar fill.
		// Putting it on the fill made the bar overflow its 1rem track and pushed
		// the percentage label off vertical centre. Bootstrap 5.3 derives the track
		// height (and the striped pattern size) from --bs-progress-height.
		$track_style = '';
		$height      = trim( (string) $args['height'] );
		if ( '' !== $height ) {
			$track_style = '--bs-progress-height: ' . $height . '; height: ' . $height . ';';
		}

		$wrapper_class = trim( (string) $args['wrapper_class'] );

		if ( $args['use_block_supports'] && function_exists( 'get_block_wrapper_attributes' ) ) {
			// Lets the block's className, anchor and style attributes reach the front end.
			$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_class ) );
		} else {
			$wrapper_attributes = 'class="' . esc_attr( $wrapper_class ) . '"';
		}

		$html = '<div ' . $wrapper_attributes . '>';
		if ( '' !== (string) $args['title'] ) {
			$html .= '<div class="mb-1 small fw-semibold">' . esc_html( $args['title'] ) . '</div>';
		}
		$html .= '<div class="progress" role="progressbar" aria-valuenow="' . esc_attr( $percentage ) . '" aria-valuemin="0" aria-valuemax="100"' . ( '' !== $track_style ? ' style="' . esc_attr( $track_style ) . '"' : '' ) . '>';
		$html .= '<div class="' . esc_attr( implode( ' ', $bar_classes ) ) . '" style="' . esc_attr( $style ) . '">';
		if ( filter_var( $args['show_label'], FILTER_VALIDATE_BOOLEAN ) ) {
			$html .= esc_html( $percentage ) . '%';
		}
		$html .= '</div></div></div>';

		return $html;
	}
}
