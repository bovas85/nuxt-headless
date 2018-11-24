<?php

function hookpress_ajax_get_fields() {
	global $wpdb, $hookpress_actions, $hookpress_filters;
	if ($_POST['type'] == 'action')
		$args = $hookpress_actions[$_POST['hook']];
	if ($_POST['type'] == 'filter')
		$args = $hookpress_filters[$_POST['hook']];

	$fields = array();
	if (is_array($args)) {
		foreach ($args as $arg) {
			if (ereg('[A-Z]+',$arg))
				$fields = array_merge($fields,hookpress_get_fields($arg));
			else
				$fields[] = $arg;
		}
	}

	header("Content-Type: text/html; charset=UTF-8");

	if ($_POST['type'] == 'filter') {
		$first = array_shift($fields);
		$first = esc_html( $first );
		echo "<option value='$first' selected='selected' class='first'>$first</option>";
	}
	sort($fields);
	foreach ($fields as $field) {
		$field = esc_html( $field );
		echo "<option value='$field'>$field</option>";
	}
	exit;
}

function hookpress_ajax_add_fields() {
	$nonce = $_POST['_nonce'];
	$nonce_compare = 'submit-webhook';

	if ( wp_verify_nonce( $nonce, $nonce_compare ) ) :

	if( isset($_POST['id']) ){

		$id = (int) $_POST['id'];
		$edithook = array(
			'url'=>$_POST['url'],
			'type'=>$_POST['type'],
			'hook'=>$_POST['hook'],
			'enabled'=>$_POST['enabled'],
			'fields'=>split(',',$_POST['fields'])
		);
		hookpress_update_hook( $id, $edithook );

	} else {

		// register the new webhook
		$newhook = array(
			'url'=>$_POST['url'],
			'type'=>$_POST['type'],
			'hook'=>$_POST['hook'],
			'fields'=>split(',',$_POST['fields']),
			'enabled'=>true
		);
		$id = hookpress_add_hook($newhook);
	}

	endif;

	// generate the return value
	header("Content-Type: text/html; charset=UTF-8");
	echo hookpress_print_webhook_row($id);
	exit;
}

function hookpress_ajax_set_enabled() {
	$nonce = $_POST['_nonce'];
	$id = $_POST['id'];
	$enabled = $_POST['enabled'];

	$nonce_compare = ($enabled == 'true'?'activate-webhook-' . $id:'deactivate-webhook-' . $id ); 

	if ( wp_verify_nonce( $nonce, $nonce_compare ) ) :

		// update the webhook
		$webhooks = hookpress_get_hooks();
		$webhooks[$id]['enabled'] = ($enabled == 'true'?true:false);
		hookpress_update_hook( $id, $webhooks[$id] );

	endif;

	header("Content-Type: text/html; charset=UTF-8");
	echo hookpress_print_webhook_row($id);
	exit;
}

function hookpress_ajax_delete_hook() {
	$nonce = $_POST['_nonce'];
	$webhooks = hookpress_get_hooks( );
	if (!isset($_POST['id']))
		die("ERROR: no id given");
	$id = (int) $_POST['id'];

	$nonce_compare = 'delete-webhook-' . $id;

	if ( !wp_verify_nonce( $nonce, $nonce_compare ) )
		die("ERROR: invalid nonce");

	 if (!$webhooks[$id])
		die("ERROR: no webhook found for that id");
	hookpress_delete_hook( $id );
	echo "ok";
	exit;
}

function hookpress_ajax_edit_hook( $id ) {
	$id = (int) $_POST['id'];
	hookpress_print_edit_webhook( $id );
	exit;
}

function hookpress_ajax_get_hooks() {
	global $wpdb, $hookpress_actions, $hookpress_filters;
	if ($_POST['type'] == 'action')
		$hooks = array_keys($hookpress_actions);
	if ($_POST['type'] == 'filter')
		$hooks = array_keys($hookpress_filters);

	header("Content-Type: text/html; charset=UTF-8");

	if (is_array($hooks)) {
		sort($hooks);
		foreach ($hooks as $hook) {
			$hook = esc_html( $hook );
			echo "<option value='$hook'>$hook</option>";
		}
	}
	exit;
}
