<?php
/**
 * The settings screen.
 *
 * @package WP-PageNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds Settings -> PageNavi with the WordPress Settings API.
 *
 * Replaces the scbAdminPage/scbForms screen used before 3.0.0. The field names,
 * the option key and the page slug are unchanged, so bookmarks and saved values
 * both survive the upgrade.
 */
class PageNavi_Admin {

	/**
	 * Settings group passed to register_setting() and settings_fields().
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'pagenavi_options_group';

	/**
	 * The page slug, unchanged since the scb version.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'pagenavi';

	/**
	 * Hook the admin screen into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WP_PAGENAVI_MAIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Add the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public static function add_page() {
		add_options_page(
			__( 'PageNavi Settings', 'wp-pagenavi' ),
			__( 'PageNavi', 'wp-pagenavi' ),
			'manage_options',
			self::PAGE_SLUG,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * Add a Settings link to the plugin's row on the Plugins screen.
	 *
	 * @param array $links Existing action links.
	 * @return array
	 */
	public static function action_links( $links ) {
		if ( ! is_array( $links ) ) {
			$links = array();
		}

		array_unshift(
			$links,
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE_SLUG ) ) . '">' . esc_html__( 'Settings', 'wp-pagenavi' ) . '</a>'
		);

		return $links;
	}

	/**
	 * Register the setting, its sections and its fields.
	 *
	 * @return void
	 */
	public static function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			PageNavi_Options::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
			)
		);

		add_settings_section(
			'pagenavi_text',
			__( 'Page Navigation Text', 'wp-pagenavi' ),
			array( __CLASS__, 'text_section_intro' ),
			self::PAGE_SLUG
		);

		add_settings_section(
			'pagenavi_options',
			__( 'Page Navigation Options', 'wp-pagenavi' ),
			'__return_false',
			self::PAGE_SLUG
		);

		foreach ( self::text_fields() as $name => $field ) {
			add_settings_field(
				$name,
				$field['title'],
				array( __CLASS__, 'render_text_field' ),
				self::PAGE_SLUG,
				'pagenavi_text',
				array(
					'label_for' => 'pagenavi-' . $name,
					'name'      => $name,
					'class_'    => isset( $field['class'] ) ? $field['class'] : '',
					'tokens'    => isset( $field['tokens'] ) ? $field['tokens'] : array(),
				)
			);
		}

		foreach ( self::option_fields() as $name => $field ) {
			add_settings_field(
				$name,
				$field['title'],
				array( __CLASS__, 'render_' . $field['type'] . '_field' ),
				self::PAGE_SLUG,
				'pagenavi_options',
				array(
					'label_for' => 'pagenavi-' . $name,
					'name'      => $name,
					'choices'   => isset( $field['choices'] ) ? $field['choices'] : array(),
					'notes'     => isset( $field['notes'] ) ? $field['notes'] : array(),
					'class_'    => isset( $field['class'] ) ? $field['class'] : '',
				)
			);
		}
	}

	/**
	 * The nine text fields, in the order the screen has always shown them.
	 *
	 * The %TOKEN% placeholders are listed separately from the translated label so
	 * they never end up inside a translatable string, where a formatting pass
	 * would rewrite them into numbered printf placeholders.
	 *
	 * @return array
	 */
	protected static function text_fields() {
		return array(
			'pages_text'    => array(
				'title'  => __( 'Text For Number Of Pages', 'wp-pagenavi' ),
				'class'  => 'regular-text',
				'tokens' => array(
					'%CURRENT_PAGE%' => __( 'The current page number.', 'wp-pagenavi' ),
					'%TOTAL_PAGES%'  => __( 'The total number of pages.', 'wp-pagenavi' ),
				),
			),
			'current_text'  => array(
				'title'  => __( 'Text For Current Page', 'wp-pagenavi' ),
				'tokens' => array(
					'%PAGE_NUMBER%' => __( 'The page number.', 'wp-pagenavi' ),
				),
			),
			'page_text'     => array(
				'title'  => __( 'Text For Page', 'wp-pagenavi' ),
				'tokens' => array(
					'%PAGE_NUMBER%' => __( 'The page number.', 'wp-pagenavi' ),
				),
			),
			'first_text'    => array(
				'title'  => __( 'Text For First Page', 'wp-pagenavi' ),
				'tokens' => array(
					'%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-pagenavi' ),
				),
			),
			'last_text'     => array(
				'title'  => __( 'Text For Last Page', 'wp-pagenavi' ),
				'tokens' => array(
					'%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-pagenavi' ),
				),
			),
			'prev_text'     => array(
				'title' => __( 'Text For Previous Page', 'wp-pagenavi' ),
			),
			'next_text'     => array(
				'title' => __( 'Text For Next Page', 'wp-pagenavi' ),
			),
			'dotleft_text'  => array(
				'title' => __( 'Text For Previous ...', 'wp-pagenavi' ),
			),
			'dotright_text' => array(
				'title' => __( 'Text For Next ...', 'wp-pagenavi' ),
			),
		);
	}

	/**
	 * The non-text fields, in the order the screen has always shown them.
	 *
	 * @return array
	 */
	protected static function option_fields() {
		$yes_no = array(
			1 => __( 'Yes', 'wp-pagenavi' ),
			0 => __( 'No', 'wp-pagenavi' ),
		);

		return array(
			'use_pagenavi_css'             => array(
				'title'   => __( 'Use pagenavi-css.css', 'wp-pagenavi' ),
				'type'    => 'radio',
				'choices' => $yes_no,
			),
			'style'                        => array(
				'title'   => __( 'Page Navigation Style', 'wp-pagenavi' ),
				'type'    => 'select',
				'choices' => array(
					1 => __( 'Normal', 'wp-pagenavi' ),
					2 => __( 'Drop-down List', 'wp-pagenavi' ),
				),
			),
			'always_show'                  => array(
				'title'   => __( 'Always Show Page Navigation', 'wp-pagenavi' ),
				'type'    => 'radio',
				'choices' => $yes_no,
				'notes'   => array( __( 'Show navigation even if there\'s only one page.', 'wp-pagenavi' ) ),
			),
			'num_pages'                    => array(
				'title' => __( 'Number Of Pages To Show', 'wp-pagenavi' ),
				'type'  => 'number',
				'class' => 'small-text',
			),
			'num_larger_page_numbers'      => array(
				'title' => __( 'Number Of Larger Page Numbers To Show', 'wp-pagenavi' ),
				'type'  => 'number',
				'class' => 'small-text',
				'notes' => array(
					__( 'Larger page numbers are in addition to the normal page numbers. They are useful when there are many pages of posts.', 'wp-pagenavi' ),
					__( 'For example, WP-PageNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50.', 'wp-pagenavi' ),
					__( 'Enter 0 to disable.', 'wp-pagenavi' ),
				),
			),
			'larger_page_numbers_multiple' => array(
				'title' => __( 'Show Larger Page Numbers In Multiples Of', 'wp-pagenavi' ),
				'type'  => 'number',
				'class' => 'small-text',
				'notes' => array( __( 'For example, if multiple is 5, it will show: 5, 10, 15, 20, 25', 'wp-pagenavi' ) ),
			),
		);
	}

	/**
	 * Intro copy for the text section.
	 *
	 * @return void
	 */
	public static function text_section_intro() {
		echo '<p>' . esc_html__( 'Leaving a field blank will hide that part of the navigation.', 'wp-pagenavi' ) . '</p>';
	}

	/**
	 * Build the name attribute for a field.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected static function field_name( $name ) {
		return PageNavi_Options::OPTION_NAME . '[' . $name . ']';
	}

	/**
	 * Print the notes shown beneath a field.
	 *
	 * @param array $notes Lines of help text.
	 * @return void
	 */
	protected static function render_notes( $notes ) {
		foreach ( $notes as $note ) {
			echo '<br />' . esc_html( $note );
		}
	}

	/**
	 * Render a text field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_text_field( $args ) {
		$value = PageNavi_Options::get( $args['name'] );
		$class = $args['class_'] ? $args['class_'] : 'regular-text';

		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="%4$s" />',
			esc_attr( $args['label_for'] ),
			esc_attr( self::field_name( $args['name'] ) ),
			esc_attr( $value ),
			esc_attr( $class )
		);

		foreach ( $args['tokens'] as $token => $description ) {
			echo '<br /><code>' . esc_html( $token ) . '</code> &mdash; ' . esc_html( $description );
		}
	}

	/**
	 * Render a number field.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_number_field( $args ) {
		$value = PageNavi_Options::get( $args['name'] );

		printf(
			'<input type="number" min="0" step="1" id="%1$s" name="%2$s" value="%3$s" class="%4$s" />',
			esc_attr( $args['label_for'] ),
			esc_attr( self::field_name( $args['name'] ) ),
			esc_attr( $value ),
			esc_attr( $args['class_'] ? $args['class_'] : 'small-text' )
		);

		self::render_notes( $args['notes'] );
	}

	/**
	 * Render a pair of radio buttons.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_radio_field( $args ) {
		$value = (int) PageNavi_Options::get( $args['name'] );

		echo '<fieldset>';
		foreach ( $args['choices'] as $choice => $label ) {
			printf(
				'<label><input type="radio" name="%1$s" value="%2$s"%3$s /> %4$s</label> ',
				esc_attr( self::field_name( $args['name'] ) ),
				esc_attr( $choice ),
				checked( $value, (int) $choice, false ),
				esc_html( $label )
			);
		}
		echo '</fieldset>';

		self::render_notes( $args['notes'] );
	}

	/**
	 * Render a select box.
	 *
	 * @param array $args Field arguments.
	 * @return void
	 */
	public static function render_select_field( $args ) {
		$value = (int) PageNavi_Options::get( $args['name'] );

		printf(
			'<select id="%1$s" name="%2$s">',
			esc_attr( $args['label_for'] ),
			esc_attr( self::field_name( $args['name'] ) )
		);
		foreach ( $args['choices'] as $choice => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $choice ),
				selected( $value, (int) $choice, false ),
				esc_html( $label )
			);
		}
		echo '</select>';

		self::render_notes( $args['notes'] );
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-pagenavi' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PageNavi Settings', 'wp-pagenavi' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::OPTION_GROUP );
				do_settings_sections( self::PAGE_SLUG );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Validate and clean the submitted settings.
	 *
	 * Values missing from the submission fall back to what is already stored, so
	 * a partial POST never wipes a setting.
	 *
	 * @param mixed $input Raw submitted values.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$options = wp_parse_args( is_array( $input ) ? $input : array(), PageNavi_Options::get() );

		foreach ( PageNavi_Options::int_keys() as $key ) {
			$options[ $key ] = absint( isset( $options[ $key ] ) ? $options[ $key ] : 0 );
		}

		foreach ( PageNavi_Options::bool_keys() as $key ) {
			$options[ $key ] = intval( isset( $options[ $key ] ) ? $options[ $key ] : 0 );
		}

		// The same allow-list the renderer uses, so an SVG arrow typed into the
		// settings screen survives exactly as one passed through the 'options'
		// argument does.
		foreach ( PageNavi_Options::text_keys() as $key ) {
			$options[ $key ] = PageNavi_Options::kses( isset( $options[ $key ] ) ? $options[ $key ] : '' );
		}

		return $options;
	}
}
