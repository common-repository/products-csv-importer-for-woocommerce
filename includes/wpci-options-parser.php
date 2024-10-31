<?php

Class wpci_options_parser{

	public static function get_user_options(){
		//TO BE ADDED IN LATER VERSIONS
	}

	public static function check_update_stock(){
		global $wpdb;
		$table = $wpdb->prefix . 'wpci_importer_options';
		$update_stock = $wpdb->get_var( "SELECT `value` FROM $table WHERE `option` = 'update_stock';" );
		return $update_stock;
	}

	// public static function get_custom_meta_fields(){
	// 	global $wpdb;
	// 	$table = $wpdb->prefix . 'wpci_custom_meta_fields';
	// 	$custom_fields = $wpdb->get_results('SELECT * FROM wpci_custom_meta_fields');
	// 	if( empty($custom_fields) ){
	// 		return false;
	// 	}

	// 	$structured_fields = array();
	// 	foreach($custom_fields as $custom_field){
	// 		$structured_fields[$custom_field->postmeta_key] = $custom_field->csv_column;
	// 	}
	// 	return $structured_fields;
	// }

}