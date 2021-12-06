<?php

namespace poker_plugin;

/**
 * The code used in the admin.
 */
class Admin
{
    private $plugin_slug;
    private $version;
    private $option_name;
    private $settings;
    private $settings_group;

    public function __construct($plugin_slug, $version, $option_name) {
        $this->plugin_slug = $plugin_slug;
        $this->version = $version;
        $this->option_name = $option_name;
    }
    
    public function assets_css_admin() {
        if (is_admin()) {
            
            $curr_page = get_current_screen();
            
            //PLUGIN
            wp_enqueue_style('plugin_admin-wtfform-css', 'https://s3-us-west-2.amazonaws.com/s.cdpn.io/3/wtf-forms.css', [], $this->version);
            
            // THEME
            wp_enqueue_style('plugin_admin-main-css', poker_PLUGIN_URL .'admin/assets/css/main.css', [], $this->version);
    
            if(file_exists(plugin_dir_path( __FILE__ ) .'assets/css/templates/'. $curr_page->id .'.css')) {
                wp_enqueue_style('plugin_admin-'. $curr_page->id .'-css', poker_PLUGIN_URL .'admin/assets/css/templates/'. $curr_page->id .'.css', [], $this->version);
            }
            
        }
    }

    public function assets_js_admin() {
        if (is_admin()) {
    
            $curr_page = get_current_screen();
            
            // Moment
            wp_enqueue_script('plugin_admin-moment-js', poker_PLUGIN_URL .'includes/libraries/moment-js/moment-with-locales.min.js', array( 'jquery' ), $this->version, true);
            
            // THEME
            wp_enqueue_script('plugin_admin-main-js', poker_PLUGIN_URL .'admin/assets/js/min/main.min.js', array( 'jquery' ), $this->version, true);
    
            if(file_exists(plugin_dir_path( __FILE__ ) .'assets/js/min/templates/'. $curr_page->id .'.min.js')) {
                wp_enqueue_script('plugin_admin-'. $curr_page->id .'-js', poker_PLUGIN_URL .'admin/assets/js/min/templates/'. $curr_page->id .'.min.js', array( 'jquery' ), $this->version, true);
            }
        }
    }
    
    public function assets_js_acf() {
        
    }
    
    public function init() {
        
    }

    /**
     * Render the view using MVC pattern.
     */
    public function render() {
        
        // View
        add_menu_page('Strumenti', 'Strumenti', 'manage_options', 'poker_plugin', array($this, 'initView'), 'dashicons-admin-generic', 75);
        add_submenu_page("poker_plugin", 'Importa Consegna', 'Importa Consegne', 'manage_options', 'poker_plugin-importa_consegne', array($this, 'initView'), 5);
        add_submenu_page("poker_plugin", 'Importa Scadenze', 'Importa Scadenze', 'manage_options', 'poker_plugin-importa_scadenze', array($this, 'initView'), 10);
        add_submenu_page("poker_plugin", 'Importa Documenti', 'Importa Documenti', 'manage_options', 'poker_plugin-importa_documenti', array($this, 'initView'), 15);
        
        if(function_exists('acf_add_options_page')) {
            $option_page = acf_add_options_page(array(
                'page_title'    => __('Variabili'),
                'menu_title'    => __('Variabili'),
                'menu_slug'     => 'poker_plugin-variabili',
                'post_id'       => 'poker_plugin-variabili',
                'parent_slug'   => 'poker_plugin',
                'capability'    => 'edit_posts',
                'redirect'      => false,
                'autoload'      => false,
                'update_button' => __('Aggiorna'),
                'updated_message' => __('Variabili aggiornate con successo.')
            ));
        }
    }
    
    public function initView() {
        
        $name = explode('-', $_GET['page']);
        $name = (count($name) > 1) ? $name[1] : false;
        
        if(!$name) include plugin_dir_path(dirname(__FILE__)).'admin/partials/view.php';
        else include plugin_dir_path(dirname(__FILE__)).'admin/partials/view-'. $name .'.php';
    }
    
    public function get_term_custom_field($term, $taxonomy) {
        
        $term->acf = array();
        
        $fields = get_fields($taxonomy .'_'. $term->term_id);

        if(is_array($fields) && count($fields)) {
            foreach( $fields as $name => $value ) {
                $term->acf[$name] = $value;
            }
        }
        
        return $term;
    }
    
    public function acf_select_allegato($field) {
        
        global $post;
        
        if(isset($post) && in_array($post->post_type, array('consegne', 'scadenze'))) {
	        $abs_dir = (substr(ABSPATH, 0, -1));
            $uploads_dir = "/wp-content/uploads/app/". get_post_type($post->ID);

            if($field['name'] == 'allegato-ddt') $uploads_dir .= '/originali';
            elseif($field['name'] == 'allegato-ddt_firmato') $uploads_dir .= '/firmati';
            elseif($field['name'] == 'allegato-firma') $uploads_dir .= '/firme';

            $field['choices'] = array();

            if ($dh = opendir($abs_dir. $uploads_dir)) {
                while (($file = readdir($dh)) !== false) {
                    if(filetype($abs_dir. $uploads_dir .'/'. $file) != 'dir' && $file != '.' && $file != '..') {
                        $field['choices'][$uploads_dir .'/'. $file] = $file;
                    }
                }
                closedir($dh);
            }
        }
        
        return $field;
    }
    
    public function acf_select_allegato_update($value, $post_id, $field) {
        
        if($value) {

	        $abs_dir = (substr(ABSPATH, 0, -1));
            $uploads_dir = "/wp-content/uploads/app/". get_post_type($post_id);

            if($field['name'] == 'allegato-ddt') $uploads_dir .= '/originali';
            elseif($field['name'] == 'allegato-ddt_firmato') $uploads_dir .= '/firmati';
            elseif($field['name'] == 'allegato-firma') $uploads_dir .= '/firme';

            $newFileName = get_post_field('post_name', $post_id) .'.'. pathinfo($value, PATHINFO_EXTENSION);

            $rename = rename($abs_dir. $value, $abs_dir. $uploads_dir .'/'. $newFileName);

            if($rename) {
                $value = $uploads_dir .'/'. $newFileName;
            }
        }
        
        return $value;
    }
    
    public function rest_filter_postsperpage($max) {
        return -1;
    }
    
    public function rest_callbacks($response, $handler, $request) {
        
        $route = $request->get_route();
        $method = $request->get_method();
        
        $source = (isset($_GET['source'])) ? $_GET['source'] : 'default';
        $action = (isset($_GET['action'])) ? $_GET['action'] : false;
        $user_id = (isset($_GET['user_id'])) ? $_GET['user_id'] : false;
        
        if($method == 'GET' && $source == 'app' && (strpos($route, '/wp/v2/consegne') !== false || strpos($route, '/wp/v2/scadenze') !== false)) {
            
            if($action && $action == 'download') {
                
                if($user_id) {
                    $user = get_user_by('id', $user_id);
                }
                
                foreach($response->data as $item) {
                    if($item['acf']['stato']['value'] == '0') {
                        
                        update_field('stato', '3', intval($item['id']));
                        $item['acf']['stato']['value'] = '3';
                        $item['acf']['stato']['label'] = 'Presa in carico';
                        
                        if(strpos($route, '/wp/v2/consegne') !== false) {
                            update_field('corriere', intval($user_id), intval($item['id']));
                        } elseif(strpos($route, '/wp/v2/scadenze') !== false && $user_id) {
                            update_field('agente-codice', intval($user_id), intval($item['id']));
                        }
                    }
                }   
            }
        }
        
        return $response;
    }
}
