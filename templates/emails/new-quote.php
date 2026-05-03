<?php
/**
 * Admin new quote notification
 *
 * @version 3.0.9
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

?>

<p><?php printf( esc_html__( 'You’ve received a new quotation request from: %s', 'woocommerce-b2b' ), $order->get_formatted_billing_full_name() ); ?></p>

<?php
    do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
    do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );
    do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email );

    if ( $additional_content ) {
        echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    }
?>

<p><a href="<?php echo get_edit_post_link( $order->get_id() ); ?>"><?php esc_html_e( 'View quotation request', 'woocommerce-b2b' ); ?></a></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>