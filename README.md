wp-custom-bulk-actions
======================
Currently Wordpress doesn't allow you to add custom bulk actions. See [codex](http://codex.wordpress.org/Plugin_API/Filter_Reference/bulk_actions). Adding them is super easy with this plugin.

This is based on solution found [here](http://www.skyverge.com/blog/add-custom-bulk-action/), but makes it more easier to use.

## Plugin

This plugin adds a class named Seravo_Custom_Bulk_Action

### Class functions

Constructor with post as default post type
	
```php
new Seravo_Custom_Bulk_Action(array('post_type' => $custom_post));
```

Add actions. You must define at least menu_text and callback function.

```php
register_bulk_action(array(
'menu_text'=>$your_menu_text,
'admin_notice'=>$display_text_for_admin,
'action_name'=>$optional_action_name,
'callback'=>$anonymous_function
));
```

Your anonymous callback function needs to have post_ids as parameter:

```php
function($post_ids) {
	//Do something here
};
$post_ids //Array of post IDs selected by user in admin panel
```

Init functions to wordpress

```php
init();
```

## Example & how to use
Install plugin and define your bulk actions in `functions.php`.

In this example we're going to update metadata _property_status of custom posts called property
```php
//Define bulk actions for custom-post-type property
$bulk_actions = new Seravo_Custom_Bulk_Action(array('post_type' => 'property'));


//ACTION EXAMPLE 1:

$bulk_actions->register_bulk_action(array(
	'menu_text'=>'Mark as sold (Myyty)',
	'admin_notice'=>'Properties marked as sold',
	'callback' => function($post_ids) {

	//Do something with $post_ids here

	//In this example properties are marked as sold
	foreach ($post_ids as $post_id) {
		update_post_meta($post_id,"_property_status", "sold");
	}
	return true;
}));

//ACTION EXAMPLE 2, non-ascii chars in menutext:
//Defining the action_name is optional but useful if you want to have non-ascii chars in menu_text

$bulk_actions->register_bulk_action(array(
	'menu_text'=>'Mark for sale (Myytäväksi)',
	'admin_notice'=>'Properties marked for sale',
	'action_name'=>'for_sale',
	'callback' => function($post_ids) {

	//Do something with $post_ids here

	//In this example properties are marked for sale
	foreach ($post_ids as $post_id) {
		update_post_meta($post_id,"_property_status", "sale");
	}
	return true;
}));

//Finally init actions
$bulk_actions->init();
```