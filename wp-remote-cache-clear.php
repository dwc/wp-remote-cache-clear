<?php
/*
Plugin Name: WP Remote Cache Clear
Version: 0.1
Plugin URI: 
Description: Clear the WP Super Cache when a specific URL is accessed, or clear a remote WP Super Cache.
Author: Daniel Westermann-Clark
Author URI: http://danieltwc.com/
*/

require_once(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'options-page.php');

class WPRemoteCacheClearPlugin {
    private $option_name = 'wp_remote_cache_clear_options';
    private $options;
    public $db_version = 1;

    public function __construct() {
        $this->options = get_option($this->option_name);

        if (is_admin()) {
            $options_page = new WPRemoteCacheClearOptionsPage(&$this, $this->option_name, __FILE__, $this->options);
            add_action('admin_init', array(&$this, 'check_options'));
        }
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
        $default_options = array(
            'server_key' => $this->generate_key(),
            'server_allowed_ip_regex' => '^127\.0\.0\.1$',
            'client_remote_url' => '',
            'client_key' => '',
        );

        if ($current_db_version < 1) {
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
}

new WPRemoteCacheClearPlugin();
?>
