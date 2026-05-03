<?php
/**
 * Customer quoted notification
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );

?>

<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce-b2b' ), esc_html( $order->get_billing_first_name() ) ); ?></p>
<p>
    <?php esc_html_e( 'We proceeded to formulate our best offer for the requested products. Here’s a summary:', 'woocommerce-b2b' ); ?>
</p>
<?php
    do_action( 'woocommerce_email_order_details', $order, $sent_to_admin, $plain_text, $email );
    do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email );

    if ( $additional_content ) {
        echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    }
?>
<p>
    <?php esc_html_e( 'You can check details of this quotation requests in your private area', 'woocommerce-b2b' ); ?>    
</p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>