=== WP Remote Cache Clear ===
Contributors: dwc
Tags: cache
Requires at least: 2.9
Tested up to: 3.2.1
Stable tag: 0.1

Clear a remote WordPress cache when a post is published.

== Description ==

This plugin allows you to clear the contents of the WordPress cache on a remote blog when you publish a post.

Both blogs must be running this plugin. The remote blog acts as a server, while your blog acts as a client. The client must provide a secret key that matches the one set on the server.

Use this for high-traffic sites that need caching but must also receive timely updates from an RSS feed on your blog.

To follow updates to this plugin, visit:

http://danieltwc.com/

For help with this version, visit:

http://danieltwc.com/2011/wp-remote-cache-clear-0-1/

== Installation ==

1. Login as an existing user, such as admin.
2. On the server, upload the `wp-remote-cache-clear` folder to your plugins folder, usually `wp-content/plugins`. (Or simply via the built-in installer.)
3. Activate the plugin on the Plugins screen.
4. Configure the secret key and IP address restrictions as desired.
5. On the side, upload the `wp-remote-cache-clear` folder to your plugins folder, usually `wp-content/plugins`. (Or simply via the built-in installer.)
6. Activate the plugin on the Plugins screen.
7. Configure the URL of the server blog and the secret key.
8. Publish a post.

== Frequently Asked Questions ==

= I'm having trouble getting this to work. How can I debug it? =

Make sure the secret keys match, being mindful of whitespace. Also, make sure that the IP address that your blog is using to connect to the remote blog is what you expect.

It might be helpful to check your server access logs.

= Does this plugin support multisite (WordPress MU) setups? =

Yes, you can enable this plugin across a network or on individual sites. However, options will need to be set on individual sites.

If you have suggestions on how to improve network support, please submit a comment.

== Screenshots ==

1. Default plugin options

== Changelog ==

= 0.1 =
Initial version
