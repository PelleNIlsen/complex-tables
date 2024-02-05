<?php
/**
 * Plugin Name: Complex Tables
 * Plugin URI: https://github.com/PelleNIlsen/complex-tables/blob/main/readme.md
 * Description: A plugin to create, store ,and display complex tables via shortcodes.
 * Version: 2.0.5
 * Author: Pelle Nilsen
 * Author URI: https://github.com/PelleNIlsen
 * License: GPLv2 or later
 * Text Domain: complex-tables
 */

include 'admin/admin-menu.php';
include 'admin/custom-post-type.php';
include 'shortcode-handler.php';

/**
 * Registers and initializes the Complex Tables plugin's custom post type and admin menu.
 * 
 * @since 1.0.0
 * 
 * @return void
 */
function complex_tables_admin() {
    $admin_menu = new Admin_menu();
    add_action( 'admin_menu', [ $admin_menu, 'create_admin_menu' ] );

    $custom_post_type = new Custom_post_type();
    add_action( 'init', [ $custom_post_type, 'create_post_type' ] );
}
add_action( 'plugins_loaded', 'complex_tables_admin' );

/**
 * Updates and existing post in the WordPress database with the specified table ID, name, and data.
 * 
 * @since 1.0.0
 * 
 * @param int $table_id         The ID of the post containing the table tp update.
 * @param string $table_name    The new name for the table.
 * @param string $table_data    The new data for the table
 * 
 * @return void
 */
function update_complex_table( $table_id, $table_name, $table_data ) {
    $table_post = [
        'ID'            => $table_id,
        'post_title'    => $table_name,
        'post_content'  => $table_data,
    ];

    wp_update_post( $table_post );
}

/**
 * Creates a new custom post in the WordPress database with the specified name and data, of post type 'complex_table'
 * 
 * @since 1.0.0
 * 
 * @param string $table_name    The name for the new table
 * @param string $table_data    The data for the new table
 * 
 * @return int|WP_Error         The ID of the newly created post on success, or a WP_Error object on failure.
 */
function create_new_complex_table( $table_name, $table_data ) {
    $table_post = [
        'post_title'    => $table_name,
        'post_content'  => $table_data,
        'post_status'   => 'publish',
        'post_type'     => 'complex_table'
    ];

    $table_id = wp_insert_post( $table_post );
    return $table_id;
}

/**
 * Callback function for the AJAX request to generate HTML for a table shortcode.
 * 
 * @since 1.0.0
 * 
 * @return void
 */
function generate_table_html_callback() {
    check_ajax_referer( 'generate_table_html', 'security' );

    if ( isset( $_POST[ 'table_data' ] ) ) {
        $shortcode_handler = new Shortcode_Handler();
        $table_data = stripslashes( $_POST[ 'table_data' ] );
        echo $shortcode_handler->generate_table_html( $table_data );
    }

    wp_die();
}
add_action( 'wp_ajax_generate_table_html', 'generate_table_html_callback' );
 

new Shortcode_Handler();