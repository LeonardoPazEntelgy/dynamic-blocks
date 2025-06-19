(function(blocks, blockEditor, element) {
    var el = element.createElement;
    var useBlockProps = blockEditor.useBlockProps;

    blocks.registerBlockType('dbb/banner', {
        title: 'Banner Hero',
        icon: 'format-image',
        category: 'widgets',
        edit: function() {
            const blockProps = useBlockProps();
            return el('div', blockProps,
                el('p', {}, 'Edita los campos del bloque en el panel derecho (ACF).')
            );
        },
        save: function() {
            const blockProps = useBlockProps.save();
            return el('div', blockProps, null);
        }
    });
})(window.wp.blocks, window.wp.blockEditor, window.wp.element);