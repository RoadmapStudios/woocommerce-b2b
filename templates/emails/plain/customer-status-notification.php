<?php
/**
 * Customer approval notification
 *
 * @version 3.0.6
 */

defined( 'ABSPATH' ) || exit;

$user = get_userdata( $user_id );

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( esc_html__( 'Hi %s,', 'woocommerce-b2b' ), esc_html( $user->first_name ) ) . "\n\n";

echo esc_html__( 'Your account is approved and you are now able to purchase.', 'woocommerce-b2b' ) . "\r\n";
echo sprintf( esc_html__( 'You can now start purchasing and access your account area at: %s', 'woocommerce-b2b' ), esc_html( wc_get_page_permalink( 'myaccount' ) ) ) . "\n\n";

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );