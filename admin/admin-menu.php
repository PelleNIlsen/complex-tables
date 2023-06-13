<?php

require_once plugin_dir_path(__FILE__) . 'class-wp-list-table.php';

class Admin_menu {
    /**
     * Registers the plugin's actions and scripts for the WordPress admin area.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function __construct() {
        add_action('admin_init', [$this, 'handle_actions']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts_and_styles']);
    }

    /**
     * Handles form submissions and delete actions for the plugin.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function handle_actions() {
        $this->handle_form_submissions();
        $this->handle_delete_action();
    }

    /**
     * Enqueues the necessary scripts and styles for the plugin's admin page.
     * 
     * @since 1.0.0
     * 
     * @param string $hook  The current admin page hook.
     * 
     * @return void
     */
    public function enqueue_scripts_and_styles($hook) {
        if ($hook !== 'toplevel_page_complex_tables') {
            return;
        }

        wp_enqueue_script('jquery-ui');
        wp_enqueue_script('jquery-ui-accordion');
        wp_enqueue_style('wp-jquery-ui-dialog');
    }

    /**
     * Created the plugin's admin menu and two submenus and enqueues necessary assets for the plugin's pages.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
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

    /**
     * Retrieves all published complex tables from the database.
     * 
     * @since 1.0.0
     * 
     * @return WP_Query     A WP_Query object containing all published complex tables.
     */
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

    /**
     * Enqueues necessary CodeMirror assets for the plugin's "Create/Edit Table" page.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function enqueue_codemirror_assets() {
        $screen = get_current_screen();
        if (strpos($screen->base, 'complex-tables-create-edit') === false) {
            return;
        }

        wp_enqueue_code_editor(['type' => 'application/json']);
        wp_enqueue_style('wp-codemirror');
    }

    /**
     * Handles form submissions for creating/editing complex tables.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    private function handle_form_submissions() {
        if (!isset($_POST['complex_tables_submit'])) {
            return;
        }

        $nonce = $_POST['complex_tables_nonce'];
        if (!wp_verify_nonce($nonce, 'complex_tables_create_edit')) {
            die(__('Security check failed', 'complex_tables'));
        }

        $table_name = sanitize_text_field($_POST['table_name']);
        $table_data = ($_POST['table_data']);
        if (empty($_POST['table_id'])) {
            $table_id = create_new_complex_table($table_name, $table_data);
        } else {
            $table_id = (int) $_POST['table_id'];
            update_complex_table($table_id, $table_name, $table_data);
        }

        $table_css = wp_unslash($_POST['table_css']);
        $table_class = sanitize_text_field($_POST['table_class']);
        update_post_meta($table_id, '_complex_tables_custom_css', $table_css);
        update_post_meta($table_id, '_complex_tables_class', $table_class);
        wp_safe_redirect('admin.php?page=complex-tables&table_saved=1');
        exit();
    }

    /**
     * Handles the delete action for complex tables.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
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

    /**
     * Displays the main page for the Complex Tables plugin, including a list of tables and shortcodes.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    function complex_tables_main_page() {
        $host = parse_url(home_url(), PHP_URL_HOST);
        $is_local = in_array($host, ['localhost', '127.0.0.1']);

        echo '<div class="wrap">';

        if (isset($_POST['submit_complex_table_feedback'])) {
            $feedback = sanitize_textarea_field($_POST['complex_table_feedback']);
            if(wp_mail('pellemnilsen@gmail.com', 'Plugin Feedback - Complex Tables', $feedback)) {
                echo '<div class="notice notice-success is-dismissible"><p>Feedback sent successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>Sorry, something went wrong. Please try again later.</p></div>';
            }
            // return;
        }

        if (isset($_GET['table_saved']) && $_GET['table_saved'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Table saved successfully!</p></div>';
        } elseif (isset($_GET['table_deleted']) && $_GET['table_deleted'] == '1') {
            echo '<div class="notice notice-success is-dismissible"><p>Table deleted successfully!</p></div>';
        }

        echo '<h1>Complex Tables</h1>';
        echo '<p>A plugin to create, store, and display complex tables via shortcodes.</p>';

        echo '<div id="feedback_accordion">';
        echo '<h3>Send Feedback</h3>';
        echo '<form method="post" class="feedbacl-form">
        <h2>Send Feedback</h2>
        <label for="complex_table_feedback">Let me know what you think:</label><br>
        <textarea name="complex_table_feedback" id="complex_table_feedback" rows="5" class="widefat"></textarea>
        <input type="submit" name="submit_complex_table_feedback" value="Submit" class="button button-primary">
        <p class="description">If the form doesn\'t work for various reasons, you can reach me at pellemnilsen@gmail.com</p>
    </form>';
        echo '</div>';

        echo '<script src="http://code.jquery.com/ui/1.10.0/jquery-ui.js"></script>';
        echo '<link rel="stylesheet" href="http://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">';
        echo '<script>
        jQuery(document).ready(function($) {
            jQuery("#feedback_accordion").accordion({
                heightStyle: "content",
                collapsible: true,
                active: false,
            });
        });
        </script>';

        if ($is_local) {
            echo '<p class="description">Note: Your WordPress installation is hosted on a local server. You can reach me at pellemnilsen@gmail.com</p>';
        }

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

    /**
     * Displays the "Create/Edit Table" page for the Complex Tables plugin, allowing users to create or edit tables.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    function complex_tables_create_edit_page() {
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
        echo '<tbody>'
        ;
        echo '<tr>';
        echo '<th scope="row"><Label for="table_name">Table Name</label></th>';
        echo '<td><input type="text" name="table_name" id="table_name" value="' . esc_attr($table_name) . '" class="regular-text"></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><Label for="table_data">Table Data (JSON)</label></th>';
        echo '<td><textarea name="table_data" id="table_data" rows="10" class="large-text code">' . esc_textarea($table_data) . '</textarea></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row">Upload CSV</th>';
        echo '<td>';
        echo '<div id="csv_accordion">';
        echo '<h3>Upload CSV</h3>';
        echo '<div>';
        echo '<p><label for="csv_has_header">CSV has header row?</label>';
        echo '<input type="checkbox" name="csv_has_header" id="csv_has_header"></p>';
        echo '<p><label for="table_csv">Upload CSV</label>';
        echo '<input type="file" name="table_csv" id="table_csv" accept=".csv"></p>';
        echo '<p><button type="button" id="insert_csv_json" class="button">Insert CSV</button></p>';
        echo '</div>';
        echo '</div>';
        echo '</td>';
        echo '</tr>';

        echo '<th scope="row">Excel Data</th>';
        echo '<td>';
        echo '<div id="excel_accordion">';
        echo '<h3>Excel Data</h3>';
        echo '<div>';
        echo '<p><label for="excel_data">Paste your data from Excel here:</label></p>';
        echo '<textarea id="excel_data" rows="5" class="large-text"></textarea>';
        echo '<p><button type="button" id="insert_excel_json" class="button">Insert Excel Data</button></p>';
        echo '</div>';
        echo '</div>';
        echo '</td>';
        
        echo '<tr>';
        echo '<th scope="row"><Label for="table_class">Table Class</label></th>';
        echo '<td><input type="text" name="table_class" id="table_class" value="' . esc_attr(isset($table_meta['_complex_tables_class'][0]) ? $table_meta['_complex_tables_class'][0] : '') . '" class="regular-text"></td>';
        echo '</tr>';
        
        echo '<tr>';
        echo '<th scope="row"><Label for="table_css">Custom CSS</label></th>';
        echo '<td><textarea name="table_css" id="table_css" rows="5" cols="60">' . esc_textarea($table_css) . '</textarea></td>';
        echo '</tr>';

        echo '<tr>';
        echo '<th scope="row"><Label>Pre-made Styles</label></th>';
        echo '<td><button class="button" type="button" id="pre-made-style-1">Basic Gray</button> <button class="button" type="button" id="pre-made-style-2">Zebra Stripes</button> <button class="button" type="button" id="pre-made-style-3">Vertical Zebra Stripes</button> <button class="button" type="button" id="pre-made-style-4">Combined Zebra Stripes</button> <button class="button" type="button" id="pre-made-style-5">Dark Theme</button></td>';
        echo '</tr>';

        echo '</tbody>';
        echo '</table>';

        echo '<p class="submit">';
        echo '<input type="submit" name="complex_tables_submit" id="submit" class="button button-primary" value="Save Table">';
        echo '</p>';
        echo '</form>';
        echo '<script src="http://code.jquery.com/ui/1.10.0/jquery-ui.js"></script>';
        echo '<link rel="stylesheet" href="http://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">';
        echo '<script>
                    var json_editor;
                    var css_editor;

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
                        css_editor = wp.codeEditor.initialize($("#table_css"), css_editor_settings);

                        jQuery("#csv_accordion, #excel_accordion").accordion({
                            heightStyle: "content",
                            collapsible: true,
                            active: false,
                        });
                    });

                    jQuery("#complex_tables_form").on("submit", function() {
                        var tableDataTextarea = jQuery("#table_data");
                        tableDataTextarea.val(json_editor.codemirror.getValue());
                        tableDataTextarea[0].defaultValue = tableDataTextarea.val();

                        var tableClass = jQuery("#table_class").val();
                        var cssTextarea = jQuery("#table_css");
                        var cssContent = css_editor.codemirror.getValue();
                        if (tableClass) {
                            cssContent = "." + tableClass + " {" + cssContent + "}";
                        }
                        cssTextarea.val(cssContent);
                        cssTextarea[0].defaultValue = cssTextarea.val();
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

                        return JSON.stringify(result, null, "\t");
                    }

                    jQuery("#insert_excel_json").on("click", function() {
                        var excelData = jQuery("#excel_data").val();

                        if (excelData) {
                            var json = excelToJson(excelData);
                            json_editor.codemirror.setValue(json);
                            jQuery("#table_data").val(json_editor.codemirror.getValue());
                        } else {
                            alert("Please enter Excel data into the textarea.");
                        }
                    });

                    function excelToJson(excelData) {
                        var lines = excelData.split("\n");
                        var result = [];
                        var headers = lines.shift().split("\t");

                        for (var i = 0; i < lines.length; i++) {
                            var obj = {};
                            var currentLine = lines[i].split("\t");

                            for (var j = 0; j < currentLine.length; j++) {
                                obj[headers[j]] = currentLine[j];
                            }

                            result.push(obj);
                        }

                        return JSON.stringify(result, null, "\t");
                    }

                    const preMadeStyles = [
`/* Pre-made style - Basic Gray */
table {
    font-family: arial, sans-serif;
    border-collapse: collapse;
    width: 100%;
}
  
table td, th {
    border: 1px solid #dddddd;
    text-align: left;
    padding: 8px;
}
  
table tr:nth-child(even) {
    background-color: #dddddd;
}`,
`/* Pre-made style - Zebra Stripes */
table {
    border-collapse: collapse;
    width: 100%;
}
  
th, td {
    text-align: left;
    padding: 8px;
}
  
tr:nth-child(even) {
    background-color: #D6EEEE;
}`,
`/* Pre-made style - Vertical Zebra Stripes */
table {
    border-collapse: collapse;
    width: 100%;
}

table th, td {
    text-align: left;
    padding: 8px;
}
  
table th:nth-child(even),td:nth-child(even) {
    background-color: #D6EEEE;
}`,
`/* Pre-made style - Combined Zebra Stripes */
table {
    border-collapse: collapse;
    width: 100%;
}

table th, td {
    text-align: left;
    padding: 8px;
}

table tr:nth-child(even) {
    background-color: rgba(150, 212, 212, 0.4);
}
  
table th:nth-child(even),td:nth-child(even) {
    background-color: rgba(150, 212, 212, 0.4);
}`,
`/* Pre-made style - Dark Theme */
table {
    width: 100%;
    border-collapse: collapse;
    font-family: Arial, sans-serif;
    color: #f0f0f0;
    text-align: left;
}

table th {
    background-color: #333;
    padding: 12px;
    border: 1px solid #444;
}

table td {
    padding: 8px;
    background-color: #222;
    border: 1px solid #444;
}

table tr:hover {
    cursor: pointer;
}`,
                    ];
            
                    document.getElementById("pre-made-style-1").addEventListener("click", () => {
                        css_editor.codemirror.setValue(preMadeStyles[0]);
                        jQuery("#table_css").val(css_editor.codemirror.getValue());
                    });
                    
                    document.getElementById("pre-made-style-2").addEventListener("click", () => {
                        css_editor.codemirror.setValue(preMadeStyles[1]);
                        jQuery("#table_css").val(css_editor.codemirror.getValue());
                    });

                    document.getElementById("pre-made-style-3").addEventListener("click", () => {
                        css_editor.codemirror.setValue(preMadeStyles[2]);
                        jQuery("#table_css").val(css_editor.codemirror.getValue());
                    });
                    
                    document.getElementById("pre-made-style-4").addEventListener("click", () => {
                        css_editor.codemirror.setValue(preMadeStyles[3]);
                        jQuery("#table_css").val(css_editor.codemirror.getValue());
                    });

                    document.getElementById("pre-made-style-5").addEventListener("click", () => {
                        css_editor.codemirror.setValue(preMadeStyles[4]);
                        jQuery("#table_css").val(css_editor.codemirror.getValue());
                    });
                      
                </script>';

        echo '</div>';
    }
}