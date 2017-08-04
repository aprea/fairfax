<?php
/**
 * A class that registers a cron job which syncs categories from the category REST API.
 *
 * @package Fairfax_Categories_Loader\Category_Sync
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Category Sync class.
 */
final class Category_Sync {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Store local instance of loader class to access variables and related class instances.
		$this->main = fairfax_categories();

		// Custom cron schedules.
		add_filter( 'cron_schedules', array( $this, 'custom_cron_schedules' ) );

		// WordPress category sync cron event listener.
		add_action( 'fairfax_sync_categories', array( $this, 'sync_categories' ) );

		// Filters the insert term data to ensure we specify the correct term ID from the API.
		add_filter( 'wp_insert_term_data', array( $this, 'add_term_id_to_term_insert' ), 10, 3 );
	}

	/**
	 * Schedules our cron jobs, this occurs on plugin activation.
	 */
	public function schedule_events() {

		// Do not attempt to schedule the event if it's already scheduled.
		if ( false !== wp_next_scheduled( 'fairfax_sync_categories' ) ) {
			return;
		}

		// Attempt to sync our categories every 30 minutes.
		wp_schedule_event( time(), 'thirty_minutes', 'fairfax_sync_categories' );
	}

	/**
	 * Unschedule our cron jobs, this occurs on plugin deactivation.
	 */
	public function unschedule_events() {

		// Daily tasks. Daily 2am.
		wp_clear_scheduled_hook( 'fairfax_sync_categories' );
	}

	/**
	 * Custom cron schedules.
	 *
	 * @param   array  $schedules  Array of schedules.
	 * @return  array              Array of modified schedules.
	 */
	public function custom_cron_schedules( $schedules ) {

		// Add a 30 minute cron schedule to the existing set.
		$schedules['thirty_minutes'] = array(
			'interval' => 30 * MINUTE_IN_SECONDS,
			'display'  => 'Once every 30 minutes.',
		);

		return $schedules;
	}

	/**
	 * WordPress category sync cron event listener.
	 */
	public function sync_categories() {

		$api_url = home_url( '/?get-categories=1' );

		// Call the API.
		$results = wp_remote_get( $api_url );

		// Error occured, return WP_Error object.
		if ( is_wp_error( $results ) ) {
			return $results;
		}

		$response_code = (int) $results['response']['code'];

		if ( ( $response_code < 200 || $response_code >= 300 ) && $response_code !== 304 ) {
			return new WP_Error( 'http_request_failed', __( 'HTTP request failed.', 'fairfax-categories' ) );
		}

		$body = $results['body'];

		// JSON decode this sucker.
		$categories = json_decode( $body, true );

		// JSON decode failed.
		if ( null === $categories ) {
			return new WP_Error( 'json_decode_failed', __( 'JSON decode failed.', 'fairfax-categories' ) );
		}

		$api_categories  = $categories['categories'];
		$blog_categories = get_categories( array( 'hide_empty' => false ) );

		$indexed_blog_categories = array();
		$indexed_api_categories  = array();

		// Reindex our blog categories to make them a little easier to work with.
		foreach ( $blog_categories as $blog_category ) {
			$indexed_blog_categories[ $blog_category->term_id ] = (array) $blog_category;
		}

		// Sort our API categories array, parents categories should come first followed by child categories.
		usort( $api_categories, function ( $cat1, $cat2 ) {
			// Using PHP7's spaceship operator here, will not work pre 7.
			return $cat1['parent_id'] <=> $cat2['parent_id'];
		} );

		// Reindex our api categories to make them a little easier to work with.
		foreach ( $api_categories as $api_category ) {
			$indexed_api_categories[ $api_category['id'] ] = $api_category;
		}

		$counters = array(
			'created' => 0,
			'updated' => 0,
		);

		// Loop through our API categories, adding or updating our local categories as necessary.
		foreach ( $indexed_api_categories as $api_category_key => $api_category ) {

			// Found an existing matching category, maybe update it.
			if ( true === isset( $indexed_blog_categories[ $api_category_key ] ) ) {

				$existing_category = $indexed_blog_categories[ $api_category_key ];

				// API category and existing category are indentical, bail.
				if ( false === $this->category_requires_updating( $api_category, $existing_category ) ) {
					continue;
				}

				// The updated parent category does not exist, create it.
				if ( false === isset( $indexed_blog_categories[ $api_category['parent_id'] ] ) && true === isset( $indexed_api_categories[ $api_category['parent_id'] ] ) ) {
					$new_category = $this->create_category( $indexed_api_categories[ $api_category['parent_id'] ] );

					$indexed_blog_categories[ $new_category['term_id'] ] = $new_category;

					++$counters['created'];
				}

				$args = array(
					'name'   => $api_category['name'],
					'parent' => $api_category['parent_id'],
				);

				// Update the category.
				wp_update_term( $api_category_key, 'category', $args );

				++$counters['updated'];

				continue;
			}

			// Not match found, create the category.
			$new_category = $this->create_category( $api_category );

			$indexed_blog_categories[ $new_category['term_id'] ] = $new_category;

			++$counters['created'];
		}

		return $counters;
	}

	/**
	 * Determines whether the given existing category differs from the matching API category.
	 *
	 * @param   array  $api_category       API category.
	 * @param   array  $existing_category  Existing WordPress category.
	 * @return  bool                       Whether the given existing category differs from the matching API category.
	 */
	public function category_requires_updating( $api_category, $existing_category ) {

		// Category name has changed.
		if ( $api_category['name'] !== $existing_category['name'] ) {
			return true;
		}

		// Convert to common format. WordPress specifies no parent as 0 while API specifies no parent as null.
		$existing_category['parent'] = ( $existing_category['parent'] === 0 ) ? null : $existing_category['parent'];

		// Category parent ID has changed.
		if ( $api_category['parent_id'] !== $existing_category['parent'] ) {
			return true;
		}

		// Nothing has changed.
		return false;
	}

	/**
	 * Creates a WordPress category using the data from the category API.
	 *
	 * @param   array  $api_category  API category.
	 * @return  array                 The newly inserted category array.
	 */
	public function create_category( $api_category ) {

		$args = array(
			'parent'     => $api_category['parent_id'],
			'api_cat_id' => $api_category['id'],
		);

		$term = wp_insert_term( $api_category['name'], 'category', $args );

		if ( true === is_wp_error( $term ) ) {
			return $term;
		}

		return get_term( $term['term_id'], 'category', ARRAY_A );
	}

	/**
	 * Filters the insert term data to ensure we specify the correct term ID from the API.
	 *
	 * @param   array   $data      Term data to be inserted.
	 * @param   string  $taxonomy  Taxonomy slug.
	 * @param   array   $args      Arguments passed to wp_insert_term().
	 * @return  array             Modified term data to be inserted.
	 */
	public function add_term_id_to_term_insert( $data, $taxonomy, $args ) {

		// Not inserting a category from our API, bail.
		if ( false === isset( $args['api_cat_id'] ) ) {
			return $data;
		}

		$data['term_id'] = $args['api_cat_id'];

		return $data;
	}
}

return new Category_Sync;
