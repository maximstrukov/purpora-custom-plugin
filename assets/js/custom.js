jQuery(document).ready(function() {
	jQuery('#certificate_verification_form').submit(function() {
		jQuery('#certificate_verification_form .certificate_message').hide();
		var certificate = jQuery.trim(jQuery('#certificate_verification_form input[type="text"]').val());
		if (certificate != '') {
			jQuery('#certificate_verification_form img').show();
			var data = {
				'action': 'verify_certificate',
				'certificate': certificate
			};
			jQuery.ajax({
				url:  ajaxurl,
				type: 'POST',
				data: data,
				success: function(response) {
					jQuery('#certificate_verification_form img').hide();
					result = jQuery.parseJSON(response);
					if (result.valid==true) {
						jQuery('#certificate_verification_form .certificate_message.valid .username strong').html(result.username);
						jQuery('#certificate_verification_form .certificate_message.valid .exp_date strong').html(result.exp_date);
						jQuery('#certificate_verification_form .certificate_message.valid').show();
						
					} else {
						jQuery('#certificate_verification_form .certificate_message.invalid').show();
					}
				}
			});
		}
		return false;
	});
});