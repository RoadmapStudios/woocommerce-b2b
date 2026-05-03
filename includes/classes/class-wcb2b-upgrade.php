<?php

/**
 * WooCommerce B2B Upgrade Class
 *
 * @version 4.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * WCB2B_Upgrade Class
 */

class WCB2B_Upgrade
{

    private $informations = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Init current class hooks
     */
    public function init_hooks() {
        add_filter( 'site_transient_update_plugins', array( $this, 'skip_plugin_update_check' ) );
        add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'set_update_transient' ) );
        add_filter( 'plugins_api', array( $this, 'get_update_info' ), 10, 3 );
    }

    /**
     * Skip update attempt
     *
     * @param string $transient Updater transient
     * @return string
     */
    public function skip_plugin_update_check( $transient ) {
        if ( empty( $transient->response ) || ! is_object( $transient->response ) ) {
            return $transient;
        }
        $plugin_file = 'woocommerce-b2b/woocommerce-b2b.php';
        if ( isset( $transient->response[ $plugin_file ] ) ) {
            unset( $transient->response[ $plugin_file ] );
        }

        return $transient;
    }

    /**
     * Add new version plugin transient
     *
     * @param object $transient Update transient
     * @return object
     */
    public function set_update_transient( $transient ) {
        if ( ! empty( $transient->checked ) ) {
            if ( $version = self::check_updates() ) {
                if ( -1 == version_compare( WCB2B_VERSION, $version ) ) {
                    $object = new stdClass();
                    $object->slug = basename( dirname( WCB2B_PLUGIN_FILE ) );
                    $object->new_version = $version;
                    $object->icons['default'] = WCB2B_PLUGIN_URI.'/assets/images/icon-256.png';
                    $transient->response[plugin_basename( WCB2B_PLUGIN_FILE )] = $object;
                }
            }
        }
        return $transient;
    }

    /**
     * Add plugin new version description
     *
     * @param boolean $response
     * @param array $action
     * @param object $args
     * @return bool|object
     */
    public function get_update_info( $response, $action, $args ) {
        if ( isset( $args->slug ) && $args->slug === basename( dirname( WCB2B_PLUGIN_FILE ) ) ) {
            if ( $informations = self::check_updates_info() ) {
                $description = sprintf( '<p>%s</p>', $informations->wordpress_plugin_metadata->description );
                $description .= sprintf( '<h4>%s</h4>', 'Before to update:' );
                $description .= sprintf( '<p><a href="%s">%s</a></p>',
                    WCB2B()->get_links()['changelog'],
                    'Take a look to changelog'
                );
                $description .= sprintf( '<p><a href="%s">%s</a></p>',
                    WCB2B()->get_links()['docs'],
                    'Read updated documentation'
                );
                foreach ( $informations->attributes as $attribute ) {
                    if ( 'demo-url' == $attribute->name ) {
                        $description .= sprintf( '<p><a href="%s">%s</a></p>',
                            $attribute->value,
                            'Try new version at work'
                        );
                    }
                }

                $object = (object)array(
                    'active_installs'          => $informations->number_of_sales,
                    'author'                   => sprintf( '<a href="%s" target="_blank">%s</a>',
                        $informations->author_url,
                        $informations->wordpress_plugin_metadata->author
                    ),
                    'banners'                  => array(
                        'high' => $informations->previews->landscape_preview->landscape_url
                    ),
                    'contributors'             => false,
                    'donate_link'              => false,
                    'download_link'            => false,
                    'downloaded'               => false,
                    'homepage'                 => $informations->url,
                    'icons'                    => false,
                    'last_updated'             => $informations->updated_at,
                    'name'                     => $informations->name,
                    'num_ratings'              => $informations->rating_count,
                    'rating'                   => $informations->rating*100/5,
                    'ratings'                  => false,
                    'requires'                 => false,
                    'requires_php'             => false,
                    'screenshots'              => false,
                    'sections'                 => array(
                        'description' => $description
                    ),
                    'short_description'        => false,
                    'slug'                     => basename( dirname( WCB2B_PLUGIN_FILE ) ),
                    'support_threads'          => false,
                    'support_threads_resolved' => false,
                    'tested'                   => false,
                    'version'                  => $informations->wordpress_plugin_metadata->version,
                    'versions'                 => false
                );
                return $object;
            }
        }
        return false;
    }

    /**
     * Check for updates
     * 
     * @return bool|object
     */
    public static function check_updates() {
        $response = wp_remote_get( "https://api.envato.com/v3/market/catalog/item-version?id=" . WCB2B_ENVATO_ID, array(
            'headers' => array(
                'Authorization' => "Bearer rzTdSkLSIciHOdxih8ubZTX7gNwPn6AK",
                'User-Agent' => "Check updates"
            )
        ) );
        if ( ! is_wp_error( $response ) ) {
            if ( isset( $response['response']['code'] ) && $response['response']['code'] == 200 ) {
                $content = json_decode( $response['body'] );
                return $content->wordpress_plugin_latest_version;
            }
        }
        return false;
    }

    /**
     * Check for updates info
     * 
     * @return bool|object
     */
    public static function check_updates_info() {
        $response = wp_remote_get( "https://api.envato.com/v3/market/catalog/item?id=" . WCB2B_ENVATO_ID, array(
            'headers' => array(
                'Authorization' => "Bearer rzTdSkLSIciHOdxih8ubZTX7gNwPn6AK",
                'User-Agent' => "Check updates"
            )
        ) );
        if ( ! is_wp_error( $response ) ) {
            if ( isset( $response['response']['code'] ) && $response['response']['code'] == 200 ) {
                $content = json_decode( $response['body'] );
                return $content;
            }
        }
        return false;
    }

}

return new WCB2B_Upgrade();
