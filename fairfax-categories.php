<?php
/**
 * The Fairfax Categories plugin.
 *
 * Syncs the local blog categories with the categories from an example REST API.
 *
 * @package Fairfax_Categories_Loader
 */

/**
 * Plugin Name: Fairfax Categories
 * Description: Syncs the local blog categories with the categories from an example REST API.
 * Author:      Chris Aprea
 * Author URI:  http://twitter.com/chrisaprea
 * Version:     1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fairfax Categories Loader class.
 */
final class Fairfax_Categories_Loader {

	/** Magic *****************************************************************/

	/**
	 * Fairfax Categories uses many variables, several of which can be filtered to
	 * customize the way it operates. Most of these variables are stored in a
	 * private array that gets updated with the help of PHP magic methods.
	 *
	 * This is a precautionary measure, to avoid potential errors produced by
	 * unanticipated direct manipulation of Fairfax Categories's run-time data.
	 *
	 * @see Fairfax_Categories::setup_globals()
	 * @var array
	 */
	private $data;

	/** Singleton *************************************************************/

	/**
	 * Loader Fairfax Categories Instance
	 *
	 * Insures that only one instance of Fairfax Categories exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @staticvar object $instance
	 * @return The one true Fairfax Categories
	 */
	public static function instance() {

		// Store the instance locally to avoid private static replication.
		static $instance = null;

		// Only run these methods if they haven't been ran previously.
		if ( null === $instance ) {
			$instance = new Fairfax_Categories_Loader;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		// Always return the instance.
		return $instance;
	}

	/** Magic Methods *********************************************************/

	/**
	 * A dummy constructor to prevent Fairfax Categories from being loaded more than once.
	 */
	private function __construct() { /* Do nothing here */ }

	/**
	 * A dummy magic method to prevent Fairfax Categories from being cloned
	 */
	public function __clone() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'fairfax-categories' ), '1.0.0' ); }

	/**
	 * A dummy magic method to prevent Fairfax Categories from being unserialized
	 */
	public function __wakeup() { _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'fairfax-categories' ), '1.0.0' ); }

	/**
	 * Magic method for checking the existence of a certain custom field
	 */
	public function __isset( $key ) { return isset( $this->data[$key] ); }

	/**
	 * Magic method for getting Fairfax Categories variables
	 */
	public function __get( $key ) { return isset( $this->data[$key] ) ? $this->data[$key] : null; }

	/**
	 * Magic method for setting Fairfax Categories variables
	 */
	public function __set( $key, $value ) { $this->data[$key] = $value; }

	/**
	 * Magic method for unsetting Fairfax Categories variables
	 */
	public function __unset( $key ) { if ( isset( $this->data[$key] ) ) unset( $this->data[$key] ); }

	/**
	 * Magic method to prevent notices and errors from invalid method calls
	 */
	public function __call( $name = '', $args = array() ) { unset( $name, $args ); return null; }

	/** Private Methods *******************************************************/

	/**
	 * Set some smart defaults to class variables.
	 */
	private function setup_globals() {

		$this->version    = '1.0.0';

		// Setup some base path and URL information.
		$this->file       = __FILE__;
		$this->basename   = plugin_basename( $this->file );
		$this->plugin_dir = plugin_dir_path( $this->file );
		$this->plugin_url = plugin_dir_url( $this->file );

		// Includes.
		$this->inc_dir    = $this->plugin_dir . 'inc/';

		// Classes.
		$this->classes    = $this->inc_dir    . 'classes/';

		// Assets.
		$this->assets_dir = $this->plugin_dir . 'assets/';
		$this->assets_url = $this->plugin_url . 'assets/';

		// CSS folder.
		$this->css_dir    = $this->assets_dir  . 'css/';
		$this->css_url    = $this->assets_url  . 'css/';

		// Images folder.
		$this->image_dir  = $this->assets_dir  . 'img/';
		$this->image_url  = $this->assets_url  . 'img/';

		// JS folder.
		$this->js_dir     = $this->assets_dir  . 'js/';
		$this->js_url     = $this->assets_url  . 'js/';
	}

	/**
	 * Include required files.
	 */
	private function includes() {

		// A class that registers a cron job which syncs categories from the category REST API.
		$this->category_sync = require $this->classes . 'class-category-sync.php';

		// A class that simulates a REST API call returning category JSON.
		$this->category_api = require $this->classes . 'class-category-api.php';

		// A class that adds on-demand admin category fetching and disables adding new categories.
		$this->category_admin = require $this->classes . 'class-category-admin.php';
	}

	/**
	 * Setup the default hooks and actions.
	 */
	private function setup_actions() {

		// Attach functions to the activate / deactive hooks.
		add_action( 'activate_'   . $this->basename, array( $this, 'activate' ) );
		add_action( 'deactivate_' . $this->basename, array( $this, 'deactivate' ) );
	}

	/** Public Methods *******************************************************/

	/**
	 * Plugin activation.
	 */
	public function activate() {

		$this->category_sync->schedule_events();
	}

	/**
	 * Plugin deactivation.
	 */
	public function deactivate() {

		$this->category_sync->unschedule_events();
	}
}

/**
 * The main function responsible for returning the one true Fairfax Categories Instance
 * to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $fairfax_categories = fairfax_categories(); ?>
 *
 * @return The one true Fairfax Categories Loader Instance
 */
function fairfax_categories() {
	return Fairfax_Categories_Loader::instance();
}

// Spin up an instance.
$GLOBALS['fairfax_categories_loader'] = fairfax_categories();
