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
 * Replaces the scbAdminPage/scbForms screen used before 3.0.0. The plugin's only
 * admin surface is its settings, so it takes a single page under Settings rather
 * than a top-level menu, and every field is registered rather than hand-written
 * into a form table.
 */
class WP_PageNavi_Settings {

	/**
	 * Settings group passed to register_setting() and settings_fields().
	 *
	 * @var string
	 */
	const GROUP = 'wp_pagenavi_options';

	/**
	 * The settings page slug.
	 *
	 * @var string
	 */
	const PAGE = 'wp-pagenavi';

	/**
	 * The capability required to see and save the settings.
	 *
	 * @var string
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * The section holding the wording of each part of the navigation.
	 *
	 * @var string
	 */
	const SECTION_TEXT = 'wp_pagenavi_text';

	/**
	 * The section holding how the navigation is presented.
	 *
	 * @var string
	 */
	const SECTION_DISPLAY = 'wp_pagenavi_display';

	/**
	 * Hook the admin screen into WordPress.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );

		// Activation hooks do not fire when a plugin is updated, so the upgrade
		// routine is also run on every admin load.
		add_action( 'admin_init', array( 'WP_PageNavi_Options', 'maybe_upgrade' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( WP_PAGENAVI_MAIN_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * The capability required for a given context.
	 *
	 * @param string $context What the capability is being checked for.
	 * @return string
	 */
	public static function capability( $context = 'settings' ) {
		/**
		 * Filters the capability required to manage WP-PageNavi.
		 *
		 * @since 3.0.0
		 *
		 * @param string $capability The required capability.
		 * @param string $context    What the capability is being checked for.
		 */
		return (string) apply_filters( 'wp_pagenavi_capability', self::CAPABILITY, $context );
	}

	/**
	 * Add the settings page under the Settings menu.
	 *
	 * @return void
	 */
	public static function add_page() {
		add_options_page(
			__( 'PageNavi Settings', 'wp-pagenavi' ),
			__( 'WP-PageNavi', 'wp-pagenavi' ),
			self::capability(),
			self::PAGE,
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
			'<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::PAGE ) ) . '">' . esc_html__( 'Settings', 'wp-pagenavi' ) . '</a>'
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
			self::GROUP,
			WP_PageNavi_Options::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'WP_PageNavi_Options', 'sanitize' ),
			)
		);

		add_settings_section(
			self::SECTION_TEXT,
			__( 'Page Navigation Text', 'wp-pagenavi' ),
			array( __CLASS__, 'section_text' ),
			self::PAGE
		);

		add_settings_section(
			self::SECTION_DISPLAY,
			__( 'Page Navigation Options', 'wp-pagenavi' ),
			'__return_false',
			self::PAGE
		);

		foreach ( self::fields() as $name => $field ) {
			add_settings_field(
				$name,
				$field['title'],
				array( __CLASS__, 'field_' . $name ),
				self::PAGE,
				$field['section'],
				array( 'label_for' => self::id( $name ) )
			);
		}
	}

	/**
	 * The field definitions: a title and a section for each registered field.
	 *
	 * The order is the one the screen has shown since the scb version, and the
	 * %TOKEN% hints live in the field callbacks rather than in these titles, so
	 * they can never end up inside a translatable string where a formatting pass
	 * would rewrite them into numbered printf placeholders.
	 *
	 * @return array
	 */
	public static function fields() {
		return array(
			'pages_text'                   => array(
				'title'   => __( 'Text For Number Of Pages', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'current_text'                 => array(
				'title'   => __( 'Text For Current Page', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'page_text'                    => array(
				'title'   => __( 'Text For Page', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'first_text'                   => array(
				'title'   => __( 'Text For First Page', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'last_text'                    => array(
				'title'   => __( 'Text For Last Page', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'prev_text'                    => array(
				'title'   => __( 'Text For Previous Page', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'next_text'                    => array(
				'title'   => __( 'Text For Next Page', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'dotleft_text'                 => array(
				'title'   => __( 'Text For Previous ...', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'dotright_text'                => array(
				'title'   => __( 'Text For Next ...', 'wp-pagenavi' ),
				'section' => self::SECTION_TEXT,
			),
			'use_pagenavi_css'             => array(
				'title'   => __( 'Use wp-pagenavi.css', 'wp-pagenavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'style'                        => array(
				'title'   => __( 'Page Navigation Style', 'wp-pagenavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'always_show'                  => array(
				'title'   => __( 'Always Show Page Navigation', 'wp-pagenavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'num_pages'                    => array(
				'title'   => __( 'Number Of Pages To Show', 'wp-pagenavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'num_larger_page_numbers'      => array(
				'title'   => __( 'Number Of Larger Page Numbers To Show', 'wp-pagenavi' ),
				'section' => self::SECTION_DISPLAY,
			),
			'larger_page_numbers_multiple' => array(
				'title'   => __( 'Show Larger Page Numbers In Multiples Of', 'wp-pagenavi' ),
				'section' => self::SECTION_DISPLAY,
			),
		);
	}

	/**
	 * Intro copy for the text section.
	 *
	 * @return void
	 */
	public static function section_text() {
		echo '<p>' . esc_html__( 'Leaving a field blank will hide that part of the navigation.', 'wp-pagenavi' ) . '</p>';
	}

	/**
	 * The wording of the "Page N of M" text.
	 *
	 * @return void
	 */
	public static function field_pages_text() {
		self::text( 'pages_text' );
		self::tokens(
			array(
				'%CURRENT_PAGE%' => __( 'The current page number.', 'wp-pagenavi' ),
				'%TOTAL_PAGES%'  => __( 'The total number of pages.', 'wp-pagenavi' ),
			)
		);
	}

	/**
	 * The wording of the current page.
	 *
	 * @return void
	 */
	public static function field_current_text() {
		self::text( 'current_text' );
		self::tokens( array( '%PAGE_NUMBER%' => __( 'The page number.', 'wp-pagenavi' ) ) );
	}

	/**
	 * The wording of a numbered page link.
	 *
	 * @return void
	 */
	public static function field_page_text() {
		self::text( 'page_text' );
		self::tokens( array( '%PAGE_NUMBER%' => __( 'The page number.', 'wp-pagenavi' ) ) );
	}

	/**
	 * The wording of the link to the first page.
	 *
	 * @return void
	 */
	public static function field_first_text() {
		self::text( 'first_text' );
		self::tokens( array( '%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-pagenavi' ) ) );
	}

	/**
	 * The wording of the link to the last page.
	 *
	 * @return void
	 */
	public static function field_last_text() {
		self::text( 'last_text' );
		self::tokens( array( '%TOTAL_PAGES%' => __( 'The total number of pages.', 'wp-pagenavi' ) ) );
	}

	/**
	 * The wording of the link to the previous page.
	 *
	 * @return void
	 */
	public static function field_prev_text() {
		self::text( 'prev_text' );
	}

	/**
	 * The wording of the link to the next page.
	 *
	 * @return void
	 */
	public static function field_next_text() {
		self::text( 'next_text' );
	}

	/**
	 * The wording of the ellipsis before the page numbers.
	 *
	 * @return void
	 */
	public static function field_dotleft_text() {
		self::text( 'dotleft_text' );
	}

	/**
	 * The wording of the ellipsis after the page numbers.
	 *
	 * @return void
	 */
	public static function field_dotright_text() {
		self::text( 'dotright_text' );
	}

	/**
	 * Whether the bundled stylesheet is enqueued.
	 *
	 * @return void
	 */
	public static function field_use_pagenavi_css() {
		self::radio( 'use_pagenavi_css', self::yes_no() );
		self::description( __( 'A copy of wp-pagenavi.css in your theme directory is used in preference to the plugin\'s own.', 'wp-pagenavi' ) );
	}

	/**
	 * Numbered links or a drop-down list.
	 *
	 * @return void
	 */
	public static function field_style() {
		self::select(
			'style',
			array(
				1 => __( 'Normal', 'wp-pagenavi' ),
				2 => __( 'Drop-down List', 'wp-pagenavi' ),
			)
		);
	}

	/**
	 * Whether a single page still gets navigation.
	 *
	 * @return void
	 */
	public static function field_always_show() {
		self::radio( 'always_show', self::yes_no() );
		self::description( __( 'Show navigation even if there\'s only one page.', 'wp-pagenavi' ) );
	}

	/**
	 * How many numbered links the window holds.
	 *
	 * @return void
	 */
	public static function field_num_pages() {
		self::number( 'num_pages' );
	}

	/**
	 * How many larger page numbers are shown.
	 *
	 * @return void
	 */
	public static function field_num_larger_page_numbers() {
		self::number( 'num_larger_page_numbers' );
		self::description( __( 'Larger page numbers are in addition to the normal page numbers. They are useful when there are many pages of posts.', 'wp-pagenavi' ) );
		self::description( __( 'For example, WP-PageNavi will display: Pages 1, 2, 3, 4, 5, 10, 20, 30, 40, 50.', 'wp-pagenavi' ) );
		self::description( __( 'Enter 0 to disable.', 'wp-pagenavi' ) );
	}

	/**
	 * The step the larger page numbers count in.
	 *
	 * @return void
	 */
	public static function field_larger_page_numbers_multiple() {
		self::number( 'larger_page_numbers_multiple' );
		self::description( __( 'For example, if multiple is 5, it will show: 5, 10, 15, 20, 25', 'wp-pagenavi' ) );
	}

	/**
	 * The two choices every toggle on this screen offers.
	 *
	 * They are a radio pair rather than a checkbox on purpose: a checkbox posts
	 * nothing when it is off, and the sanitiser reads only what was posted.
	 *
	 * @return array
	 */
	protected static function yes_no() {
		return array(
			1 => __( 'Yes', 'wp-pagenavi' ),
			0 => __( 'No', 'wp-pagenavi' ),
		);
	}

	/**
	 * The id attribute for a field, matching the label_for it was registered with.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected static function id( $name ) {
		return self::PAGE . '-' . $name;
	}

	/**
	 * The name attribute for a field, which posts into the settings array.
	 *
	 * @param string $name Option key.
	 * @return string
	 */
	protected static function name( $name ) {
		return WP_PageNavi_Options::OPTION . '[' . $name . ']';
	}

	/**
	 * Print a field's description.
	 *
	 * @param string $text Description text.
	 * @return void
	 */
	protected static function description( $text ) {
		printf( '<p class="description">%s</p>', esc_html( $text ) );
	}

	/**
	 * Print the list of %TOKEN% placeholders a text field understands.
	 *
	 * @param array $tokens Description keyed by token.
	 * @return void
	 */
	protected static function tokens( array $tokens ) {
		foreach ( $tokens as $token => $description ) {
			printf(
				'<p class="description"><code>%1$s</code> &mdash; %2$s</p>',
				esc_html( $token ),
				esc_html( $description )
			);
		}
	}

	/**
	 * Print a single-line text box.
	 *
	 * @param string $name Option key.
	 * @return void
	 */
	protected static function text( $name ) {
		printf(
			'<input type="text" id="%1$s" name="%2$s" value="%3$s" class="regular-text" />',
			esc_attr( self::id( $name ) ),
			esc_attr( self::name( $name ) ),
			esc_attr( (string) WP_PageNavi_Options::get( $name ) )
		);
	}

	/**
	 * Print a whole-number box that cannot go negative.
	 *
	 * @param string $name Option key.
	 * @return void
	 */
	protected static function number( $name ) {
		printf(
			'<input type="number" min="0" step="1" id="%1$s" name="%2$s" value="%3$s" class="small-text" />',
			esc_attr( self::id( $name ) ),
			esc_attr( self::name( $name ) ),
			esc_attr( (string) WP_PageNavi_Options::get( $name ) )
		);
	}

	/**
	 * Print a set of radio buttons.
	 *
	 * The first one carries the id the field was registered with, so the label
	 * do_settings_fields() prints has something to point at.
	 *
	 * @param string $name    Option key.
	 * @param array  $choices Label keyed by stored value.
	 * @return void
	 */
	protected static function radio( $name, array $choices ) {
		$value = (int) WP_PageNavi_Options::get( $name );
		$first = true;

		echo '<fieldset>';

		foreach ( $choices as $choice => $label ) {
			printf(
				'<label><input type="radio" id="%1$s" name="%2$s" value="%3$s"%4$s /> %5$s</label> ',
				esc_attr( $first ? self::id( $name ) : self::id( $name ) . '-' . $choice ),
				esc_attr( self::name( $name ) ),
				esc_attr( $choice ),
				checked( $value, (int) $choice, false ),
				esc_html( $label )
			);

			$first = false;
		}

		echo '</fieldset>';
	}

	/**
	 * Print a select box.
	 *
	 * @param string $name    Option key.
	 * @param array  $choices Label keyed by stored value.
	 * @return void
	 */
	protected static function select( $name, array $choices ) {
		$value = (int) WP_PageNavi_Options::get( $name );

		printf(
			'<select id="%1$s" name="%2$s">',
			esc_attr( self::id( $name ) ),
			esc_attr( self::name( $name ) )
		);

		foreach ( $choices as $choice => $label ) {
			printf(
				'<option value="%1$s"%2$s>%3$s</option>',
				esc_attr( $choice ),
				selected( $value, (int) $choice, false ),
				esc_html( $label )
			);
		}

		echo '</select>';
	}

	/**
	 * Render the settings page.
	 *
	 * @return void
	 */
	public static function render_page() {
		if ( ! current_user_can( self::capability() ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-pagenavi' ) );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'PageNavi Settings', 'wp-pagenavi' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( self::GROUP );
				do_settings_sections( self::PAGE );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}
}
