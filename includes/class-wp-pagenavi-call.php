<?php
/**
 * A single invocation of the template tag.
 *
 * @package WP-PageNavi
 */

defined( 'ABSPATH' ) || exit;

/**
 * Resolves pagination figures and builds the individual page links for one call
 * to wp_pagenavi().
 */
class WP_PageNavi_Call {

	/**
	 * The parsed arguments for this call.
	 *
	 * @var array
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array $args Parsed wp_pagenavi() arguments.
	 */
	public function __construct( $args ) {
		$this->args = $args;
	}

	/**
	 * Read an argument as a property.
	 *
	 * @param string $key Argument name.
	 * @return mixed Null when the argument was not supplied.
	 */
	public function __get( $key ) {
		return isset( $this->args[ $key ] ) ? $this->args[ $key ] : null;
	}

	/**
	 * Work out how many pages there are and which one is current.
	 *
	 * The figures are cast to int before being returned: the user-query branch
	 * derives them with floor()/ceil(), which yield floats, and a float page
	 * number silently breaks every strict comparison downstream.
	 *
	 * @return array List of posts per page, current page, total pages.
	 */
	public function get_pagination_args() {
		global $numpages;

		$query = $this->query;

		switch ( $this->type ) {
			case 'multipart':
				// A post split with <!--nextpage-->.
				$posts_per_page = 1;
				$paged          = max( 1, absint( get_query_var( 'page' ) ) );
				$total_pages    = max( 1, $numpages );
				break;
			case 'users':
				// WP_User_Query.
				$posts_per_page = $query->query_vars['number'];
				$paged          = max( 1, floor( $query->query_vars['offset'] / $posts_per_page ) + 1 );
				$total_pages    = max( 1, ceil( $query->total_users / $posts_per_page ) );
				break;
			default:
				// WP_Query.
				$posts_per_page = intval( $query->get( 'posts_per_page' ) );
				$paged          = max( 1, absint( $query->get( 'paged' ) ) );
				$total_pages    = max( 1, absint( $query->max_num_pages ) );
				break;
		}

		return array( (int) $posts_per_page, (int) $paged, (int) $total_pages );
	}

	/**
	 * Build one page link.
	 *
	 * @param int    $page     Page number to link to.
	 * @param string $raw_text Link text, possibly containing a token.
	 * @param array  $attr     Attributes for the anchor.
	 * @param string $format   The token in $raw_text to replace with the page number.
	 * @return string The anchor markup, or an empty string when there is no text.
	 */
	public function get_single( $page, $raw_text, $attr, $format = '%PAGE_NUMBER%' ) {
		if ( empty( $raw_text ) ) {
			return '';
		}

		$text = str_replace( $format, number_format_i18n( $page ), $raw_text );

		$attr['href'] = $this->get_url( $page );

		return self::anchor( $attr, $text );
	}

	/**
	 * Get the URL for a page number.
	 *
	 * @param int $page Page number.
	 * @return string
	 */
	public function get_url( $page ) {
		return ( 'multipart' === $this->type ) ? get_multipage_link( $page ) : get_pagenum_link( $page );
	}

	/**
	 * Assemble an anchor tag.
	 *
	 * Attributes are emitted in the order given. A false value omits the attribute
	 * entirely and a true value repeats the attribute name as its value, matching
	 * the behaviour of the scb helper this replaces.
	 *
	 * @param array  $attr Attribute name/value pairs.
	 * @param string $text Already-escaped link text.
	 * @return string
	 */
	protected static function anchor( array $attr, $text ) {
		$out = '<a';

		foreach ( $attr as $key => $value ) {
			if ( false === $value ) {
				continue;
			}

			if ( true === $value ) {
				$value = $key;
			}

			$value = ( 'href' === $key ) ? esc_url( $value ) : esc_attr( $value );

			$out .= ' ' . $key . '="' . $value . '"';
		}

		return $out . '>' . $text . '</a>';
	}
}
