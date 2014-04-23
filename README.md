wp-custom-bulk-actions
======================
Currently Wordpress doesn't allow you to add custom bulk actions. See [codex](http://codex.wordpress.org/Plugin_API/Filter_Reference/bulk_actions).

This plugin is based on solution found [here](http://www.skyverge.com/blog/add-custom-bulk-action/), but makes it more easier to use.

## TODO
Custom admin panel texts from callbacks are not working

## Plugin

This plugin adds a class named Seravo_Custom_Bulk_Action

### Class functions

Constructor with post as default post type
	
```php
new Seravo_Custom_Bulk_Action(array('post_type' => $custom_post));
```

Add actions

```php
register_bulk_action(array('menu_text'=>$your_menu_text, 'action_name'=>$action_name, 'callback'=>$anonymous_function));
```

Your callback anonymous functions need to have two parameters:

```php
function($post_ids,$admin_text) {};
$post_ids //Array of post IDs selected in admin panel
$admin_text //Text which will show in admin panel after your action
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

$bulk_actions->register_bulk_action(array('menu_text'=>'Mark as Sold',
	'callback' => function($post_ids,$admin_text) {

	//Do something with $post_ids here

	//In this example properties are marked as sold
	foreach ($post_ids as $post_id) {
		update_post_meta($post_id,"_property_status", "sold");
	}
	$admin_text = "Marked targets as sold!";
	return true;
}));

//Defining the action_name is optional but useful if you want to have non-ascii chars in menu_text
$bulk_actions->register_bulk_action(array('menu_text'=>'Mark for Sale', 'action_name'=>'for_sale',
	'callback' => function($post_ids,$admin_text) {

	//Do something with $post_ids here

	//In this example properties are marked for sale
	foreach ($post_ids as $post_id) {
		update_post_meta($post_id,"_property_status", "sale");
	}
	$admin_text = "Marked targets for sale!";
	return true;
}));

$bulk_actions->init();
```