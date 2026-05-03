<?php
/**
 * Customer approval notification
 *
 * @version 3.0.6
 */

defined( 'ABSPATH' ) || exit;

$user = get_userdata( $user_id );

do_action( 'woocommerce_email_header', $email_heading, $email );

?>

<p><?php printf( esc_html__( 'Hi %s,', 'woocommerce-b2b' ), $user->first_name ); ?></p>
<p><?php esc_html_e( 'Your account is approved and you are now able to purchase.', 'woocommerce-b2b' ); ?></p>
<p><?php printf( esc_html__( 'You can now start purchasing and access your account area at: %s', 'woocommerce-b2b' ), make_clickable( esc_url( wc_get_page_permalink( 'myaccount' ) ) ) ); ?></p>

<?php
    if ( $additional_content ) {
        echo wp_kses_post( wpautop( wptexturize( $additional_content ) ) );
    }
?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>