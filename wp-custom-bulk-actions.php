<?php
/**
 * Plugin Name: Custom Bulk Actions
 * Plugin URI: https://github.com/Seravo/wp-custom-bulk-actions
 * Description: Custom bulk actions for any type of post
 * Author: Seravo Oy
 * Author URI: http://seravo.fi
 * Version: 0.1.3
 * License: GPLv3
*/

/** Copyright 2014 Seravo Oy

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 3, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


if (!class_exists('Seravo_Custom_Bulk_Action')) {
 
	class Seravo_Custom_Bulk_Action {
		public $bulk_action_post_type;
		private $actions = array();
		
		public function __construct($args='') {
			//Define which post types these bulk actions affect.
			$defaults = array(
				'post_type' => 'post'
			);

			$args = wp_parse_args( $args, $defaults);
			//Define args as their own variables as well eg. $post_type
			extract( $args, EXTR_SKIP );

			$this->bulk_action_post_type = $post_type;
		}

		/**
		 * Define all your custom bulk actions and corresponding callbacks
		 * Define at least $menu_text and $callback parameters
		 */
		public function register_bulk_action($args='') {
			$defaults = array (
				'action_name' => ''
			);

			$args = wp_parse_args( $args, $defaults);
			//Define args as their own variables as well eg. $post_type
			extract( $args, EXTR_SKIP );

			$func = array();
			$func["callback"] = $callback;
			$func["menu_text"] = $menu_text;
			$func["admin_notice"] = $admin_notice;

			if ($action_name === '') {
				//Convert menu text to action_name 'Mark as sold' => 'mark_as_sold'
				$action_name = lcfirst(str_replace(' ', '_', $menu_text));
			}

			$this->actions[$action_name] = $func;
		}

		//Callbacks need to be registered before add_actions
		public function init() {
			if(is_admin()) {
				// admin actions/filters
				add_action('admin_footer-edit.php', array(&$this, 'custom_bulk_admin_footer'));
				add_action('load-edit.php',         array(&$this, 'custom_bulk_action'));
				add_action('admin_notices',         array(&$this, 'custom_bulk_admin_notices'));
			}
		}
		
		
		/**
		 * Step 1: add the custom Bulk Action to the select menus
		 */
		function custom_bulk_admin_footer() {
			global $post_type;
			
			//Only permit actions with defined post type
			if($post_type == $this->bulk_action_post_type) {
				?>
					<script type="text/javascript">
						jQuery(document).ready(function() {
							<?php
							foreach ($this->actions as $action_name => $action) { ?>
								jQuery('<option>').val('<?php echo $action_name ?>').text('<?php echo $action["menu_text"] ?>').appendTo("select[name='action']");
								jQuery('<option>').val('<?php echo $action_name ?>').text('<?php echo $action["menu_text"] ?>').appendTo("select[name='action2']");
							<?php } ?>
						});
					</script>
				<?php
			}
		}

		
		
		/**
		 * Step 2: handle the custom Bulk Action
		 * 
		 * Based on the post http://wordpress.stackexchange.com/questions/29822/custom-bulk-action
		 */
		function custom_bulk_action() {
			global $typenow;
			$post_type = $typenow;
			
			if($post_type == $this->bulk_action_post_type) {
				
				// get the action
				$wp_list_table = _get_list_table('WP_Posts_List_Table');  // depending on your resource type this could be WP_Users_List_Table, WP_Comments_List_Table, etc
				$action = $wp_list_table->current_action();
				
				// allow only defined actions
				$allowed_actions = array_keys($this->actions);
				if(!in_array($action, $allowed_actions)) return;
				
				// security check
				check_admin_referer('bulk-posts');
				
				// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
				if(isset($_REQUEST['post'])) {
					$post_ids = array_map('intval', $_REQUEST['post']);
				}
				
				if(empty($post_ids)) return;
				
				// this is based on wp-admin/edit.php
				$sendback = remove_query_arg( array('exported', 'untrashed', 'deleted', 'ids'), wp_get_referer() );
				if ( ! $sendback )
					$sendback = admin_url( "edit.php?post_type=$post_type" );
				
				$pagenum = $wp_list_table->get_pagenum();
				$sendback = add_query_arg( 'paged', $pagenum, $sendback );

				if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
					//check that we have anonymous function as a callback
					$anon_fns = array_filter( $this->actions[$action], function( $el) { return $el instanceof Closure; });
					if( count($anon_fns) != 0) {
						//Finally use the callback
						$result = $this->actions[$action]['callback']($post_ids);
					}
					else {
						$result = call_user_func($this->actions[$action]['callback'], $post_ids);
					}
				}
				else {
					$result = call_user_func($this->actions[$action]['callback'], $post_ids);
				}

				$sendback = add_query_arg( array('success_action' => $action, 'ids' => join(',', $post_ids)), $sendback );
				
				$sendback = remove_query_arg( array('action', 'paged', 'mode', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $sendback );
				
				wp_redirect($sendback);
				exit();
			}
		}
		
		
		/**
		 * Step 3: display an admin notice after action
		 */
		function custom_bulk_admin_notices() {
			global $post_type, $pagenow;
			
			if( isset($_REQUEST['ids']) ){
				$post_ids = explode( ',', $_REQUEST['ids'] );
			}
			
			// make sure ids are submitted.  depending on the resource type, this may be 'media' or 'ids'
			if(empty($post_ids)) return;
			
			$post_ids_count = 1;
			if( is_array($post_ids) ){
				$post_ids_count = count($post_ids);
			}
			
			if($pagenow == 'edit.php' && $post_type == $this->bulk_action_post_type) {
				if (isset($_REQUEST['success_action'])) {
					//Print notice in admin bar
					$message = $this->actions[$_REQUEST['success_action']]['admin_notice'];
					
					if( is_array($message) ){
						$message = sprintf( _n( $message['single'], $message['plural'], $post_ids_count, 'wordpress' ), $post_ids_count );
					}
					$class = "updated notice is-dismissible above-h2";
					if(!empty($message)) {
						echo "<div class=\"{$class}\"><p>{$message}</p></div>";
					}
				}
			}
		}
	}
}
