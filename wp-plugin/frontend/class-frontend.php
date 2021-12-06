<?php

namespace poker_plugin;

/**
 * The code used on the frontend.
 */
class Frontend
{
    private $plugin_slug;
    private $version;
    private $option_name;
    private $jwt_error = null;

    public function __construct($plugin_slug, $version, $option_name) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->option_name = $option_name;
    }

    public function assets_css_frontend() {
        
        if (!is_admin()) {

            // MAIN CSS
            wp_enqueue_style('plugin-main-css', poker_PLUGIN_URL .'frontend/assets/css/main.css', [], null);
        }
    }

    public function assets_js_frontend() {
        
        if (!is_admin()) {
            
            // MAIN JS
            wp_enqueue_script('plugin-main-js', poker_PLUGIN_URL .'frontend/assets/js/min/main.min.js', array( 'jquery' ), null);
        }
    }

    public function init() {
        
        // INIT SHORTCODES
        require_once plugin_dir_path(dirname(__FILE__)).'frontend/partials/shortcodes.php';
    }

    /**
     * Render the view using MVC pattern.
     */
    public function render() {
        
        // View
        require_once plugin_dir_path(dirname(__FILE__)).'frontend/partials/components.php';
        require_once plugin_dir_path(dirname(__FILE__)).'frontend/partials/view.php';
    }
    
    /**
     * Add CORs suppot to the request.
     */
    public function add_cors_support() {
        $enable_cors = defined('JWT_AUTH_CORS_ENABLE') ? JWT_AUTH_CORS_ENABLE : false;
        if ($enable_cors) {
            $headers = apply_filters('jwt_auth_cors_allow_headers', 'Access-Control-Allow-Headers, Content-Type, Authorization');
            header(sprintf('Access-Control-Allow-Headers: %s', $headers));
        }
    }
    
    /**
     * This is our Middleware to try to authenticate the user according to the
     * token send.
     *
     * @param (int|bool) $user Logged User ID
     *
     * @return (int|bool)
     */
    public function determine_current_user($user) {
        
        /**
         * This hook only should run on the REST API requests to determine
         * if the user in the Token (if any) is valid, for any other
         * normal call ex. wp-admin/.* return the user.
         *
         * @since 1.2.3
         **/
        $rest_api_slug = rest_get_url_prefix();
        $valid_api_uri = strpos($_SERVER['REQUEST_URI'], $rest_api_slug);
        
        if (!$valid_api_uri) {
            return $user;
        }

        /*
         * if the request URI is for validate the token don't do anything,
         * this avoid double calls to the validate_token function.
         */
        $validate_uri = strpos($_SERVER['REQUEST_URI'], 'auth/token/validate');
        $refresh_uri = strpos($_SERVER['REQUEST_URI'], 'auth/token/reset');
        if ($validate_uri > 0 || $refresh_uri > 0) {
            return $user;
        }
        
        $token = validate_token(false);

        if (is_wp_error($token)) {
            if ($token->get_error_code() != 'jwt_auth_no_auth_header') {
                /** If there is a error, store it to show it after see rest_pre_dispatch */
                $this->jwt_error = $token;
                return $user;
            } else {
                return $user;
            }
        }
        /** Everything is ok, return the user ID stored in the token*/
        return $token->data->user->id;
    }
    
    /**
     * Filter to hook the rest_pre_dispatch, if the is an error in the request
     * send it, if there is no error just continue with the current request.
     *
     * @param $request
     */
    public function rest_pre_dispatch($request) {
        if (is_wp_error($this->jwt_error)) {
            return $this->jwt_error;
        }
        return $request;
    }
}
