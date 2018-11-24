<script type='text/javascript'>
var getTb;

(function($) {

var thickboxWindow = false;
getTb = function() {
	return jQuery('#TB_window');
}

var getHooks = function getHooks() {
	console.log('getHooks');
	var tb = getTb();
	var type = tb.find('.newtype:checked').attr('id');
	if (type == 'action') {
		tb.find('#action_or_filter').text('<?php _e("Action:",'hookpress');?> ');
		tb.find('#filtermessage').hide();
	}
	if (type == 'filter') {
		tb.find('#action_or_filter').text('<?php _e("Filter:",'hookpress');?> ');
		tb.find('#filtermessage').show();
	}
	$.ajax({type:'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_get_hooks&type='+type,
		beforeSend:function(){
			tb.find('#newhook').html('<div class="webhooks-spinner">&nbsp;</div>');
		},
		success:function(html){
			tb.find('#newhook').html(html);
			getFields();
		},
		dataType:'html'}
	)
}

var getFields = function getFields() {
	console.log('getFields');
	var tb = getTb();
	var hook = tb.find('#newhook').val();
	var type = tb.find('.newtype:checked').attr('id');
	$.ajax({type:'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_get_fields&hook='+hook+'&type='+type,
		beforeSend:function(){
			tb.find('#newfields').html('<div class="webhooks-spinner">&nbsp;</div>');
		},
		success:function(html){
			tb.find('#newfields').html(html)
		},
		dataType:'html'}
	)
};

var getEditHooks = function getEditHooks() {
	console.log('getEditHooks');
	var tb = getTb();
	var type = tb.find('.newtype:checked').attr('id');
	if (type == 'action') {
		tb.find('#action_or_filter').text('<?php _e("Action:",'hookpress');?> ');
		tb.find('#filtermessage').hide();
	}
	if (type == 'filter') {
		tb.find('#action_or_filter').text('<?php _e("Filter:",'hookpress');?> ');
		tb.find('#filtermessage').show();
	}
	$.ajax({type:'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_get_hooks&type='+type,
		beforeSend:function(){
			tb.find('#edithook').html('<div class="webhooks-spinner">&nbsp;</div>');
		},
		success:function(html){
			tb.find('#edithook').html(html);
			getEditFields();
		},
		dataType:'html'}
	)
}

var getEditFields = function getEditFields() {
	console.log('getEditFields');
	var tb = getTb();
	var hook = tb.find('#edithook').val();
	var type = tb.find('.newtype:checked').attr('id');
	$.ajax({type:'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_get_fields&hook='+hook+'&type='+type,
		beforeSend:function(){
			tb.find('#editfields').html('<div class="webhooks-spinner">&nbsp;</div>');
		},
		success:function(html){
			tb.find('#editfields').html(html);
		},
		dataType:'html'}
	)
};

var editSubmit = function editSubmit() {
	console.log('editSubmit');
	var tb = getTb();
	if (!tb.find('#editfields').val()) {
		tb.find('#editindicator').html('<small><?php _e("You must select at least one field to send.","hookpress");?></small>');
		return;
	}
	if (!/^https?:\/\/\w+/.test(tb.find('#editurl').val())) {
		tb.find('#editindicator').html('<small><?php _e("Please enter a valid URL.","hookpress");?></small>');
		return;
	}

	tb.find('#editindicator').html('<div class="webhooks-spinner">&nbsp;</div>');

	id = tb.find('#edit-hook-id').val();
	
	$.ajax({type: 'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_add_fields'
				 +'&fields='+tb.find('#editfields').val().join()
				 +'&url='+tb.find('#editurl').val()
				 +'&type='+tb.find('.newtype:checked').attr('id')
				 +'&hook='+tb.find('#edithook').val()
				 +'&enabled='+tb.find('#enabled').val()
				 +'&id='+id
				 +'&_nonce='+tb.find('#submit-nonce').val(),
		beforeSend:function(){
			tb.find('#editsubmit').hide();
			tb.find('#editcancel').hide()
		},
		success:function(html){
			tb.find('#editsubmit').show();
			tb.find('#editcancel').show()
			tb.find('#editindicator').html('');
			if (/^ERROR/.test(html))
				tb.find('#editindicator').html(html);
			else if (!html)
				tb.find('#editindicator').html('<?php _e("There was an unknown error.","hookpress");?>');
			else {
				$('#'+id).replaceWith(html);
				tb_init('a.thickbox, area.thickbox, input.thickbox');
				tb_remove();
			}
		},
		dataType:'html'}
	);
};

var enforceFirst = function enforceFirst() {
	console.log('enforceFirst');
	var tb = getTb();
	var type = tb.find('.newtype:checked').attr('id');
	if (type == 'action')
		return;
	tb.find('option.first').attr('selected',true);
}

var newSubmit = function newSubmit() {
	console.log('newSubmit');
	var tb = getTb();
	if (!tb.find('#newfields').val()) {
		tb.find('#newindicator').html('<small><?php _e("You must select at least one field to send.","hookpress");?></small>');
		return;
	}
	if (!/^https?:\/\/\w+/.test(tb.find('#newurl').val())) {
		tb.find('#newindicator').html('<small><?php _e("Please enter a valid URL.","hookpress");?></small>');
		return;
	}

	tb.find('#newindicator').html('<div class="webhooks-spinner">&nbsp;</div>');

	$.ajax({type: 'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_add_fields'
				 +'&fields='+tb.find('#newfields').val().join()
				 +'&url='+tb.find('#newurl').val()
				 +'&type='+tb.find('.newtype:checked').attr('id')
				 +'&hook='+tb.find('#newhook').val()
				 +'&_nonce='+tb.find('#submit-nonce').val(),
		beforeSend:function(){
			tb.find('#newsubmit').hide();
			tb.find('#newcancel').hide()
		},
		success:function(html){
			tb.find('#newsubmit').show();
			tb.find('#newcancel').show()
			tb.find('#newindicator').html('');
			if (/^ERROR/.test(html))
				tb.find('#newindicator').html(html);
			else if (!html)
				tb.find('#newindicator').html('<?php _e("There was an unknown error.","hookpress");?>');
			else {
				var newhook = $(html);
				newhook.css('background-color','rgb(255, 251, 204)');
				newhook.appendTo($('#webhooks'));
				tb_init('a.thickbox, area.thickbox, input.thickbox');
				tb_remove();
/*				setEvents(); */
				newhook.animate({backgroundColor:'white'},2000,null,
					function(){newhook.css('background-color','transparent')});
			}
		},
		dataType:'html'}
	);
};

var deleteHook = function deleteHook(id) {
	console.log('deleteHook');
	var nonce = $('#delete-nonce-' + id).val();
	$.ajax({type: 'POST',
		url:'admin-ajax.php',
		beforeSend:function(){$('#' + id + ' span.edit').html('<div class="webhooks-spinner">&nbsp;</div>')},
		data:'action=hookpress_delete_hook&id='+id + '&_nonce=' +nonce,
		success:function(html){
			if (/^ERROR/.test(html))
				$('#message').html(html);
			else {
				$('#'+id).fadeOut('fast',function(){$('#'+id).remove()});
			}
		},
		dataType:'html'}
	);
}

var setHookEnabled = function setHookEnabled(id, nonce, boolean) {
	console.log('setHookEnabled');
	$.ajax({type: 'POST',
		url:'admin-ajax.php',
	beforeSend:function(){$('#' + id + ' span.edit').html('<div class="webhooks-spinner">&nbsp;</div>')},
		data:'action=hookpress_set_enabled&id='+id+'&_nonce='+nonce+'&enabled='+boolean,
		success:function(html){
			if (/^ERROR/.test(html))
				$('#message').html(html);
			else {
				$('#'+id).fadeOut('fast',function(){
					$('#'+id).replaceWith(html);
					tb_init('a.thickbox, area.thickbox, input.thickbox');
/*					setEvents(); */
				});
			}
		},
		dataType:'html'}
	);
}

var setupEditHook = function setupEditHook(id) {
	console.log('setupEditHook');
	var tb = getTb();
	$.ajax({type: 'POST',
		url:'admin-ajax.php',
		data:'action=hookpress_edit_hook&id='+id,
		success: function(html){
			$('#TB_ajaxContent').html(html)
				.find('.newtype').change(getEditHooks).end()
				.find('#edithook').change(getEditFields).end()
				.find('#editfields').change(enforceFirst).end()
				.find('#editsubmit').click(editSubmit).end()
				.find('#editcancel').click(tb_remove);

			var type = $('#TB_ajaxContent').find('.newtype:checked').attr('id');
			if (type == 'action') {
				$('#TB_ajaxContent').find('#action_or_filter').text('<?php _e("Action:",'hookpress');?> ');
				$('#TB_ajaxContent').find('#filtermessage').hide();
			}
			if (type == 'filter') {
				$('#TB_ajaxContent').find('#action_or_filter').text('<?php _e("Filter:",'hookpress');?> ');
				$('#TB_ajaxContent').find('#filtermessage').show();
			}

		},
		dataType:'html'}
	);
}

$(document).ready(function(){
	// initial setup
//	getHooks();
	$('#newwebhook').click(function() {
		setTimeout(function() {
			getHooks();
		},0);
	})
	// set event handler
	setEvents();
});

var setEvents = function setEvents() {
	$(document.body)
		.on('change','#TB_window .newtype',getHooks)
		.on('change','#TB_window #newhook',getFields)
		.on('change','#TB_window #newfields',enforceFirst)
		.on('click','#TB_window #newsubmit',newSubmit)
		.on('click','#TB_window #newcancel',tb_remove);

	$('#webhooks')
		.on('click', '.delete', function(e){
			var id = e.currentTarget.id.replace('delete','');
			deleteHook(id);
		})
		.on('click', '.edit', function(e){
			var id = e.currentTarget.id.replace('edit','');
			if(id){setupEditHook(id);}
		})
		.on('click', '.on', function(e){
			var id = e.currentTarget.id.replace('on','');
			var nonce = $('#action-nonce-' + id).val();
			if(id && nonce){setHookEnabled(id, nonce, 'false');}
		})
		.on('click', '.off', function(e){
			var id = e.currentTarget.id.replace('off','');
			var nonce = $('#action-nonce-' + id).val();
			if(id&&nonce){setHookEnabled(id, nonce, 'true');}
		});
}

})(jQuery);
</script>
<style>
/* styles for 3.2+ */
#webhooks .active {
	background-color: #FCFCFC;
}
#webhooks .inactive {
	background-color: #F4F4F4;
}

/* styles for pre-3.2; only supporing 3.1 now */
.version-3-1 #webhooks .inactive {
	background-color: #eee;
}
.version-3-1 #webhooks .active {
	background-color: transparent;
}

.webhooks-spinner {
	background: url(<?php echo admin_url( 'images/wpspin_light.gif' ); ?>);
	height: 16px;
	width: 16px;
	visibility: visible !important;
}

</style>

<div class="wrap">
		<h2>
			<?php _e('HookPress','hookpress');?> <small><?php 
			
			$display_version = $hookpress_version;
			$split = explode('.',$display_version);
			if (strlen($split[1]) != 1) {
				$pos = strpos($display_version,'.')+2;
				$display_version = substr($display_version,0,$pos).'.'.substr($display_version,$pos);
			}
			echo $display_version;
			?></small>
		</h2>
		
	<form method="post">

			<a href='http://tinyurl.com/donatetomitcho' target='_new'><img src="https://www.paypal.com/en_US/i/btn/btn_donate_SM.gif" name="submit" alt="<?php _e('Donate to mitcho (Michael Yoshitaka Erlewine) for this plugin via PayPal');?>" title="<?php _e('Donate to mitcho (Michael Yoshitaka Erlewine) for this plugin via PayPal','hookpress');?>" style="float:right" /></a>

	<p><small><?php _e('by <a href="http://mitcho.com/">mitcho (Michael 芳貴 Erlewine)</a>','hookpress');?>.</small></p>

	<h3><?php _e("Webhooks","hookpress");?></h3>

<?php echo hookpress_print_webhooks_table();?>

	<p><input id="newwebhook" class="thickbox button" type="button" value="<?php _e("Add webhook",'hookpress');?>" title="<?php _e('Add new webhook','hookpress');?>" alt="#TB_inline?height=330&width=500&inlineId=hookpress-webhook"/></p>
		
</form>

<div id='hookpress-webhook' style='display:none;'>
<form id='newform'>
<table>
<tr><td><label style='font-weight: bold' for='newhook'><?php _e("WordPress hook type",'hookpress');?>: </label></td><td><input type='radio' id='action' class='newtype' name='newtype' checked='checked'> <?php _e("action","hookpress");?></input> <input type='radio' id='filter' class='newtype' name='newtype'> <?php _e("filter","hookpress");?></input></td></tr>
<tr>
<td><label style='font-weight: bold' for='newhook' id='action_or_filter'></label></td>
<td><select name='newhook' id='newhook'></select></td></tr>
<tr><td style='vertical-align: top'><label style='font-weight: bold' for='newfields'><?php _e("Fields",'hookpress');?>: </label><br/><small><?php _e("Ctrl-click on Windows or Command-click on Mac to select multiple. The <code>hook</code> field with the relevant hook name is always sent.");?></small><br/><span id='filtermessage'><small><?php _e('The first argument of a filter must always be sent and should be returned by the webhook, with modification.','hookpress');?></small></span></td><td><select style='vertical-align: top' name='newfields' id='newfields' multiple='multiple' size='8'>
	</select></td></tr>
<tr><td><label style='font-weight: bold' for='newurl'><?php _e("URL",'hookpress');?>: </label></td><td><input name='newurl' id='newurl' size='40' value='http://'></input></td></tr>
</table>
<?php	echo "<input type='hidden' id='submit-nonce' name='submit-nonce' value='" . wp_create_nonce( 'submit-webhook') . "' />"; ?>
	<center><span id='newindicator'></span><br/>
	<input type='button' class='button' id='newsubmit' value='<?php _e('Add new webhook','hookpress');?>'/>
	<input type='button' class='button' id='newcancel' value='<?php _e('Cancel');?>'/></center>

</form>
</div>
