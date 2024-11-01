<?php
/**
 * Escaping Html class
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\EscapeHelper' ) ) {
	/**
	 * Escaping Html class
	 *
	 * @since 1.0.0
	 */
	class EscapeHelper {

		/**
		 * Wrapper method for WordPress wp_kses escaping function.
		 *
		 * @param string $data raw data to clean.
		 * @since 1.0.0
		 */
		public static function wp_kses( $data ) {
			$allowed_html = self::get_allowed_html();
			return wp_kses( $data, $allowed_html );
		}

		/**
		 * Get Allowed HTML Tags for escaping.
		 *
		 * @since 1.0.0
		 * @since 1.1.4 Added tc-range-slider as allowed tag.
		 */
		public static function get_allowed_html() {
			$allowed_html             = wp_kses_allowed_html( 'post' );
			$allowed_html['form']     = array(
				'name'   => true,
				'id'     => true,
				'class'  => true,
				'action' => true,
				'method' => true,
			);
			$allowed_html['input']    = array(
				'type'        => true,
				'name'        => true,
				'value'       => true,
				'placeholder' => true,
				'id'          => true,
				'class'       => true,
				'required'    => true,
				'data-*'      => true,
				'style'       => true,
				'checked'     => true,
			);
			$allowed_html['select']   = array(
				'name'     => true,
				'value'    => true,
				'id'       => true,
				'class'    => true,
				'required' => true,
				'data-*'   => true,
				'style'    => true,
			);
			$allowed_html['option']   = array(
				'value'    => true,
				'selected' => true,
			);
			$allowed_html['textarea'] = array(
				'name'        => true,
				'placeholder' => true,
				'id'          => true,
				'class'       => true,
				'required'    => true,
				'data-*'      => true,
				'style'       => true,
			);
			$allowed_html['iframe']   = array(
				'width'        => true,
				'height'       => true,
				'frameborder'  => true,
				'scrolling'    => true,
				'marginheight' => true,
				'marginwidth'  => true,
				'src'          => true,
			);
			$allowed_html['br']       = array(
				'class' => true,
				'id'    => true,
			);
			// SVG.
			$allowed_html['svg']             = array(
				'data-prefix' => true,
				'class'       => true,
				'data-icon'   => true,
				'xmlns'       => true,
				'viewBox'     => true,
				'viewbox'     => true,
				'width'       => true,
				'height'      => true,
			);
			$allowed_html['path']            = array(
				'd' => true,
			);
			$allowed_html['tc-range-slider'] = array(
				'class'                              => true,
				'id'                                 => true,
				'marks'                              => true,
				'marks-count'                        => true,
				'marks-values-count'                 => true,
				'value'                              => true,
				'value1'                             => true,
				'value2'                             => true,
				'step'                               => true,
				'round'                              => true,
				'generate-labels'                    => true,
				'generate-labels-text-color'         => true,
				'generate-labels-units'              => true,
				'min'                                => true,
				'max'                                => true,
				'moving-tooltip'                     => true,
				'moving-tooltip-distance-to-pointer' => true,
				'moving-tooltip-width'               => true,
				'moving-tooltip-height'              => true,
				'moving-tooltip-bg'                  => true,
				'moving-tooltip-text-color'          => true,
				'moving-tooltip-units'               => true,
				'keyboard-disabled'                  => true,
				'mousewheel-disabled'                => true,
				'css-links'                          => true,
				'pointers-min-distance'              => true,
				'unit_position'                      => true,
				'before_field'                       => true,
				'placeholder'                        => true,
			);

			$allowed_html['style'] = array(); // internal style.
			return apply_filters( 'tripzzy_filter_wp_kses_allowed_html_tags', $allowed_html );
		}
	}
}
