<?php
class DBB_Blocks_Render {
    public static function render_field_visual($label, $field) {
        if (empty($field)) return;

        echo '<div class="acf-block-field" style="margin-bottom: 1.5em;">';

        if (is_numeric($field)) {
            $url = wp_get_attachment_url($field);
            if ($url) {
                echo '<figure class="wp-block-image">';
                echo '<img src="' . esc_url($url) . '" alt="' . esc_attr($label) . '" style="max-width:100%; height:auto;" />';
                echo '</figure>';
            }
        } elseif (is_array($field) && isset($field['url'])) {
            if (isset($field['mime_type']) && strpos($field['mime_type'], 'image/') === 0) {
                echo '<figure class="wp-block-image">';
                echo '<img src="' . esc_url($field['url']) . '" alt="' . esc_attr($label) . '" style="max-width:100%; height:auto;" />';
                echo '</figure>';
            } else {
                echo '<div class="wp-block-button">';
                echo '<a class="wp-block-button__link wp-element-button" href="' . esc_url($field['url']) . '" target="' . esc_attr($field['target'] ?? '_self') . '">';
                echo esc_html($field['title'] ?? $field['url']);
                echo '</a></div>';
            }
        } elseif (is_string($field) && preg_match('/\.(jpg|jpeg|png|gif|webp)\$/i', $field)) {
            echo '<figure class="wp-block-image">';
            echo '<img src="' . esc_url($field) . '" alt="' . esc_attr($label) . '" style="max-width:100%; height:auto;" />';
            echo '</figure>';
        } elseif (is_string($field)) {
            if (preg_match('/title|título/i', $label)) {
                echo '<h2 class="wp-block-heading has-text-align-center">' . esc_html($field) . '</h2>';
            } elseif (preg_match('/text|contenido|descripcion/i', $label)) {
                echo '<div class="has-text-align-center"><p>' . wp_kses_post($field) . '</p></div>';
            } else {
                echo '<p><strong>' . esc_html($label) . ':</strong> ' . esc_html($field) . '</p>';
            }
        } else {
            echo '<pre>' . esc_html(print_r($field, true)) . '</pre>';
        }

        echo '</div>';
    }

    public static function generate_render_template($block_path, $acf_fields = []) {
        $render_code = <<<PHP
        <?php
        if (!function_exists('get_field')) {
            echo '<p><em>ACF no está activo.</em></p>';
            return;
        }

        if (!function_exists('dbb_render_field_visual')) {
            function dbb_render_field_visual(\$label, \$field) {
                if (empty(\$field)) return;

                echo '<div class="acf-block-field" style="margin-bottom: 1.5em;">';

                if (is_numeric(\$field)) {
                    \$url = wp_get_attachment_url(\$field);
                    if (\$url) {
                        echo '<figure class="wp-block-image">';
                        echo '<img src="' . esc_url(\$url) . '" alt="' . esc_attr(\$label) . '" style="max-width:100%; height:auto;" />';
                        echo '</figure>';
                    }
                } elseif (is_array(\$field) && isset(\$field['url'])) {
                    if (isset(\$field['mime_type']) && strpos(\$field['mime_type'], 'image/') === 0) {
                        echo '<figure class="wp-block-image">';
                        echo '<img src="' . esc_url(\$field['url']) . '" alt="' . esc_attr(\$label) . '" style="max-width:100%; height:auto;" />';
                        echo '</figure>';
                    } else {
                        echo '<div class="wp-block-button">';
                        echo '<a class="wp-block-button__link wp-element-button" href="' . esc_url(\$field['url']) . '" target="' . esc_attr(\$field['target'] ?? '_self') . '">';
                        echo esc_html(\$field['title'] ?? \$field['url']);
                        echo '</a></div>';
                    }
                } elseif (is_string(\$field) && preg_match('/\.(jpg|jpeg|png|gif|webp)\$/i', \$field)) {
                    echo '<figure class="wp-block-image">';
                    echo '<img src="' . esc_url(\$field) . '" alt="' . esc_attr(\$label) . '" style="max-width:100%; height:auto;" />';
                    echo '</figure>';
                } elseif (is_string(\$field)) {
                    if (preg_match('/title|título/i', \$label)) {
                        echo '<h2 class="wp-block-heading has-text-align-center">' . esc_html(\$field) . '</h2>';
                    } elseif (preg_match('/text|contenido|descripcion/i', \$label)) {
                        echo '<div class="has-text-align-center"><p>' . wp_kses_post(\$field) . '</p></div>';
                    } else {
                        echo '<p><strong>' . esc_html(\$label) . ':</strong> ' . esc_html(\$field) . '</p>';
                    }
                } else {
                    echo '<pre>' . esc_html(print_r(\$field, true)) . '</pre>';
                }

                echo '</div>';
            }
        }

        echo '<div class="wp-block-group is-style-default" style="padding:2em;">';
        PHP;

        foreach ($acf_fields as $field_label => $field_name) {
            $render_code .= "\ndbb_render_field_visual('" . addslashes($field_label) . "', get_field('" . esc_sql($field_name) . "'));";
        }

        $render_code .= "\necho '</div>';";

        file_put_contents($block_path . '/render.php', $render_code);
    }
}

// Funciones globales para compatibilidad
function dbb_render_field_visual($label, $field) {
    DBB_Blocks_Render::render_field_visual($label, $field);
}

function dbb_generate_render_template($block_path, $acf_fields = []) {
    DBB_Blocks_Render::generate_render_template($block_path, $acf_fields);
}