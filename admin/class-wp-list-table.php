<?php

if ( !class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Complex_Tables_List_Table extends WP_List_Table {
    /**
     * Constructs a new custom post type for 'table' with its corresponding singular and
     * plural names.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function __construct() {
        parent::__construct( [
            'singular'  => 'table',
            'plural'    => 'tables',
            'ajax'      => false
        ] );
    }

    /**
     * Returns an array of column names for the 'table' custom post type table in the
     * WordPress admin panel.
     * 
     * @since 1.0.0
     * 
     * @return array    An array of column names along with their human-readable labels.
     */
    public function get_columns() {
        return [
            'table_id'      => 'Table ID',
            'table_name'    => 'Table Name',
            'shortcode'     => 'Shortcode',
            'actions'       => 'Actions'
        ];
    }

    /**
     * Prepares the table data for display in the WordPress admin panel by querying the 'complex_table'
     * custom post type and setting up pagination.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function prepare_items() {
        $columns = $this->get_columns();
        $this->_column_headers = [ $columns, [], [] ];

        $per_page = 10;
        $current_page = $this->get_pagenum();
        $offset = ( $current_page - 1 ) * $per_page;

        $args = [
            'post_type'         => 'complex_table',
            'post_status'       => 'publish',
            'posts_per_page'    => $per_page,
            'offset'            => $offset,
            'orderby'           => 'ID',
            'order'             => 'ASC'
        ];

        if ( !empty( $_REQUEST[ 's' ] ) ) {
            $args[ 's' ] = sanitize_text_field( $_REQUEST[ 's' ] );
        }

        $tables = new WP_Query( $args );

        $this->items = $tables->posts;

        $total_items = $tables->found_posts;
        $this->set_pagination_args( [
            'total_items'   => $total_items,
            'per_page'      => $per_page,
            'total_pages'   => ceil( $total_items / $per_page )
        ] );
    }

    /**
     * Handles the default output for each column in the 'table' custom post type table in
     * the WordPress admin panel.
     * 
     * @since 1.0.0
     * 
     * @param object $item          The Current 'table' custom post type object being displayed.
     * @param string $column_name   The name of the current column being displayed.
     * 
     * @return string               The HTML output for the current column of the current 'table' custom post type object.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'table_id':
                return $item->ID;
            case 'table_name':
                return $item->post_title;
            case 'shortcode':
                return '[complex_table id="' . $item->ID . '"]';
            case 'actions':
                $edit_url = 'admin.php?page=complex-tables-create-edit&table_id=' . $item->ID;
                $delete_url = 'admin.php?page=complex-tables&action=delete&table_id=' . $item->ID . '&complex_tables_nonce=' . wp_create_nonce( 'delete_table_' . $item->ID );
                $delete_link = sprintf( '<a href="%s" onclick="return confirm(\'Are you sure you want to delete this table?\');">Delete</a>', $delete_url );
                return sprintf( '<a href="%s">Edit</a> | %s', $edit_url, $delete_link );
            default:
                return '';
        }
    }
}