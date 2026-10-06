<?php
/**
 * The sidebar.
 *
 * @package Tabarak_Electronics
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! is_active_sidebar( 'shop-sidebar' ) ) {
    return;
}
?>
<aside id="secondary" class="widget-area tabarak-sidebar" role="complementary">
    <?php dynamic_sidebar( 'shop-sidebar' ); ?>
</aside>
