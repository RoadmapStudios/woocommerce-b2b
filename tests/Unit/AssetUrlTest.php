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
