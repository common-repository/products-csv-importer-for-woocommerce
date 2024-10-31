<?php
add_action('admin_post_wpci_upload_csv', 'wpci_upload_csv');

function wpci_upload_csv(){

	if( !wp_verify_nonce( $_POST['wpci-upload-csv-nonce'], 'wpci-upload-csv-nonce' ) ){
		exit;
	}

	// *** UPLOAD THE CSV TO TEMPORARY DIRECTORY ***
	session_start();

	//SET UPLOAD PARAMETERS
	$upload_ok = 1;
	$size_max = 21012500;
	$size_min = 1024;

	//GET FILE
	$file = $_FILES['csv-file'];
	$target_file = wp_upload_dir()['basedir'] . '/wpci-csv-temp/' . basename($_FILES["csv-file"]["name"]);

	//VALIDATION
	$errors = array();

	//FOR FILE SIZE
	if( filesize($file['tmp_name']) > $size_max ){
		$errors[] = 'File is too large. Maximum upload size is 50MB';
	}
	elseif( filesize($file['tmp_name']) < $size_min ){
		$errors[] = 'File must be a minimum of 1KB';
	}

	//FOR FILE TYPE
	$file_type = strtolower( pathinfo($target_file, PATHINFO_EXTENSION) );
	if( 'csv' != $file_type ){
		$errors[] = 'This plugin accepts only files of type .csv';
	}

	//CHECK IF ERRORS
	if( !empty($errors) ){
		$_SESSION['wpci_errors'] = $errors;
	}
	else{
		unset($_SESSION['wpci_errors']);
		if( move_uploaded_file($_FILES['csv-file']['tmp_name'], $target_file) ){
			$_SESSION['wpci_csv'] = $target_file;
			$fp = file($target_file);
			$_SESSION['wpci_csv_total'] = count($fp);
		}
		else{
			$_SESSION['wpci_errors'] = array('The file could not be uploaded to the server.');
		}
	}
	header("Location:" . admin_url() . "?page=wpci_importer_view");
}