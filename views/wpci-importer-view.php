<?php if(!isset($_SESSION)) { session_start(); } ?>

<div class='wpci-wrapper'>
	<h1>Products CSV Importer</h1>
	<?php $plugin_url = plugin_dir_url(__FILE__); ?>
	<div class="wrapper notifications-page">
		<form method='post' id='upload_products' enctype='multipart/form-data' action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
			<input type="hidden" name="action" value="wpci_upload_csv">
			<div>
				<label>Upload Product CSV</label>
				<p style='color:red;'>This will overwrite your current products.<br/>The upload document is meant to be an exhaustive list of all your store's products.</p>
				<input class='file_upload' type='file' name='csv-file'/>
			</div>
			<input type='submit' value='Import Products' name='csv-submit' class='submit-btn'/>
			<span class='message-output'></span>
			<?php wp_nonce_field('wpci-upload-csv-nonce','wpci-upload-csv-nonce'); ?>
		</form>
		<p><span style='color:red'>IMPORTANT:</span> This plugin expects a .csv import document in a specific format. <br/>For details, please consult the <a href="<?php echo plugins_url() . '/products-csv-importer-for-woocommerce/readme.txt';?>" download>readme file</a>, and follow the <a href="<?php echo plugins_url() . '/products-csv-importer-for-woocommerce/import_template.csv';?>" download>import_template.csv file</a> included in this plugin.</p>
		<div id='loading-screen'>
			<div id='loading-display'>
				<h2>Uploading Products, Please Wait...</h2>
				<p>Progress : <span id='running_total'>0</span> / <?php echo $_SESSION['wpci_csv_total']; ?> rows processed</p>
			</div>
		</div>
		<?php if(isset($_SESSION['wpci_csv_total'])) : ?>
			<script> wpci_request_run(0); </script>
		<?php elseif(isset($_SESSION['wpci_errors'])) : ?>
			<script> wpci_show_errors(<?php echo json_encode($_SESSION['wpci_errors']); ?>); </script>
		<?php endif; ?>
		<?php 
			unset($_SESSION['wpci_csv_total']);
			unset($_SESSION['wpci_errors']);
		?>
	</div>
	<?php do_action('wpci_add_exporter'); ?>
</div>