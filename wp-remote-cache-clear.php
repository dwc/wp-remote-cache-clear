<?php
/*
Plugin Name: WP Remote Cache Clear
Version: 0.1
Plugin URI: http://danieltwc.com/2011/wp-remote-cache-clear-0-1/
Description: Clear the WP Super Cache when a specific URL is accessed, or clear a remote WP Super Cache.
Author: Daniel Westermann-Clark
Author URI: http://danieltwc.com/
*/

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'options-page.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'server.php');
require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'client.php');

class WPRemoteCacheClearPlugin {
    private $option_name = 'wp_remote_cache_clear_options';
    private $options;
    private $db_version = 3;
    private $query_var = 'wp_remote_cache_clear_key';
    private $server;
    private $client;

    /*
     * Load options, create the options page, and create the server and client.
     */
    public function __construct() {
        $this->options = get_option($this->option_name);

        if (is_admin()) {
            $options_page = new WPRemoteCacheClearOptionsPage(&$this, $this->option_name, __FILE__, $this->options);
            add_action('admin_init', array(&$this, 'check_options'));
        }

        $this->server = new WPRemoteCacheClearServer($this->options, $this->query_var, array(&$this, 'debug'));
        $this->client = new WPRemoteCacheClearClient($this->options, $this->query_var, array(&$this, 'debug'));
    }

    /*
     * Return the current version of this plugin's options. Used on
     * upgrade.
     */
    public function db_version() {
        return $this->db_version;
    }

    /*
     * Return the name of the query parameter used to clear the cache
     * by the client and server.
     */
    public function query_var() {
        return $this->query_var;
    }

    /*
     * Check the options currently in the database and upgrade if necessary.
     */
    public function check_options() {
        if ($this->options === false || ! isset($this->options['db_version']) || $this->options['db_version'] < $this->db_version) {
            if (! is_array($this->options)) {
                $this->options = array();
            }

            $current_db_version = isset($this->options['db_version']) ? $this->options['db_version'] : 0;
            $this->upgrade($current_db_version);
            $this->options['db_version'] = $this->db_version;
            update_option($this->option_name, $this->options);
        }
    }

    /*
     * Upgrade options as needed depending on the current database version.
     */
    private function upgrade($current_db_version) {
        $debug_file = trailingslashit(WP_CONTENT_DIR) . 'wp-remote-cache-clear-' . $this->generate_key() . '.txt';

        $default_options = array(
            'server_key' => $this->generate_key(),
            'server_allowed_ip_regex' => '^127\.0\.0\.1$',
            'server_delete_transients' => true,
            'client_remote_url' => '',
            'client_key' => '',
            'debug' => false,
            'debug_file' => $debug_file,
        );

        if ($current_db_version < $this->db_version) {
            foreach ($default_options as $key => $value) {
                if (! isset($this->options[$key])) {
                    $this->options[$key] = $value;
                }
            }
        }
    }

    /*
     * Generate a key for authenticating clients.
     */
    private function generate_key() {
        return sha1(uniqid() . microtime());
    }

    /*
     * Write information to the WP Cache debugging log.
     */
    public function debug($message) {
        if ((bool) $this->options['debug'] && ! empty($this->options['debug_file'])) {
            if (($fh = fopen($this->options['debug_file'], 'a')) !== false) {
                fwrite($fh, strftime('%c') . " $message\n");
                fclose($fh);
            }
        }
    }
}

new WPRemoteCacheClearPlugin();
?>
