jQuery(document).ready(function($) {
	let mediaUploader;

	$('#wc-tp-upload-profile-picture').on('click', function(e) {
		e.preventDefault();

		if (mediaUploader) {
			mediaUploader.open();
			return;
		}

		mediaUploader = wp.media.frames.file_frame = wp.media({
			title: wcTPProfilePicture.selectTitle,
			button: {
				text: wcTPProfilePicture.useButton
			},
			multiple: false,
			library: {
				type: 'image'
			}
		});

		mediaUploader.on('select', function() {
			const attachment = mediaUploader.state().get('selection').first().toJSON();
			$('#wc_tp_profile_picture').val(attachment.id);
			$('#wc-tp-profile-picture-preview').html('<img src="' + attachment.url + '" style="max-width: 150px; height: auto; border-radius: 8px;" />');
			
			// Show remove button
			if (!$('#wc-tp-remove-profile-picture').length) {
				$('#wc-tp-upload-profile-picture').after('<button type="button" class="button" id="wc-tp-remove-profile-picture" style="margin-left: 5px;">' + wcTPProfilePicture.removeText + '</button>');
			}
		});

		mediaUploader.open();
	});

	$(document).on('click', '#wc-tp-remove-profile-picture', function(e) {
		e.preventDefault();
		$('#wc_tp_profile_picture').val('');
		$('#wc-tp-profile-picture-preview').html('');
		$(this).remove();
	});
});
