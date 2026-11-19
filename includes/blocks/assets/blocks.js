/**
 * Editor bundle for the dukeyin/alert, dukeyin/progress and dukeyin/headline
 * (Subtitle) blocks.
 *
 * The alert and progress blocks are server rendered: their markup comes from
 * includes/blocks/render.php, and the previews below mirror it one-to-one.
 *
 * The subtitle block is editor-only. Its value lives in the _headline post meta
 * (the theme prints it under the post title), so save() returns null and nothing
 * is stored in the post content beyond the empty block delimiter.
 *
 * No build step: plain wp.element.createElement, registered by
 * includes/blocks/blocks.php.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.blockEditor || ! wp.element || ! wp.components ) {
		return;
	}

	const el = wp.element.createElement;
	const Fragment = wp.element.Fragment;
	const __ = wp.i18n.__;
	const registerBlockType = wp.blocks.registerBlockType;

	const useBlockProps = wp.blockEditor.useBlockProps;
	const InnerBlocks = wp.blockEditor.InnerBlocks;
	const InspectorControls = wp.blockEditor.InspectorControls;
	const RichText = wp.blockEditor.RichText;

	const PanelBody = wp.components.PanelBody;
	const SelectControl = wp.components.SelectControl;
	const ToggleControl = wp.components.ToggleControl;
	const RangeControl = wp.components.RangeControl;
	const TextControl = wp.components.TextControl;

	const ALERT_TYPES = [
		{ label: __( 'Primary', 'duke-yin-helper' ), value: 'primary' },
		{ label: __( 'Secondary', 'duke-yin-helper' ), value: 'secondary' },
		{ label: __( 'Success', 'duke-yin-helper' ), value: 'success' },
		{ label: __( 'Danger', 'duke-yin-helper' ), value: 'danger' },
		{ label: __( 'Warning', 'duke-yin-helper' ), value: 'warning' },
		{ label: __( 'Info', 'duke-yin-helper' ), value: 'info' },
		{ label: __( 'Light', 'duke-yin-helper' ), value: 'light' },
		{ label: __( 'Dark', 'duke-yin-helper' ), value: 'dark' },
	];

	const PROGRESS_COLORS = [
		{ label: __( 'Default', 'duke-yin-helper' ), value: '' },
	].concat( ALERT_TYPES );

	const clampPercentage = function ( value ) {
		const number = parseInt( value, 10 );

		if ( isNaN( number ) ) {
			return 0;
		}

		return Math.max( 0, Math.min( 100, number ) );
	};

	/**
	 * Strips a single wrapping <p>…</p>, so migrating the old inner-block alert
	 * does not carry the paragraph (and its margin) into the new plain-text field.
	 *
	 * @param {string} html Raw inner-block HTML.
	 * @return {string} Inline HTML.
	 */
	const unwrapSingleParagraph = function ( html ) {
		const value = ( html || '' ).trim();
		const match = /^<p(?:\s[^>]*)?>([\s\S]*)<\/p>$/i.exec( value );

		return match ? match[ 1 ].trim() : value;
	};

	/**
	 * Alert box: a real Bootstrap .alert wrapper around a single RichText field.
	 *
	 * The message is stored as inline HTML, not as inner blocks, so WordPress does
	 * not wrap it in <p class="wp-block-paragraph"> — that wrapper added paragraph
	 * margins inside the box and made the block look taller than the shortcode.
	 */
	registerBlockType( 'dukeyin/alert', {
		edit: function ( props ) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;

			const blockProps = useBlockProps( {
				className: 'alert alert-dismissible fade show alert-' + attributes.type,
			} );

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Alert settings', 'duke-yin-helper' ) },
						el( SelectControl, {
							label: __( 'Type', 'duke-yin-helper' ),
							value: attributes.type,
							options: ALERT_TYPES,
							onChange: function ( value ) {
								setAttributes( { type: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show close button', 'duke-yin-helper' ),
							checked: !! attributes.close,
							onChange: function ( value ) {
								setAttributes( { close: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( RichText, {
						tagName: 'div',
						className: 'dukeyin-alert__content',
						value: attributes.content,
						onChange: function ( value ) {
							setAttributes( { content: value } );
						},
						placeholder: __( 'Write the alert message…', 'duke-yin-helper' ),
						'aria-label': __( 'Alert message', 'duke-yin-helper' ),
					} ),
					attributes.close
						? el( 'button', {
								type: 'button',
								className: 'btn-close dukeyin-alert__close',
								'aria-label': __( 'Close', 'duke-yin-helper' ),
								tabIndex: -1,
						  } )
						: null
				)
			);
		},
		save: function () {
			return null;
		},
		deprecated: [
			{
				// v1 stored the message as a core/paragraph inner block. Its saved
				// markup is empty (the paragraph lives in innerBlocks), so it would
				// still validate against the new save() and the text would silently
				// disappear from the editor. isEligible forces the migration.
				attributes: {
					type: { type: 'string', default: 'danger' },
					close: { type: 'boolean', default: true },
				},
				supports: { anchor: true, html: false },
				isEligible: function ( attributes, innerBlocks ) {
					return Array.isArray( innerBlocks ) && innerBlocks.length > 0;
				},
				save: function () {
					return el( InnerBlocks.Content );
				},
				migrate: function ( attributes, innerBlocks ) {
					const html = ( innerBlocks || [] )
						.map( function ( block ) {
							const blockAttributes = block.attributes || {};
							if ( typeof blockAttributes.content === 'string' ) {
								return blockAttributes.content;
							}
							return block.originalContent || '';
						} )
						.join( '\n' )
						.trim();

					return {
						type: attributes.type,
						close: attributes.close,
						content: unwrapSingleParagraph( html ),
					};
				},
			},
		],
	} );

	/**
	 * Progress bar: attributes only, the preview is rebuilt from them.
	 */
	registerBlockType( 'dukeyin/progress', {
		edit: function ( props ) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;
			const percentage = clampPercentage( attributes.percentage );

			const blockProps = useBlockProps( { className: 'mb-3' } );

			const barClassName = [ 'progress-bar' ]
				.concat( attributes.color ? [ 'bg-' + attributes.color ] : [] )
				.concat( attributes.striped ? [ 'progress-bar-striped' ] : [] )
				.concat( attributes.animated ? [ 'progress-bar-animated' ] : [] )
				.join( ' ' );

			const barStyle = { width: percentage + '%' };

			// Match the front end: the custom height belongs to the .progress track,
			// not to the .progress-bar fill.
			const trackStyle = {};
			if ( attributes.height ) {
				trackStyle[ '--bs-progress-height' ] = attributes.height;
				trackStyle.height = attributes.height;
			}

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Progress settings', 'duke-yin-helper' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Percentage', 'duke-yin-helper' ),
							value: percentage,
							min: 0,
							max: 100,
							onChange: function ( value ) {
								setAttributes( { percentage: clampPercentage( value ) } );
							},
						} ),
						el( TextControl, {
							label: __( 'Title', 'duke-yin-helper' ),
							value: attributes.title,
							onChange: function ( value ) {
								setAttributes( { title: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Color', 'duke-yin-helper' ),
							value: attributes.color,
							options: PROGRESS_COLORS,
							onChange: function ( value ) {
								setAttributes( { color: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Height', 'duke-yin-helper' ),
							value: attributes.height,
							help: __( 'For example 1rem or 24px. Empty keeps the Bootstrap default.', 'duke-yin-helper' ),
							onChange: function ( value ) {
								setAttributes( { height: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Striped', 'duke-yin-helper' ),
							checked: !! attributes.striped,
							onChange: function ( value ) {
								setAttributes( { striped: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Animated', 'duke-yin-helper' ),
							checked: !! attributes.animated,
							help: __( 'Animated stripes are only visible on striped bars.', 'duke-yin-helper' ),
							onChange: function ( value ) {
								setAttributes( { animated: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Show percentage label', 'duke-yin-helper' ),
							checked: !! attributes.showLabel,
							onChange: function ( value ) {
								setAttributes( { showLabel: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					attributes.title
						? el( 'div', { className: 'mb-1 small fw-semibold' }, attributes.title )
						: null,
					el(
						'div',
						{
							className: 'progress',
							role: 'progressbar',
							'aria-valuenow': percentage,
							'aria-valuemin': 0,
							'aria-valuemax': 100,
							style: trackStyle,
						},
						el(
							'div',
							{ className: barClassName, style: barStyle },
							attributes.showLabel ? percentage + '%' : null
						)
					)
				)
			);
		},
		save: function () {
			return null;
		},
	} );

	/**
	 * Subtitle: writes to the _headline post meta, not to the post content.
	 *
	 * `source: 'meta'` makes WordPress sync the attribute with the meta field via
	 * the REST API, so the theme's get_post_meta( …, '_headline', true ) call keeps
	 * working untouched. save() returns null: the theme prints the subtitle under
	 * the title, and anything rendered here would duplicate it in the body.
	 */
	registerBlockType( 'dukeyin/headline', {
		edit: function ( props ) {
			const attributes = props.attributes;
			const setAttributes = props.setAttributes;

			const blockProps = useBlockProps( {
				className: 'dukeyin-subtitle',
			} );

			return el(
				'div',
				blockProps,
				el(
					'h5',
					{ className: 'dukeyin-subtitle__label' },
					__( 'Subtitle', 'duke-yin-helper' )
				),
				el( TextControl, {
					label: __( 'Subtitle', 'duke-yin-helper' ),
					value: attributes.blockValue || '',
					onChange: function ( value ) {
						setAttributes( { blockValue: value } );
					},
					help: __(
						'Shown under the post title. Stored in post meta, not in the post content.',
						'duke-yin-helper'
					),
				} )
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
