jQuery(function ($) {
	// Setting image
	var custom_uploader_file;
	$('#default_image_setting').click(function (e) {
		e.preventDefault();
		var self = $(this);
		var outputImage = $('#default-image-area');
		var outputId = $('#default_image');
		if (custom_uploader_file) {
			custom_uploader_file.open();
			return;
		}
		custom_uploader_file = wp.media({
			title: self.attr('data-title'),
			//button: { text: self.attr('data-button') },
			library: { type: 'image' },
			multiple: false
		});
		custom_uploader_file.on('select', function () {
			var images = custom_uploader_file.state().get('selection');
			images.each(function (file) {
				var attachment = file.toJSON();
				outputId.val(attachment.id);
				outputImage.find('img').remove();
				outputImage.prepend('<img src="' + attachment.sizes.thumbnail.url + '">');
				$('#default_image_clear').prop('disabled', false);
				$('#skip_draft').prop('disabled', false);
				$('#skip_draft').prop('checked', true);
			});
		});
		custom_uploader_file.open();
	});
	// Remove image
	$('#default_image_clear').click(function (e) {
		e.preventDefault();
		$('#default_image').val('0');
		$('#default-image-area img').remove();
		$(this).prop('disabled', true);
		$('#skip_draft').prop('disabled', true);
	});
});
