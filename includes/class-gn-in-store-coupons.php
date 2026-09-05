<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://www.georgenicolaou.me
 * @since      1.0.0
 *
 * @package    Gn_In_Store_Coupons
 * @subpackage Gn_In_Store_Coupons/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Gn_In_Store_Coupons
 * @subpackage Gn_In_Store_Coupons/includes
 * @author     George Nicolaou <orionas.elite@gmail.com>
 */
class Gn_In_Store_Coupons {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Gn_In_Store_Coupons_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'GN_IN_STORE_COUPONS_VERSION' ) ) {
			$this->version = GN_IN_STORE_COUPONS_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'gn-in-store-coupons';

		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->loader->add_action( 'init', $this, 'initialize_coupons' );
		$this->loader->add_action( 'woocommerce_created_customer', 'Gn_In_Store_Coupons_Eligibility', 'customer', 20 );
		$this->loader->add_action( 'user_register', 'Gn_In_Store_Coupons_Eligibility', 'registration' );
		$this->loader->add_action( 'gn_coupons_customer', 'Gn_In_Store_Coupons_Eligibility', 'customer', 10, 2 );
		$this->loader->add_action( 'mailmint_list_applied', 'Gn_In_Store_Coupons_Eligibility', 'list_applied', 10, 2 );
		$this->loader->add_action( 'mint_subscriber_status_to_subscribed', 'Gn_In_Store_Coupons_Eligibility', 'contact' );
		$this->loader->add_action( 'gn_coupons_scan', 'Gn_In_Store_Coupons_Eligibility', 'scan' );
		$this->loader->add_action( 'gn_coupons_scan_soon', 'Gn_In_Store_Coupons_Eligibility', 'scan' );
		$this->loader->add_action( 'gn_coupons_send', 'Gn_In_Store_Coupons_Store', 'send_pending' );

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Gn_In_Store_Coupons_Loader. Orchestrates the hooks of the plugin.
	 * - Gn_In_Store_Coupons_i18n. Defines internationalization functionality.
	 * - Gn_In_Store_Coupons_Admin. Defines all hooks for the admin area.
	 * - Gn_In_Store_Coupons_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gn-in-store-coupons-store.php';
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gn-in-store-coupons-eligibility.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gn-in-store-coupons-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-gn-in-store-coupons-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-gn-in-store-coupons-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-gn-in-store-coupons-public.php';

		$this->loader = new Gn_In_Store_Coupons_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Gn_In_Store_Coupons_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Gn_In_Store_Coupons_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Gn_In_Store_Coupons_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'menu' );
		$this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );
		$this->loader->add_action( 'admin_post_gn_coupon_action', $plugin_admin, 'action' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Gn_In_Store_Coupons_Public( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'template_redirect', $plugin_public, 'display', 0 );
		$this->loader->add_action( 'admin_post_gn_coupon_preview', $plugin_public, 'preview' );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	public function initialize_coupons() {
		if ( '1' !== get_option( 'gn_coupons_db_version' ) ) {
			Gn_In_Store_Coupons_Store::install();
		}
		if ( ! wp_next_scheduled( 'gn_coupons_scan' ) ) {
			wp_schedule_event( time() + 300, 'hourly', 'gn_coupons_scan' );
		}
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Gn_In_Store_Coupons_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
