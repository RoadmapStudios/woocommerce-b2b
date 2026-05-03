<?php

/**
 * WooCommerce B2B My account business certificate
 *
 * @version 3.2.1
 */

defined( 'ABSPATH' ) || exit;

?>

<fieldset>
    <legend><?php esc_html_e( 'Business certificate', 'woocommerce-b2b' ); ?></legend>
    <div class="wcb2b-business_certificate-wrapper">

        <?php
            if ( empty( $value ) || $can_edit ) {
                woocommerce_form_field( 'wcb2b_business_certificate', array(
                    'type'              => 'text',
                    'label'             => esc_html__( 'Add business certificate', 'woocommerce-b2b' ),
                    'required'          => $required,
                    'class'             => array( 'form-row-wide' ),
                    'clear'             => true,
                    'custom_attributes' => array(
                        'accept' => implode( ',', $allowed )
                    )
                ) );
            }
        ?>

        <?php if ( ! empty( $value ) ) : ?>
        <div>
            <p>
                <a class="button" href="<?php echo wp_get_attachment_url( $value ); ?>" target="_blank">
                    <?php esc_html_e( 'View current', 'woocommerce-b2b' ); ?>
                </a>
            </p>

            <?php
                if ( $can_edit && ! $required ) {
                    woocommerce_form_field( 'wcb2b_business_certificate_delete', array(
                        'type'          => 'checkbox',
                        'label'         => esc_html__( 'Delete current', 'woocommerce-b2b' ),
                        'required'      => false,
                        'class'         => array()
                    ) );
                }
            ?>
        </div> 
        <input type="hidden" name="wcb2b_business_certificate_exists" value="1" required readonly="">   
        <?php endif; ?>

        <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxsize; ?>" required readonly>
    </div>
</fieldset>