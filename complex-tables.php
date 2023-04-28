<?php
/**
 * Plugin Name: Complex Tables
 * Plugin URI: https://blisynlig.com
 * Description: A plugin to create, store ,and display complex tables via shortcodes.
 * Version: 1.0.0
 * Author: Pelle Nilsen
 * Author URI: https://blisynlig.com
 * License: GPLv2 or later
 * Text Domain: complex-tables
 */

include 'admin/admin-menu.php';
include 'admin/custom-post-type.php';
include 'shortcode-handler.php';

function complex_tables_admin() {
    $admin_menu = new Admin_menu();
    add_action( 'admin_menu', [$admin_menu, 'create_admin_menu'] );

    $custom_post_type = new Custom_post_type();
    add_action( 'init', [$custom_post_type, 'create_post_type'] );
}
add_action( 'plugins_loaded', 'complex_tables_admin' );

function update_complex_table($table_id, $table_name, $table_data) {
    $table_post = [
        'ID'            => $table_id,
        'post_title'    => $table_name,
        'post_content'  => $table_data,
    ];

    wp_update_post($table_post);
}

function create_new_complex_table($table_name, $table_data) {
    $table_post = [
        'post_title'    => $table_name,
        'post_content'  => $table_data,
        'post_status'   => 'publish',
        'post_type'     => 'complex_table'
    ];

    $table_id = wp_insert_post($table_post);
    return $table_id;
}

function generate_table_html_callback() {
    check_ajax_referer('generate_table_html', 'security');

    if (isset($_POST['table_data'])) {
        $shortcode_handler = new Shortcode_Handler();
        $table_data = stripslashes($_POST['table_data']);
        echo $shortcode_handler->generate_table_html($table_data);
    }

    wp_die();
}
add_action('wp_ajax_generate_table_html', 'generate_table_html_callback');
 

new Shortcode_Handler();