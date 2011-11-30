<?php
class WPRemoteCacheClearOptionsPage {
    private $plugin;
    private $group;
    private $page;
    private $sections = array();
    private $options;
    private $title;

    /*
     * Prepare the options page for display.
     */
    public function __construct($plugin, $group, $page, $options, $title = 'WP Remote Cache Clear') {
        $this->plugin = $plugin;
        $this->group = $group;
        $this->page = $page;
        $this->options = $options;
        $this->title = $title;

        add_action('admin_init', array(&$this, 'register_options'));
        add_action('admin_menu', array(&$this, 'add_options_page'));
    }

    /*
     * Register the options for this plugin so they can be displayed and updated below.
     */
    public function register_options() {
        register_setting($this->group, $this->group, array(&$this, 'sanitize_settings'));

        $this->sections[] = $debug_section = 'wp_remote_cache_clear_debug';
        add_settings_section($debug_section, 'Debug Settings', array(&$this, 'describe_debug_options'), $debug_section);

        add_settings_field('wp_remote_cache_clear_debug', 'Enabled?', array(&$this, 'display_option_debug'), $debug_section, $debug_section);
        add_settings_field('wp_remote_cache_clear_debug_file', 'Debug log', array(&$this, 'display_option_debug_file'), $debug_section, $debug_section);

        $this->sections[] = $server_section = 'wp_remote_cache_clear_server';
        add_settings_section($server_section, 'Server Settings', array(&$this, 'describe_server_options'), $server_section);

        add_settings_field('wp_remote_cache_clear_server_key', 'Secret key', array(&$this, 'display_option_server_key'), $server_section, $server_section);
        add_settings_field('wp_remote_cache_clear_server_allowed_ip_regex', 'Allow IPs matching', array(&$this, 'display_option_server_allowed_ip_regex'), $server_section, $server_section);
        add_settings_field('wp_remote_cache_clear_server_delete_transients', 'Delete transients?', array(&$this, 'display_option_server_delete_transients'), $server_section, $server_section);

        $this->sections[] = $client_section = 'wp_remote_cache_clear_client';
        add_settings_section($client_section, 'Client Settings', array(&$this, 'describe_client_options'), $client_section);

        add_settings_field('wp_remote_cache_clear_client_remote_url', 'Remote URL', array(&$this, 'display_option_client_remote_url'), $client_section, $client_section);
        add_settings_field('wp_remote_cache_clear_client_key', 'Secret key', array(&$this, 'display_option_client_key'), $client_section, $client_section);
    }

    /*
     * Set the database version on saving the options.
     */
    public function sanitize_settings($input) {
        $output = $input;
        $output['db_version'] = $this->plugin->db_version();
        $output['debug'] = (bool) $input['debug'];

        return $output;
    }

    /*
     * Add an options page for this plugin.
     */
    public function add_options_page() {
        add_options_page($this->title, $this->title, 'manage_options', $this->page, array(&$this, 'display_options_page'));
    }

    /*
     * Display the options for this plugin.
     */
    public function display_options_page() {
        if (! current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
?>
<div class="wrap">
  <h2><?php esc_attr_e($this->title); ?> Options</h2>
  <form action="options.php" method="post">
    <?php if (function_exists('settings_errors')): settings_errors(); endif; ?>
    <?php settings_fields($this->group); ?>
    <?php foreach ($this->sections as $section): do_settings_sections($section); endforeach; ?>
    <p class="submit">
      <input type="submit" name="Submit" value="<?php esc_attr_e('Save Changes'); ?>" class="button-primary" />
    </p>
  </form>
</div>
<?php
    }

    /*
     * Describe the options used to debug the plugin.
     */
    public function describe_debug_options() {
        echo '<p>The following options control logging of the plugin actions for debugging purposes.</p>';
    }

    /*
     * Display the checkbox for enabling debugging.
     */
    public function display_option_debug() {
        $debug = (bool) $this->options['debug'];
        $this->display_checkbox_field('debug', $debug);
    }

    /*
     * Display the logging location.
     */
    public function display_option_debug_file() {
        $debug_file = $this->options['debug_file'];
        $this->display_input_text_field('debug_file', $debug_file);
    }

    /*
     * Describe the options used to configure the server.
     */
    public function describe_server_options() {
        echo '<p>The following options control access to the endpoint for clearing the cache.</p>';
    }

    /*
     * Display the server key field.
     */
    public function display_option_server_key() {
        $server_key = $this->options['server_key'];
        $this->display_input_text_field('server_key', $server_key);
?>
The secret key that clients must use to clear the cache on this blog, passed in the <code><?php esc_attr_e($this->plugin->query_var()); ?></code> parameter.
<?php
    }

    /*
     * Display the allowed IP regular expression field.
     */
    public function display_option_server_allowed_ip_regex() {
        $allowed_ip_regex = $this->options['server_allowed_ip_regex'];
        $this->display_input_text_field('server_allowed_ip_regex', $allowed_ip_regex);
?>
A <a href="http://www.php.net/manual/en/reference.pcre.pattern.syntax.php">regular expression</a> that client IP addresses must match to clear the cache.
<?php
    }

    /*
     * Display the checkbox for enabling deletion of transients.
     */
    public function display_option_server_delete_transients() {
        $delete_transients = (bool) $this->options['server_delete_transients'];
        $this->display_checkbox_field('server_delete_transients', $delete_transients);
?>
Turn this on to remove cached RSS and Atom feeds fetched by WordPress when a successful request is received.
<?php
    }

    /*
     * Describe the options used to configure the client.
     */
    public function describe_client_options() {
        echo '<p>The following options configure what URL to access when a post is published in this blog.</p>';
    }

    /*
     * Display the remote URL field.
     */
    public function display_option_client_remote_url() {
        $client_remote_url = $this->options['client_remote_url'];
        $this->display_input_text_field('client_remote_url', $client_remote_url);
?>
The URL of the remote instance on which to clear the cache.
<?php
    }

    /*
     * Display the client key field.
     */
    public function display_option_client_key() {
        $client_key = $this->options['client_key'];
        $this->display_input_text_field('client_key', $client_key);
?>
The key that the server requires for authentication, passed in the <code><?php esc_attr_e($this->plugin->query_var()); ?></code> parameter.
<?php
    }

    /*
     * Display a text input field.
     */
    private function display_input_text_field($name, $value, $size = 75) {
?>
<input type="text" name="<?php echo htmlspecialchars($this->group); ?>[<?php echo htmlspecialchars($name); ?>]" id="http_authentication_<?php echo htmlspecialchars($name); ?>" value="<?php echo htmlspecialchars($value) ?>" size="<?php echo htmlspecialchars($size); ?>" /><br />
<?php
    }

    /*
     * Display a checkbox field.
     */
    private function display_checkbox_field($name, $value) {
?>
<input type="checkbox" name="<?php echo htmlspecialchars($this->group); ?>[<?php echo htmlspecialchars($name); ?>]" id="http_authentication_<?php echo htmlspecialchars($name); ?>"<?php if ($value) echo ' checked="checked"' ?> value="1" /><br />
<?php
    }
}
?>
