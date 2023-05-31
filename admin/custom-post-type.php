<?php

class Custom_post_type {
    /**
     * Registers the 'complex_table' custom post type for the plugin.
     * 
     * @since 1.0.0
     * 
     * @return void
     */
    public function create_post_type() {
        $labels = [
            'name'                  => _x('Complex Tables', 'post type general name', 'complex-tables'),
            'singular_name'         => _x('Complex Table', 'post type singular name', 'complex-tables'),
            'menu_name'             => _x('Complex Tables', 'admin menu', 'complex-tables'),
            'name_admin_bar'        => _x('Complex Table', 'add new on admin bar', 'complex-tables'),
            'add_new'               => _x('Add New', 'complex table', 'complex-tables'),
            'add_new_item'          => __('Add New Complex Table', 'complex-tables'),
            'new_item'              => __('New Complex Table', 'complex-tables'),
            'edit_item'             => __('Edit Complex Table', 'complex-tables'),
            'view_item'             => __('View Complex Table', 'complex-tables'),
            'all_items'             => __('All Complex Tables', 'complex-tables'),
            'search_items'          => __('Search Complex Tables', 'complex-tables'),
            'parent_item_colon'     => __('Parent Complex Tables:', 'complex-tables'),
            'not_found'             => __('No complex tables found.', 'complex-tables'),
            'not_found_in_trash'    => __('No complex tables found in Trash.', 'complex-tables')
        ];

        $args = [
            'labels'                => $labels,
            'public'                => false,
            'publicly_queryable'    => false,
            'show_ui'               => false,
            'show_in_menu'          => false,
            'query_var'             => true,
            'rewrite'               => ['slug' => 'complex-table'],
            'capability_type'       => 'post',
            'has_archive'           => false,
            'hierarchical'          => false,
            'menu_position'         => null,
            'supports'              => ['title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments']
        ];

        register_post_type('complex_table', $args);
    }
}