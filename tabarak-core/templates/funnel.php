<?php
/**
 * Sales funnel template. Uses the active theme header and footer.
 *
 * @package Tabarak_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
get_header();
?>
<main id="primary" class="site-main tf-main">
	<?php Tabarak_Funnels::render(); ?>
</main>
<?php
get_footer();
