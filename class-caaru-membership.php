<?php
/**
 * The file that defines the core plugin class
 *
 * @package    CAARU_Membership
 */
class CAARU_Membership {

    protected $loader;
    protected $plugin_name;
    protected $version;

    public function __construct() {
        $this->version = defined( 'CAARU_MEMBERSHIP_VERSION' ) ? CAARU_MEMBERSHIP_VERSION : '2.3.0';
        $this->plugin_name = 'caaru-membership';

        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

    private function load_dependencies() {
        require_once CAARU_MEMBERSHIP_PLUGIN_DIR . 'includes/class-caaru-membership-loader.php';
        require_once CAARU_MEMBERSHIP_PLUGIN_DIR . 'admin/class-caaru-membership-admin.php';
        require_once CAARU_MEMBERSHIP_PLUGIN_DIR . 'public/class-caaru-membership-public.php';
        require_once CAARU_MEMBERSHIP_PLUGIN_DIR . 'includes/caaru-form-helpers.php';
        $this->loader = new CAARU_Membership_Loader();
    }

    private function define_admin_hooks() {
        $plugin_admin = new CAARU_Membership_Admin( $this->get_plugin_name(), $this->get_version() );

        $this->loader->add_action( 'init', $plugin_admin, 'register_cpt_application' );
        $this->loader->add_action( 'admin_menu', $plugin_admin, 'add_admin_menu' );
        $this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles_scripts' );
        $this->loader->add_action( 'add_meta_boxes', $plugin_admin, 'add_application_meta_box' );
        $this->loader->add_action( 'save_post_caaru_application', $plugin_admin, 'save_application_data' );
        $this->loader->add_action( 'admin_init', $plugin_admin, 'register_settings' );

        $this->loader->add_filter( 'manage_caaru_application_posts_columns', $plugin_admin, 'add_custom_columns' );
        $this->loader->add_action( 'manage_caaru_application_posts_custom_column', $plugin_admin, 'render_custom_columns', 10, 2 );

        // AJAX for user search in admin
        $this->loader->add_action( 'wp_ajax_caaru_search_members', $plugin_admin, 'ajax_search_members' );
    }

    private function define_public_hooks() {
        $plugin_public = new CAARU_Membership_Public( $this->get_plugin_name(), $this->get_version() );
        
        $this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles_scripts' );
        $this->loader->add_shortcode( 'caaru_application_form', $plugin_public, 'render_application_form' );
        
        // AJAX Hooks for public form interactions
        $this->loader->add_action( 'wp_ajax_save_application_section', $plugin_public, 'ajax_save_application_section' );
        $this->loader->add_action( 'wp_ajax_submit_full_application', $plugin_public, 'ajax_submit_full_application' );
        $this->loader->add_action( 'wp_ajax_caaru_search_members', $plugin_public, 'ajax_search_members' ); // For logged-in users
    }

    public function run() {
        $this->loader->run();
    }

    public function get_plugin_name() {
        return $this->plugin_name;
    }

    public function get_version() {
        return $this->version;
    }
}

