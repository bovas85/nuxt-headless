// get/post via jQuery
(function ($) {
    $.extend({
        ure_getGo: function (url, params) {
            document.location = url + '?' + $.param(params);
        },
        ure_postGo: function (url, params) {
            var $form = $('<form>')
                    .attr('method', 'post')
                    .attr('action', url);
            $.each(params, function (name, value) {
                $("<input type='hidden'>")
                        .attr('name', name)
                        .attr('value', value)
                        .appendTo($form);
            });
            $form.appendTo('body');
            $form.submit();
        }
    });
})(jQuery);

var ure_ajax_get_caps_to_remove = null;

jQuery(document).ready(function() {
    
// Get from the server a list of capabilities we can delete and show dialog to select what to delete
    ure_ajax_get_caps_to_remove = {
        url: ajaxurl,
        type: 'POST',
        dataType: 'html',
        data: {
            action: 'ure_ajax',
            sub_action: 'get_caps_to_remove',
            current_role: jQuery('#user_role').val(),
            network_admin: ure_data.network_admin,
            wp_nonce: ure_data.wp_nonce
        },
        success: function (response) {
            var data = jQuery.parseJSON(response);
            if (typeof data.result !== 'undefined') {
                if (data.result === 'success') {
                    jQuery('#ure_delete_capability_dialog .ure-input').html(data.html);
                    ure_main.show_delete_capability_dialog();
                } else if (data.result === 'failure') {
                    alert(data.message);
                } else {
                    alert('Wrong response: ' + response)
                }
            } else {
                alert('Wrong response: ' + response)
            }
        },
        error: function (XMLHttpRequest, textStatus, exception) {
            alert("Ajax failure\n" + XMLHttpRequest.statusText);
        },
        async: true
    };

});

// Main User Role Editor object
var ure_main = {
    selected_group: 'all', 
    caps_counter: null,
    class_prefix: 'ure-',
        
    show_delete_capability_dialog: function () {
        jQuery('#ure_delete_capability_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 350,
            height: 400,
            resizable: false,
            title: ure_data.delete_capability,
            buttons: {
                'Delete Capability': function () {
                    if (!confirm(ure_data.delete_capability + ' - ' + ure_data.delete_capability_warning)) {
                        return;
                    }                    
                    jQuery('#ure_remove_caps_form').submit();                    
                    jQuery(this).dialog('close');
                },
                Cancel: function () {
                    jQuery(this).dialog('close');
                }
            }
        });
        // translate buttons caption
        jQuery('.ui-dialog-buttonpane button:contains("Delete Capability")').attr('id', 'dialog-delete-capability-button');
        jQuery('#dialog-delete-capability-button').html(ure_ui_button_text(ure_data.delete_capability));
        jQuery('.ui-dialog-buttonpane button:contains("Cancel")').attr('id', 'delete-capability-dialog-cancel-button');
        jQuery('#delete-capability-dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
        jQuery('#ure_remove_caps_select_all').click(this.remove_caps_auto_select);
    },
    
    remove_caps_auto_select: function (event) {
        if (event.shiftKey) {
            jQuery('.ure-cb-column').each(function () {   // reverse selection
                jQuery(this).prop('checked', !jQuery(this).prop('checked'));
            });
        } else {    // switch On/Off all checkboxes
            jQuery('.ure-cb-column').prop('checked', jQuery('#ure_remove_caps_select_all').prop('checked'));

        }
    }

};  // end of ure_main declaration
//-------------------------------



function ure_ui_button_text(caption) {
    var wrapper = '<span class="ui-button-text">' + caption + '</span>';

    return wrapper;
}


function ure_select_selectable_element(selectable_container, elements_to_select) {
    // add unselecting class to all elements in the styleboard canvas except the ones to select
    jQuery(".ui-selected", selectable_container).not(elements_to_select).removeClass("ui-selected").addClass("ui-unselecting");    
    // add ui-selecting class to the elements to select
    jQuery(elements_to_select).not(".ui-selected").addClass("ui-selecting");
    // trigger the mouse stop event (this will select all .ui-selecting elements, and deselect all .ui-unselecting elements)
    selectable_container.data("ui-selectable")._mouseStop(null);
}


jQuery(function ($) {

    ure_count_caps_in_groups();
    ure_sizes_update();
    if ($('#ure_select_all_caps').length>0) {
        $('#ure_select_all_caps').click(ure_auto_select_caps);
    }    
    $('#granted_only').click(ure_show_granted_caps_only);
    $('#ure_caps_groups_list').selectable({
        selected: function( event, ui ) {
            // do not allow multiple selection
            $(ui.selected).siblings().removeClass("ui-selected");
            ure_caps_refresh(ui.selected.id);
        }
    });            
    ure_select_selectable_element($('#ure_caps_groups_list'), $('#ure_caps_group_all'));

    $('#ure_update_role').button({
        label: ure_data.update
    }).click(function () {
        if (ure_data.confirm_role_update == 1) {
            event.preventDefault();
            ure_confirm(ure_data.confirm_submit, ure_form_submit);
        }
    });


    function ure_form_submit() {
        $('#ure_form').submit();
    }


    function ure_show_add_role_dialog() {
        
        $('#ure_add_role_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 450,
            height: 230,
            resizable: false,
            title: ure_data.add_new_role_title,
            'buttons': {
                'Add Role': function () {
                    var role_id = $('#user_role_id').val();
                    if (role_id == '') {
                        alert(ure_data.role_name_required);
                        return false;
                    }
                    if (!(/^[\w-]*$/.test(role_id))) {
                        alert(ure_data.role_name_valid_chars);
                        return false;
                    }
                    if ((/^[0-9]*$/.test(role_id))) {
                        alert(ure_data.numeric_role_name_prohibited);
                        return false;
                    }
                    var role_name = $('#user_role_name').val();
                    var role_copy_from = $('#user_role_copy_from').val();

                    $(this).dialog('close');
                    $.ure_postGo(ure_data.page_url,
                            {action: 'add-new-role', user_role_id: role_id, user_role_name: role_name, user_role_copy_from: role_copy_from,
                                ure_nonce: ure_data.wp_nonce});
                },
                Cancel: function () {
                    $(this).dialog('close');
                    return false;
                }
            }
        });
        $('.ui-dialog-buttonpane button:contains("Add Role")').attr('id', 'dialog-add-role-button');
        $('#dialog-add-role-button').html(ure_ui_button_text(ure_data.add_role));
        $('.ui-dialog-buttonpane button:contains("Cancel")').attr('id', 'add-role-dialog-cancel-button');
        $('#add-role-dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));

    }


    $('#ure_add_role').button({
        label: ure_data.add_role
    }).click(function (event) {
        event.preventDefault();
        ure_show_add_role_dialog();
    });


    function ure_show_rename_role_dialog() {

        $('#ure_rename_role_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 450,
            height: 230,
            resizable: false,
            title: ure_data.rename_role_title,
            'buttons': {
                'Rename Role': function () {
                    var role_id = $('#ren_user_role_id').val();
                    var role_name = $('#ren_user_role_name').val();
                    $(this).dialog('close');
                    $.ure_postGo(ure_data.page_url,
                            {action: 'rename-role', user_role_id: role_id, user_role_name: role_name, ure_nonce: ure_data.wp_nonce}
                    );
                },
                Cancel: function () {
                    $(this).dialog('close');
                    return false;
                }
            }
        });
        $('.ui-dialog-buttonpane button:contains("Rename Role")').attr('id', 'dialog-rename-role-button');
        $('#dialog-rename-role-button').html(ure_ui_button_text(ure_data.rename_role));
        $('.ui-dialog-buttonpane button:contains("Cancel")').attr('id', 'rename-role-dialog-cancel-button');
        $('#rename-role-dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
        $('#ren_user_role_id').val(ure_current_role);
        $('#ren_user_role_name').val(ure_current_role_name);

    }


    $('#ure_rename_role').button({
        label: ure_data.rename_role
    }).click(function (event) {
        event.preventDefault();
        ure_show_rename_role_dialog();
    });

        
    function ure_show_delete_role_dialog() {
        $('#ure_delete_role_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 320,
            height: 190,
            resizable: false,
            title: ure_data.delete_role,
            buttons: {
                'Delete Role': function () {
                    var user_role_id = $('#del_user_role').val();
                    if (!confirm(ure_data.delete_role)) {
                        return false;
                    }
                    $(this).dialog('close');
                    $.ure_postGo(ure_data.page_url,
                            {action: 'delete-role', user_role_id: user_role_id, ure_nonce: ure_data.wp_nonce});
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
        // translate buttons caption
        $('.ui-dialog-buttonpane button:contains("Delete Role")').attr('id', 'dialog-delete-button');
        $('#dialog-delete-button').html(ure_ui_button_text(ure_data.delete_role));
        $('.ui-dialog-buttonpane button:contains("Cancel")').attr('id', 'delete-role-dialog-cancel-button');
        $('#delete-role-dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
    }
    

    $('#ure_delete_role').button({
        label: ure_data.delete_role
    }).click(function (event) {
        event.preventDefault();
        ure_show_delete_role_dialog();
    });


    function ure_show_add_capability_dialog() {
        $('#ure_add_capability_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 350,
            height: 190,
            resizable: false,
            title: ure_data.add_capability,
            'buttons': {
                'Add Capability': function () {
                    var capability_id = $('#capability_id').val();
                    if (capability_id == '') {
                        alert(ure_data.capability_name_required);
                        return false;
                    }
                    if (!(/^[\w-]*$/.test(capability_id))) {
                        alert(ure_data.capability_name_valid_chars);
                        return false;
                    }

                    $(this).dialog('close');
                    $.ure_postGo(ure_data.page_url,
                            {action: 'add-new-capability', capability_id: capability_id, ure_nonce: ure_data.wp_nonce});
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
        $('.ui-dialog-buttonpane button:contains("Add Capability")').attr('id', 'dialog-add-capability-button');
        $('#dialog-add-capability-button').html(ure_ui_button_text(ure_data.add_capability));
        $('.ui-dialog-buttonpane button:contains("Cancel")').attr('id', 'add-capability-dialog-cancel-button');
        $('#add-capability-dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
        
    }


    $('#ure_add_capability').button({
        label: ure_data.add_capability
    }).click(function (event) {
        event.preventDefault();
        ure_show_add_capability_dialog();
    });
        

    if ($('#ure_delete_capability').length > 0) {
        $('#ure_delete_capability').button({
            label: ure_data.delete_capability
        }).click(function (event) {
            event.preventDefault();
            $.ajax(ure_ajax_get_caps_to_remove);
        });
    }            

    
    function ure_show_default_role_dialog() {
        $('#ure_default_role_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 320,
            height: 190,
            resizable: false,
            title: ure_data.default_role,
            buttons: {
                'Set New Default Role': function () {
                    $(this).dialog('close');
                    var user_role_id = $('#default_user_role').val();
                    $.ure_postGo(ure_data.page_url,
                            {action: 'change-default-role', user_role_id: user_role_id, ure_nonce: ure_data.wp_nonce});
                },
                Cancel: function () {
                    $(this).dialog('close');
                }
            }
        });
        // translate buttons caption
        $('.ui-dialog-buttonpane button:contains("Set New Default Role")').attr('id', 'dialog-default-role-button');
        $('#dialog-default-role-button').html(ure_ui_button_text(ure_data.set_new_default_role));
        $('.ui-dialog-buttonpane button:contains("Cancel")').attr('id', 'default-role-dialog-cancel-button');
        $('#default-role-dialog-cancel-button').html(ure_ui_button_text(ure_data.cancel));
    }
    

    if ($('#ure_default_role').length > 0) {
        $('#ure_default_role').button({
            label: ure_data.default_role
        }).click(function (event) {
            event.preventDefault();                
            ure_show_default_role_dialog();
        });
    }
    

    function ure_confirm(message, routine) {

        $('#ure_confirmation_dialog').dialog({
            dialogClass: 'wp-dialog',
            modal: true,
            autoOpen: true,
            closeOnEscape: true,
            width: 400,
            height: 180,
            resizable: false,
            title: ure_data.confirm_title,
            'buttons': {
                'No': function () {
                    $(this).dialog('close');
                    return false;
                },
                'Yes': function () {
                    $(this).dialog('close');
                    routine();
                    return true;
                }
            }
        });
        $('#ure_cd_html').html(message);

        $('.ui-dialog-buttonpane button:contains("No")').attr('id', 'dialog-no-button');
        $('#dialog-no-button').html(ure_ui_button_text(ure_data.no_label));
        $('.ui-dialog-buttonpane button:contains("Yes")').attr('id', 'dialog-yes-button');
        $('#dialog-yes-button').html(ure_ui_button_text(ure_data.yes_label));

    }
    // end of ure_confirm()


});
// end of jQuery(function() ...


// change color of apply to all check box - for multi-site setup only
function ure_apply_to_all_on_click(cb) {
    el = document.getElementById('ure_apply_to_all_div');
    if (cb.checked) {
        el.style.color = '#FF0000';
    } else {
        el.style.color = '#000000';
    }
}
// end of ure_apply_to_all_on_click()


// turn on checkbox back if clicked to turn off
function ure_turn_it_back(control) {

    control.checked = true;

}
// end of ure_turn_it_back()


function ure_apply_selection(cb_id) {
    var qfilter = jQuery('#quick_filter').val();
    var parent_div = jQuery('#ure_cap_div_'+ cb_id);
    var disabled = jQuery('#'+ cb_id).attr('disabled');
    var result = false;
    if (parent_div.hasClass(ure_main.class_prefix + ure_main.selected_group) && // make selection inside currently selected group of capabilities only
        !parent_div.hasClass('hidden') && disabled!=='disabled') {   // select not hidden and not disabled checkboxes (capabilities) only
        //  if quick filter is not empty, then apply selection to the tagged element only
        if (qfilter==='' || parent_div.hasClass('ure_tag')) {            
            result = true;
        }
    }
    
    return result;
}


function ure_auto_select_caps(event) {
    
    if (event.shiftKey) {
        jQuery('.ure-cap-cb').each(function () {   // reverse selection
            if (ure_apply_selection(this.id)) {
                jQuery(this).prop('checked', !jQuery(this).prop('checked'));
            }
        });
    } else {    
        jQuery('.ure-cap-cb').each(function () { // switch On/Off all checkboxes
            if (ure_apply_selection(this.id)) {
                jQuery(this).prop('checked', jQuery('#ure_select_all_caps').prop('checked'));
            }
        });
    }

}


function ure_turn_caps_readable(user_id) {
    var ure_obj = 'user';
    if (user_id === 0) {
        ure_obj = 'role';
    }

    jQuery.ure_postGo(ure_data.page_url, {action: 'caps-readable', object: ure_obj, user_id: user_id, ure_nonce: ure_data.wp_nonce});

}
// end of ure_turn_caps_readable()


function ure_turn_deprecated_caps(user_id) {

    var ure_obj = 'user';
    if (user_id === 0) {
        ure_obj = 'role';
    }
    jQuery.ure_postGo(ure_data.page_url, {action: 'show-deprecated-caps', object: ure_obj, user_id: user_id, ure_nonce: ure_data.wp_nonce});

}
// ure_turn_deprecated_caps()


function ure_refresh_role_view(response) {
    jQuery('#ure_task_status').hide();
    if (response!==null && response.result=='error') {
        alert(response.message);
        return;
    }
    
    ure_current_role = response.role_id;
    ure_current_role_name = response.role_name;        
    // Select capabilities granted to a newly selected role and exclude others
    jQuery('.ure-cap-cb').each(function () { // go through all capabilities checkboxes
        jQuery(this).prop('checked', response.caps.hasOwnProperty(this.id) && response.caps[this.id]);
    }); 
    
    // Recalculate granted capabilities for capabilities groups
    ure_count_caps_in_groups();
    ure_select_selectable_element(jQuery('#ure_caps_groups_list'), jQuery('#ure_caps_group_all'));
    var granted_only = jQuery('#granted_only').prop('checked');
    if (granted_only) {
        jQuery('#granted_only').prop('checked', false);
        ure_show_granted_caps_only();
    }
    
    // additional options section
    jQuery('#additional_options').find(':checkbox').each(function() {   // go through all additional options checkboxes
        jQuery(this).prop('checked', response.options.hasOwnProperty(this.id));
    });
    
}
// end of refresh_role_view()


function ure_role_change(role_name) {

    //jQuery.ure_postGo(ure_data.page_url, {action: 'role-change', object: 'role', user_role: role_name, ure_nonce: ure_data.wp_nonce});
    jQuery('#ure_task_status').show();
    var data = {
        'action': 'ure_ajax',
        'sub_action':'get_role_caps', 
        'role': role_name, 
        'wp_nonce': ure_data.wp_nonce};
    jQuery.post(ajaxurl, data, ure_refresh_role_view, 'json');

}
// end of ure_role_change()


function ure_filter_capabilities(cap_id) {
    var div_list = jQuery('.ure-cap-div');
    for (i = 0; i < div_list.length; i++) {
        if (cap_id !== '' && div_list[i].id.substr(11).indexOf(cap_id) !== -1) {
            jQuery('#'+ div_list[i].id).addClass('ure_tag');
            div_list[i].style.color = '#27CF27';
        } else {
            div_list[i].style.color = '#000000';
            jQuery('#'+ div_list[i].id).removeClass('ure_tag');
        }
    }
    ;

}
// end of ure_filter_capabilities()


function ure_hide_pro_banner() {

    jQuery.ure_postGo(ure_data.page_url, {action: 'hide-pro-banner', ure_nonce: ure_data.wp_nonce});

}
// end of ure_hide_this_banner()


function ure_caps_refresh_all() {
    jQuery('.ure-cap-div').each(function () {
        if (jQuery(this).hasClass('hidden')) {
            if (!jQuery(this).hasClass(ure_main.class_prefix + 'deprecated')) {
                jQuery(this).removeClass('hidden');
            }
        }        
    });
}


function ure_caps_refresh_for_group(group_id) {
    var show_deprecated = jQuery('#ure_show_deprecated_caps').attr('checked');
    jQuery('.ure-cap-div').each(function () {
        var el = jQuery(this);
        if (el.hasClass(ure_main.class_prefix + group_id)) {
            if (el.hasClass('hidden')) {
                if (el.hasClass('blocked')) {
                    return;
                }
                if (el.hasClass(ure_main.class_prefix + 'deprecated')) {
                    if (group_id==='deprecated' || show_deprecated) {
                        el.removeClass('hidden');
                    }
                } else {                    
                    el.removeClass('hidden');
                }                
            } else {
                if (el.hasClass(ure_main.class_prefix + 'deprecated')) {
                    if (!show_deprecated) {
                        el.addClass('hidden');
                    }
                }
            }
        } else {
            if (!el.hasClass('hidden')) {
                el.addClass('hidden');
            }
        }
    });    
}


function ure_caps_refresh(group) {

    var group_id = group.substr(15);
    ure_main.selected_group = group_id;
    if (group_id === 'all') {
        ure_caps_refresh_all();
    } else {
        ure_caps_refresh_for_group(group_id);
    }    
    ure_change_caps_columns_quant();
    jQuery('#granted_only').attr('checked', false);
} 


function ure_validate_columns(columns) {    
    if (columns==1 || ure_main.selected_group=='all') {  
        return columns;
    }
    
    // Do not split list on columns in case it contains less then < 25 capabilities
    for (i=0; i<ure_main.caps_counter.length; i++) {
        if (ure_main.caps_counter[i].id==ure_main.selected_group) {
            if (ure_main.caps_counter[i].total<=25) {
                columns = 1;
            }
            break;
        }
    }
    
    return columns;
}


function ure_change_caps_columns_quant() {
    var selected_index = parseInt(jQuery('#caps_columns_quant').val());
    var columns = ure_validate_columns(selected_index);
    var el = jQuery('#ure_caps_list');
    el.css('-moz-column-count', columns);
    el.css('-webkit-column-count', columns);
    el.css('column-count', columns);

}


function ure_init_caps_counter() {
    ure_main.caps_counter = new Array();
    jQuery('#ure_caps_groups_list li').each(function() {
        var group_id = jQuery(this).attr('id').substr(15);
        group_counter = {'id': group_id, 'total': 0, 'granted':0};
        ure_main.caps_counter.push(group_counter);
    });
    
}


function ure_count_caps_in_groups() {    
    ure_init_caps_counter();    
    
    jQuery('.ure-cap-div').each(function () {
        var cap_div = jQuery(this);
        var capability = cap_div.attr('id').substr(12);
        for (i=0; i<ure_main.caps_counter.length; i++) {
            if (cap_div.hasClass(ure_main.class_prefix + ure_main.caps_counter[i].id)) {
                ure_main.caps_counter[i].total++;
                if (jQuery('#'+ capability).is(':checked')) {
                    ure_main.caps_counter[i].granted++;
                }
            }                            
        }
    });
    
    for (i=0; i<ure_main.caps_counter.length; i++) {
        var el = jQuery('#ure_caps_group_'+ ure_main.caps_counter[i].id);
        var old_text = el.text();
        var key_pos = old_text.indexOf('(');    // exclude (0/0) text if it is in string already
        if (key_pos>0) {
            old_text = old_text.substr(0, key_pos - 1);
        }
        var value = old_text +' ('+ ure_main.caps_counter[i].total +'/'+ ure_main.caps_counter[i].granted +')';
        
        el.text(value);
    }
    
}


function ure_sizes_update() {
    var width = jQuery('#ure_caps_td').css('width');
    jQuery('#ure_caps_list_container').css('width', width);
}


jQuery(window).resize(function() {
   ure_sizes_update(); 
});


function ure_show_granted_caps_only() {
    var show_deprecated = jQuery('#ure_show_deprecated_caps').attr('checked');
    var hide_flag = jQuery('#granted_only').attr('checked');
    jQuery('.ure-cap-div').each(function () {
        var cap_div = jQuery(this);
        if (!cap_div.hasClass(ure_main.class_prefix + ure_main.selected_group)) {    // apply to the currently selected group only
            return;
        }
        var cap_id = cap_div.attr('id').substr(12);        
        var granted = jQuery('#'+ cap_id).attr('checked');
        if (granted) {
            return;
        }
        if (hide_flag) {
            if (!cap_div.hasClass('hidden')) {
                cap_div.addClass('hidden');
            }
        } else {
            if (cap_div.hasClass('deprecated') && !show_deprecated) {
                return;
            }
            if (cap_div.hasClass('hidden')) {
                cap_div.removeClass('hidden');
            }
        }
    });    
}