<?php // *** PROCESS CSV UPLOAD ***

add_action("wp_ajax_wpci_import_request", "wpci_import_request");

function wpci_import_request(){
	check_ajax_referer('wpci_nonce', 'wpci_nonce');
	if(!isset($_SESSION)) { session_start(); }
	require_once('wpci-importer-class.php');
	$request_count = (int)$_POST['request'];
	if(0 == $request_count){
		wpci_first_request($request_count);
	}
	$response = wpci_run_request($request_count);
	echo $response;
	die();
}

function wpci_first_request(){
	//INIT FUNCTIONS TO SET STATIC PROPERTIES
	wpci_importer::clear_products();
	wpci_importer::set_all_product_categories();
	wpci_importer::set_all_product_images();
	wpci_importer::set_id_associations();

	$_SESSION['wpci_all_product_categories'] 	= wpci_importer::get_all_product_categories();
	$_SESSION['wpci_all_images'] 				= wpci_importer::get_all_product_images();
	$_SESSION['wpci_id_associations']			= wpci_importer::get_id_association();
}

function wpci_run_request($row_count){	
	
	if(0 != $row_count){
		wpci_reset_values_from_session();
	}
	else{
		$row_count = null;
	}

	$file_name = $_SESSION['wpci_csv'];
	$handle = fopen( $file_name, "r");
	$row = null;
	$current_count = 0;
	$row_start = $row_count;

	//LOOP THROUGH ALL ROWS IN THE CSV
	while( $next_row = fgetcsv($handle, 0, ',') ){

		//DECIDE WHICH ROWS TO RUN
		if($current_count < $row_start){
			$current_count ++;
			continue;
		}
		elseif( $row_count == ($row_start + 50) ){
			$_SESSION['wpci_last_parent_id']			 = wpci_importer::get_last_parent_id();
			$_SESSION['wpci_new_id_associations']		 = wpci_importer::get_new_id_associations();
			$_SESSION['wpci_all_product_categories']	 = wpci_importer::get_all_product_categories();
			return $row_count;
		}

		//SET ATTRIBUTES BASED ON FIRST ROW
		if( $row_count === null ){
			wpci_importer::set_csv_atts($next_row);
			wpci_importer::set_custom_meta_fields($next_row);
			$row_count = 0;
			continue;
		}
		//STORE ROW
		if( null === $row ){
			$row = $next_row;
			continue;
		}

		wpci_process_product_row($row, $next_row, $product);
		$row_count ++;
		$row = $next_row;
	}
	wpci_process_product_row($row, false, $product);
	wpci_last_request();
	return false;
}

function wpci_last_request(){
	//UPDATE ID ASSOCIATIONS TABLE WITH NEW DATA
	wpci_importer::save_id_associations();
	//EXECUTE CUSTOM USER-DEFINED HOOK
	do_action('wpci_importer_finished');
	foreach($_SESSION as $session_key => $session_val){
		if( strpos($session_key, 'wpci_') !== false){
			unset($_SESSION[$session_key]);
		}
	}
}

function wpci_process_product_row($row, $next_row, $product){
	//GET THE ROW'S BASIC DETAILS
	$product = wpci_importer::get_row_basic_details($row);
	//ADD PRODUCT TYPE
	$product = wpci_importer::check_variable($row, $product, $next_row);
	//ADD IMAGE DATA
	$product = wpci_importer::get_row_image_data($row, $product);
	//ADD ATTRIBUTRES
	$product = wpci_importer::get_row_atts($row, $product);
	//ADD CUSTOM META FIELDS
	$product = wpci_importer::get_row_custom_meta($row, $product);
	//IMPORT THE PRODUCT
	wpci_importer::import_product($product);
}

function wpci_reset_values_from_session(){
	wpci_importer::reset_all_product_categories();
	wpci_importer::reset_all_product_images();
	wpci_importer::reset_id_associations();
	wpci_importer::reset_last_parent_id();
	wpci_importer::reset_new_id_associations();
	wpci_importer::reset_custom_meta_fields();
	wpci_importer::reset_csv_atts();
}
