<?php
/**
 * Plugin Name: Example Plugin
 * Plugin URI: https://github.com/keygen-sh/example-wordpress-plugin
 * Description: This is an example WP plugin that utilizes Keygen for licensing.
 * Version: 1.0.0
 * Author: Keygen
 * Author URI: https://keygen.sh
 * License: GPL
 */
namespace Keygen;

if (!defined("ABSPATH")) {
  exit; // Exit if accessed directly
}

class ExamplePlugin {
  // Replace this with your Keygen account's slug or ID.
  // Available at: https://app.keygen.sh/settings
  const KEYGEN_ACCOUNT = 'demo';

  private $license;

  public function __construct() {
    // Get the current validation status from the database. What you do with
    // this information is up to you, e.g. disallow access after the expiry,
    // utilize it only for support/updates, display a notice, etc.
    $this->license = get_option('ex_license');
  }

  // Add our WP admin hooks.
  public function load() {
    add_action('admin_menu', [$this, 'add_plugin_options_page']);
    add_action('admin_init', [$this, 'add_plugin_settings']);
  }

  // Add our plugin's option page to the WP admin menu.
  public function add_plugin_options_page() {
    add_options_page(
      'Example Plugin Settings',
      'Example Plugin Settings',
      'manage_options',
      'ex',
      [$this, 'render_admin_page']
    );
  }

  // Render our plugin's option page.
  public function render_admin_page() {
    ?>
    <div class="wrap">
      <h1>Example Plugin Settings</h1>
      <form method="post" action="options.php">
        <?php
        settings_fields('ex');
        do_settings_sections('ex');
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  // Initialize our plugin's settings.
  public function add_plugin_settings() {
    register_setting('ex', 'ex_license', [$this, 'license_key_callback']);

    add_settings_section(
      'ex_settings',
      'Licensing Information',
      [$this, 'render_licensing_instructions'],
      'ex'
    );

    add_settings_field(
      'ex_license',
      'License Key',
      [$this, 'render_license_key_field'],
      'ex',
      'ex_settings'
    );
  }

  // Render instructions for our plugin's licensing section.
  public function render_licensing_instructions() {
    print 'Enter your licensing information below:';
  }

  // Render the license key field.
  public function render_license_key_field() {
    printf(
      '<input type="text" id="key" name="ex_license[key]" value="%s" />',
      isset($this->license['key']) ? esc_attr($this->license['key']) : ''
    );

    if (isset($this->license['status'])) {
      printf(
        '&nbsp;<span class="description">License %s</span>',
        isset($this->license['status']) ? esc_attr($this->license['status']) : 'is missing'
      );
    }
  }

  // Sanitize input from our plugin's option form and validate the provided key.
  public function license_key_callback($options) {
    if (!isset($options['key'])) {
      add_settings_error('ex_license', esc_attr('settings_updated'), 'License key is required', 'error');

      return;
    }

    // Detect multiple sanitizing passes.
    // Workaround for: https://core.trac.wordpress.org/ticket/21989
    static $cache = null;

    if ($cache !== null) {
      return $cache;
    }

    // Get the current domain. This example validates keys against a node-locked
    // license policy, allowing us to lock our plugin to a specific domain. If
    // you don't want to do that, simple remove the domain part of the plugin's
    // license validation flow.
    $domain = parse_url(get_bloginfo('url'), PHP_URL_HOST);

    // Validate the license key within the scope of the current domain.
    $key = sanitize_text_field($options['key']);
    $res = $this->validate_license_key($key, $domain);

    if (isset($res->errors)) {
      $error = $res->errors[0];
      $msg = "{$error->title}: {$error->detail}";

      if (isset($error->source)) {
        $msg = "{$error->title}: {$error->source->pointer} {$error->detail}";
      }

      add_settings_error('ex_license', esc_attr('settings_updated'), $msg, 'error');
    }

    if (!$res->meta->valid) {
      switch ($res->meta->constant) {
        // When the license has been activated, but the current domain is not
        // associated with it, return an error.
        case 'FINGERPRINT_SCOPE_MISMATCH': {
          add_settings_error('ex_license', esc_attr('settings_updated'), 'License is not valid on the current domain', 'error');

          break;
        }
        // When the license has not been activated yet, return an error. This
        // shouldn't happen, since we should be activating the customer's domain
        // upon purchase - around the time we create their license.
        case 'NO_MACHINES':
        case 'NO_MACHINE': {
          add_settings_error('ex_license', esc_attr('settings_updated'), 'License has not been activated', 'error');

          break;
        }
        // When the license key does not exist, return an error.
        case 'NOT_FOUND': {
          add_settings_error('ex_license', esc_attr('settings_updated'), 'License key was not found', 'error');

          break;
        }
        // You may want to handle more statuses, depending on your license requirements.
        // See: https://keygen.sh/docs/api/#licenses-actions-validate-key-constants.
        default: {
          add_settings_error('ex_license', esc_attr('settings_updated'), "Unhandled error: {$res->meta->detail} ({$res->meta->detail})", 'error');

          break;
        }
      }

      // Clear any options that were previously stored in the database.
      return [];
    }

    // Save result to local cache.
    $cache = [
      'policy' => $res->data->relationships->policy->data->id,
      'key' => $res->data->attributes->key,
      'expiry' => $res->data->attributes->expiry,
      'valid' => $res->meta->valid,
      'status' => $res->meta->detail,
      'domain' => $domain
    ];

    return $cache;
  }

  // Validate the provided license key within the scope of the current domain. This
  // sends a JSON request to Keygen's API, but this could also be your own server
  // which you're using to handle licensing and activation, e.g. something like
  // https://github.com/keygen-sh/example-php-activation-server.
  private function validate_license_key($key, $domain) {
    $res = wp_remote_post('https://api.keygen.sh/v1/accounts/' . self::KEYGEN_ACCOUNT . '/licenses/actions/validate-key', [
      'headers' => [
        'Content-Type' => 'application/vnd.api+json',
        'Accept' => 'application/vnd.api+json'
      ],
      'body' => json_encode([
        'meta' => [
          'scope' => ['fingerprint' => $domain],
          'key' => $key
        ]
      ])
    ]);

    return json_decode($res['body']);
  }
}

// Load our plugin within the WP admin dashboard.
if (is_admin()) {
  $plugin = new ExamplePlugin();
  $plugin->load();
}