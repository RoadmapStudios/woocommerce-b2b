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

    public function test_package_uninstall_keeps_data_while_another_copy_runs() {
        // The host being deleted is inactive: a defined mode means the standalone or another host runs B2B
        define( 'WCB2B_MODE', WCB2B_Package::MODE_PLUGIN );

        WCB2B_Package::uninstall();

        $this->assertSame( array(), $this->deleted );
        $this->assertSame( array(), $this->wpdb->queries );
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
