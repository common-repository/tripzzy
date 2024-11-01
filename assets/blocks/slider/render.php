<?php
/**
 * Tripzzy Slider Block.
 *
 * @since 1.0.8
 * @since 1.1.2 Added inline style insted of style written in style tag.
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Assets;

$block_id        = $attributes['blockId'] ?? '';
$autoplay        = empty( $attributes['autoplay'] ) ? false : $attributes['autoplay'];
$delay           = empty( $attributes['delay'] ) ? 3000 : $attributes['delay'];
$navigation      = empty( $attributes['navigation'] ) ? false : $attributes['navigation'];
$pagination      = empty( $attributes['pagination'] ) ? false : $attributes['pagination'];
$slides_per_view = empty( $attributes['slidesPerView'] ) ? false : $attributes['slidesPerView'];
$loop            = empty( $attributes['loop'] ) ? false : $attributes['loop'];

$swiper_attr = array(
	'autoplay'      => $autoplay,
	'delay'         => $delay,
	'navigation'    => $navigation,
	'pagination'    => $pagination,
	'slidesPerView' => $slides_per_view,
	'loop'          => $loop,
);

$swiper_attr = htmlspecialchars( wp_json_encode( $swiper_attr ) );

$block_class = array(
	'tripzzy-slider-block swiper',
	'tz-block-' . $block_id,
);

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => implode( ' ', $block_class ),
	)
);

$css_vars = array();
if ( isset( $attributes['primaryColor'] ) && ! empty( $attributes['primaryColor'] ) ) {
	$css_vars['--swiper-theme-color'] = $attributes['primaryColor'];
}
if ( isset( $attributes['primaryColorHover'] ) && ! empty( $attributes['primaryColorHover'] ) ) {
	$css_vars['--swiper-theme-color-hover'] = $attributes['primaryColorHover'];
}
if ( isset( $attributes['sliderHeight'] ) && ! empty( $attributes['sliderHeight'] ) ) {
	$css_vars['--swiper-slider-height'] = $attributes['sliderHeight'];
}
$styles = array(
	array(
		'selector' => '.tz-block-' . $block_id,
		'css'      => $css_vars,
	),
);
wp_register_style( 'tripzzy-' . $block_id, false, array(), TRIPZZY_VERSION );
wp_enqueue_style( 'tripzzy-' . $block_id );
wp_add_inline_style( 'tripzzy-' . $block_id, Assets::array_to_css( $styles ) );
?>
<div <?php echo wp_kses_data( $wrapper_attributes ) . 'data-swiper="' . esc_attr( $swiper_attr ) . '"'; ?>>
	<div class="swiper-wrapper">
		<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php if ( $pagination ) : ?>
	<div class="swiper-pagination"></div>
	<?php endif; ?>
</div><!-- .swiper -->
