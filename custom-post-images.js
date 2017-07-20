/*
 * Attaches the image uploader to the input field
 */
 
jQuery(document).ready(function($){
 
	$('#cpi .cpi-upload').each(function() {
	
		var cpi_image_frame;
		
		var p = $(this);
		
		//Choose/upload image
		p.find('.cpi-upload-button').click(function(e) {
		
			e.preventDefault();
			
			if ( cpi_image_frame ) {
				cpi_image_frame.open();
				return;
			}

			cpi_image_frame = wp.media.frames.cpi_image_frame = wp.media({
				title: meta_image.title,
				button: { text:  meta_image.button }
			});

			// Runs when an image is selected.
			cpi_image_frame.on('select', function() {

				// Grabs the attachment selection and creates a JSON representation of the model.
				var media_attachment = cpi_image_frame.state().get('selection').first().toJSON();

                console.log('media_attachment: ');
				console.log(media_attachment);

				var media_id = media_attachment.id;

                // exists not always
				// var media_thumbnail = media_attachment.sizes.thumbnail.url;
				// var media_thumbnail = media_attachment.sizes.full.url;

                // example: 'http://domain.loc/energy/wp-content/uploads/2016/11/slide4.jpg'
				var media_thumbnail = media_attachment.url;

				// Sends the attachment URL to our custom image input field.
				p.find('.cpi-upload-id').val(media_id);

				//p.find('.cpi-upload-thumbnail').html('<img src="' + media_thumbnail + '">');

                // var html = '<img src="http://domain.loc/energy/wp-content/uploads/2016/11/slide4.jpg">';
                // var html = '<div style="background-image: url(http://domain.loc/energy/wp-content/uploads/2016/11/slide4.jpg);' +
                var html = '<div style="background-image: url(' + media_thumbnail + ');"></div>';


                p.find('.cpi-upload-thumbnail').html(html);

			});

			// Opens the media library frame.
			cpi_image_frame.open();

		});
		
		//Unset current image
		p.find('.cpi-upload-clear').click(function(e) {
			
			e.preventDefault();
			
			console.log('clear');
			
			p.find('.cpi-upload-id').val('');
			p.find('.cpi-upload-thumbnail').empty();
		
		});
		
	});
	
});