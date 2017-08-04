<?php
/**
 * A class that adds on-demand admin category fetching and disables adding new categories.
 *
 * @package Fairfax_Categories_Loader\Category_Admin
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Category Admin class.
 */
final class Category_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {

		// Store local instance of loader class to access variables and related class instances.
		$this->main = fairfax_categories();

		// Adds a "Update categories now" field to the wp-admin/options-general.php page.
		add_action( 'admin_init', array( $this , 'add_update_categories_field' ) );

		// Enqueues scripts.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// AJAX handler for the 'fairfax_sync_categories' request.
		add_action( 'wp_ajax_fairfax_sync_categories', array( $this, 'ajax_sync_categories' ) );
	}

	/**
	 * Adds a "Update categories now" field to the wp-admin/options-general.php page.
	 */
	public function add_update_categories_field() {

		if ( false === current_user_can( 'manage_options' ) ) {
			return;
		}

		add_settings_field(
			'fairfax_update_categories',
			'<label>' . __( 'Update Categories' , 'fairfax-categories' ) . '</label>',
			array( $this, 'add_update_categories_button' ),
			'general'
		);
	}

	/**
	 * Adds a "Update categories now" button to the wp-admin/options-general.php page.
	 */
	public function add_update_categories_button() {

		ob_start(); ?>

		<a href="#" id="fairfax-update-categories" class="button">Update categories now</a>
		<div class="fairfax-update-categories-console"></div><?php

		echo ob_get_clean();
	}

	/**
	 * Enqueues scripts.
	 */
	public function enqueue_scripts( $hook ) {

		// Only enqueue our scripts if we're on the options-general.php page.
		if ( 'options-general.php' !== $hook ) {
			return;
		}

		// The get categories script.
		wp_enqueue_script( 'fairfax-get-categories', $this->main->js_url . 'admin-get-categories.js', array( 'jquery' ), $this->main->version, true );

		$localize_args = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'fairfax_sync_categories' ),
		);

		// Exposes JS variables to the get categories script.
		wp_localize_script( 'fairfax-get-categories', 'fairfax', $localize_args );

		// The get categories styles.
		wp_enqueue_style( 'fairfax-get-categories', $this->main->css_url . 'admin-get-categories.css', false, $this->main->version );
	}

	/**
	 * AJAX handler for the 'fairfax_sync_categories' request.
	 */
	public function ajax_sync_categories() {

		// Create a specific 'fairfax_sync_categories' WordPress capability rather than piggybacking off 'manage_options'.
		if ( false === current_user_can( 'manage_options' ) ) {
			return;
		}

		$post_data = wp_unslash( $_POST );
		$post_data = array_map( 'trim', $post_data );

		// Nonce not found, bail.
		if ( true === empty( $post_data['nonce'] ) ) {
			wp_send_json_error(
				array( 'msg' => 'Error: nonce not provided.' )
			);
		}

		if ( false === wp_verify_nonce( $post_data['nonce'], 'fairfax_sync_categories' ) ) {
			wp_send_json_error(
				array( 'msg' => 'Error: invalid nonce.' )
			);
		}

		$result = $this->main->category_sync->sync_categories();

		if ( true === is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'msg' => 'Error: ' . $result->get_error_message() )
			);
		}

		wp_send_json_success( $result );
	}
}

return new Category_Admin;
