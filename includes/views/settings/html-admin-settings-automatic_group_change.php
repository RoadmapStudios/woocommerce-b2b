<?php

/**
 * WooCommerce B2B Admin settings page - Rules
 *
 * @version 3.3.7
 */

defined( 'ABSPATH' ) || exit;

?>

<tr valign="top">
    <th scope="row" class="titledesc">
        <label for="<?php echo esc_attr( $option['id'] ); ?>"><?php echo esc_html( $option['title'] ); ?></label>
    </th>
    <td class="forminp forminp-<?php echo esc_attr( sanitize_title( $option['type'] ) ); ?>">
        <table id="wcb2b_automatic_group_change-table" class="wc_input_table widefat">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Total spent', 'woocommerce-b2b' ); ?></th>
                    <th><?php esc_html_e( 'Target group', 'woocommerce-b2b' ); ?></th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="2">
                        <a href="#wcb2b_automatic_group_change_add" class="button" <?php echo count( $groups ) == 0 ? 'disabled' : false; ?>><?php esc_html_e( 'Insert row', 'woocommerce-b2b' ); ?></a>
                        <a href="#wcb2b_automatic_group_change_remove" class="button" <?php echo count( $rules ) == 0 ? 'disabled' : false; ?>><?php esc_html_e( 'Remove selected row(s)', 'woocommerce-b2b' ); ?></a>
                    </th>
                </tr>
            </tfoot>
            <tbody>

                <?php foreach ( $rules as $i => $rule ) : ?>
                <tr>
                    <td>
                        
                        <?php
                            printf( '<input name="%s[%s][limit]" id="%s_limit" type="number" value="%s" step="1" min="1" />',
                                esc_attr( $option['field_name'] ),
                                $i,
                                esc_attr( $option['id'] ),
                                esc_attr( $rule['limit'] )
                            );
                        ?>

                    </td>
                    <td>
                        
                        <?php
                            $options = '';
                            foreach ( $groups as $group ) {
                                $options .= sprintf( '<option value="%s" %s>%s</option>',
                                    esc_attr( $group->ID ),
                                    selected( $group->ID, $rule['group'], false ) ? 'selected' : false,
                                    esc_html( $group->post_title )
                                );
                            }
                            printf( '<select name="%s[%s][group]" id="%s_group" style="%s" class="%s">%s</select>',
                                esc_attr( $option['field_name'] ),
                                $i,
                                esc_attr( $option['id'] ),
                                esc_attr( $option['css'] ),
                                esc_attr( $option['class'] ),
                                $options
                            );
                            
                        ?>

                    </td>
                </tr>
                <?php endforeach; ?>

            </tbody>
        </table>
        <p class="description"><?php echo esc_html( $option['desc'] ); ?></p>
        <script type="text/html" id="tmpl-wcb2b_automatic_group_change-table-row">
            <tr>
                <td><input type="number" name="wcb2b_automatic_group_change[{{index}}][limit]" value="" step="1" min="1" /></td>
                <td>
                    <select name="wcb2b_automatic_group_change[{{index}}][group]">

                        <?php
                            foreach ( $groups as $group ) {
                                printf( '<option value="%s">%s</option>',
                                    $group->ID,
                                    $group->post_title
                                );
                            }
                        ?>

                    </select>
                </td>
            </tr>
        </script>
    </td>
</tr>