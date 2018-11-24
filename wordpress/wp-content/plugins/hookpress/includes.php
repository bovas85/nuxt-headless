<?php

require('services.php');
require('hooks.php');

// OPTIONS

function hookpress_get_fields( $type ) {
	global $wpdb;
	$map = array('POST' => array($wpdb->posts),
							 'PARENT_POST' => array($wpdb->posts),
							 'COMMENT' => array($wpdb->comments),
							 'CATEGORY' => array($wpdb->terms,$wpdb->term_taxonomy),
							 'ATTACHMENT' => array($wpdb->posts),
							 'LINK' => array($wpdb->links),
							 'USER' => array($wpdb->users),
							 'TAG_OBJ' => array($wpdb->terms,$wpdb->term_taxonomy),
							 'USER_OBJ' => array($wpdb->users),
							 'OLD_USER_OBJ' => array($wpdb->users));
	$tables = $map[$type];
	$fields = array();
	foreach ( (array) $tables as $table) {
		if (is_array($table))
			$fields = array_merge($fields,$table);
		else
			$fields = array_merge($fields,$wpdb->get_col("show columns from $table"));
	}

	// if it's a POST, we have a URL for it as well.
	if ($type == 'POST' || $type == 'PARENT_POST')
		$fields[] = 'post_url';

	if ($type == 'PARENT_POST')
		$fields = array_map(create_function('$x','return "parent_$x";'),$fields);

	if ($type == 'OLD_USER_OBJ')
		$fields = array_map(create_function('$x','return "old_$x";'),$fields);

	return array_unique($fields);
}

function hookpress_print_edit_webhook( $id ){
?>
<?php
	global $wpdb, $hookpress_actions, $hookpress_filters;
	
	$webhooks = hookpress_get_hooks( );
	$desc = $webhooks[$id];
		
	if ($desc['type'] == 'action')
		$hooks = array_keys($hookpress_actions);	
	if ($desc['type'] == 'filter')
		$hooks = array_keys($hookpress_filters);
?>
<div id='hookpress-webhook' style='display:block;'>
<form id='editform'>
<input type="hidden" name="edit-hook-id" id="edit-hook-id" value="<?php echo $id ?>" />
<input type="hidden" name="enabled" id="enabled" value="<?php echo $desc['enabled']; ?>" />
<table>
<tr><td><label style='font-weight: bold' for='edithook'><?php _e("WordPress hook type",'hookpress');?>: </label></td>
<td><input type='radio' id='action' class='newtype' name='newtype' <?php checked('action',$desc['type']);?>> <?php _e("action","hookpress");?></input> 
<input type='radio' id='filter' class='newtype' name='newtype' <?php checked('filter',$desc['type']);?>> <?php _e("filter","hookpress");?></input></td></tr>
<tr>
<td><label style='font-weight: bold' for='edithook' id='action_or_filter'>
<?php
if ($desc['type'] == 'action')
	echo 'Action:';
if ($desc['type'] == 'filter')
	echo 'Filter:';
?>
</label></td>
<td><select name='edithook' id='edithook'>
		<?php		
			sort($hooks);
			foreach ($hooks as $hook) {
				$selected = ($hook == $desc['hook'])?'selected="true"':'';
		$hook = esc_html( $hook );
				echo "<option value='$hook' $selected>$hook</option>";
			}
			$nonce_submit = "<input type='hidden' id='submit-nonce' name='submit-nonce' value='" . wp_create_nonce( 'submit-webhook') . "' />";
		?>
	</select></td></tr>
<tr><td style='vertical-align: top'><label style='font-weight: bold' for='editfields'><?php _e("Fields",'hookpress');?>: </label>
<br/>
<small><?php _e("Ctrl-click on Windows or Command-click on Mac to select multiple. The <code>hook</code> field with the relevant hook name is always sent.");?></small>
<br/>
<span id='filtermessage'><small><?php _e('The first argument of a filter must always be sent and should be returned by the webhook, with modification.','hookpress');?></small></span>
</td>
<td>
<select style='vertical-align: top' name='editfields' id='editfields' multiple='multiple' size='8'>
<?php
	global $wpdb, $hookpress_actions, $hookpress_filters;
	if ($desc['type'] == 'action')
		$args = $hookpress_actions[$desc['hook']];
	if ($desc['type'] == 'filter')
		$args = $hookpress_filters[$desc['hook']];
		
	$fields = array();
	foreach ($args as $arg) {
		if (ereg('[A-Z]+',$arg))
			$fields = array_merge($fields,hookpress_get_fields($arg));
		else
			$fields[] = $arg;
	}
	
	if ($desc['type'] == 'filter') {
		$first = array_shift($fields);
		$first = esc_html( $first );
		echo "<option value='$first' selected='selected' class='first'>$first</option>";
	}
	sort($fields);
	foreach ($fields as $field) {
		$selected = '';
		foreach($desc['fields'] as $cmp){
			if($cmp==$field){
				$selected = 'selected="true"';
			}
		}
	$field = esc_html( $field );
		echo "<option value='$field' $selected>$field</option>";
	}
	$desc['url'] = esc_html( $desc['url'] );
?></select></td></tr>
<tr><td><label style='font-weight: bold' for='newurl'><?php _e("URL",'hookpress');?>: </label></td>
<td><input name='editurl' id='editurl' size='40' value="<?php echo $desc['url']; ?>"></input></td></tr>
</table>
<?php	echo $nonce_submit; ?>
	<center><span id='editindicator'></span><br/>
	<input type='button' class='button' id='editsubmit' value='<?php _e('Save webhook','hookpress');?>'/>
	<input type='button' class='button' id='editcancel' value='<?php _e('Cancel');?>'/></center>

</form>
</div>
<?php
}

function hookpress_print_webhook_row( $id ) {
#	$webhooks = get_option('hookpress_webhooks');
	$webhooks = hookpress_get_hooks( );
	$desc = $webhooks[$id];

	if( !empty( $desc ) ):
	
	$is_active = $desc['enabled'];
	$html_safe['id'] = esc_html( $id );

	if ( $is_active ) :
		$nonce_action = "<input type='hidden' id='action-nonce-{$html_safe['id']}' name='action-nonce-{$html_safe['id']}' value='" . wp_create_nonce( 'deactivate-webhook-' . $html_safe['id'] ) . "' />";
		$action = '<a href="#" id="on'. $html_safe['id'] . '" title="' . __('Deactivate this webhook') . '" class="on">' . __('Deactivate') . '</a>';
	else :
		$nonce_action = "<input type='hidden' id='action-nonce-{$html_safe['id']}' name='action-nonce-{$html_safe['id']}' value='" . wp_create_nonce( 'activate-webhook-' . $html_safe['id'] ) . "' />";
#		$action = '<a href="#'. $nonce_action . '" id="off'. $html_safe['id'] . '" title="' . __('Activate this webhook') . '" class="off">' . __('Activate') . '</a>';
		$action = '<a href="#" id="off'. $html_safe['id'] . '" title="' . __('Activate this webhook') . '" class="off">' . __('Activate') . '</a>';
	endif;

	$nonce_delete = "<input type='hidden' id='delete-nonce-{$html_safe['id']}' name='delete-nonce-{$html_safe['id']}' value='" . wp_create_nonce( 'delete-webhook-' . $html_safe['id'] ) . "' />";
	$delete = '<a href="#" id="delete'. $html_safe['id'] . '" title="' . __('Delete this webhook') . '" class="delete">' . __('Delete') . '</a>';
	if( count( $desc['fields'] ) > 1 ) {
		$desc['fields'] = array_map( 'esc_html', $desc['fields'] );
		$fields = implode('</code>, <code>', $desc['fields'] );
	} else
		$fields = esc_html( $desc['fields'][0] );
		
	$edit = '<a href="#TB_inline?inlineId=hookpress-webhook&height=330&width=500" id="edit'. $html_safe['id'] . '" title="' . __('Edit this webhook') . '" class="thickbox edit">' . __('Edit') . '</a>';
	
	$activeornot = $desc['enabled'] ? 'active' : 'inactive';

	$html_safe['hook'] = esc_html( $desc['hook'] );
	$html_safe['url'] = esc_html( $desc['url'] );

	echo "
<tr id='$id' class='$activeornot'>
	<td class='webhook-title'><strong>{$html_safe['hook']}</strong>
	<div class='row-actions'>$nonce_action $nonce_delete<span class='edit'>$edit | <span class='delete'>$delete | </span><span class='action'>$action</span></div></td>
	<td class='desc'><p>{$html_safe['url']}</p></td>
	<td class='desc'><code ".($desc['type'] == 'filter' ? " style='background-color:#ECEC9D' title='".__('The data in the highlighted field is expected to be returned from the webhook, with modification.','hookpress')."'":"").">$fields</code></td>
</tr>\n";
	endif;
}

function hookpress_print_webhooks_table() {
	global $page;
	$webhooks = null;
#	$webhooks = get_option('hookpress_webhooks');
	$webhooks = hookpress_get_hooks( );
?>
<table class="widefat" cellspacing="0" id="webhooks">
	<thead>
	<tr>
		<th scope="col" class="manage-column" style="width:15%"><?php _e("Hook","hookpress");?></th>
		<th scope="col" class="manage-column" style="width:25%"><?php _e("URL","hookpress");?></th>
		<th scope="col" class="manage-column"><?php _e("Fields","hookpress");?></th>
	</tr>
	</thead>

	<tfoot>
	<tr>
		<th scope="col" class="manage-column"><?php _e("Hook","hookpress");?></th>
		<th scope="col" class="manage-column"><?php _e("URL","hookpress");?></th>
		<th scope="col" class="manage-column"><?php _e("Fields","hookpress");?></th>
	</tr>
	</tfoot>

	<tbody class="webhooks">
<?php

	if ( !empty($webhooks) ) :
	
	foreach ( (array)$webhooks as $id => $desc) :
		
		if( !empty( $desc ) ):		
			hookpress_print_webhook_row( $id );
		endif;
	endforeach;
	endif;
?>
	</tbody>
</table>
<?php
}

// MAGIC

function hookpress_register_hooks() {
	global $hookpress_callbacks, $hookpress_actions, $hookpress_filters;
	$hookpress_callbacks = array();
	
	$all_hooks = hookpress_get_hooks( );
	
	if (!is_array( $all_hooks ) )
		return;

	foreach ( $all_hooks as $id => $desc) {
		if (count($desc) && $desc['enabled']) {
			$hookpress_callbacks[$id] = create_function('','
				$args = func_get_args();
				return hookpress_generic_action('.$id.',$args);
			');

			$arg_count = 0;
			if (isset($desc['type']) && $desc['type'] == 'filter')
				$arg_count = count($hookpress_filters[$desc['hook']]);
			else
				$arg_count = count($hookpress_actions[$desc['hook']]);

			add_filter($desc['hook'], $hookpress_callbacks[$id], HOOKPRESS_PRIORITY, $arg_count);
		}
	}
}

function hookpress_generic_action($id,$args) {
	global $hookpress_version, $wpdb, $hookpress_actions, $hookpress_filters, $wp_version;
	
	$webhooks = hookpress_get_hooks( );
	$desc = $webhooks[$id];

	do_action( 'hookpress_hook_fired', $desc );

	$obj = array();
	
	// generate the expected argument names
	if (isset($desc['type']) && $desc['type'] == 'filter')
		$arg_names = $hookpress_filters[$desc['hook']];	
	else
		$arg_names = $hookpress_actions[$desc['hook']];
	
	foreach($args as $i => $arg) {
		$newobj = array();
		switch($arg_names[$i]) {
			case 'POST':
			case 'ATTACHMENT':
				$newobj = get_post($arg,ARRAY_A);

				if ($arg_names[$i] == 'POST')
					$newobj["post_url"] = get_permalink($newobj["ID"]);
					
				if (wp_is_post_revision($arg)) {
					$parent = get_post(wp_is_post_revision($arg));
					foreach ($parent as $key => $val) {
						$newobj["parent_$key"] = $val;
					}
					$newobj["parent_post_url"] = get_permalink($newobj["parent_ID"]);
				}
				
				break;
			case 'COMMENT':
				$arg = (int) $arg;
				$newobj = (array) get_comment( $arg );
				break;
			case 'CATEGORY':
				$newobj = $wpdb->get_row("select * from $wpdb->categories where cat_ID = $arg",ARRAY_A);
				break;
			case 'USER':
				$newobj = $wpdb->get_row("select * from $wpdb->users where ID = $arg",ARRAY_A);
				break;
			case 'LINK':
				$newobj = $wpdb->get_row("select * from $wpdb->links where link_id = $arg",ARRAY_A);
				break;
			case 'TAG_OBJ':
				$newobj = (array) $arg;
				break;
			case 'USER_OBJ':
				$newobj = (array) $arg;
			case 'OLD_USER_OBJ':
				$newobj = array_map(create_function('$x','return "old_$x";'), (array) $arg);
			default:
				$newobj[$arg_names[$i]] = $arg;
		}
		$obj = array_merge($obj,$newobj);
	}
	
	// take only the fields we care about
	$obj_to_post = array_intersect_key($obj,array_flip($desc['fields']));
	$obj_to_post['hook'] = $desc['hook'];
	
	$user_agent = "HookPress/{$hookpress_version} (compatible; WordPress {$wp_version}; +http://mitcho.com/code/hookpress/)";
	
	$request = apply_filters( 'hookpress_request', array('user-agent' => $user_agent, 'body' => $obj_to_post, 'referer' => get_bloginfo('url')) );
	
	return wp_remote_post($desc['url'], $request);
}
