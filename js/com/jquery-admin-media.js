jQuery(document).ready(function ($) {
	var sucom_custom_uploader;
	$('.sucom_image_upload_button').click(function (e) {
		opt_prefix = $(this).attr('id').replace(/^button_/,'');
		e.preventDefault();
		if (sucom_custom_uploader) {
			sucom_custom_uploader.open();
			return;
		}
		sucom_custom_uploader = wp.media.frames.file_frame = wp.media({
			title   : sucomMediaL10n.choose_image,
			button  : { text: sucomMediaL10n.choose_image },
			multiple: false
		});
		sucom_custom_uploader.on( 'select', function () {
			var attachment = sucom_custom_uploader.state().get('selection').first().toJSON();
			$('#text_'+opt_prefix+'_id').val(attachment.id);
			$('#select_'+opt_prefix+'_id_pre').val('wp');
			$('#text_'+opt_prefix+'_url').prop('disabled', true);
		});
		sucom_custom_uploader.open();
	});
});
