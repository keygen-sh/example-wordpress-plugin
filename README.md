# Example WordPress Plugin
This is an example of a WordPress plugin that utilizes Keygen for licensing.
For this example, each license key will be locked to a specific domain (also
referred to as "node-locked" licensing).

> **This example plugin is not 100% production-ready**, but it should get
> you 90% of the way there. You may need to add additional error handling,
> machine activation (client-side or server-side), etc.

## Running the example

You will need to set up a local WordPress installation on `localhost` in
order for the license to be valid. Simply move the `example-plugin.php`
script into the `wp-content/plugins` folder and activate the plugin.

## Validating a license key

Navigate to the plugin's settings menu. You can use the license key "`wp-localhost-key`"
while on `localhost` to test a valid license. If you're not on `localhost`, an
error will be shown.

![image](https://user-images.githubusercontent.com/6979737/37048186-bc96c234-2132-11e8-81f1-b681cd69303c.png)

**Note:** This example assumes that you will create a new [machine resource](https://keygen.sh/docs/activating-machines/)
upon license creation, e.g. you require the customer to input the domain(s)
they will be using your plugin on during checkout. Otherwise, you will need to
set up client-side machine activation, or [host your own license activation server](https://github.com/keygen-sh/example-php-activation-server).

## Configuring a license policy

If you want to utilize your own Keygen account (this example plugin uses our
`demo` account), then visit [your dashboard](https://app.keygen.sh/policies)
and create a new policy with the following attributes:

```javascript
{
  requireFingerprintScope: true,
  maxMachines: 1,
  concurrent: false,
  floating: false,
  protected: true,
  strict: true
}
```

You can leave all other attributes to their defaults, but feel free to
modify them if needed for your particular licensing model, e.g. change
the `maxMachines` limit, set it to `floating = true`, etc.

## Questions?

Reach out at [support@keygen.sh](mailto:support@keygen.sh) if you have any
questions or concerns!
