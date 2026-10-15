<?php

namespace Automattic\WooCommerce\Utilities;

class FeaturesUtil {

    public static $declared = array();
    public static $throw = false;

    public static function declare_compatibility( $feature_id, $plugin_file, $positive_compatibility = true ) {
        // WooCommerce throws when a plugin already declared the opposite compatibility
        if ( self::$throw ) {
            throw new \Exception( "Plugin is trying to declare itself as compatible with the '$feature_id' feature, but it already declared itself as incompatible" );
        }
        self::$declared[] = $feature_id;
        return true;
    }
}
