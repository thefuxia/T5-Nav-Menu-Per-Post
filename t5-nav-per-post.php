<?php # -*- coding: utf-8 -*-
declare( encoding = 'UTF-8' );
/**
 * Plugin Name: T5 Nav Menu Per Post
 * Text Domain: t5_nav_menu_per_post
 * Domain Path: /lang
 * Description: Select a nav menu per post or page
 * Version:     2012.02.09
 * Required:    3.3
 * Author:      Thomas Scholz
 * Author URI:  http://toscho.de
 * License:     GPL
 *
 * T5_Nav_Menu_Per_Post, Copyright (C) 2012 Thomas Scholz
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
! defined( 'ABSPATH' ) and exit;

! class_exists( 'T5_Basic_Meta_Box' )
	and require_once dirname( __FILE__ ) . '/basic_meta_box.php';

add_action( 'plugins_loaded', array ( 'T5_Nav_Menu_Per_Post', 'init' ) );

/**
 * Adds a meta box to select a custom menu per post or page.
 *
 * @author Thomas Scholz, <info@toscho.de>
 *
 */
class T5_Nav_Menu_Per_Post extends T5_Basic_Meta_Box
{
	/**
	 * Creates a new instance. Called on 'after_setup_theme'.
	 *
	 * @see    __construct()
	 * @return void
	 */
	public static function init()
	{
		NULL == self::$instance and self::$instance = new self;
		return self::$instance;
	}

	public function __construct()
	{
		// Load language files
		$path             = basename( dirname( __FILE__ ) ) . '/lang';
		$lang_loaded      = load_plugin_textdomain(
			't5_nav_menu_per_post',
			FALSE,
			$path
		);

		$this->handle     = 't5_nav_menu_per_post_meta_box';
		$this->nonce_name = 't5_nav_menu_per_post_nonce';
		$this->box_title  = __( 'Custom Nav Menu', 't5_nav_menu_per_post' );
		$this->fields     = array (
		'_t5_nav_menu_per_post' => array (
				'label' => __( 'Select a menu.', 't5_nav_menu_per_post' ),
				'method' => 'nav_menu_drop_down'
			)
		);

		// Make it flexible: Let other plugins extend or restrict the list of
		// supported post types.
		$this->post_types = apply_filters(
			't5_nav_menu_per_post_post_types',
			$post_types
		);
		parent::__construct();
	}

	/**
	 * The visible meta box markup for the post editor.
	 *
	 * @param object $post
	 * @return void
	 */
	protected function print_markup( $post )
	{
		foreach ( $this->fields as $key => $properties )
		{
			$current_value = get_post_meta( $post->ID, $key, TRUE );
			if ( 'nav_menu_drop_down' == $properties['method'] )
			{
				print "<label for='id_$key'>"
					. $properties['label'] . '</label><br />';
				$this->nav_menu_drop_down( $key, $current_value );
			}
		}
	}

	/**
	 * Build a dropdown selector for all existing nav menus.
	 *
	 * @author Thomas Scholz, toscho.de
	 * @param  string $name     Used as name attribute for the select element.
	 * @param  string $selected Slug of the selected menu
	 * @param  bool   $print    Print output or just return the HTML.
	 * @return string
	 */
	function nav_menu_drop_down( $name, $selected = '', $print = TRUE )
	{
		// array of menu objects
		$menus = wp_get_nav_menus();
		$out   = '';

		// No menu found.
		if ( empty ( $menus ) or is_a( $menus, 'WP_Error' )  )
		{
			// Give some feedback …
			$out .= __( 'There are no menus.', 't5_nav_menu_per_post' );

			// … and make it usable …
			if ( current_user_can( 'edit_theme_options' ) )
			{
				$out .= sprintf(
					__( ' <a href="%s">Create one</a>.', 't5_nav_menu_per_post' ),
					admin_url( 'nav-menus.php' )
				);
			}
			// … and stop.
			$print and print $out;
			return $out;
		}

		// Set name and ID to let you use a <label for='id_$name'>
		$out = "<select name='$name' id='id_$name'>\n";
		$out .= '<option value="">' . __( 'Pick one', 't5_nav_menu_per_post' )
			. '</option>';

		foreach ( $menus as $menu )
		{
			// Preselect the active menu
			$active = $selected == $menu->slug ? 'selected' : '';
			// Show the description
			$title  = empty ( $menu->description ) ? '' : esc_attr( $menu->description );

			$out .= "\t<option value='$menu->slug' $active $title>$menu->name</option>\n";
		}

		$out .= '</select>';

		$print and print $out;
		return $out;
	}

	/**
	 * More actions. May be overridden in a child class.
	 *
	 * @return void
	 */
	protected function extra_actions()
	{
		add_action( 't5_custom_nav_menu', array ( $this, 'theme_output' ), 10, 1 );
	}

	/**
	 * Wrapper for wp_nav_menu().
	 *
	 * Usage:
	 * do_action( 't5_custom_nav_menu', array ( 'menu' => 'default-menu' ) );
	 *
	 * @param  array $options See wp_nav_menu() for details.
	 * 		'post_id' will override the current posts ID (if available)
	 * 		'menu' will set a fallback menu if there is no dedicated custom menu.
	 * @return void
	 */
	public function theme_output( $options = array() )
	{
		$defaults = array (
			'post_id' => isset ( $GLOBALS['post'] ) ? $GLOBALS['post']->ID : FALSE,
			// Fallback if no custom menu is found.
			'menu'    => 'top-menu'
		);
		$args   = array_merge( $defaults, (array) $options );
		$custom = get_post_meta( $args['post_id'], '_t5_nav_menu_per_post', TRUE );
		$custom and $args['menu'] = $custom;
		wp_nav_menu( $args );
	}

	/**
	 * We let this empty at the moment. See the parent class for details.
	 *
	 * @param  string $post_type
	 * @return void
	 */
	protected function add_help( $post_type ) {}
}