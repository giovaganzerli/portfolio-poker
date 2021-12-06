<?php
/**
 * Plugin Name:       Poker srl
 * Plugin URI:        
 * Description:       
 * Version:           1.0.0
 * Author:            
 * Author URI:        
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       poker-plugin
 * Domain Path:       /languages
 */

namespace poker_plugin;

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

define( 'poker_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'poker_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// The class that contains the plugin info.
require_once poker_PLUGIN_PATH . 'includes/class-info.php';

/**
 * The code that runs during plugin activation.
 */
function activation() {
    require_once poker_PLUGIN_PATH . 'includes/class-activator.php';
    Activator::activate();
}
register_activation_hook(__FILE__, __NAMESPACE__ . '\\activation');

/**
 * Run the plugin.
 */
function run() {
    require_once poker_PLUGIN_PATH . 'includes/class-plugin.php';
    $plugin = new Plugin();
    $plugin->run();
}

run();
