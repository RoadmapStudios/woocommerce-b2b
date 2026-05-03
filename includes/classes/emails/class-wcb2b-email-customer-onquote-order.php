<?php

/**
 * WooCommerce B2B Email On quote notification
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WCB2B_Email_Customer_OnQuote_Order', false ) ) {
    return new WCB2B_Email_Customer_OnQuote_Order();
}

class WCB2B_Email_Customer_OnQuote_Order extends WC_Email {

    public $recipients = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->id               = 'wcb2b_customer_onquote_order';
        $this->customer_email   = true;

        $this->title            = __( 'On quote order', 'woocommerce-b2b' );
        $this->description      = __( 'Send email notification to customer when his quote request is received.', 'woocommerce-b2b' );

        $this->template_html    = 'emails/customer-onquote-order.php';
        $this->template_plain   = 'emails/plain/customer-onquote-order.php';

        add_action( 'woocommerce_order_status_on-quote_notification', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();

        $this->template_base = WCB2B_ABSPATH . 'templates/';
    }

    /**
     * Get email subject
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Your quote request on {site_title} is received!', 'woocommerce-b2b' );
    }

    /**
     * Get email heading
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'We are checking your request.', 'woocommerce-b2b' );
    }

    /**
     * Trigger the sending of this email
     *
     * @param int order_id The order ID.
     * @param object $order The order object
     */
    public function trigger( $order_id, $order = false ) {
        $this->setup_locale();

        if ( $order_id && ! is_a( $order, 'WC_Order' ) ) {
            $order = wc_get_order( $order_id );
        }

        if ( is_a( $order, 'WC_Order' ) ) {
            $this->object                         = $order;
            $this->recipient                      = $this->object->get_billing_email();
            $this->placeholders['{order_date}']   = wc_format_datetime( $this->object->get_date_created() );
            $this->placeholders['{order_number}'] = $this->object->get_order_number();
        }

        if ( $this->is_enabled() && $this->get_recipient() ) {
            $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
        }

        $this->restore_locale();
    }

    /**
     * Get content html
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'blogname'      => $this->get_blogname(),
            'sent_to_admin' => false,
            'plain_text'    => false,
            'email'         => $this,
        ), WCB2B_OVERRIDES, $this->template_base );
    }

    /**
     * Get content plain
     *
     * @return string
     */
    public function get_content_plain() {
        return wc_get_template_html( $this->template_plain, array(
            'order'         => $this->object,
            'email_heading' => $this->get_heading(),
            'additional_content' => $this->get_additional_content(),
            'blogname'      => $this->get_blogname(),
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ), WCB2B_OVERRIDES, $this->template_base );
    }

}

return new WCB2B_Email_Customer_OnQuote_Order();