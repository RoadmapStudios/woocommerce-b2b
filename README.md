# woocommerce-b2b


## Using WooCommerce B2B inside another plugin (Composer)

WooCommerce B2B can be bundled in a host plugin instead of being installed as a separate plugin.

```json
{
    "repositories": [ { "type": "vcs", "url": "https://github.com/RoadmapStudios/woocommerce-b2b" } ],
    "require": { "commercebird/woocommerce-b2b": "^4.2" }
}
```

In the host plugin's main file, at top level (not inside `plugins_loaded`, `init` or any other hook):

```php
require __DIR__ . '/vendor/autoload.php';
WCB2B_Package::boot( __FILE__ );
```

If `vendor/` is outside `wp-content/plugins` (for example a Bedrock root `vendor/`), pass the public URL of the package folder:

```php
WCB2B_Package::boot( __FILE__, array( 'url' => content_url( '/vendor/commercebird/woocommerce-b2b/' ) ) );
```

In the host plugin's `uninstall.php`, call `WCB2B_Package::uninstall();` if uninstalling the host should delete all B2B data. It does nothing while the standalone plugin or another host still runs B2B.

What the host plugin takes over in package mode:

- **Updates**: B2B has no update checker of its own. Update it with `composer update`.
- **Activation and dependencies**: B2B never deactivates the host. If WooCommerce is missing, B2B shows a notice and stays idle.
- **HPOS and Cart/Checkout blocks**: B2B declares compatibility for the host plugin file. If the host is not compatible, opt out with `add_filter( 'wcb2b_declare_wc_compatibility', '__return_false' );` and make the host's own declaration.
- **Plugins screen**: B2B adds no row links or Settings link. Settings stay at WooCommerce → Settings → B2B.

Detect B2B with `function_exists( 'WCB2B' )`. Don't check `is_plugin_active( 'woocommerce-b2b/woocommerce-b2b.php' )`, because that is false in package mode.

### Moving a site from the standalone plugin to a host plugin

All data (groups, prices, options, meta) is shared and carries over. If the standalone plugin is active, it always wins and the bundled copy shows a notice and steps aside. To switch:

1. Update the standalone plugin to **4.2.0 or later**. Older versions' `uninstall.php` deletes all B2B data when the plugin is deleted.
2. Activate the host plugin, then deactivate the standalone plugin.
3. Delete the standalone plugin. From 4.2.0, its uninstall keeps the data while a host runs B2B.
