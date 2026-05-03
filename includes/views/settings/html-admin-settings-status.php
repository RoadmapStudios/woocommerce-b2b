<?php

/**
 * WooCommerce B2B Admin settings page - Status
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

?>

<fieldset>
    <h2><?php esc_html_e( 'Templates override', 'woocommerce-b2b' ); ?></h2>

    <?php
        $override_files     = array();
        $has_outdated_templates = false;
        $scan_files         = WC_Admin_Status::scan_template_files( WCB2B_ABSPATH . '/templates/' );
        foreach ( $scan_files as $file ) {
            if ( file_exists( $file ) ) {
                $theme_file = $file;
            } elseif ( file_exists( get_stylesheet_directory() . '/' . WCB2B_OVERRIDES . '/' . $file ) ) {
                $theme_file = get_stylesheet_directory() . '/' . WCB2B_OVERRIDES . '/' . $file;
            } elseif ( file_exists( get_template_directory() . '/' . WCB2B_OVERRIDES . '/' . $file ) ) {
                $theme_file = get_template_directory() . '/' . WCB2B_OVERRIDES . '/' . $file;
            } else {
                $theme_file = false;
            }

            if ( ! empty( $theme_file ) ) {
                $core_version  = WC_Admin_Status::get_file_version( WCB2B_ABSPATH . '/templates/' . $file );
                $theme_version = WC_Admin_Status::get_file_version( $theme_file );
                if ( $core_version && ( empty( $theme_version ) || version_compare( $theme_version, $core_version, '<' ) ) ) {
                    if ( ! $has_outdated_templates ) {
                        $has_outdated_templates = true;
                    }
                }
                $override_files[] = array(
                    'file'         => str_replace( WP_CONTENT_DIR . '/themes/', '', $theme_file ),
                    'version'      => $theme_version,
                    'core_version' => $core_version,
                );
            }
        }
        if ( count( $override_files ) ) {
            foreach ( $override_files as $override ) {
                echo '<div>';
                if ( $override['core_version'] && ( empty( $override['version'] ) || version_compare( $override['version'], $override['core_version'], '<' ) ) ) {
                    $current_version = $override['version'] ? $override['version'] : '-';
                    printf(
                        /* Translators: %1$s: Template name, %2$s: Template version, %3$s: Core version. */
                        esc_html__( '%1$s version %2$s is out of date. The core version is %3$s', 'woocommerce-b2b' ),
                        '<code>' . esc_html( $override['file'] ) . '</code>',
                        '<strong class="error">' . esc_html( $current_version ) . '</strong>',
                        esc_html( $override['core_version'] )
                    );
                } else {
                    echo esc_html( $override['file'] );
                }
                echo '</div>';
            }
        } else {
            esc_html_e( 'There are no template overrides', 'woocommerce-b2b' );
        }
        if ( true === $has_outdated_templates ) {
            printf('<br /><p class="error"><span class="dashicons dashicons-warning"></span><a href="%s" target="_blank">%s</a></p>',
                'https://woocommerce-b2b.com/documentation/#s8',
                esc_html__( 'Learn how to update templates', 'woocommerce-b2b' )
            );
        }
    ?>

</fieldset>