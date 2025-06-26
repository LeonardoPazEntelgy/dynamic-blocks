<?php
class DBB_ACF_Integration {
    public function __construct() {
        add_action('acf/init', [$this, 'init_acf_integration']);
    }

    public function init_acf_integration() {
        if (!function_exists('acf_get_field_groups')) return;

        $groups = acf_get_field_groups();

        foreach ($groups as $group) {
            foreach ($group['location'] as $ruleset) {
                foreach ($ruleset as $rule) {
                    if ($rule['param'] === 'block') {
                        $this->process_acf_block($group, $rule['value']);
                    }
                }
            }
        }
    }

    private function process_acf_block($group, $block_value) {
        $block_name = str_replace('acf/', '', $block_value);
        $block_folder = str_replace('dbb-', '', $block_name);
        // $dir = DBB_PLUGIN_DIR . "blocks/$block_folder/";
        $dir = DBB_USER_BLOCKS_DIR . $block_folder . '/';

        if (!is_dir($dir)) return;

        $fields = acf_get_fields($group['key']);
        if (!$fields) return;

        $content = <<<PHP
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

        foreach ($fields as $field) {
            $label = addslashes($field['label']);
            $name = $field['name'];
            $content .= "\ndbb_render_field_visual('$label', get_field('$name'));";
        }

        $content .= "\necho '</div>';\n";

        file_put_contents($dir . 'render.php', $content);
    }
}