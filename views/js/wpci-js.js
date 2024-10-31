var request_running = 0;

jQuery(document).ready(function($){
	$('#upload_products').submit(function(e){
		var file = $('.file_upload').val();
		if('' == file){
			$('.message-output').html('Please Select a File');
			$('.message-output').css('color','red');
			e.preventDefault();
		}
		else if(file.split('.').pop() !== 'csv'){
			$('.message-output').html('.CSV file required');
			$('.message-output').css('color','red');
			e.preventDefault();
		}
	});

	$(document).on('click', '#close-importer-window', function(){
		$('#loading-screen').fadeOut();
	});
});

function wpci_request_run( request_running ){
	jQuery('#loading-screen').fadeIn();
	data = {
		request:request_running,
		action:'wpci_import_request',
		wpci_nonce:wpci_nonce.ajax_nonce
	}
	jQuery.post(wpci_ajax.ajaxurl, data, function(res){
		if(!res){
			jQuery('#loading-display p').html('');
			jQuery('#loading-display h2').html('Your Products are ready to Go!');
			jQuery('#loading-display h2').after('<button id="close-importer-window">Close</button>');
		}
		else{
			jQuery('#running_total').html(res);
			wpci_request_run(res);
		}
	});
}

function wpci_show_errors(errors){
	var err = jQuery.parseJSON(errors);
	console.log(err);
}