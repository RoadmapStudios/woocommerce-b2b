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
