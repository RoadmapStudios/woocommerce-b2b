<?php

/**
 * WooCommerce B2B Email Status notification
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WCB2B_Email_Customer_Status_Notification', false ) ) {
    return new WCB2B_Email_Customer_Status_Notification();
}

class WCB2B_Email_Customer_Status_Notification extends WC_Email {

    public $user_id;
    public $recipients = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->id               = 'wcb2b_customer_status_notification';
        $this->customer_email   = true;

        $this->title            = __( 'Status notification', 'woocommerce-b2b' );
        $this->description      = __( 'Send email notification to customer when his account is enabled.', 'woocommerce-b2b' );

        $this->template_html    = 'emails/customer-status-notification.php';
        $this->template_plain   = 'emails/plain/customer-status-notification.php';

        add_action( 'wcb2b_customer_status_notification', array( $this, 'trigger_status_notification' ), 10, 2 );

        parent::__construct();

        $this->template_base = WCB2B_ABSPATH . 'templates/';
    }

    /**
     * Custom trigger for this email
     * 
     * @param int $user_id Customer ID
     */
    public function trigger_status_notification( $user_id ) {
        $this->trigger( $user_id );
    }

    /**
     * Get email subject
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'Your account on {site_title} is approved!', 'woocommerce-b2b' );
    }

    /**
     * Get email heading
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'Your customer account is now able to purchase.', 'woocommerce-b2b' );
    }

    /**
     * Trigger the sending of this email
     *
     * @param int user_id The customer ID.
     */
    public function trigger( $user_id ) {
        $user = get_userdata( $user_id );
        $this->recipient  = $user->user_email;

        if ( wcb2b_has_role( $user_id, 'customer' ) ) {
            if ( $this->is_enabled() && $this->get_recipient() ) {
                $this->user_id = $user_id;

                $this->setup_locale();
                $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
                $this->restore_locale();
            }
        }
    }

    /**
     * Get content html
     *
     * @return string
     */
    public function get_content_html() {
        return wc_get_template_html( $this->template_html, array(
            'email_heading' => $this->get_heading(),
            'blogname'      => $this->get_blogname(),
            'additional_content' => $this->get_additional_content(),
            'user_id'       => $this->user_id,
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
            'email_heading' => $this->get_heading(),
            'blogname'      => $this->get_blogname(),
            'additional_content' => $this->get_additional_content(),
            'user_id'       => $this->user_id,
            'sent_to_admin' => false,
            'plain_text'    => true,
            'email'         => $this,
        ), WCB2B_OVERRIDES, $this->template_base );
    }

}

return new WCB2B_Email_Customer_Status_Notification();