<?php

require_once plugin_dir_path(__FILE__) . 'class-wp-list-table.php';

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
            $table_data = ($_POST['table_data']);

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
        } elseif (isset($_GET['table_deleted']) && $_GET['table_deleted'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Table deleted successfully!</p></div>';
        }

        echo '<h1>Complex Tables</h1>';
        echo '<p>A plugin to create, store, and display complex tables via shortcodes.</p>';
        echo '<h2>Shortcodes</h2>';

        $table_list = new Complex_Tables_List_Table();
        $table_list->prepare_items();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline">Complex Tables</h1>
            <a href="admin.php?page=complex-tables-create-edit" class="page-title-action">Add New</a>
            <hr class="wp-header-end">
            <form method="get">
                <input type="hidden" name="page" value="complex-tables">
                <?php $table_list->search_box('Search Tables', 'search_id'); ?>
            </form>
            <form method="post">
                <?php
                $table_list->display();
                ?>
            </form>
        </div>
        <?php

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

        echo '<form id="complex_tables_form" method="post" action="" enctype="multipart/form-data">';
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
        echo '<th scope="row"><label for="csv_has_header">CSV has header row?</label></th>';
        echo '<td><input type="checkbox" name="csv_has_header" id="csv_has_header"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="table_csv">Upload CSV</label></th>';
        echo '<td><input type="file" name="table_csv" id="table_csv" accept=".csv"></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"></th>';
        echo '<td><button type="button" id="insert_csv_json" class="button">Insert CSV</button></td>';
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
                    var json_editor;

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
                        json_editor = wp.codeEditor.initialize($("#table_data"), json_editor_settings);
                        var css_editor = wp.codeEditor.initialize($("#table_css"), css_editor_settings);
                    });

                    jQuery("#complex_tables_form").on("submit", function() {
                        var tableDataTextarea = jQuery("#table_data");
                        tableDataTextarea.val(json_editor.codemirror.getValue());
                        tableDataTextarea[0].defaultValue = tableDataTextarea.val();
                    });

                    function handleCsvFileUpload(file) {
                        var reader = new FileReader();
                        reader.onload = function(e) {
                            var csv = e.target.result;
                            var json = csvToJson(csv, jQuery("#csv_has_header").is(":checked"));
            
                            json_editor.codemirror.setValue(json);
                            jQuery("#table_data").val(json_editor.codemirror.getValue());
                        };
                        reader.readAsText(file);
                    }

                    jQuery("#insert_csv_json").on("click", function() {
                        var fileInput = document.getElementById("table_csv");
                        var file = fileInput.files[0];
            
                        if (file) {
                            handleCsvFileUpload(file);
                        } else {
                            alert("Please upload a CSV file.");
                        }
                    });

                    function csvToJson(csv, hasHeader) {
                        var lines = csv.split("\n");
                        var result = [];
                        var headers = hasHeader ? lines.shift().split(",") : null;

                        for (var i = 0; i < lines.length; i++) {
                            var obj = {};
                            var currentLine = lines[i].split(",");

                            for (var j = 0; j < currentLine.length; j++) {
                                obj[headers ? headers[j] : j] = currentLine[j];
                            }

                            result.push(obj);
                        }

                        return JSON.stringify(result);
                    }
                </script>';

        echo '</div>';
    }
}