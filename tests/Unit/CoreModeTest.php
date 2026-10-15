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
