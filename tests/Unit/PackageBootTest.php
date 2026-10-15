<?php

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

    public function test_standalone_runs_its_own_copy_when_loader_came_from_vendor() {
        // A host autoloaded WCB2B_Package from vendor/ first: the standalone must still use its own folder
        $file = self::package_dir() . 'includes/../woocommerce-b2b.php';
        Functions\expect( 'plugin_dir_url' )->once()->with( $file )->andReturn( 'https://example.test/plugins/woocommerce-b2b/' );

        $this->assertTrue( WCB2B_Package::boot_standalone( $file ) );

        $this->assertSame( self::package_dir() . 'includes/../', WCB2B_PACKAGE_DIR );
        $this->assertSame( 'https://example.test/plugins/woocommerce-b2b/', WCB2B_PLUGIN_URI );
    }

    public function test_activation_sandbox_boots_after_plugins_loaded() {
        // activate_plugin() includes the plugin file after plugins_loaded, inside plugin_sandbox_scrape()
        define( 'WP_SANDBOX_SCRAPING', true );
        do_action( 'plugins_loaded' );
        $this->stub_plugins_url();
        Functions\expect( '_doing_it_wrong' )->never();

        $this->assertTrue( WCB2B_Package::boot_standalone( self::package_dir() . 'woocommerce-b2b.php' ) );
    }

    public function test_boot_proceeds_when_active_standalone_folder_is_missing() {
        // Folder removed by FTP or deploy, still listed in active_plugins until the plugins screen is opened
        $this->define_plugins_dir( false );
        $this->set_active_plugins( array( WCB2B_Package::STANDALONE_PLUGIN ) );
        $this->stub_plugins_url();

        $this->assertTrue( WCB2B_Package::boot( self::HOST ) );
    }

    public function test_boot_is_refused_when_standalone_plugin_active() {
        $this->define_plugins_dir( true );
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
