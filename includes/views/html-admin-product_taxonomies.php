<?php

/**
 * WooCommerce B2B Admin Product Categories view
 *
 * @version 4.0.4
 */

defined( 'ABSPATH' ) || exit;

?>

<?php if ( $context == 'edit' ) : ?>
<tr class="wcb2b-table-row">
    <td colspan="2">
<?php endif; ?>
        <div class="form-field term-display-type-wrap">
            <div class="wcb2b-card">
                <input type="checkbox" id="wcb2b-collapse_<?php echo isset($term) ? $term->term_id : 1; ?>" checked>
                <div class="wcb2b-card-title">
                    <?php esc_html_e( 'WooCommerce B2B', 'woocommerce-b2b' ); ?>
                        
                    <label for="wcb2b-collapse_<?php echo isset($term) ? $term->term_id : 1; ?>"></label> 
                </div>
                <div class="wcb2b-card-body">
                    <div class="wcb2b-label"><?php esc_html_e( 'Group access', 'woocommerce-b2b' ); ?></div>
                    <table class="form-table">
                        <tbody>

                            <?php if ( $groups->have_posts() ) : ?>
                                <?php foreach ( $groups->get_posts() as $group ) : ?>
                                <tr>

                                    <?php
                                        $checked = checked( true, true, false );
                                        if ( $visibility !== false ) {
                                            $checked = checked( in_array( $group->ID, (array)$visibility ), true, false );
                                        }
                                    ?>

                                    <td width="1%"><input type="checkbox" name="wcb2b_group_visibility[]" id="wcb2b_group_visibility_<?php echo $group->ID; ?>" value="<?php echo $group->ID; ?>" <?php echo $checked; ?> /></td>
                                    <td><?php echo get_the_title( $group->ID ); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>

                        </tbody>
                    </table>
                    <em class="wcb2b-help"><?php esc_html_e( 'Select the groups you want to be able to view this category', 'woocommerce-b2b' ); ?></em>
                </div>
            </div>
        </div>
<?php if ( $context == 'edit' ) : ?>
    </td>
</tr>
<?php endif; ?>