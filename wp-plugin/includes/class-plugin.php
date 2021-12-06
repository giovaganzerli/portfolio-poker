<?php

namespace poker_plugin;

/**
 * The main plugin class.
 */
class Plugin
{

    private $loader;
    private $plugin_slug;
    private $version;
    private $option_name;

    public function __construct() {
        $this->plugin_slug = Info::SLUG;
        $this->version     = Info::VERSION;
        $this->option_name = Info::OPTION_NAME;
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_frontend_hooks();
    }

    private function load_dependencies() {
        
        require_once plugin_dir_path(dirname(__FILE__)) .'includes/libraries/fpdf-php/fpdf/fpdf.php';
        require_once plugin_dir_path(dirname(__FILE__)) .'includes/libraries/fpdf-php/fpdi/autoload.php';
        
        foreach ( glob( poker_PLUGIN_PATH . 'includes/libraries/jwt-php/src/*.php' ) as $filename ) {
            include $filename;
        }
        
        require_once plugin_dir_path(dirname(__FILE__)) .'includes/class-loader.php';
        require_once plugin_dir_path(dirname(__FILE__)) .'admin/class-admin.php';
        require_once plugin_dir_path(dirname(__FILE__)) .'frontend/class-frontend.php';

        foreach ( glob( poker_PLUGIN_PATH . 'includes/api/*.php' ) as $filename ) {
            include $filename;
        }
        foreach ( glob( poker_PLUGIN_PATH . 'includes/api/v1/*.php' ) as $filename ) {
            include $filename;
        }

        $this->loader = new Loader();
    }
    
    private function define_admin_hooks() {
        $plugin_admin = new Admin($this->plugin_slug, $this->version, $this->option_name);
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'assets_js_admin');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'assets_css_admin');
        $this->loader->add_action('acf/input/admin_enqueue_scripts', $plugin_admin, 'assets_js_acf');
        $this->loader->add_action('init', $plugin_admin, 'init');
        $this->loader->add_action('admin_menu', $plugin_admin, 'render');
        
        $this->loader->add_filter('get_term', $plugin_admin, 'get_term_custom_field', 10, 2);
        $this->loader->add_filter('acf/load_field/name=allegato-ddt', $plugin_admin, 'acf_select_allegato', 10, 2);
        $this->loader->add_filter('acf/load_field/name=allegato-ddt_firmato', $plugin_admin, 'acf_select_allegato', 10, 2);
        $this->loader->add_filter('acf/load_field/name=allegato-firma', $plugin_admin, 'acf_select_allegato', 10, 2);
        $this->loader->add_filter('acf/update_value/name=allegato-ddt', $plugin_admin, 'acf_select_allegato_update', 10, 3);
        $this->loader->add_filter('acf/update_value/name=allegato-ddt_firmato', $plugin_admin, 'acf_select_allegato_update', 10, 3);
        $this->loader->add_filter('acf/update_value/name=allegato-firma', $plugin_admin, 'acf_select_allegato_update', 10, 3);
        $this->loader->add_filter('wp_query_route_to_rest_api_max_posts_per_page', $plugin_admin, 'rest_filter_postsperpage', 10, 3);
        $this->loader->add_filter('rest_request_after_callbacks', $plugin_admin, 'rest_callbacks', 10, 3);
        
    }

    private function define_frontend_hooks() {
        $plugin_frontend = new Frontend($this->plugin_slug, $this->version, $this->option_name);
        $this->loader->add_action('wp_enqueue_scripts', $plugin_frontend, 'assets_js_frontend');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_frontend, 'assets_css_frontend');
        $this->loader->add_action('init', $plugin_frontend, 'init');
        $this->loader->add_action('wp_footer', $plugin_frontend, 'render');
        
        // JWT AUTH
        $this->loader->add_filter('rest_api_init', $plugin_frontend, 'add_cors_support');
        $this->loader->add_filter('rest_pre_dispatch', $plugin_frontend, 'rest_pre_dispatch', 10, 2);
        $this->loader->add_filter('determine_current_user', $plugin_frontend, 'determine_current_user', 10);
    }

    public function run() {
        $this->loader->run();
    }
}
