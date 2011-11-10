<?php
class WPRemoteCacheClearServer {
    private $options;
    private $query_var;
    private $configured = false;

    /*
     * If the server is configured, set it up to handle the cache
     * clearing requests.
     */
    public function __construct($options, $query_var) {
        $this->options = $options;
        $this->query_var = $query_var;

        if (! empty($this->options['server_key']) && ! empty($this->options['server_allowed_ip_regex'])) {
            add_filter('query_vars', array(&$this, 'add_query_var'));
            add_action('parse_request', array(&$this, 'handle_request'));

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
        if ($this->verify_request($request)) {
            error_log("Valid request from " . $_SERVER['REMOTE_ADDR']);

            if (function_exists('wp_cache_clear_cache')) {
                error_log("Clearing WP Super Cache via " . $_SERVER['REMOTE_ADDR']);
                wp_cache_clear_cache();
            }
        }
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
        if (array_key_exists($this->query_var, $request->query_vars)) {
            return $request->query_vars[$this->query_var] == $this->options['server_key'];
        }

        return false;
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
}
?>
