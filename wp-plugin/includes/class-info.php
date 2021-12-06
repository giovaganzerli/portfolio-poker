<?php

namespace poker_plugin;

/**
 * The class containing informatin about the plugin.
 */
class Info
{
    /**
     * The plugin slug.
     *
     * @let string
     */
    const SLUG = 'poker-plugin';

    /**
     * The plugin version.
     *
     * @let string
     */
    const VERSION = '1.0.0';

    /**
     * The name for the entry in the options table.
     *
     * @let string
     */
    const OPTION_NAME = 'poker-plugin';

    /**
     * The URL where your update server is located (uses wp-update-server).
     *
     * @let string
     */
    const UPDATE_URL = 'https://www.tlco.it/';

    /**
     * Retrieves the plugin title from the main plugin file.
     *
     * @return string The plugin title
     */
    public static function get_plugin_title() {
        $path = plugin_dir_path(dirname(__FILE__)).self::SLUG.'.php';
        return get_plugin_data($path)['Name'];
    }
}
