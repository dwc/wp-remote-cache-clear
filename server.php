<?php
class WPRemoteCacheClearServer {
    private $options;
    private $query_var;
    private $debug_func;
    private $configured = false;

    /*
     * If the server is configured, set it up to handle the cache
     * clearing requests.
     */
    public function __construct($options, $query_var, $debug_func = 'error_log') {
        $this->options = $options;
        $this->query_var = $query_var;
        $this->debug_func = $debug_func;

        if (! empty($this->options['server_key']) && ! empty($this->options['server_allowed_ip_regex'])) {
            add_filter('query_vars', array(&$this, 'add_query_var'));
            add_action('parse_request', array(&$this, 'handle_request'));

            if ((bool) $this->options['server_delete_transients']) {
                add_action('wp_remote_cache_clear_valid_request', array(&$this, 'delete_transients'), 10);
            }

            if (function_exists('wp_cache_clear_cache')) {
                add_action('wp_remote_cache_clear_valid_request', array(&$this, 'clear_cache'), 11);
            }

            $this->configured = true;
        }
    }

    /*
     * Return true if this plugin is configured to handle requests.
     */
    public function configured() {
        return $this->configured;
    }

    /*
     * Callback to make WordPress pay attention to the query parameter.
     */
    public function add_query_var($query_vars) {
        if (! in_array($this->query_var, $query_vars)) {
            $query_vars[] = $this->query_var;
        }

        return $query_vars;
    }

    /*
     * If the request is verified, clear the cache.
     */
    public function handle_request(&$request) {
        $identification = $this->client_identification();

        if (array_key_exists($this->query_var, $request->query_vars)) {
            call_user_func($this->debug_func, "Received request to clear WP Cache from $identification");

            if ($this->verify_request($request)) {
                call_user_func($this->debug_func, "Valid request to clear WP Cache from $identification");
                do_action('wp_remote_cache_clear_valid_request', $request);
            }
            else {
                call_user_func($this->debug_func, "Invalid request to clear WP Cache from $identification");
                do_action('wp_remote_cache_clear_invalid_request', $request);
            }
        }
    }

    /*
     * Return a string useful for identifying the client in logs.
     */
    private function client_identification() {
        $identification = $_SERVER['REMOTE_ADDR'] . ' [' . $_SERVER['HTTP_USER_AGENT'] . ']';

        return $identification;
    }

    /*
     * Verify the request by checking the key and the client IP.
     */
    private function verify_request(&$request) {
        return $this->verify_key($request) && $this->verify_ip($request);
    }

    /*
     * Verify the key supplied by the client.
     */
    private function verify_key(&$request) {
        return $request->query_vars[$this->query_var] == $this->options['server_key'];
    }

    /*
     * Verify the client's IP address against the configured regular
     * expression.
     */
    private function verify_ip(&$request) {
        $allowed_ip_regex = $this->options['server_allowed_ip_regex'];
        if (strpos($allowed_ip_regex, '/') !== 0) {
            $allowed_ip_regex = '/' . $allowed_ip_regex . '/';
        }

        return (bool) preg_match($allowed_ip_regex, $_SERVER['REMOTE_ADDR']);
    }

    /*
     * Delete the transient objects for RSS and Atom feeds cached by
     * WordPress.
     */
    public function delete_transients() {
        global $wpdb;

        $identification = $this->client_identification();
        call_user_func($this->debug_func, "Deleting transients via $identification");

        $sql = $wpdb->prepare(
            "DELETE FROM $wpdb->options WHERE `option_name` LIKE %s",
            '_transient%_feed_%'
        );

        $deleted = $wpdb->query($sql);

        if ($deleted !== false) {
            call_user_func($this->debug_func, "Deleted $deleted transients via $identification");
        }
        else {
            call_user_func($this->debug_func, "Error deleting transients via $identification");
        }

        return $deleted;
    }

    /*
     * Clear the WordPress cache.
     */
    public function clear_cache() {
        $identification = $this->client_identification();
        call_user_func($this->debug_func, "Clearing WP Cache via $identification");

        wp_cache_clear_cache();
    }
}
?>
