/*
 * User Role Editor WordPress plugin JavaScript for Settings page
 */ 

function ure_ui_button_text(caption) {
    var wrapper = '<span class="ui-button-text">' + caption + '</span>';

    return wrapper;
}

function ure_roles_reset_form_submit() {
    jQuery('#ure_reset_roles_form').submit();
}

jQuery(document).ready(function() {   
        
    jQuery('#ure_reset_roles_button').button({
        label: ure_data.reset
    }).click(function (event) {
        event.preventDefault();        
        ure_confirm(ure_data.reset_warning, ure_roles_reset_form_submit);
    });
    
    function ure_confirm(message, routine) {

        jQuery('#ure_confirmation_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 600,
            height: 280,
            resizable: false,
            title: ure_data.confirm_title,
            'buttons': {
                'No': function () {
                    jQuery(this).dialog('close');
                    return false;
                },
                'Yes': function () {
                    jQuery(this).dialog('close');
                    routine();
                    return true;
                }
            }
        });
        jQuery('#ure_cd_html').html(message);
        jQuery('.ui-dialog-buttonpane button:contains("No")').attr('id', 'dialog-no-button');
        jQuery('#dialog-no-button').html(ure_ui_button_text(ure_data.no_label));
        jQuery('.ui-dialog-buttonpane button:contains("Yes")').attr('id', 'dialog-yes-button');
        jQuery('#dialog-yes-button').html(ure_ui_button_text(ure_data.yes_label));

    }
    // end of ure_confirm()

        
});
