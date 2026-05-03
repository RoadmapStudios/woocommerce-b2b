<?php

/**
 * Admin View: Group metabox
 *
 * @version 3.2.0
 */

defined( 'ABSPATH' ) || exit;

?>

<?php foreach ( $metaboxes as $metabox ) : ?>
<div class="wcb2b-metabox">

    <div class="wcb2b-metabox-title"><?php printf( '%s', $metabox['title'] ); ?></div>

    <?php foreach ( $metabox['options'] as $option ) : ?>
    <div class="wcb2b-metabox-option <?php echo $option['full'] ? 'wcb2b-metabox-option-full' : false; ?>">
        <div class="wcb2b-metabox-option-label"><?php printf( '%s', $option['label'] ); ?></div>
        <div class="wcb2b-metabox-option-data">
            <div class="wcb2b-metabox-option-field"><?php printf( '%s%s', $option['field'], $option['desc'] ); ?></div>
            <div class="wcb2b-metabox-option-helper"><?php printf( '%s', $option['helper'] ); ?></div>
        </div>
    </div>
    <?php endforeach; ?>

</div>
<?php endforeach; ?>