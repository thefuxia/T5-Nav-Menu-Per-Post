<?php # -*- coding: utf-8 -*-
declare( encoding = 'UTF-8' );
// Called directly or at least not in WordPress context.
! defined( 'ABSPATH' ) and exit;

#add_action( 'plugins_loaded', array ( 'T5_Basic_Meta_Box', 'init' ) );

/**
 * Create a simple meta box. Demo plugin.
 *
 * @author Thomas Scholz, http://toscho.de
 */
class T5_Basic_Meta_Box
{
	/**
	 * Global accessible instance (per init()). A singleton is not enforced tough.
	 *
	 * @type object
	 */
	protected static $instance = NULL;

	/**
	 * Internal identifier for the meta box. Must be unique in WordPress.
	 *
	 * @type string
	 */
	protected $handle = 't5_basic_meta_box';

	/**
	 * Box Title.
	 * In a real application make sure the title is translatable.
	 *
	 * You may use markup here, an icon for example.
	 *
	 * @type string
	 */
	protected $box_title = 'Basic Meta Box';

	/**
	 * May be 'normal' or 'side'
	 *
	 * @type string
	 */
	protected $priority = 'side';

	/**
	 * Where to show the meta box. Any post type or link.
	 *
	 * @type array
	 */
	protected $post_types = array ( 'post', 'page' );

	/**
	 * nonce = number used once, unique identifier for request validation.
	 *
	 * @type string
	 */
	protected $nonce_name = 't5_basic_meta_box_nonce';

	/**
	 * Post meta fields handled by this class. Must be unique in WordPress.
	 *
	 * Never combine multiple fields in an array! They are stored as serialized
	 * data then, and it will be almost impossible to sort a query per API.
	 *
	 * The leading underscore prevents those fields from showing up in the
	 * generic 'custom fields' box.
	 *
	 * The key is the fields name. You may extend the values to get more
	 * flexibility.
	 *
	 * In your application make the labels translatable.
	 *
	 * @type array
	 */
	protected $fields = array (
		'_t5_basic_meta_box_title' => array (
				'label' => 'Title'
			,	'type'  => 'text'
			)
	,	'_t5_basic_meta_box_text'  => array (
				'label' => 'Text'
			,	'type'  => 'wp_editor'
			)
	,	'_t5_basic_meta_box_checkbox'  => array (
				'label' => 'This is boring'
			,	'type'  => 'checkbox'
			)
	);


	/**
	 * Creates a new instance. Called on 'plugins_loaded'.
	 *
	 * @see    __construct()
	 * @return void
	 */
	public static function init()
	{
		NULL == self::$instance and self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Called by 'init()'. Registers the action handlers.
	 *
	 * @see    save()
	 * @see    register_meta_box()
	 * @see    front_box()
	 * @return void
	 */
	public function __construct()
	{
		add_action( 'save_post',      array ( $this, 'save' ) );
		add_action( 'add_meta_boxes', array ( $this, 'register_meta_box' ) );
		$this->extra_actions();
	}

	/**
	 * More actions. May be overridden in a child class.
	 *
	 * @return void
	 */
	protected function extra_actions()
	{
		add_action( 'basic_meta_box', array ( $this, 'front_box' ), 10, 1 );
	}

	/**
	 * Handler to get the content of the meta box.
	 *
	 * Usage:
	 * do_action( 'basic_meta_box' ); or
	 * do_action( 'basic_meta_box', array ( 'post_id' => 15 ) );
	 *
	 * You could also use:
	 * T5_Basic_Meta_Box::init()->front_box();
	 *
	 * But do_action() is better: It doesn’t require a theme update after
	 * disabling them meta box script.
	 *
	 * @param  array   $options See $defaults for possible options.
	 * @return string
	 */
	public function front_box( $options = array () )
	{
		global $post;
		$defaults = array (
			'post_id'  => isset ( $post->ID ) ? $post->ID : FALSE
		,	'template' => '<div class="t5_basic_meta_box"><h2>%1$s</h2>%2$s</div>'
		,	'print'    => TRUE
		);
		$options = array_merge( $defaults, $options );
		extract( $options );

		// We are not on a single page, and no post id was set. Nothing to do.
		if ( FALSE == $post_id )
		{
			return;
		}

		// Prepare the variables.
		$title  = get_post_meta( $post_id, '_t5_basic_meta_box_title', TRUE );
		$text   = get_post_meta( $post_id, '_t5_basic_meta_box_text', TRUE );
		$text   = wpautop( $text );
		$output = sprintf( $template, $title, $text );

		$print and print $output;
		return $output;
	}

	/**
	 * Called on 'add_meta_boxes'.
	 *
	 * @see    __construct()()
	 * @see    show()
	 * @return void
	 */
	public function register_meta_box()
	{
		foreach ( $this->post_types as $post_type )
		{
			add_meta_box(
				$this->handle
			,	$this->box_title
			,	array ( $this, 'show' )
			,	$post_type
			,	$this->priority
			);

			$this->add_help( $post_type );
		}
	}

	/**
	 * Set help tab content.
	 *
	 * @param  string $post_type
	 * @return void
	 */
	protected function add_help( $post_type )
	{
		if ( get_current_screen()->post_type == $post_type )
		{
			get_current_screen()->add_help_tab(
				array(
					'id'      => $this->handle
				,	'title'   => strip_tags( $this->box_title )
				,	'content' => '<p>Detailed instructions for your meta box.</p>',
				)
			);
		}
	}

	/**
	 * Print the meta box in the editor page.
	 *
	 * @return void
	 */
	public function show( $post )
	{
		// Our secret key for validation.
		$nonce = wp_create_nonce( __FILE__ );
		echo "<input type='hidden' name='$this->nonce_name' value='$nonce' />";
		$this->print_markup( $post );
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
			$content = get_post_meta( $post->ID, $key, TRUE );
			$label = "<label for='$key'>" . $properties['label'] . "</label>";

			// You may extend the following to handle more types.
			if ( 'text' == $properties['type'] )
			{
				$content = htmlspecialchars( $content, ENT_QUOTES, 'utf-8', FALSE );
				print "<p>$label<input style='padding:2px 0' name='$key' id='$key' value='$content' class='large-text' /></p>";
			}
			elseif ( 'wp_editor' == $properties['type'] )
			{
				print $label;
				$editor_settings =  array (
					'textarea_rows' => 8
				,	'media_buttons' => FALSE
				,	'teeny'         => TRUE
				,	'tinymce'       => FALSE
					// a very minimal setup
				,	'quicktags'     => array ( 'buttons' => 'strong,em,link' )
				);
				wp_editor( $content, $key, $editor_settings );
			}
			elseif ( 'checkbox' == $properties['type'] )
			{
				$checked = checked( $content, 'on', FALSE );
				print "<p><input type='checkbox' name='$key' id='$key' $checked /> $label</p>";
			}
			else
			{
				// Again, make it translatable.
				print "Unrecognized type for $key.";
			}
			print '</p>';
		}
	}

	/**
	 * Save the POSTed values on 'save_post'.
	 *
	 * @param  int  $post_id
	 * @return void
	 */
	public function save( $post_id )
	{
		if ( ! $this->save_allowed( $post_id ) )
		{
			return;
		}

		foreach ( $this->fields as $key => $properties )
		{
			if ( isset ( $_POST[ $key ] ) )
			{
				update_post_meta( $post_id, $key, $_POST[ $key ] );
			}
			else
			{
				delete_post_meta( $post_id, $key );
			}
		}
	}

	/**
	 * Check permission to save the POSTed data.
	 *
	 * @param  int $post_id
	 * @return bool
	 */
	protected function save_allowed( $post_id )
	{
		// AJAX autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		{
			return FALSE;
		}
		// Some other POST request
		if ( ! isset ( $_POST['post_type'] ) )
		{
			return FALSE;
		}
		// Wrong post type.
		if ( ! in_array( $_POST['post_type'], $this->post_types ) )
		{
			return FALSE;
		}
		// Missing capability
		if ( ! current_user_can( 'edit_' . $_POST['post_type'], $post_id ) )
		{
			return FALSE;
		}
		// Wrong or missing nonce
		return wp_verify_nonce( $_POST[ $this->nonce_name ], __FILE__ );
	}
}