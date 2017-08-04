<?php
/**
 * A class that simulates a REST API call returning category JSON.
 *
 * @package Fairfax_Categories_Loader\Category_API
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Category API class.
 */
final class Category_API {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Store local instance of loader class to access variables and related class instances.
		$this->main = fairfax_categories();

		// Responds to the '?get_categories=1' API request.
		add_action( 'init', array( $this, 'respond_to_api_request' ) );
	}

	/**
	 * Responds to the '?get-categories=1' API request.
	 */
	public function respond_to_api_request() {

		// Not a request to our category API, bail.
		if ( true === empty( $_GET['get-categories'] ) ) {
			return;
		}

		// Set some standard headers for good measure.
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );

		require $this->main->inc_dir . 'categories.json';

		exit;
	}
}

return new Category_API;
