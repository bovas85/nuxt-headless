jQuery(document).ready(function() {

	// enable toggling of the API field from password to plain text
	enablePasswordDisplayOnEntry('mailgun_api_key', 'toggleMailgunApiKey');

	// define the PostmanMandrill class
	var PostmanMailgun = function() {

	}

	// behavior for handling the user's transport change
	PostmanMailgun.prototype.handleTransportChange = function(transportName) {
		if (transportName == 'mailgun_api') {
			hide('div.transport_setting');
			hide('div.authentication_setting');
			show('div#mailgun_settings');
		}
	}

	// behavior for handling the wizard configuration from the
	// server (after the port test)
	PostmanMailgun.prototype.handleConfigurationResponse = function(response) {
		var transportName = response.configuration.transport_type;
		if (transportName == 'mailgun_api') {
			show('section.wizard_mailgun');
		} else {
			hide('section.wizard_mailgun');
		}
	}

	// add this class to the global transports
	var transport = new PostmanMailgun();
	transports.push(transport);

	// since we are initialize the screen, check if needs to be modded by this
	// transport
	var transportName = jQuery('select#input_transport_type').val();
	transport.handleTransportChange(transportName);

});
