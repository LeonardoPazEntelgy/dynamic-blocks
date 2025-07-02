<?php
class DBB_Blocks_Utils {
    public static function format_value($key, $value) {
        if (is_numeric($value) && wp_attachment_is_image($value)) {
            return [
                'id'    => $value,
                'url'   => wp_get_attachment_url($value),
                'alt'   => get_post_meta($value, '_wp_attachment_image_alt', true),
                'title' => get_the_title($value),
            ];
        }
        return $value;
    }

    public static function generate_uuid() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function dbb_generate_indexjs_from_acf($short_name, $title, $category, $icon, $acf_fields) {
    $attributes = [];
    $edit_controls = [];
    $save_controls = [];

    foreach ($acf_fields as $field) {
        $name = $field['name'];
        $label = $field['label'];
        $type = $field['type'];
        $attr_line = "$name: { type: 'string' }";
        $edit = '';
        $save = '';

        switch ($type) {
            case 'text':
            case 'email':
            case 'url':
            case 'number':
            case 'range':
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: '$type',
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'textarea':
                $edit = "el('div', {}, el('label', {}, '$label'), el('textarea', {
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'true_false':
                $attr_line = "$name: { type: 'boolean', default: false }";
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: 'checkbox',
                    checked: props.attributes['$name'],
                    onChange: (e) => props.setAttributes({ $name: e.target.checked })
                }))";
                $save = "el('p', {}, props.attributes['$name'] ? 'Sí' : 'No')";
                break;

            case 'select':
            case 'radio':
                $options = isset($field['choices']) ? $field['choices'] : [];
                $optionsJs = json_encode($options, JSON_UNESCAPED_SLASHES);
                $edit = <<<JS
                    el('div', {}, 
                        el('label', {}, '$label'),
                        el('select', {
                            value: props.attributes['$name'] || '',
                            onChange: (e) => props.setAttributes({ $name: e.target.value })
                        },
                            Object.entries($optionsJs).map(([val, text]) =>
                                el('option', { value: val }, text)
                            )
                        )
                    )
                    JS;
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'image':
            case 'file':
                $attr_line = "$name: { type: 'string' }";
                $edit = <<<JS
                    el(wp.blockEditor.MediaUpload, {
                        onSelect: (media) => props.setAttributes({ $name: media.url }),
                        allowedTypes: ['image'],
                        render: ({ open }) => el('div', {},
                            el('p', {}, '$label'),
                            props.attributes['$name'] 
                                ? el('img', { src: props.attributes['$name'], style: { maxWidth: '100%', height: 'auto' } }) 
                                : null,
                            el('button', { onClick: open }, 'Seleccionar imagen')
                        )
                    })
                    JS;
                $save = "el('img', { src: props.attributes['$name'] || '', alt: '$label' })";
                break;

            case 'gallery':
                $attr_line = "$name: { type: 'array', default: [] }";
                $edit = <<<JS
                    el(wp.blockEditor.MediaUpload, {
                        onSelect: (media) => props.setAttributes({ $name: media.map(m => m.url) }),
                        allowedTypes: ['image'],
                        multiple: true,
                        gallery: true,
                        render: ({ open }) => el('div', {},
                            el('p', {}, '$label'),
                            props.attributes['$name']?.map((url, i) => el('img', { key: i, src: url, style: { width: '80px', marginRight: '5px' } })),
                            el('div', { style: { marginTop: '10px' } },
                            el('button', { onClick: open }, 'Seleccionar imágenes')
                            )
                        )
                    })
                    JS;
                $save = "el('div', {}, props.attributes['$name']?.map((url, i) => el('img', { key: i, src: url, style: { width: '100px', marginRight: '5px' } })))";
                break;

            case 'color_picker':
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: 'color',
                    value: props.attributes['$name'] || '#000000',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('div', { style: { backgroundColor: props.attributes['$name'], width: '50px', height: '20px' } })";
                break;

            case 'date_picker':
            case 'time_picker':
                $edit = "el('div', {}, el('label', {}, '$label'), el('input', {
                    type: 'text',
                    value: props.attributes['$name'] || '',
                    onChange: (e) => props.setAttributes({ $name: e.target.value })
                }))";
                $save = "el('p', {}, props.attributes['$name'])";
                break;

            case 'wysiwyg':
                $edit = <<<JS
                el(wp.blockEditor.RichText, {
                    tagName: 'div',
                    value: props.attributes['$name'] || '',
                    onChange: (content) => props.setAttributes({ $name: content }),
                    placeholder: '$label'
                })
                JS;
                                $save = "el(wp.blockEditor.RichText.Content, {
                    tagName: 'div',
                    value: props.attributes['$name'] || ''
                })";
                break;

                case 'repeater':
                    // Soporte limitado: solo mostramos contador
                    $attr_line = "$name: { type: 'string' }";
                    $edit = "el('p', {}, '$label (campo repeater no editable desde bloque)')";
                    $save = "el('p', {}, '$label (repeater)')";
                    break;

                case 'post_object':
                case 'user':
                case 'taxonomy':
                case 'relationship':
                    $edit = "el('div', {}, el('label', {}, '$label (ID)'), el('input', {
                        type: 'text',
                        value: props.attributes['$name'] || '',
                        onChange: (e) => props.setAttributes({ $name: e.target.value })
                    }))";
                    $save = "el('p', {}, 'ID relacionado: ' + props.attributes['$name'])";
                    break;

                default:
                    $edit = "el('div', {}, el('label', {}, '$label ($type)'), el('input', {
                        type: 'text',
                        value: props.attributes['$name'] || '',
                        onChange: (e) => props.setAttributes({ $name: e.target.value })
                    }))";
                    $save = "el('p', {}, props.attributes['$name'])";
            }

            $attributes[$name] = $attr_line;
            $edit_controls[] = $edit;
            $save_controls[] = $save;
        }

        $attributes_js = implode(",\n      ", $attributes);
        $edit_js = implode(",\n      ", $edit_controls);
        $save_js = implode(",\n      ", $save_controls);

        return <<<JS
        (function(blocks, blockEditor, element) {
        var el = element.createElement;
        var useBlockProps = blockEditor.useBlockProps;

        blocks.registerBlockType('dbb/{$short_name}', {
            title: '{$title}',
            icon: '{$icon}',
            category: '{$category}',
            attributes: {
            $attributes_js
            },
            edit: function(props) {
            const blockProps = useBlockProps();
            return el('div', blockProps, 
                $edit_js
            );
            },
            save: function(props) {
            const blockProps = useBlockProps.save();
            return el('div', blockProps, 
                $save_js
            );
            }
        });
        })(
        window.wp.blocks,
        window.wp.blockEditor,
        window.wp.element
        );
        JS;
    }
}

// Funciones globales para compatibilidad
function dbb_format_value($key, $value) {
    return DBB_Blocks_Utils::format_value($key, $value);
}

function dbb_generate_uuid() {
    return DBB_Blocks_Utils::generate_uuid();
}

function dbb_generate_indexjs_from_acf($short_name, $title, $category, $icon, $acf_fields) {
    return DBB_Blocks_Utils::generate_indexjs_from_acf($short_name, $title, $category, $icon, $acf_fields);
}

function dbb_decode_and_sanitize_svg_icon($icon) {
    if (strpos($icon, 'data:image/svg+xml;base64,') === 0) {
        $base64 = explode(',', $icon, 2)[1];
        $decoded = base64_decode($base64);
        if ($decoded) {
            // Sanitizar SVG
            $decoded = preg_replace('/<script.*?>.*?<\\/script>/is', '', $decoded);
            $decoded = preg_replace('/\\son\\w+=\"[^\"]*\"/i', '', $decoded);
            $decoded = preg_replace('/(href|xlink:href)\\s*=\\s*\"javascript:[^\"]*\"/i', '', $decoded);
            $decoded = preg_replace('/<(iframe|object|embed).*?>.*?<\\/\\1>/is', '', $decoded);
            return $decoded;
        }
    }
    return $icon;
}

function dbb_validate_svg_icon($svg) {
    $svg = trim($svg);
    if (strpos($svg, '<svg') !== 0 || substr($svg, -6) !== '</svg>') {
        return new WP_Error('invalid_svg', '⚠️ El ícono SVG no está bien formado. Debe comenzar con <svg> y terminar con </svg>.');
    }
    return $svg;
}
