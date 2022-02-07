( function( wp ) {
	const { __ } = wp.i18n;
    const el = wp.element.createElement;
    var registerBlockType = wp.blocks.registerBlockType;
    const TextControl = wp.components.TextControl;
	
// subtitle
	registerBlockType( 'dukeyin/headline', {
    title: __('Subtitle','duke-yin-helper'),
    icon: 'editor-paragraph',
    category: 'layout',
    attributes: {
        blockValue: {
            type: 'string',
            source: 'meta',
            meta: '_headline',
        }
    },

    edit: function( props ) {
		var className = props.className;
        var setAttributes = props.setAttributes;

        function updateBlockValue( blockValue ) {
            setAttributes({ blockValue });
        }

        return el(
           'div',
           { className: className },
            el( 'h5',{}, __('Subtitle','duke-yin-helper')),
            el (TextControl,
            {
				label: __('Subtitle.','duke-yin-helper'),
                value: props.attributes.blockValue,
				onChange: updateBlockValue,
            })
        );
    },

    save: function( props ) {
         return null;
    },
    } );
	
} )( window.wp );