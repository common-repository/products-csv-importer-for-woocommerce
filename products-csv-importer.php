<?php
/**
 * Plugin Name: Products CSV Importer for Woocommerce
 * Plugin URI: https://simplistics.ca
 * Description: Imports a CSV containing your products to Woocommerce
 * Version: 1.0
 * Author: Jonathan Boss
 * Author URI: https://simplistics.ca
 */

function wpci_admin() {
	add_menu_page("Products Importer", "Products Importer", 9, "wpci_importer_view", "wpci_importer_view");
	//THIS FUNCTIONALITY TO BE ADDED IN LATER VERSIONS
	//add_submenu_page( "wpci_importer_view", "Product Importer Options", "Options", 9, "wpci_options_view", "wpci_options_view" );
}
add_action('admin_menu', 'wpci_admin');

//Admin page for importing products csv
function wpci_importer_view(){
	//DISPLAY IMPORTER VIEW
	require_once('views/wpci-importer-view.php');
}

//Admin page for setting plugin options
function wpci_options_view(){
	require_once('views/wpci-options-view.php');
}

function wpci_add_scripts(){
	$plugin_url = plugin_dir_url(__FILE__);

	wp_enqueue_style('wpci-styles', $plugin_url . 'style.css');

	wp_register_script('wpci-js', plugins_url() . '/products-csv-importer/views/js/wpci-js.js');
    wp_enqueue_script('wpci-js');
    wp_localize_script("wpci-js", 'wpci_ajax', array( 'ajaxurl' => admin_url('admin-ajax.php') ));
    wp_localize_script('wpci-js', 'wpci_nonce', array('ajax_nonce' => wp_create_nonce('wpci_nonce') ) );
}
add_action( 'admin_enqueue_scripts', 'wpci_add_scripts' );

function wpci_plugin_activation(){
	//CREATE NECESSARY PLUGIN TABLES
	global $wpdb;
	$charset_collate = $wpdb->get_charset_collate();

	//CREATE OPTIONS TABLE
	$options_table = $wpdb->prefix . 'wpci_importer_options';
	$sql1 = "CREATE TABLE $options_table (
		id mediumint(9) NOT NULL AUTO_INCREMENT, 
		option varchar(255) NOT NULL, 
		value varchar(255) NOT NULL, 
		PRIMARY KEY  (id)
	) $charset_collate; ";
	
	//UNIQUE ID ASSOCIATIONS TABLE
	$id_associations = $wpdb->prefix . 'wpci_id_associations';
	$sql2 = "CREATE TABLE $id_associations (
		id mediumint(9) NOT NULL AUTO_INCREMENT, 
		unique_id mediumint(9) NOT NULL, 
		post_id mediumint(9) NOT NULL, 
		product_type varchar(50) NOT NULL, 
		PRIMARY KEY (id)
	) $charset_collate; ";
	
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta( $sql1 );
	dbDelta( $sql2 );

	//INSERT DEFAULT OPTIONS INTO OPTIONS TABLE
	$options_exist = $wpdb->get_results( 'SELECT * FROM ' . $options_table );
	if( empty($options_exist) ){
		$wpdb->insert( $options_table, array('option' => 'update_stock', 'value' => 0) );
	}

	//MAKE DIRECTORY FOR CSV
	$path = wp_upload_dir()['basedir'];
	mkdir($path . '/wpci-csv-temp');
}
register_activation_hook(__FILE__, 'wpci_plugin_activation');

//REQUIRE AJAX PROCESSING FILE
require_once('includes/wpci-import-csv-action.php');
require_once('includes/wpci-upload-csv.php');