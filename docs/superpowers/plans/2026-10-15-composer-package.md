# WooCommerce B2B as a Composer Package — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Make WooCommerce B2B installable with Composer and bootable from inside another ("host") plugin with one call, `WCB2B_Package::boot( __FILE__ )`, while the standalone plugin keeps working exactly as before.

**Architecture:** A new loader class, `WCB2B_Package`, becomes the only entry point. It defines the path/URL/mode constants and then loads the existing `WooCommerceB2B` core. It runs in one of two modes. In `plugin` mode, `woocommerce-b2b.php` calls `boot_standalone()` and behaviour stays as it is today. In `package` mode, the host plugin calls `boot()`. The core then hands plugin-lifecycle concerns to the host: updates, activation/deactivation, self-deactivation when WooCommerce is missing, plugin-row links and uninstall. Asset URLs come from the package directory, not the plugin file. Everything else (option and meta keys, hooks, template override folder, block field IDs) stays unchanged, so data carries over when a site moves from the standalone plugin to a host plugin.

**Tech Stack:** PHP ≥ 7.4, WordPress ≥ 5.0, WooCommerce ≥ 8.0, Composer 2, PHPUnit 9.6 + Brain Monkey 2.6 (unit tests, no WordPress install needed).

**Spec:** None exists. The requirements are the user's request ("convert this WooCommerce plugin to a vendor package to be loaded by another plugin") plus the Global Constraints below.

## Global Constraints

- Composer package name: `commercebird/woocommerce-b2b`, `"type": "library"`, license `GPL-3.0-or-later`.
- Minimum PHP stays `>=7.4`: no `str_contains`, `match`, typed properties, constructor promotion or `?->` in shipped code (tests may use 7.4 syntax).
- The standalone plugin must keep working with no Composer `vendor/` folder present.
- Do not rename options, post/user/term meta keys, hook names, the text domain `woocommerce-b2b`, the template override folder `WCB2B_OVERRIDES = 'woocommerce-b2b'` or the block checkout field IDs `woocommerce-b2b/vat-number` and `woocommerce-b2b/invoice-email`.
- Keep the existing global API: `WCB2B()`, `$GLOBALS['wcb2b']`, the class names `WooCommerceB2B` and `WCB2B_*`, and the constants `WCB2B_PLUGIN_FILE`, `WCB2B_PLUGIN_URI`, `WCB2B_ABSPATH`, `WCB2B_VERSION`.
- New constants: `WCB2B_MODE` (`'plugin'` or `'package'`) and `WCB2B_PACKAGE_DIR` (package root, trailing slash).
- In package mode, `WCB2B_PLUGIN_FILE` is the **host** plugin's main file. WooCommerce's HPOS/blocks compatibility declarations and `plugin_basename()` lookups need a real plugin file.
- When the standalone plugin `woocommerce-b2b/woocommerce-b2b.php` is active, it always wins and any bundled copy steps aside.
- Follow the existing code style: WordPress spacing `( $x )`, `array()` and not `[]` in shipped code, a docblock on every method, `defined( 'ABSPATH' ) || exit;` at the top of every PHP file.
- New version: `4.2.0`.

## Review Focus

1. **Deleting the standalone plugin after moving to a host plugin wipes all B2B data.** Expected: when a host plugin still runs the package, the standalone `uninstall.php` does nothing. Pinned by `test_standalone_uninstall_keeps_data_while_package_runs` (Task 5). Releases ≤ 4.1.0 have no such guard, so the README warns about it.
2. **Standalone plugin and host bundle both active, in either load order.** Expected: no "Cannot declare class" fatal, one copy runs, and an admin notice names both. Pinned by `test_boot_is_refused_when_standalone_plugin_active`, `test_boot_is_refused_when_core_class_already_declared` and `test_second_boot_is_refused_with_notice` (Task 1).
3. **Host calls `boot()` from inside `plugins_loaded` or `init`.** The core's `plugins_loaded` callbacks at priority -1 and 10 would silently never run. Expected: boot refuses and calls `_doing_it_wrong`. Pinned by `test_boot_after_plugins_loaded_is_doing_it_wrong` (Task 1).
4. **WooCommerce inactive in package mode.** Today the core calls `deactivate_plugins( WCB2B_PLUGIN_FILE )`, which would deactivate the host plugin. Expected: show a notice only. Pinned by `test_package_mode_without_woocommerce_does_not_deactivate_host` (Task 2).
5. **The Envato update checker hijacks the host plugin's update row.** It writes `$transient->response[ plugin_basename( WCB2B_PLUGIN_FILE ) ]`, which is the host's key in package mode. Decision (user, 2026-10-15): remove the Envato update checker entirely, in both modes. The standalone plugin gets an `Update URI` header instead, so WordPress.org never offers a same-slug update. Pinned by `test_package_mode_skips_plugin_row_hooks` and `test_envato_update_checker_is_gone` (Task 2).

---

## File Structure

| File | Status | Responsibility |
|---|---|---|
| `composer.json` | Create | Package metadata, autoloads only the loader class, dev deps |
| `phpunit.xml.dist` | Create | Unit test suite config |
| `includes/class-wcb2b-package.php` | Create | `WCB2B_Package`: mode, constants, boot guards, conflict notice, uninstall entry |
| `includes/wcb2b-uninstall.php` | Create | `wcb2b_uninstall()`: data wipe shared by standalone and host |
| `tests/bootstrap.php` | Create | Defines `ABSPATH`, loads Composer autoload |
| `tests/Unit/WCB2B_TestCase.php` | Create | Brain Monkey base test case with common WP stubs |
| `tests/Unit/PackageBootTest.php` | Create | Loader tests (Task 1) |
| `tests/Unit/CoreModeTest.php` | Create | Mode-aware core tests (Task 2) |
| `tests/Unit/AssetUrlTest.php` | Create | Asset URL tests (Task 3) |
| `tests/Unit/DebuggerTest.php` | Create | MU debugger tests (Task 4) |
| `tests/Unit/UninstallTest.php` | Create | Uninstall tests (Task 5) |
| `tests/stubs/OrderUtil.php` | Create | Fake `Automattic\WooCommerce\Utilities\OrderUtil` |
| `tests/stubs/FakeWpdb.php` | Create | Records `$wpdb->query()` calls |
| `tests/fixtures/wcb2b-host/*` | Create | Throwaway host plugin for the manual smoke test |
| `woocommerce-b2b.php` | Modify | Becomes a thin standalone entry: require the loader, `boot_standalone( __FILE__ )` |
| `includes/classes/class-wcb2b.php` | Modify | Mode-aware lifecycle; hosts the `WCB2B()` function |
| `includes/classes/class-wcb2b-admin.php`, `class-wcb2b-frontend.php` | Modify | `plugins_url( …, WCB2B_PLUGIN_FILE )` → `WCB2B_PLUGIN_URI . …` |
| `src/Blocks/Payments/Integrations/*.php`, `includes/classes/class-wcb2b-upgrade.php` | Modify | Drop the leading `/` after `WCB2B_PLUGIN_URI` |
| `includes/classes/class-wcb2b-debug.php`, `src/mu-debugger.php` | Modify | Debug mode keeps the *actual* owning plugin active |
| `uninstall.php` | Modify | Guard, then delegate to `wcb2b_uninstall()` |
| `.gitattributes`, `.gitignore`, `README.md` | Modify | Dist excludes, vendor ignore, consumer docs |

---

### Task 1: `WCB2B_Package` loader + Composer + test harness

**Files:**
- Create: `composer.json`, `phpunit.xml.dist`, `tests/bootstrap.php`, `tests/Unit/WCB2B_TestCase.php`, `tests/Unit/PackageBootTest.php`, `includes/class-wcb2b-package.php`
- Modify: `woocommerce-b2b.php` (whole file), `includes/classes/class-wcb2b.php` (append `WCB2B()` after the class), `.gitignore`

**Interfaces:**
- Produces:
  - `WCB2B_Package::boot( string $plugin_file, array $args = array() ): bool`. `$args['url']` optionally overrides the package base URL.
  - `WCB2B_Package::boot_standalone( string $plugin_file ): bool`
  - `WCB2B_Package::dir(): string`. Package root with a trailing `/`.
  - `WCB2B_Package::uninstall(): void`. Its body is completed in Task 5.
  - `WCB2B_Package::already_loaded_notice(): void`
  - Constants `WCB2B_Package::MODE_PLUGIN = 'plugin'`, `MODE_PACKAGE = 'package'`, `STANDALONE_PLUGIN = 'woocommerce-b2b/woocommerce-b2b.php'`
  - Defines `WCB2B_MODE`, `WCB2B_PLUGIN_FILE`, `WCB2B_PACKAGE_DIR` and `WCB2B_PLUGIN_URI` (always with a trailing slash). The URL is filterable via `wcb2b_package_url`.
  - Test helpers on `WCB2B_TestCase`: `$this->options` (backs `get_option()`), `set_active_plugins( array )`, `activate_woocommerce()`, `stub_plugins_url()`, `static package_dir(): string`

- [ ] **Step 1: Add Composer and the test harness**

`composer.json`:
```json
{
    "name": "commercebird/woocommerce-b2b",
    "description": "Transform your WooCommerce into Business-to-Business shop for wholesale. Runs as a standalone plugin or bundled inside another plugin.",
    "type": "library",
    "license": "GPL-3.0-or-later",
    "homepage": "https://commercebird.com/",
    "require": {
        "php": ">=7.4"
    },
    "require-dev": {
        "brain/monkey": "^2.6",
        "phpunit/phpunit": "^9.6"
    },
    "autoload": {
        "classmap": [ "includes/class-wcb2b-package.php" ]
    },
    "autoload-dev": {
        "classmap": [ "tests/Unit/WCB2B_TestCase.php", "tests/stubs/" ]
    },
    "scripts": {
        "test": "phpunit"
    },
    "config": {
        "sort-packages": true
    }
}
```

Only the loader is autoloaded, on purpose. Every file in `includes/classes/` ends in `return new WCB2B_X();`, so autoloading those files would instantiate them as a side effect.

`phpunit.xml.dist`:
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="tests/bootstrap.php" colors="true">
    <testsuites>
        <testsuite name="unit">
            <directory>tests/Unit</directory>
        </testsuite>
    </testsuites>
</phpunit>
```

`tests/bootstrap.php`:
```php
<?php

define( 'ABSPATH', __DIR__ . '/wordpress/' );

require dirname( __DIR__ ) . '/vendor/autoload.php';
```

`tests/Unit/WCB2B_TestCase.php`:
```php
<?php

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Base test case: Brain Monkey plus the WordPress functions the core touches while booting
 */
abstract class WCB2B_TestCase extends \PHPUnit\Framework\TestCase {

    /**
     * Values returned by the get_option() stub
     *
     * @var array
     */
    protected $options = array( 'active_plugins' => array() );

    protected function setUp(): void {
        parent::setUp();
        Monkey\setUp();
        Functions\stubTranslationFunctions();
        Functions\stubEscapeFunctions();
        Functions\when( 'trailingslashit' )->alias( function ( $value ) {
            return rtrim( $value, '/\\' ) . '/';
        } );
        Functions\when( 'plugin_basename' )->alias( function ( $file ) {
            return basename( dirname( $file ) ) . '/' . basename( $file );
        } );
        Functions\when( 'register_activation_hook' )->justReturn( null );
        Functions\when( 'register_deactivation_hook' )->justReturn( null );
        Functions\when( 'is_admin' )->justReturn( true );
        Functions\when( 'get_site_option' )->justReturn( array() );
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
        } );
    }

    protected function tearDown(): void {
        Monkey\tearDown();
        parent::tearDown();
    }

    protected function stub_plugins_url() {
        Functions\when( 'plugins_url' )->justReturn( 'https://example.test/plugins-url/' );
    }

    protected function set_active_plugins( array $plugins ) {
        $this->options['active_plugins'] = $plugins;
    }

    protected function activate_woocommerce() {
        $this->set_active_plugins( array( 'woocommerce/woocommerce.php' ) );
    }

    protected static function package_dir() {
        return dirname( __DIR__, 2 ) . '/';
    }
}
```

Create an empty `tests/stubs/` directory with a `.gitkeep` file so the classmap path exists. Append to `.gitignore`:
```
# Composer
/vendor/
/composer.lock
```

Run: `composer install`
Expected: installs `brain/monkey` and `phpunit/phpunit`. A "Could not scan for classes inside includes/class-wcb2b-package.php" warning is expected until Step 3.

- [ ] **Step 2: Write the failing loader tests**

`tests/Unit/PackageBootTest.php`:
```php
<?php

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Brain\Monkey\Functions;

/**
 * Constants and static state survive between tests, so each test runs in its own process
 *
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class PackageBootTest extends WCB2B_TestCase {

    const HOST = '/sites/wp/wp-content/plugins/host/host.php';

    public function test_boot_defines_package_constants() {
        Functions\expect( 'plugins_url' )
            ->once()
            ->with( '/', WCB2B_Package::dir() . 'woocommerce-b2b.php' )
            ->andReturn( 'https://example.test/plugins-url' );

        $this->assertTrue( WCB2B_Package::boot( self::HOST ) );

        $this->assertSame( WCB2B_Package::MODE_PACKAGE, WCB2B_MODE );
        $this->assertSame( self::HOST, WCB2B_PLUGIN_FILE );
        $this->assertSame( WCB2B_Package::dir(), WCB2B_PACKAGE_DIR );
        $this->assertFileExists( WCB2B_PACKAGE_DIR . 'includes/classes/class-wcb2b.php' );
        $this->assertSame( 'https://example.test/plugins-url/', WCB2B_PLUGIN_URI );
        $this->assertInstanceOf( WooCommerceB2B::class, $GLOBALS['wcb2b'] );
        $this->assertSame( $GLOBALS['wcb2b'], WCB2B() );
    }

    public function test_boot_uses_url_argument() {
        WCB2B_Package::boot( self::HOST, array( 'url' => 'https://cdn.test/b2b' ) );

        $this->assertSame( 'https://cdn.test/b2b/', WCB2B_PLUGIN_URI );
    }

    public function test_package_url_filter_overrides_url() {
        $this->stub_plugins_url();
        Filters\expectApplied( 'wcb2b_package_url' )->once()->andReturn( 'https://filtered.test/b2b' );

        WCB2B_Package::boot( self::HOST );

        $this->assertSame( 'https://filtered.test/b2b/', WCB2B_PLUGIN_URI );
    }

    public function test_boot_standalone_sets_plugin_mode() {
        $this->stub_plugins_url();
        $file = self::package_dir() . 'woocommerce-b2b.php';

        $this->assertTrue( WCB2B_Package::boot_standalone( $file ) );

        $this->assertSame( WCB2B_Package::MODE_PLUGIN, WCB2B_MODE );
        $this->assertSame( $file, WCB2B_PLUGIN_FILE );
    }

    public function test_standalone_entry_file_boots_through_loader() {
        $this->stub_plugins_url();

        include self::package_dir() . 'woocommerce-b2b.php';

        $this->assertSame( WCB2B_Package::MODE_PLUGIN, WCB2B_MODE );
        $this->assertInstanceOf( WooCommerceB2B::class, WCB2B() );
    }

    public function test_second_boot_is_refused_with_notice() {
        $this->stub_plugins_url();

        $this->assertTrue( WCB2B_Package::boot( '/wp/plugins/host-a/host-a.php' ) );
        $this->assertFalse( WCB2B_Package::boot( '/wp/plugins/host-b/host-b.php' ) );

        $this->assertNotFalse( has_action( 'admin_notices', array( WCB2B_Package::class, 'already_loaded_notice' ) ) );
        $this->expectOutputRegex( '#host-a/host-a\.php.*host-b/host-b\.php#s' );
        WCB2B_Package::already_loaded_notice();
    }

    public function test_boot_is_refused_when_standalone_plugin_active() {
        $this->set_active_plugins( array( WCB2B_Package::STANDALONE_PLUGIN ) );

        $this->assertFalse( WCB2B_Package::boot( self::HOST ) );

        $this->assertFalse( defined( 'WCB2B_MODE' ) );
        $this->expectOutputRegex( '#woocommerce-b2b/woocommerce-b2b\.php.*host/host\.php#s' );
        WCB2B_Package::already_loaded_notice();
    }

    public function test_boot_is_refused_when_core_class_already_declared() {
        // Simulates a pre-4.2.0 standalone copy that loaded first
        eval( 'final class WooCommerceB2B {}' );

        $this->assertFalse( WCB2B_Package::boot( self::HOST ) );
        $this->assertFalse( defined( 'WCB2B_MODE' ) );
    }

    public function test_boot_after_plugins_loaded_is_doing_it_wrong() {
        do_action( 'plugins_loaded' );
        Functions\expect( '_doing_it_wrong' )->once();

        $this->assertFalse( WCB2B_Package::boot( self::HOST ) );
        $this->assertFalse( defined( 'WCB2B_MODE' ) );
    }
}
```

- [ ] **Step 3: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Unit/PackageBootTest.php`
Expected: FAIL / errors with `Class "WCB2B_Package" not found`.

- [ ] **Step 4: Implement the loader**

`includes/class-wcb2b-package.php`:
```php
<?php

/**
 * WooCommerce B2B package loader
 *
 * Boots WooCommerce B2B either as a standalone plugin or as a Composer
 * package bundled inside another (host) plugin
 *
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Package Class
 */
final class WCB2B_Package {

    const MODE_PLUGIN       = 'plugin';
    const MODE_PACKAGE      = 'package';
    const STANDALONE_PLUGIN = 'woocommerce-b2b/woocommerce-b2b.php';

    /**
     * Plugin file that booted WooCommerce B2B
     *
     * @var string|null
     */
    private static $booted_by = null;

    /**
     * Plugin files whose boot was refused
     *
     * @var string[]
     */
    private static $refused = array();

    /**
     * Boot WooCommerce B2B from a host plugin
     *
     * Must be called while the host plugin main file loads, not inside a hook
     *
     * @param string $plugin_file Host plugin main file (__FILE__)
     * @param array $args Optional: 'url' => package base URL when vendor/ lives outside wp-content/plugins
     * @return bool True if this call booted WooCommerce B2B
     */
    public static function boot( $plugin_file, array $args = array() ) {
        // The standalone plugin always wins when active
        if ( self::standalone_is_active() ) {
            return self::refuse( $plugin_file );
        }
        return self::start( $plugin_file, self::MODE_PACKAGE, $args );
    }

    /**
     * Boot WooCommerce B2B as the standalone plugin
     *
     * @param string $plugin_file Standalone plugin main file
     * @return bool
     */
    public static function boot_standalone( $plugin_file ) {
        return self::start( $plugin_file, self::MODE_PLUGIN, array() );
    }

    /**
     * Delete all WooCommerce B2B data, for host plugins' uninstall.php
     */
    public static function uninstall() {
    }

    /**
     * Package root directory
     *
     * @return string
     */
    public static function dir() {
        return dirname( __DIR__ ) . '/';
    }

    /**
     * Admin notice shown when a copy of WooCommerce B2B was skipped
     */
    public static function already_loaded_notice() {
        printf( '<div class="error"><p>%s</p></div>',
            sprintf( esc_html__( 'WooCommerce B2B is already loaded by %1$s, so the copy in %2$s was skipped. Keep only one of them active.', 'woocommerce-b2b' ),
                '<code>' . esc_html( self::loaded_by() ) . '</code>',
                '<code>' . esc_html( implode( ', ', array_map( 'plugin_basename', self::$refused ) ) ) . '</code>'
            )
        );
    }

    /**
     * Define constants and load the core
     *
     * @param string $plugin_file Plugin main file
     * @param string $mode Boot mode
     * @param array $args Boot arguments
     * @return bool
     */
    private static function start( $plugin_file, $mode, array $args ) {
        if ( null !== self::$booted_by || class_exists( 'WooCommerceB2B', false ) ) {
            return self::refuse( $plugin_file );
        }
        if ( did_action( 'plugins_loaded' ) || doing_action( 'plugins_loaded' ) ) {
            _doing_it_wrong( __METHOD__, 'WooCommerce B2B must be booted while the plugin file loads, before plugins_loaded.', '4.2.0' );
            return false;
        }
        self::$booted_by = $plugin_file;

        $url = isset( $args['url'] ) ? $args['url'] : plugins_url( '/', self::dir() . 'woocommerce-b2b.php' );

        defined( 'WCB2B_MODE' ) || define( 'WCB2B_MODE', $mode );
        defined( 'WCB2B_PLUGIN_FILE' ) || define( 'WCB2B_PLUGIN_FILE', $plugin_file );
        defined( 'WCB2B_PACKAGE_DIR' ) || define( 'WCB2B_PACKAGE_DIR', self::dir() );
        defined( 'WCB2B_PLUGIN_URI' ) || define( 'WCB2B_PLUGIN_URI', trailingslashit( apply_filters( 'wcb2b_package_url', $url ) ) );

        include_once self::dir() . 'includes/classes/class-wcb2b.php';

        // Global for backwards compatibility
        $GLOBALS['wcb2b'] = WCB2B();

        return true;
    }

    /**
     * Record a refused boot and warn the admin once
     *
     * @param string $plugin_file Plugin main file
     * @return bool Always false
     */
    private static function refuse( $plugin_file ) {
        self::$refused[] = $plugin_file;
        if ( 1 === count( self::$refused ) ) {
            add_action( 'admin_notices', array( __CLASS__, 'already_loaded_notice' ) );
        }
        return false;
    }

    /**
     * Whether the standalone WooCommerce B2B plugin is active
     *
     * @return bool
     */
    private static function standalone_is_active() {
        return array_key_exists( self::STANDALONE_PLUGIN, (array) get_site_option( 'active_sitewide_plugins', array() ) )
            || in_array( self::STANDALONE_PLUGIN, (array) get_option( 'active_plugins', array() ), true );
    }

    /**
     * Plugin basename of the copy that runs WooCommerce B2B
     *
     * @return string
     */
    private static function loaded_by() {
        return null !== self::$booted_by ? plugin_basename( self::$booted_by ) : self::STANDALONE_PLUGIN;
    }

}
```

Replace the body of `woocommerce-b2b.php` below the plugin header (keep the header comment block exactly as it is):
```php
// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

// A host plugin may already have autoloaded the loader
if ( ! class_exists( 'WCB2B_Package', false ) ) {
    require_once __DIR__ . '/includes/class-wcb2b-package.php';
}

WCB2B_Package::boot_standalone( __FILE__ );
```

Append to the end of `includes/classes/class-wcb2b.php`, after the closing `}` of the class:
```php

if ( ! function_exists( 'WCB2B' ) ) {

    /**
     * Returns the main instance of WCB2B
     *
     * @return WooCommerceB2B
     */
    function WCB2B() {
        return WooCommerceB2B::instance();
    }

}
```

Run: `composer dump-autoload`

- [ ] **Step 5: Run the tests to verify they pass**

Run: `php vendor/bin/phpunit tests/Unit/PackageBootTest.php`
Expected: PASS, 9 tests.

- [ ] **Step 6: Lint and commit**

Run: `git ls-files -co --exclude-standard '*.php' | xargs -n1 php -l | grep -v '^No syntax errors'`
Expected: no output.

```bash
git add composer.json phpunit.xml.dist .gitignore tests/ includes/class-wcb2b-package.php includes/classes/class-wcb2b.php woocommerce-b2b.php
git commit -m "feat: add WCB2B_Package loader so WooCommerce B2B can boot from a host plugin"
```

---

### Task 2: Mode-aware core lifecycle + Envato update checker removal

**Files:**
- Delete: `includes/classes/class-wcb2b-upgrade.php` (Envato update checker, including its hard-coded API token)
- Modify: `woocommerce-b2b.php` header (add `Update URI: https://commercebird.com/`)
- Modify: `includes/classes/class-wcb2b.php`: `$envato_id` property, `__construct()` (lines 77-87), `define_constants()` (91-96, drop `WCB2B_ENVATO_ID`), `includes()` (101-129, drop the upgrade include), `init_hooks()` (133-149), `load_plugin_textdomain()` (292-294), `dependencies_notice()` (354-362)
- Test: `tests/Unit/CoreModeTest.php`

**Interfaces:**
- Consumes: `WCB2B_Package::boot()`, `boot_standalone()`, `MODE_PACKAGE`, `WCB2B_PACKAGE_DIR`, `WCB2B_TestCase` helpers (Task 1)
- Produces: `WooCommerceB2B::is_package(): bool` (public). `WCB2B_ABSPATH` is now `WCB2B_PACKAGE_DIR`.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/CoreModeTest.php`:
```php
<?php

use Brain\Monkey\Functions;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class CoreModeTest extends WCB2B_TestCase {

    const HOST = '/sites/wp/wp-content/plugins/host/host.php';

    public function test_package_mode_without_woocommerce_does_not_deactivate_host() {
        $this->stub_plugins_url();

        WCB2B_Package::boot( self::HOST );

        $this->assertTrue( WCB2B()->is_package() );
        $this->assertFalse( has_action( 'admin_init', array( WCB2B(), 'dependencies_disable' ) ) );
        $this->assertNotFalse( has_action( 'admin_notices', array( WCB2B(), 'dependencies_notice' ) ) );
    }

    public function test_plugin_mode_without_woocommerce_deactivates_itself() {
        $this->stub_plugins_url();

        WCB2B_Package::boot_standalone( self::package_dir() . 'woocommerce-b2b.php' );

        $this->assertFalse( WCB2B()->is_package() );
        $this->assertNotFalse( has_action( 'admin_init', array( WCB2B(), 'dependencies_disable' ) ) );
    }

    public function test_package_mode_skips_plugin_row_hooks() {
        $this->stub_plugins_url();
        $this->activate_woocommerce();

        WCB2B_Package::boot( self::HOST );

        $this->assertSame( WCB2B_PACKAGE_DIR, WCB2B_ABSPATH );
        $this->assertFalse( has_filter( 'plugin_row_meta', array( WCB2B(), 'plugin_row_meta' ) ) );
        $this->assertFalse( has_filter( 'plugin_action_links_host/host.php' ) );
    }

    public function test_plugin_mode_keeps_plugin_row_hooks() {
        $this->stub_plugins_url();
        $this->activate_woocommerce();

        WCB2B_Package::boot_standalone( self::package_dir() . 'woocommerce-b2b.php' );

        $this->assertNotFalse( has_filter( 'plugin_row_meta', array( WCB2B(), 'plugin_row_meta' ) ) );
    }

    public function test_envato_update_checker_is_gone() {
        $this->stub_plugins_url();
        $this->activate_woocommerce();

        WCB2B_Package::boot_standalone( self::package_dir() . 'woocommerce-b2b.php' );

        $this->assertFalse( class_exists( 'WCB2B_Upgrade', false ) );
        $this->assertFalse( has_filter( 'pre_set_site_transient_update_plugins' ) );
        $this->assertFalse( has_filter( 'plugins_api' ) );
        $this->assertFalse( defined( 'WCB2B_ENVATO_ID' ) );
        $this->assertFileDoesNotExist( self::package_dir() . 'includes/classes/class-wcb2b-upgrade.php' );
        $this->assertMatchesRegularExpression( '/^\s*Update URI:\s*\S+/m', file_get_contents( self::package_dir() . 'woocommerce-b2b.php' ) );
    }

    public function test_package_mode_loads_bundled_translation() {
        define( 'WP_LANG_DIR', '/sites/wp/wp-content/languages' );
        $this->stub_plugins_url();
        $this->activate_woocommerce();
        Functions\when( 'determine_locale' )->justReturn( 'it_IT' );
        WCB2B_Package::boot( self::HOST );

        Functions\expect( 'load_textdomain' )->once()->ordered()
            ->with( 'woocommerce-b2b', '/sites/wp/wp-content/languages/plugins/woocommerce-b2b-it_IT.mo' );
        Functions\expect( 'load_textdomain' )->once()->ordered()
            ->with( 'woocommerce-b2b', WCB2B_PACKAGE_DIR . 'i18n/woocommerce-b2b-it_IT.mo' );

        WCB2B()->load_plugin_textdomain();
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Unit/CoreModeTest.php`
Expected: FAIL with `Call to undefined method WooCommerceB2B::is_package()`.

- [ ] **Step 3: Implement**

In `includes/classes/class-wcb2b.php`:

`__construct()`: replace the dependency branch so it reads:
```php
        if ( ! array_key_exists( 'woocommerce/woocommerce.php', get_site_option( 'active_sitewide_plugins', array() ) ) && ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            add_action( 'admin_notices', array( $this, 'dependencies_notice' ) );
            // Never deactivate a host plugin, it owns its own lifecycle
            if ( ! $this->is_package() ) {
                add_action( 'admin_init', array( $this, 'dependencies_disable' ) );
            }
            return false;
        }
```

`define_constants()`: change the first line to:
```php
        $this->define( 'WCB2B_ABSPATH', WCB2B_PACKAGE_DIR );
```

`includes()`: delete the `/* Upgrades */` comment and its `include_once … class-wcb2b-upgrade.php` line. Then `git rm includes/classes/class-wcb2b-upgrade.php`. Delete the `$envato_id` property and its docblock, and the `$this->define( 'WCB2B_ENVATO_ID', … )` line in `define_constants()`.

`woocommerce-b2b.php` header: add `Update URI: https://commercebird.com/` after the `License URI:` line. WordPress 5.8+ then never matches this plugin against a WordPress.org plugin with the same slug. Before this change, `WCB2B_Upgrade::skip_plugin_update_check()` did that job.

`init_hooks()`: replace the first two lines and the `plugin_row_meta` / `plugin_action_links_` filters with one guarded block at the top of the method:
```php
        // Plugin lifecycle and plugins screen links belong to the host in package mode
        if ( ! $this->is_package() ) {
            register_activation_hook( WCB2B_PLUGIN_FILE, array( $this, 'activation' ) );
            register_deactivation_hook( WCB2B_PLUGIN_FILE, array( $this, 'deactivation' ) );
            add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 2 );
            add_filter( 'plugin_action_links_' . plugin_basename( WCB2B_PLUGIN_FILE ), array( $this, 'settings_link' ) );
        }
```
Leave every other hook in `init_hooks()` as it is.

`load_plugin_textdomain()`:
```php
    public function load_plugin_textdomain() {
        if ( $this->is_package() ) {
            // load_plugin_textdomain() resolves paths relative to WP_PLUGIN_DIR, which vendor/ may be outside of
            $locale = apply_filters( 'plugin_locale', determine_locale(), 'woocommerce-b2b' );
            load_textdomain( 'woocommerce-b2b', WP_LANG_DIR . '/plugins/woocommerce-b2b-' . $locale . '.mo' );
            load_textdomain( 'woocommerce-b2b', WCB2B_ABSPATH . 'i18n/woocommerce-b2b-' . $locale . '.mo' );
            return;
        }
        load_plugin_textdomain( 'woocommerce-b2b', false, plugin_basename( dirname( WCB2B_PLUGIN_FILE ) ) . '/i18n/' );
    }
```

`dependencies_notice()`:
```php
    public function dependencies_notice() {
        if ( $this->is_package() ) {
            $message = sprintf( esc_html__( '%s requires %s plugin to be installed and active. B2B features are disabled until it is.', 'woocommerce-b2b' ),
                '<strong>' . $this->plugin_name . '</strong>',
                '<strong>WooCommerce</strong>'
            );
        } else {
            $message = sprintf( esc_html__( '%s requires %s plugin to be installed and active, please check. %s has been disabled.', 'woocommerce-b2b' ),
                '<strong>' . $this->plugin_name . '</strong>',
                '<strong>WooCommerce</strong>',
                '<strong>' . $this->plugin_name . '</strong>'
            );
        }
        printf( '<div class="error"><p>%s</p></div>', $message );
    }
```

Add a new public method after `get_links()`:
```php
    /**
     * Whether WooCommerce B2B runs bundled inside a host plugin
     *
     * @return bool
     */
    public function is_package() {
        return defined( 'WCB2B_MODE' ) && WCB2B_Package::MODE_PACKAGE === WCB2B_MODE;
    }
```

- [ ] **Step 4: Run all tests**

Run: `php vendor/bin/phpunit`
Expected: PASS (Task 1 + Task 2 suites). If the WC-active tests error with `Call to undefined function X()`, a constructor in `includes/classes/` calls X at include time. Add `Functions\when( 'X' )->justReturn( null );` to `WCB2B_TestCase::setUp()`. Do not change production code to fix that.

- [ ] **Step 5: Commit**

```bash
git add woocommerce-b2b.php includes/classes/class-wcb2b.php includes/classes/class-wcb2b-upgrade.php tests/Unit/CoreModeTest.php tests/Unit/WCB2B_TestCase.php
git commit -m "feat: hand plugin lifecycle to the host plugin, remove Envato update checker"
```

---

### Task 3: Asset URLs from the package directory

**Files:**
- Modify: `includes/classes/class-wcb2b-admin.php:47,58`, `includes/classes/class-wcb2b-frontend.php:47-83` (11 calls), `src/Blocks/Payments/Integrations/WCB2B_Invoice.php:42`, `WCB2B_PurchaseOrder.php:42`, `WCB2B_Quotation.php:42`
- Test: `tests/Unit/AssetUrlTest.php`

**Interfaces:**
- Consumes: `WCB2B_PLUGIN_URI` (always has a trailing slash, from Task 1), `WCB2B_TestCase::package_dir()`

- [ ] **Step 1: Write the failing tests**

`tests/Unit/AssetUrlTest.php`:
```php
<?php

use Brain\Monkey\Functions;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class AssetUrlTest extends WCB2B_TestCase {

    public function test_admin_styles_use_package_url() {
        $this->activate_woocommerce();
        WCB2B_Package::boot( '/sites/wp/wp-content/plugins/host/host.php', array( 'url' => 'https://cdn.test/b2b/' ) );
        Functions\when( 'wp_enqueue_style' )->justReturn( null );
        Functions\expect( 'wp_register_style' )
            ->once()
            ->with( 'wcb2b_admin', 'https://cdn.test/b2b/assets/css/admin.min.css', false, '4.0.1' );

        ( new WCB2B_Admin() )->print_styles();
    }

    public function test_source_builds_asset_urls_only_from_package_uri() {
        $offenders = array();
        foreach ( array( 'includes', 'src' ) as $folder ) {
            $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( self::package_dir() . $folder ) );
            foreach ( $files as $file ) {
                if ( 'php' !== $file->getExtension() ) {
                    continue;
                }
                $source = file_get_contents( $file->getPathname() );
                if ( preg_match( '/plugins_url\([^)]*WCB2B_PLUGIN_FILE/', $source ) || preg_match( "/WCB2B_PLUGIN_URI\s*\.\s*'\//", $source ) ) {
                    $offenders[] = $file->getPathname();
                }
            }
        }
        $this->assertSame( array(), $offenders );
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Unit/AssetUrlTest.php`
Expected: FAIL. The first test gets `https://example…`/`plugins_url` mismatches or an undefined `plugins_url`. The second lists 5 files.

- [ ] **Step 3: Implement**

```bash
sed -i -E "s#plugins_url\( '(assets/[^']+)', WCB2B_PLUGIN_FILE \)#WCB2B_PLUGIN_URI . '\1'#g" includes/classes/class-wcb2b-admin.php includes/classes/class-wcb2b-frontend.php
sed -i -E "s#WCB2B_PLUGIN_URI ?\. ?'/assets/#WCB2B_PLUGIN_URI . 'assets/#g" src/Blocks/Payments/Integrations/WCB2B_Invoice.php src/Blocks/Payments/Integrations/WCB2B_PurchaseOrder.php src/Blocks/Payments/Integrations/WCB2B_Quotation.php
git diff --stat
```
Expected diff: 2 lines in admin, 11 in frontend, 1 in each blocks file. For example, `class-wcb2b-admin.php:47` becomes:
```php
        wp_register_style( 'wcb2b_admin', WCB2B_PLUGIN_URI . 'assets/css/admin.min.css', false, '4.0.1' );
```
and `WCB2B_Invoice.php:42` becomes:
```php
            WCB2B_PLUGIN_URI . 'assets/client/blocks/wcb2b-payment-method-invoice.js',
```
`includes/views/settings/html-admin-settings.php:16` already uses `WCB2B_PLUGIN_URI . 'assets/…'` and needs no change.

- [ ] **Step 4: Run all tests**

Run: `php vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add includes/classes/class-wcb2b-admin.php includes/classes/class-wcb2b-frontend.php src/Blocks tests/Unit/AssetUrlTest.php
git commit -m "fix: build asset URLs from the package directory, not the plugin file"
```

---

### Task 4: Debug mode keeps the owning plugin active

`src/mu-debugger.php` disables every plugin except WooCommerce and the hard-coded `woocommerce-b2b/woocommerce-b2b.php`. In package mode that would disable the host plugin, and B2B with it.

**Files:**
- Modify: `src/mu-debugger.php:39-47`, `includes/classes/class-wcb2b-debug.php:53-55` and `:70-73`
- Test: `tests/Unit/DebuggerTest.php`

**Interfaces:**
- Produces: option `wcb2b_debug_plugin` (plugin basename of the plugin that runs B2B). Task 5 deletes it on uninstall.

- [ ] **Step 1: Write the failing tests**

`tests/Unit/DebuggerTest.php`:
```php
<?php

use Brain\Monkey\Filters;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class DebuggerTest extends WCB2B_TestCase {

    private function load_debugger() {
        $callback = null;
        Filters\expectAdded( 'option_active_plugins' )->once()->whenHappen( function ( $cb ) use ( &$callback ) {
            $callback = $cb;
        } );
        include self::package_dir() . 'src/mu-debugger.php';
        return $callback;
    }

    public function test_keeps_host_plugin_active_for_debug_ip() {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $this->options['wcb2b_debug'] = '203.0.113.7';
        $this->options['wcb2b_debug_plugin'] = 'host/host.php';

        $filter = $this->load_debugger();

        $this->assertSame( array( 'woocommerce/woocommerce.php', 'host/host.php' ), $filter( array( 'other/other.php' ), 'active_plugins' ) );
    }

    public function test_falls_back_to_standalone_plugin() {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.7';
        $this->options['wcb2b_debug'] = '203.0.113.7';

        $filter = $this->load_debugger();

        $this->assertSame( array( 'woocommerce/woocommerce.php', 'woocommerce-b2b/woocommerce-b2b.php' ), $filter( array( 'other/other.php' ), 'active_plugins' ) );
    }

    public function test_other_visitors_keep_all_plugins() {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.1';
        $this->options['wcb2b_debug'] = '203.0.113.7';

        $filter = $this->load_debugger();

        $this->assertSame( array( 'other/other.php' ), $filter( array( 'other/other.php' ), 'active_plugins' ) );
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Unit/DebuggerTest.php`
Expected: `test_keeps_host_plugin_active_for_debug_ip` FAILS (gets `woocommerce-b2b/woocommerce-b2b.php`). The other two pass.

- [ ] **Step 3: Implement**

`src/mu-debugger.php`: replace the array inside the filter:
```php
        return apply_filters( 'wcb2b_debug_muplugins', array(
            'woocommerce/woocommerce.php',
            get_option( 'wcb2b_debug_plugin', 'woocommerce-b2b/woocommerce-b2b.php' )
        ) );
```
Also bump its `Version:` header to `4.2.0`. The MU file is only copied when it is missing, so sites that already have debug mode on get the new copy the next time debug mode is switched off and on.

`includes/classes/class-wcb2b-debug.php`, disable branch: insert just before `// Delete debug mode allowed IP`:
```php
                        delete_option( 'wcb2b_debug_plugin' );
```
Enable branch: insert just before `// Store current IP address`:
```php
                        // Store the plugin that runs WooCommerce B2B (standalone or host), debug mode keeps it active
                        update_option( 'wcb2b_debug_plugin', plugin_basename( WCB2B_PLUGIN_FILE ) );
```

- [ ] **Step 4: Run all tests**

Run: `php vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/mu-debugger.php includes/classes/class-wcb2b-debug.php tests/Unit/DebuggerTest.php
git commit -m "fix: keep the plugin that runs WooCommerce B2B active in debug mode"
```

---

### Task 5: Shared uninstall routine

**Files:**
- Create: `includes/wcb2b-uninstall.php`, `tests/stubs/OrderUtil.php`, `tests/stubs/FakeWpdb.php`, `tests/Unit/UninstallTest.php`
- Modify: `uninstall.php` (whole file), `includes/class-wcb2b-package.php` (`uninstall()` body)

**Interfaces:**
- Consumes: `WCB2B_Package::dir()`, `WCB2B_MODE` (Task 1), option `wcb2b_debug_plugin` (Task 4)
- Produces: `wcb2b_uninstall(): void` and a working `WCB2B_Package::uninstall()`

- [ ] **Step 1: Write the test stubs and failing tests**

`tests/stubs/OrderUtil.php`:
```php
<?php

namespace Automattic\WooCommerce\Utilities;

class OrderUtil {

    public static $hpos = false;

    public static function custom_orders_table_usage_is_enabled() {
        return self::$hpos;
    }
}
```

`tests/stubs/FakeWpdb.php`:
```php
<?php

class WCB2B_Fake_Wpdb {

    public $prefix   = 'wp_';
    public $posts    = 'wp_posts';
    public $postmeta = 'wp_postmeta';
    public $termmeta = 'wp_termmeta';
    public $usermeta = 'wp_usermeta';
    public $queries  = array();

    public function query( $sql ) {
        $this->queries[] = $sql;
        return 0;
    }
}
```
Delete `tests/stubs/.gitkeep`, then run `composer dump-autoload`.

`tests/Unit/UninstallTest.php`:
```php
<?php

use Automattic\WooCommerce\Utilities\OrderUtil;
use Brain\Monkey\Functions;

/**
 * @runTestsInSeparateProcesses
 * @preserveGlobalState disabled
 */
class UninstallTest extends WCB2B_TestCase {

    /** @var WCB2B_Fake_Wpdb */
    private $wpdb;

    /** @var string[] */
    private $deleted = array();

    protected function setUp(): void {
        parent::setUp();
        $this->wpdb = $GLOBALS['wpdb'] = new WCB2B_Fake_Wpdb();
        Functions\when( 'delete_option' )->alias( function ( $option ) {
            $this->deleted[] = $option;
            return true;
        } );
        Functions\when( 'flush_rewrite_rules' )->justReturn( null );
        Functions\when( 'wp_cache_flush' )->justReturn( true );
    }

    public function test_package_uninstall_removes_options_and_meta() {
        WCB2B_Package::uninstall();

        $this->assertContains( 'wcb2b_version', $this->deleted );
        $this->assertContains( 'wcb2b_debug_plugin', $this->deleted );
        $this->assertCount( 10, $this->wpdb->queries );
        $this->assertStringContainsString( "DELETE FROM wp_postmeta WHERE meta_key IN ( '_wcb2b_group'", $this->wpdb->queries[0] );
    }

    public function test_uninstall_uses_hpos_table_when_enabled() {
        OrderUtil::$hpos = true;

        WCB2B_Package::uninstall();

        $this->assertStringContainsString( 'DELETE FROM wp_wc_orders_meta', $this->wpdb->queries[0] );
    }

    public function test_standalone_uninstall_wipes_data() {
        define( 'WP_UNINSTALL_PLUGIN', 'woocommerce-b2b/woocommerce-b2b.php' );

        include self::package_dir() . 'uninstall.php';

        $this->assertContains( 'wcb2b_version', $this->deleted );
        $this->assertNotEmpty( $this->wpdb->queries );
    }

    public function test_standalone_uninstall_keeps_data_while_package_runs() {
        define( 'WP_UNINSTALL_PLUGIN', 'woocommerce-b2b/woocommerce-b2b.php' );
        define( 'WCB2B_MODE', WCB2B_Package::MODE_PACKAGE );

        include self::package_dir() . 'uninstall.php';

        $this->assertSame( array(), $this->deleted );
        $this->assertSame( array(), $this->wpdb->queries );
    }
}
```

- [ ] **Step 2: Run the tests to verify they fail**

Run: `php vendor/bin/phpunit tests/Unit/UninstallTest.php`
Expected: the two `package_uninstall`/`hpos` tests FAIL (nothing deleted). `keeps_data_while_package_runs` FAILS (data wiped).

- [ ] **Step 3: Implement**

Create `includes/wcb2b-uninstall.php`. The function body is the current `uninstall.php` from `global $wpdb;` through `wp_cache_flush();`, **moved verbatim**, with two changes: the HPOS check is guarded with `class_exists`, and `wcb2b_debug_plugin` is added to the deleted options.
```php
<?php

/**
 * WooCommerce B2B uninstall routine
 *
 * Shared by the standalone plugin uninstall.php and by host plugins through WCB2B_Package::uninstall()
 *
 * @version 4.2.0
 */

defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Utilities\OrderUtil;

if ( ! function_exists( 'wcb2b_uninstall' ) ) {

    /**
     * Delete WooCommerce B2B options, meta, groups and cached data
     */
    function wcb2b_uninstall() {
        global $wpdb;

        // Delete options and settings
        delete_option( 'wcb2b_version' );
        // … every other delete_option() line from uninstall.php, unchanged …
        delete_option( 'wcb2b_debug' );
        delete_option( 'wcb2b_debug_plugin' );

        // Delete orders data
        if ( class_exists( OrderUtil::class ) && OrderUtil::custom_orders_table_usage_is_enabled() ) {
            // … HPOS query from uninstall.php, unchanged …
        } else {
            // … postmeta query from uninstall.php, unchanged …
        }

        // … the remaining $wpdb->query() lines, flush_rewrite_rules() and wp_cache_flush() from uninstall.php, unchanged …
    }

}
```
The `// …` lines above mean "paste the existing lines here". Copy them from `uninstall.php:19-74` and do not retype them. Afterwards `git diff --stat` should show `uninstall.php` losing about as many lines as `includes/wcb2b-uninstall.php` gains.

Replace `uninstall.php` with:
```php
<?php

/**
 * WooCommerce B2B Uninstall
 *
 * Uninstalling WooCommerce B2B deletes user roles and options
 *
 * @version 4.2.0
 */

// Exit if accessed directly
defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// A host plugin still runs WooCommerce B2B as a package: keep its data
if ( defined( 'WCB2B_MODE' ) && 'package' === WCB2B_MODE ) {
    return;
}

include_once __DIR__ . '/includes/wcb2b-uninstall.php';

wcb2b_uninstall();
```
The guard compares against the literal `'package'` because `WCB2B_Package` may not be loaded during uninstall.

Fill in `WCB2B_Package::uninstall()`:
```php
    public static function uninstall() {
        include_once self::dir() . 'includes/wcb2b-uninstall.php';
        wcb2b_uninstall();
    }
```

- [ ] **Step 4: Run all tests**

Run: `php vendor/bin/phpunit`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add uninstall.php includes/wcb2b-uninstall.php includes/class-wcb2b-package.php tests/
git commit -m "feat: share uninstall routine with host plugins and protect data while a host runs B2B"
```

---

### Task 6: Packaging, docs, version bump and end-to-end smoke test

**Files:**
- Create: `tests/fixtures/wcb2b-host/wcb2b-host.php`, `tests/fixtures/wcb2b-host/uninstall.php`, `tests/fixtures/wcb2b-host/composer.json`
- Modify: `.gitattributes`, `.gitignore`, `README.md`, `woocommerce-b2b.php` (header `Version:`), `includes/classes/class-wcb2b.php` (`$version`, `@version`)

**Interfaces:**
- Consumes: the whole public surface from Tasks 1-5

- [ ] **Step 1: Keep dev files out of the Composer dist**

Append to `.gitattributes` (GitHub zipballs honour `export-ignore`, so `composer install --prefer-dist` skips these):
```
/tests              export-ignore
/docs               export-ignore
/phpunit.xml.dist   export-ignore
/.gitattributes     export-ignore
/.gitignore         export-ignore
```
Append to `.gitignore`:
```
/tests/fixtures/wcb2b-host/vendor/
/tests/fixtures/wcb2b-host/composer.lock
```

- [ ] **Step 2: Add the host fixture**

`tests/fixtures/wcb2b-host/wcb2b-host.php`:
```php
<?php

/*
    Plugin Name: WCB2B Host Fixture
    Description: Test-only host plugin that bundles WooCommerce B2B as a Composer package.
    Version: 0.1.0
 */

defined( 'ABSPATH' ) || exit;

require __DIR__ . '/vendor/autoload.php';

// Top level, never inside a hook
WCB2B_Package::boot( __FILE__ );
```

`tests/fixtures/wcb2b-host/uninstall.php`:
```php
<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require __DIR__ . '/vendor/autoload.php';

WCB2B_Package::uninstall();
```

`tests/fixtures/wcb2b-host/composer.json`:
```json
{
    "name": "commercebird/wcb2b-host-fixture",
    "description": "Test-only host plugin that bundles WooCommerce B2B",
    "type": "wordpress-plugin",
    "license": "GPL-3.0-or-later",
    "require": {
        "commercebird/woocommerce-b2b": "*@dev"
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
```

- [ ] **Step 3: Bump the version to 4.2.0**

- `woocommerce-b2b.php`: `Version: 4.1.0` → `Version: 4.2.0`
- `includes/classes/class-wcb2b.php`: `@version 4.1.0` → `@version 4.2.0` and `public $version = '4.1.0';` → `public $version = '4.2.0';`

`WCB2B_Install::$db_updates` needs no change, because 4.2.0 has no DB migrations.

- [ ] **Step 4: Document consumer usage in `README.md`**

Append:
````markdown
## Using WooCommerce B2B inside another plugin (Composer)

WooCommerce B2B can be bundled in a host plugin instead of being installed as a separate plugin.

```json
{
    "repositories": [ { "type": "vcs", "url": "https://github.com/<org>/woocommerce-b2b" } ],
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

In the host plugin's `uninstall.php`, call `WCB2B_Package::uninstall();` only if uninstalling the host should delete all B2B data.

What the host plugin takes over in package mode:

- **Updates**: B2B has no update checker of its own. Update it with `composer update`.
- **Activation and dependencies**: B2B never deactivates the host. If WooCommerce is missing, B2B shows a notice and stays idle.
- **HPOS and Cart/Checkout blocks**: B2B declares compatibility for the host plugin file. The rest of the host plugin must be compatible too.
- **Plugins screen**: B2B adds no row links or Settings link. Settings stay at WooCommerce → Settings → B2B.

Detect B2B with `function_exists( 'WCB2B' )`. Don't check `is_plugin_active( 'woocommerce-b2b/woocommerce-b2b.php' )`, because that is false in package mode.

### Moving a site from the standalone plugin to a host plugin

All data (groups, prices, options, meta) is shared and carries over. If the standalone plugin is active, it always wins and the bundled copy shows a notice and steps aside. To switch:

1. Update the standalone plugin to **4.2.0 or later**. Older versions' `uninstall.php` deletes all B2B data when the plugin is deleted.
2. Activate the host plugin, then deactivate the standalone plugin.
3. Delete the standalone plugin. From 4.2.0, its uninstall keeps the data while a host runs B2B.
````

- [ ] **Step 5: Verify packaging**

Run:
```bash
composer validate --strict
php vendor/bin/phpunit
git ls-files -co --exclude-standard '*.php' | xargs -n1 php -l | grep -v '^No syntax errors'
```
Expected: `./composer.json is valid`, all tests PASS, no lint output.

Commit everything so far, then check the dist contents:
```bash
git add -A && git commit -m "chore: package WooCommerce B2B for Composer, document host usage, bump to 4.2.0"
git archive --format=tar HEAD | tar -t | grep -E '^(tests|docs)/|phpunit' ; echo "excluded-check-exit=$?"
git archive --format=tar HEAD | tar -t | grep -cE '^(assets|templates|i18n|includes|src)/'
```
Expected: the first command prints nothing and ends with `excluded-check-exit=1`. The second prints a count above 90.

- [ ] **Step 6: Manual end-to-end smoke test on a local WordPress + WooCommerce site**

Use any local site (Local, wp-env, XAMPP) with WooCommerce ≥ 8.0, `WP_DEBUG` and `WP_DEBUG_LOG` on.

```bash
cp -r tests/fixtures/wcb2b-host "<site>/wp-content/plugins/wcb2b-host"
cd "<site>/wp-content/plugins/wcb2b-host"
composer config repositories.wcb2b '{"type":"path","url":"C:/Users/FawadTiemoerieFreshM/Documents/GitHub/woocommerce-b2b","options":{"symlink":false}}'
composer install --no-dev
```

Check each item and record pass/fail:

1. Deactivate the standalone WooCommerce B2B if present, then activate **WCB2B Host Fixture**. No fatal, nothing in `debug.log`.
2. WooCommerce → Settings → **B2B** tab renders, with the logo image loading.
3. Front end: product page CSS/JS (`…/wcb2b-host/vendor/commercebird/woocommerce-b2b/assets/…`) return 200 in DevTools Network.
4. Block checkout: the Invoice / Purchase order / Quotation payment methods appear (when enabled) and their JS loads.
5. WooCommerce → Settings → Advanced → Features: no "incompatible plugins" warning for the host.
6. Plugins screen: no update notice on the host row. The host row has no B2B links.
7. Switch the site language to Italiano: B2B settings labels are translated.
8. Deactivate WooCommerce: B2B notice appears, **the host stays active**.
9. With the host active, activate the standalone plugin (this checkout): no fatal, the "already loaded" notice names both, and the B2B tab still works.
10. Tools → B2B debug mode on: other plugins are disabled for your IP, **the host stays active**. Then switch debug mode off.

Fix any failure in the owning task's files, add a unit test that reproduces it, and commit.

---

## Out of scope (flagged, not done)

- **Rotating the Envato API bearer token.** Task 2 deletes it from the code, but it remains in git history, so revoke it in the Envato account.
- **Sister add-ons** (Sales Agents, Warehouses) may check `woocommerce-b2b/woocommerce-b2b.php` in `active_plugins`. They need the `function_exists( 'WCB2B' )` check described in the README.
- **PHP-Scoper / namespacing** to let two host plugins bundle different B2B versions. The global `wcb2b_*` functions and `WCB2B_*` classes are public API used by themes and templates, so the first copy to boot wins (with a notice) instead.
- **Packagist publishing**: consumers use a `vcs` repository until the package is published.
