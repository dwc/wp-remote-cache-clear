<?php
class WPRemoteCacheClearClient {
    private $options;
    private $query_var;
    private $configured = false;

    /*
     * If the client is configured, set it up to make the cache
     * clearing request.
     */
    public function __construct($options, $query_var) {
        $this->options = $options;
        $this->query_var = $query_var;

        if (! empty($this->options['client_remote_url']) && ! empty($this->options['client_key'])) {
            add_action('publish_post', array(&$this, 'make_request'));

            $this->configured = true;
        }
    }

    /*
     * Return true if this plugin is configured to make requests.
     */
    public function configured() {
        return $this->configured;
    }

    /*
     * Build the server URL, containing the key for this client.
     */
    private function build_url() {
        $args = array(
            $this->query_var => $this->options['client_key'],
        );

        $url = $this->options['client_remote_url'];
        $url = add_query_arg($args, $url);

        return $url;
    }

    /*
     * Make the request to the server.
     */
    public function make_request() {
        $url = $this->build_url();
        wp_remote_fopen($url);
    }
}
?>
