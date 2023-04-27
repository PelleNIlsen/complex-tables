<?php

class Admin_menu {
    public function create_admin_menu() {
        add_menu_page(
            'Complex Tables',
            'Complex Tables',
            'manage_options',
            'complex-tables',
            [$this, 'complex_tables_main_page'],
            'dashicons-editor-table',
            25
        );

        add_submenu_page(
            'complex-tables',
            'Create/Edit Table',
            'Create/Edit Table',
            'manage_options',
            'complex-tables-create-edit',
            [$this, 'complex_tables_create_edit_page']
        );

        add_action('admin_enqueue_scripts', [$this, 'enqueue_codemirror_assets']);
    }

    private function get_all_tables() {
        $args = [
            'post_type'         => 'complex_table',
            'post_status'       => 'publish',
            'posts_per_page'    => -1,
            'orderby'           => 'ID',
            'order'             => 'ASC'
        ];

        $tables = new WP_Query($args);
        return $tables;
    }

    public function enqueue_codemirror_assets() {
        $screen = get_current_screen();
        if (strpos($screen->base, 'complex-tables-create-edit') === false) {
            return;
        }

        wp_enqueue_code_editor(['type' => 'application/json']);
        wp_enqueue_style('wp-codemirror');
    }

    private function handle_form_submissions() {
        if (isset($_POST['complex_tables_submit'])) {
            if (!wp_verify_nonce($_POST['complex_tables_nonce'], 'complex_tables_create_edit')) {
                die(__('Security check failed', 'complex-tables'));
            }

            $table_name = sanitize_text_field($_POST['table_name']);
            $table_data = stripslashes($_POST['table_data']);

            if (empty($_POST['table_id'])) {
                $table_id = create_new_complex_table($table_name, $table_data);
            } else {
                $table_id = intval($_POST['table_id']);
                update_complex_table($table_id, $table_name, $table_data);
            }
            $table_css = stripslashes($_POST['table_css']);
            update_post_meta($table_id, '_complex_tables_custom_css', $table_css);

            wp_redirect('admin.php?page=complex-tables&table_saved=1');
            exit;
        }
    }

    private function handle_delete_action() {
        if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['table_id'])) {
            $table_id = intval($_GET['table_id']);

            if (!wp_verify_nonce($_GET['complex_tables_nonce'], 'delete_table_' . $table_id)) {
                die(__('Security check failed', 'complex-tables'));
            }

            wp_delete_post($table_id, true);
            wp_redirect('admin.php?page=complex-tables&table_deleted=1');
            exit;
        }
    }

    function complex_tables_main_page() {
        $this->handle_delete_action();

        echo '<div class="wrap">';

        if (isset($_GET['table_saved']) && $_GET['table_saved'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Table saved successfully!</p></div>';
        }

        echo '<h1>Complex Tables</h1>';
        echo '<p>A plugin to create, store, and display complex tables via shortcodes.</p>';
        echo '<h2>Shortcodes</h2>';

        // Fetch all tables
        $tables = $this->get_all_tables();

        if ($tables->have_posts()) {
            echo '<table class="widefat">';
            echo '<thead>';
            echo '<tr>';
            echo '<th>Table ID</th>';
            echo '<th>Table Name</th>';
            echo '<th>Shortcode</th>';
            echo '<th>Actions</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            while ($tables->have_posts()) {
                $tables->the_post();
                $table_id = get_the_ID();
                $table_name = get_the_title();

                echo '<tr>';
                echo '<td>' . $table_id . '</td>';
                echo '<td>' . $table_name . '</td>';
                echo '<td>[complex_table id="' . $table_id . '"]</td>';
                echo '<td><a href="admin.php?page=complex-tables-create-edit&table_id=' . $table_id . '">Edit</a> | <a href="admin.php?page=complex-tables&table_id=' . $table_id . '&action=delete&complex_tables_nonce=' . wp_create_nonce('delete_table_' . $table_id) . '" onclick="return confirm(\'Are you sure you want to delete this table?\');">Delete</a></td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
        } else {
            echo '<p>No tables found.</p>';
        }

        echo '<p><a href="admin.php?page=complex-tables-create-edit" class="button button-primary">Create New Table</a></p>';
        echo '</div>';
    }

    function complex_tables_create_edit_page() {
        $this->handle_form_submissions();

        $table_id = isset($_GET['table_id']) ? intval($_GET['table_id']) : null;
        $table = $table_id ? get_post($table_id) : null;

        $table_name = $table ? $table->post_title : '';
        $table_data = $table ? $table->post_content : '';

        $table_meta = $table_id ? get_post_meta($table_id) : [];
        $table_css = isset($table_meta['_complex_tables_custom_css'][0]) ? $table_meta['_complex_tables_custom_css'][0] : '';

        echo '<div class="wrap">';
        echo '<h1>Create/Edit Table</h1>';

        echo '<form method="post" action="">';
        echo '<input type="hidden" name="table_id" value="' . esc_attr($table_id) . '">';
        wp_nonce_field('complex_tables_create_edit', 'complex_tables_nonce');

        echo '<table class="form-table">';
        echo '<tbody>';
        echo '<tr>';
        echo '<th scope="row"><Label for="table_name">Table Name</label></th>';
        echo '<td><input type="text" name="table_name" id="table_name" value="' . esc_attr($table_name) . '" class="regular-text"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><Label for="table_data">Table Data (JSON)</label></th>';
        echo '<td><textarea name="table_data" id="table_data" rows="10" class="large-text code">' . esc_textarea($table_data) . '</textarea></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><Label for="table_css">Custom CSS</label></th>';
        echo '<td><textarea name="table_css" id="table_css" rows="5" cols="60">' . esc_textarea($table_css) . '</textarea></td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';

        echo '<p class="submit">';
        echo '<input type="submit" name="complex_tables_submit" id="submit" class="button button-primary" value="Save Table">';
        echo '</p>';
        echo '</form>';

        echo '<script>
                    jQuery(document).ready(function($) {
                        var json_editor_settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                        json_editor_settings.codemirror = _.extend({}, json_editor_settings.codemirror, {
                            indentUnit: 4,
                            tabSize: 4,
                            mode: "application/json"
                        });
                        var css_editor_settings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
                        css_editor_settings.codemirror = _.extend({}, css_editor_settings.codemirror, {
                            indentUnit: 4,
                            tabSize: 4,
                            mode: "css",
                            lint: false
                        });
                        var json_editor = wp.codeEditor.initialize($("#table_data"), json_editor_settings);
                        var css_editor = wp.codeEditor.initialize($("#table_css"), css_editor_settings);
                    });

                    //jQuery("#table_css").on("input", function() {
                        //var style_tag_id = "live-preview-style";
                        //var style_element = jQuery("#" + style_tag_id);
                        //if (!style_element.length) {
                            //jQuery("head").append("<style id=\'" + style_tag_id + "\'></style>");
                            //style_element = jQuery("#" + style_tag_id);
                        //}
                        //style_element.text(jQuery(this).val());
                    //});
                </script>';


        //echo '<div class="preview-table">';
       /* echo '<table class="table-live-preview">
                <tr>
                    <th>Company</th>
                    <th>Contact</th>
                    <th>Country</th>
                </tr>
                <tr>
                    <td>Alfreds Futterkiste</td>
                    <td>Maria Anders</td>
                    <td>Germany</td>
                </tr>
                <tr>
                    <td>Centro comercial Moctezuma</td>
                    <td>Francisco Chang</td>
                    <td>Mexico</td>
                </tr>
                <tr>
                    <td>Ernst Handel</td>
                    <td>Roland Mendel</td>
                    <td>Austria</td>
                </tr>
                <tr>
                    <td>Island Trading</td>
                    <td>Helen Bennett</td>
                    <td>UK</td>
                </tr>
                <tr>
                    <td>Laughing Bacchus Winecellars</td>
                    <td>Yoshi Tannamuri</td>
                    <td>Canada</td>
                </tr>
                <tr>
                    <td>Magazzini Alimentari Riuniti</td>
                    <td>Giovanni Rovelli</td>
                    <td>Italy</td>
                </tr>
            </table>';
        echo '</div>'; */

        echo '</div>';
    }
}