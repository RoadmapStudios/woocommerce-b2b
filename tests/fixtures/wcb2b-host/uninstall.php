<?php

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require __DIR__ . '/vendor/autoload.php';

WCB2B_Package::uninstall();
