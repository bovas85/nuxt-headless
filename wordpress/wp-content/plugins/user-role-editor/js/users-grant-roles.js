
/*
 * User Role Editor: support of 'Grant Roles' button for Users page (wp-admin/users.php)
 */

jQuery(document).ready(function() {
    jQuery('#ure_grant_roles').click(function() {
        ure_prepare_grant_roles_dialog();
    });
    jQuery('#ure_grant_roles_2').click(function() {
        ure_prepare_grant_roles_dialog();
    });
    
    if (ure_users_grant_roles_data.show_wp_change_role!=1) {        
        jQuery('#new_role').hide();
        jQuery('#new_role2').hide();
        jQuery('#changeit').hide();
        jQuery('[id=changeit]:eq(1)').hide();   // for 2nd 'Change' button with the same ID.        
    }
});


function ure_get_selected_checkboxes(item_name) {
    var items = jQuery('input[type="checkbox"][name="'+ item_name +'\\[\\]"]:checked').map(function() { return this.value; }).get();
    
    return items;
}


function ure_show_grant_roles_dialog_pre_selected(response) {
    jQuery('#ure_task_status').hide();
    if (response!==null && response.result=='error') {
        alert(response.message);
        return;
    }
    if (response.primary_role.length>0 && jQuery('#primary_role').length>0) {
        jQuery('#primary_role').val(response.primary_role);
    }
    
    if (response.other_roles.length>0) {
        for(i=0;i<response.other_roles.length;i++) {
            jQuery('#wp_role_'+ response.other_roles[i]).prop('checked', true);
        }
    }
    
    ure_show_grant_roles_dialog();
    
}


function ure_get_selected_user_roles(users) {
    jQuery('#ure_task_status').show();
    var user_id = users.shift();
    var data = {
        'action': 'ure_ajax',
        'sub_action':'get_user_roles', 
        'user_id': user_id, 
        'wp_nonce': ure_users_grant_roles_data.wp_nonce};
    jQuery.post(ajaxurl, data, ure_show_grant_roles_dialog_pre_selected, 'json');
}


function ure_unselect_roles() {
    jQuery('#primary_role').val([]);
    
    // uncheck all checked checkboxes if there are any
    jQuery('input[type="checkbox"][name="ure_roles\\[\\]"]:checked').map(function() { 
        this.checked = false; 
    });
}

function ure_prepare_grant_roles_dialog() {
    var users = ure_get_selected_checkboxes('users');
    if (users.length==0) {
        alert(ure_users_grant_roles_data.select_users_first);
        return;
    } 
    
    if (users.length==1) {
        ure_get_selected_user_roles(users);
    } else {
        ure_unselect_roles();        
        ure_show_grant_roles_dialog();
    }
    
}


function ure_show_grant_roles_dialog() {
    
    jQuery('#ure_grant_roles_dialog').dialog({
        dialogClass: 'wp-dialog',
        modal: true,
        autoOpen: true,
        closeOnEscape: true,
        width: 400,
        height: 400,
        resizable: false,
        title: ure_users_grant_roles_data.dialog_title,
        'buttons': {
            'OK': function () {
                ure_grant_roles();
                jQuery(this).dialog('close');
                return true;
            },
            Cancel: function () {
                jQuery(this).dialog('close');
                return false;
            }
        }
    });
}


function ure_grant_roles() {    
    var primary_role = jQuery('#primary_role').val();
    var other_roles = ure_get_selected_checkboxes('ure_roles');
    jQuery('#ure_task_status').show();
    var users = ure_get_selected_checkboxes('users');
    var data = {
        'action': 'ure_ajax',
        'sub_action':'grant_roles', 
        'users': users, 
        'primary_role': primary_role,
        'other_roles': other_roles,
        'wp_nonce': ure_users_grant_roles_data.wp_nonce};
    jQuery.post(ajaxurl, data, ure_page_reload, 'json');
    
    return true;
}


function ure_set_url_arg(arg_name, arg_value) {
    var url = window.location.href;
    var hash = location.hash;
    url = url.replace(hash, '');
    if (url.indexOf(arg_name + "=")>=0) {
        var prefix = url.substring(0, url.indexOf(arg_name));
        var suffix = url.substring(url.indexOf(arg_name));
        suffix = suffix.substring(suffix.indexOf("=") + 1);
        suffix = (suffix.indexOf("&") >= 0) ? suffix.substring(suffix.indexOf("&")) : "";
        url = prefix + arg_name + "=" + arg_value + suffix;
    } else {
        if (url.indexOf("?") < 0) {
            url += "?" + arg_name + "=" + arg_value;
        } else {
            url += "&" + arg_name + "=" + arg_value;
        }
    }
    url = url + hash;
    
    return url;
}


function ure_page_reload(response) {
    
    if (response!==null && response.result=='error') {
        jQuery('#ure_task_status').hide();
        alert(response.message);
        return;
    }
    
    var url = ure_set_url_arg('update', 'promote');
    document.location = url;
}
