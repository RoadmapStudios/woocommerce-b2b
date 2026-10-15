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
        Functions\when( 'add_shortcode' )->justReturn( null );
        Functions\when( 'is_admin' )->justReturn( true );
        Functions\when( 'get_site_option' )->justReturn( array() );
        Functions\when( 'get_option' )->alias( function ( $key, $default = false ) {
            return array_key_exists( $key, $this->options ) ? $this->options[ $key ] : $default;
        } );
    }

    protected function tearDown(): void {
        // Count Brain Monkey expectations as assertions, so expectation-only tests are not flagged risky
        $this->addToAssertionCount( \Mockery::getContainer()->mockery_getExpectationCount() );
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
