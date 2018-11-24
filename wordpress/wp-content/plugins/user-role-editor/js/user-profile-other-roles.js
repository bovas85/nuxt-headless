/* User Role Editor - assign to the user other roles 
 * Author: Vladimir Garagulya
 * email: support@role-editor.com
 */

jQuery(document).ready(function() {
    
    ure_init_controls('');
    ure_init_controls('_2');          
            
});    


function ure_init_controls(context) {
    if (jQuery('#ure_select_other_roles'+ context).length==0) {
        return;
    }
    
    if (ure_data_user_profile_other_roles.select_primary_role!=1) {
        jQuery('.user-role-wrap').hide();
    }
    
    jQuery('#ure_select_other_roles'+ context).multipleSelect({
            filter: true,
            multiple: true,
            selectAll: false,
            multipleWidth: 600,            
            maxHeight: 300,
            placeholder: ure_data_user_profile_other_roles.select_roles,
            onClick: function(view) {
                ure_update_linked_controls_other_roles(context);
            }
    });
      
    var other_roles = jQuery('#ure_other_roles'+ context).val();
    var selected_roles = other_roles.split(',');
    jQuery('#ure_select_other_roles'+ context).multipleSelect('setSelects', selected_roles);
}


function ure_update_linked_controls_other_roles(context) {
    var data_value = jQuery('#ure_select_other_roles'+ context).multipleSelect('getSelects');
    var to_save = '';
    for (i=0; i<data_value.length; i++) {
        if (to_save!=='') {
            to_save = to_save + ', ';
        }
        to_save = to_save + data_value[i];
    }
    jQuery('#ure_other_roles'+ context).val(to_save);
    
    var data_text = jQuery('#ure_select_other_roles'+ context).multipleSelect('getSelects', 'text');
    var to_show = '';
    for (i=0; i<data_text.length; i++) {        
        if (to_show!=='') {
            to_show = to_show + ', ';
        }
        to_show = to_show + data_text[i];
    }    
    jQuery('#ure_other_roles_list'+ context).html(to_show);
}
