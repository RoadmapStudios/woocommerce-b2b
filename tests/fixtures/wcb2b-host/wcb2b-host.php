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
