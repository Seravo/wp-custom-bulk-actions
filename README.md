wp-custom-bulk-actions
======================
Currently Wordpress doesn't allow you to add custom bulk actions. See [codex](http://codex.wordpress.org/Plugin_API/Filter_Reference/bulk_actions).

## Plugin

This plugin adds a class named Seravo_Custom_Bulk_Action

### Class functions

Constructor with post as default post type

	new Seravo_Custom_Bulk_Action(array('post_type' => $custom_post));

Add actions

	register_bulk_action(array('menu_text'=>$your_menu_text, 'action_name'=>$action_name, 'callback'=>$anonymous_function));

Your anonymous_functions needs to have two parameters:
	$post_ids (array of post IDs selected in admin panel)
	$admin_text (reference to text which will show in admin panel after your action)

Init functions to wordpress

	init();

## Example & how to use
Install plugin and define your bulk actions in `functions.php`.

In this example we're going to update metadata of posts called _property_status
```php
$bulk_actions = new Seravo_Custom_Bulk_Action(array('post_type' => 'property'));

$bulk_actions->register_bulk_action(array('menu_text'=>'Mark as Sold',
	'callback' => function($post_ids,$admin_text) {
	foreach ($post_ids as $post_id) {
		update_post_meta($post_id,"_property_status", "sold");
	}
	$admin_text = "Merkattiin myyntiin!";
	return true;
}));

//Defining the action_name is optional but useful if you want to have non-ascii chars in menu_text
$bulk_actions->register_bulk_action(array('menu_text'=>'Mark for Sale', 'action_name'=>'for_sale',
	'callback' => function($post_ids,$admin_text) {
	foreach ($post_ids as $post_id) {
		update_post_meta($post_id,"_property_status", "sale");
	}
	$admin_text = "Merkattiin myytäväksi!";
	return true;
}));

$bulk_actions->init();
```