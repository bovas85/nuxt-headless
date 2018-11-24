<?php
/*
Plugin Name: Duplicate Page
Plugin URI: https://wordpress.org/plugins/duplicate-page/
Description: Duplicate Posts, Pages and Custom Posts using single click.
Author: mndpsingh287
Version: 2.8
Author URI: https://profiles.wordpress.org/mndpsingh287/
License: GPLv2
Text Domain: duplicate-page
*/
if (!defined("DUPLICATE_PAGE_PLUGIN_DIRNAME")) define("DUPLICATE_PAGE_PLUGIN_DIRNAME", plugin_basename(dirname(__FILE__)));
if(!class_exists('duplicate_page')):
	class duplicate_page 
	{
		/*
		* AutoLoad Hooks
		*/	
		public function __construct(){
			register_activation_hook(__FILE__, array(&$this, 'duplicate_page_install'));
			add_action('admin_menu', array(&$this, 'duplicate_page_options_page'));
			add_filter( 'plugin_action_links', array(&$this, 'duplicate_page_plugin_action_links'), 10, 2 );
			add_action( 'admin_action_dt_duplicate_post_as_draft', array(&$this,'dt_duplicate_post_as_draft') ); 
			add_filter( 'post_row_actions', array(&$this,'dt_duplicate_post_link'), 10, 2);
			add_filter( 'page_row_actions', array(&$this,'dt_duplicate_post_link'), 10, 2);
			add_action( 'post_submitbox_misc_actions', array(&$this,'duplicate_page_custom_button'));
			add_action( 'wp_before_admin_bar_render', array(&$this, 'duplicate_page_admin_bar_link'));
			add_action('init', array(&$this, 'duplicate_page_load_text_domain'));
		}
		/*
		* Localization - 19-dec-2016
		*/
		public function duplicate_page_load_text_domain(){
			load_plugin_textdomain('duplicate-page', false, DUPLICATE_PAGE_PLUGIN_DIRNAME . "/languages");
		}
		/*
		* Activation Hook
		*/
		public function duplicate_page_install(){
					$defaultsettings = array(
											 'duplicate_post_status' => 'draft',
											 'duplicate_post_redirect' => 'to_list',
											 'duplicate_post_suffix' => ''
											 );
					$opt = get_option('duplicate_page_options');
					if(!$opt['duplicate_post_status']) {
						update_option('duplicate_page_options', $defaultsettings);
					}           	
		}
		/*
		Action Links
		*/
		public function duplicate_page_plugin_action_links($links, $file){
			if ( $file == plugin_basename( __FILE__ ) ) {
				$duplicate_page_links = '<a href="'.get_admin_url().'options-general.php?page=duplicate_page_settings">'.__('Settings', 'duplicate-page').'</a>';
				$duplicate_page_donate = '<a href="http://www.webdesi9.com/donate/?plugin=duplicate-page" title="Donate Now" target="_blank" style="font-weight:bold">'.__('Donate', 'duplicate-page').'</a>';
				array_unshift( $links, $duplicate_page_donate );
				array_unshift( $links, $duplicate_page_links );
			}
		
			return $links;
		}
		/*
		* Admin Menu 
		*/
		public function duplicate_page_options_page(){	
		 add_options_page( __( 'Duplicate Page', 'duplicate-page' ), __( 'Duplicate Page', 'duplicate-page' ), 'manage_options', 'duplicate_page_settings',array(&$this, 'duplicate_page_settings'));
		}
		/*
		* Duplicate Page Admin Settings
		*/
		public function duplicate_page_settings(){
			if(current_user_can( 'manage_options' )){
			   include('admin-settings.php');
			}
		}
		/*
		* Main function
		*/
		public function dt_duplicate_post_as_draft(){
		global $wpdb;
		$opt = get_option('duplicate_page_options');
		$suffix = isset($opt['duplicate_post_suffix']) && !empty($opt['duplicate_post_suffix']) ? ' -- '.$opt['duplicate_post_suffix'] : '';
		$post_status = !empty($opt['duplicate_post_status']) ? $opt['duplicate_post_status'] : 'draft';	
		$redirectit = !empty($opt['duplicate_post_redirect']) ? $opt['duplicate_post_redirect'] : 'to_list';	 
					 if (! ( isset( $_GET['post']) || isset( $_POST['post']) || ( isset($_REQUEST['action']) && 'dt_duplicate_post_as_draft' == $_REQUEST['action'] ) ) ) {
					 wp_die('No post to duplicate has been supplied!');
				 } 
				 $returnpage = '';
				 /*
				 * get the original post id
				 */
				  $post_id = (isset($_GET['post']) ? $_GET['post'] : $_POST['post']);
				 /*
				 * and all the original post data then
				 */
				 $post = get_post( $post_id ); 
				 /*
				 * if you don't want current user to be the new post author,
				 * then change next couple of lines to this: $new_post_author = $post->post_author;
				 */
				 $current_user = wp_get_current_user();
				 $new_post_author = $current_user->ID; 
				 /*
				 * if post data exists, create the post duplicate
				 */
				 if (isset( $post ) && $post != null) { 
				 /*
				 * new post data array
				 */
				 $args = array(
					 'comment_status' => $post->comment_status,
					 'ping_status' => $post->ping_status,
					 'post_author' => $new_post_author,
					 'post_content' => $post->post_content,
					 'post_excerpt' => $post->post_excerpt,
					 'post_name' => $post->post_name,
					 'post_parent' => $post->post_parent,
					 'post_password' => $post->post_password,
					 'post_status' => $post_status,
					 'post_title' => $post->post_title.$suffix,
					 'post_type' => $post->post_type,
					 'to_ping' => $post->to_ping,
					 'menu_order' => $post->menu_order
				 ); 
				 /*
				 * insert the post by wp_insert_post() function
				 */
				 $new_post_id = wp_insert_post( $args ); 
				 /*
				 * get all current post terms ad set them to the new post draft
				 */
				 $taxonomies = get_object_taxonomies($post->post_type);
				 if(!empty($taxonomies) && is_array($taxonomies)):
				 foreach ($taxonomies as $taxonomy) {
					 $post_terms = wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs'));
					 wp_set_object_terms($new_post_id, $post_terms, $taxonomy, false);
				 } 
				 endif;
				 /*
				 * duplicate all post meta
				 */
				 $post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$post_id");
				 if (count($post_meta_infos)!=0) {
				 $sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
				 foreach ($post_meta_infos as $meta_info) {
					 $meta_key = $meta_info->meta_key;
					 $meta_value = addslashes($meta_info->meta_value);
					 $sql_query_sel[]= "SELECT $new_post_id, '$meta_key', '$meta_value'";
					 }
					 $sql_query.= implode(" UNION ALL ", $sql_query_sel);
					 $wpdb->query($sql_query);
					 } 
					 /*
					 * finally, redirecting to your choice
					 */
					if($post->post_type != 'post'):
					   $returnpage = '?post_type='.$post->post_type;
					endif;
					if(!empty($redirectit) && $redirectit == 'to_list'): 
						wp_redirect( admin_url( 'edit.php'.$returnpage ) );
					elseif(!empty($redirectit) && $redirectit == 'to_page'): 	
						wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
					else:
						wp_redirect( admin_url( 'edit.php'.$returnpage ) );
					endif;	
					 exit;
					 } else {
						wp_die('Error! Post creation failed, could not find original post: ' . $post_id);
					 }
		}
		/*
		 * Add the duplicate link to action list for post_row_actions
		 */
		public function dt_duplicate_post_link( $actions, $post ) {
			$opt = get_option('duplicate_page_options');
			$post_status = !empty($opt['duplicate_post_status']) ? $opt['duplicate_post_status'] : 'draft';	
			 if (current_user_can('edit_posts')) {
			 $actions['duplicate'] = '<a href="admin.php?action=dt_duplicate_post_as_draft&amp;post=' . $post->ID . '" title="Duplicate this as '.$post_status.'" rel="permalink">'.__( "Duplicate This", "duplicate-page" ).'</a>';
			 }
			 return $actions;
		} 
		/*
		 * Add the duplicate link to edit screen
		 */
		public function duplicate_page_custom_button(){
			global $post;
			$opt = get_option('duplicate_page_options');
			$post_status = !empty($opt['duplicate_post_status']) ? $opt['duplicate_post_status'] : 'draft';	
				$html  = '<div id="major-publishing-actions">';
				$html .= '<div id="export-action">';
				$html .= '<a href="admin.php?action=dt_duplicate_post_as_draft&amp;post=' . $post->ID . '" title="Duplicate this as '.$post_status.'" rel="permalink">'.__( "Duplicate This", "duplicate-page" ).'</a>';
				$html .= '</div>';
				$html .= '</div>';
				echo $html;
		}
		/*
		* Admin Bar Duplicate This Link
		*/
		public function duplicate_page_admin_bar_link(){
			global $wp_admin_bar, $post;
			$opt = get_option('duplicate_page_options');
			$post_status = !empty($opt['duplicate_post_status']) ? $opt['duplicate_post_status'] : 'draft';	
			$current_object = get_queried_object();
			if ( empty($current_object) )
			return;
			if ( ! empty( $current_object->post_type )
			&& ( $post_type_object = get_post_type_object( $current_object->post_type ) )
			&& ( $post_type_object->show_ui || $current_object->post_type  == 'attachment') )
			{
				$wp_admin_bar->add_menu( array(
				'parent' => 'edit',
				'id' => 'duplicate_this',
				'title' => __("Duplicate this as ".$post_status."", 'duplicate-page'),
				'href' => admin_url().'admin.php?action=dt_duplicate_post_as_draft&amp;post='. $post->ID
				) );
			}
		}
		/*
		 * Redirect function
		*/
		static function dp_redirect($url){
			echo '<script>window.location.href="'.$url.'"</script>';
		}
	}
new duplicate_page;
endif;
?>