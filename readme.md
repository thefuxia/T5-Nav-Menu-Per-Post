This plugin adds a selector to the post editor to let you assign a custom menu to a single post or page.

To use it in your theme call

	do_action( 't5_custom_nav_menu' );
	
You may add an array of [`wp_nav_menu()`][1] options to customize the output.

[1] http://codex.wordpress.org/Function_Reference/wp_nav_menu