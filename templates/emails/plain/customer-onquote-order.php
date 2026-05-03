<?php
/**
 * Customer on quote notification
 *
 * @version 3.0.7
 */

defined( 'ABSPATH' ) || exit;

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n";
echo esc_html( wp_strip_all_tags( $email_heading ) );
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( esc_html__( 'Hi %s,', 'woocommerce-b2b' ), esc_html( $order->get_billing_first_name() ) ) . "\n\n";
echo esc_html__( 'We have received your quotation request, we will provide you with our best offer for the requested products as soon as possible, by sending you an email notification. In the meantime, here’s a reminder of your request:', 'woocommerce-b2b' ) . "\n\n";

do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );

echo "\n----------------------------------------\n\n";

do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

echo "\n\n----------------------------------------\n\n";

if ( $additional_content ) {
    echo esc_html( wp_strip_all_tags( wptexturize( $additional_content ) ) );
    echo "\n\n----------------------------------------\n\n";
}

echo esc_html__( 'At any time, you can check the status of your quotation requests in your private area', 'woocommerce-b2b' ) . "\n\n"; 

echo wp_kses_post( apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) ) );