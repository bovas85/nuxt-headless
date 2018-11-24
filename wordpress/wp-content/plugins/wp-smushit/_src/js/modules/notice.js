/**
 * Created by umeshkumar on 12/05/16.
 */
jQuery(function () {
	var el_notice = jQuery(".smush-notice"),
		btn_act = el_notice.find(".smush-notice-act"),
		btn_dismiss = el_notice.find(".smush-notice-dismiss");
	el_notice.fadeIn(500);

	// Hide the notice after a CTA button was clicked
	function remove_notice() {
		el_notice.fadeTo(100, 0, function () {
			el_notice.slideUp(100, function () {
				el_notice.remove();
			});
		});
	}

	btn_act.click(function (ev) {
		remove_notice();
		notify_wordpress(btn_act.data("msg"));
	});

	btn_dismiss.click(function (ev) {
		remove_notice();
		notify_wordpress(btn_act.data("msg"));
	});

	// Notify WordPress about the users choice and close the message.
	function notify_wordpress(message) {
		el_notice.attr("data-message", message);
		el_notice.addClass("loading");

		//Send a ajax request to save the dismissed notice option
		var param = {
			action: 'dismiss_upgrade_notice'
		};
		jQuery.post(ajaxurl, param);
	}

	// Store the preference in db.
	function send_dismiss_request( action ) {
		var param = {
			action: action
		};
		jQuery.post(ajaxurl, param);
	}

	// Dismiss the update notice.
	jQuery('.wp-smush-update-info').on('click', '.notice-dismiss', function (e) {
		e.preventDefault();
		el_notice = jQuery(this);
		remove_notice();
		send_dismiss_request( 'dismiss_update_info' );
	});

	// Dismiss S3 support alert.
	jQuery('div.wp-smush-s3support-alert').on('click', '.icon-fi-close', function (e) {
		el_notice = jQuery(this);
		remove_notice();
		send_dismiss_request( 'dismiss_s3support_alert' );
	});

});