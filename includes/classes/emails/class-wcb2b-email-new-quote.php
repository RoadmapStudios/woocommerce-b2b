<?php

/**
 * WooCommerce B2B Email New quote request
 *
 * @version 3.0.3
 */

defined( 'ABSPATH' ) || exit;

if ( class_exists( 'WCB2B_Email_New_Quote', false ) ) {
    return new WCB2B_Email_New_Quote();
}

class WCB2B_Email_New_Quote extends WC_Email {

    /**
     * Constructor
     */
    public function __construct() {
        $this->id               = 'wcb2b_new_quote';

        $this->title            = __( 'New quote', 'woocommerce-b2b' );
        $this->description      = __( 'Send email notification to admin when a new quote request is received.', 'woocommerce-b2b' );

        $this->template_html    = 'emails/new-quote.php';
        $this->template_plain   = 'emails/plain/new-quote.php';

        add_action( 'woocommerce_order_status_pending_to_on-quote_notification', array( $this, 'trigger' ), 10, 2 );

        parent::__construct();

        $this->template_base = WCB2B_ABSPATH . 'templates/';

        $this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
    }

    /**
     * Get email subject
     *
     * @return string
     */
    public function get_default_subject() {
        return __( 'A new quote request on {site_title} is received!', 'woocommerce-b2b' );
    }

    /**
     * Get email heading
     *
     * @return string
     */
    public function get_default_heading() {
        return __( 'New quotation request.', 'woocommerce-b2b' );
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
            'sent_to_admin' => true,
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
            'sent_to_admin' => true,
            'plain_text'    => true,
            'email'         => $this,
        ), WCB2B_OVERRIDES, $this->template_base );
    }

    /**
     * Default content to show below main email content.
     *
     * @since 3.7.0
     * @return string
     */
    public function get_default_additional_content() {
        return '';
    }

    /**
     * Initialise settings form fields.
     */
    public function init_form_fields() {
        /* translators: %s: list of placeholders */
        $placeholder_text  = sprintf( __( 'Available placeholders: %s', 'woocommerce-b2b' ), '<code>' . implode( '</code>, <code>', array_keys( $this->placeholders ) ) . '</code>' );
        $this->form_fields = array(
            'enabled'            => array(
                'title'   => __( 'Enable/Disable', 'woocommerce-b2b' ),
                'type'    => 'checkbox',
                'label'   => __( 'Enable this email notification', 'woocommerce-b2b' ),
                'default' => 'yes',
            ),
            'recipient'          => array(
                'title'       => __( 'Recipient(s)', 'woocommerce-b2b' ),
                'type'        => 'text',
                /* translators: %s: WP admin email */
                'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to %s.', 'woocommerce-b2b' ), '<code>' . esc_attr( get_option( 'admin_email' ) ) . '</code>' ),
                'placeholder' => '',
                'default'     => '',
                'desc_tip'    => true,
            ),
            'subject'            => array(
                'title'       => __( 'Subject', 'woocommerce-b2b' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_subject(),
                'default'     => '',
            ),
            'heading'            => array(
                'title'       => __( 'Email heading', 'woocommerce-b2b' ),
                'type'        => 'text',
                'desc_tip'    => true,
                'description' => $placeholder_text,
                'placeholder' => $this->get_default_heading(),
                'default'     => '',
            ),
            'additional_content' => array(
                'title'       => __( 'Additional content', 'woocommerce-b2b' ),
                'description' => __( 'Text to appear below the main email content.', 'woocommerce-b2b' ) . ' ' . $placeholder_text,
                'css'         => 'width:400px; height: 75px;',
                'placeholder' => __( 'N/A', 'woocommerce-b2b' ),
                'type'        => 'textarea',
                'default'     => $this->get_default_additional_content(),
                'desc_tip'    => true,
            ),
            'email_type'         => array(
                'title'       => __( 'Email type', 'woocommerce-b2b' ),
                'type'        => 'select',
                'description' => __( 'Choose which format of email to send.', 'woocommerce-b2b' ),
                'default'     => 'html',
                'class'       => 'email_type wc-enhanced-select',
                'options'     => $this->get_email_type_options(),
                'desc_tip'    => true,
            ),
        );
    }

}

return new WCB2B_Email_New_Quote();