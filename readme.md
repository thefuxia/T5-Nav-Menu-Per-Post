This plugin adds a selector to the post editor to let you assign a custom menu to a single post or page.

To use it in your theme call

	do_action( 't5_custom_nav_menu' );
	
You may add an array of [`wp_nav_menu()`][1] options to customize the output.

This plugin was created as an answer for a [question on WordPress.StackExchange.com][2]

[1]: http://codex.wordpress.org/Function_Reference/wp_nav_menu "see Codex description".
[2]: http://wordpress.stackexchange.com/questions/41695/adding-a-nav-menu-to-post-admin