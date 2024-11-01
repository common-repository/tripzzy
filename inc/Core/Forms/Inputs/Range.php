<?php
/**
 * Range Input.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms\Inputs;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\EscapeHelper;
use Tripzzy\Core\Helpers\Currencies;


if ( ! class_exists( 'Tripzzy\Core\Forms\Inputs\Range' ) ) {
	/**
	 * Range Input.
	 *
	 * @since 1.1.4
	 */
	class Range {
		/**
		 * Field array.
		 *
		 * @var $field
		 * @since 1.1.4
		 */
		protected $field;
		/**
		 * Field Type.
		 *
		 * @var $field_type
		 * @since 1.1.4
		 */
		protected static $field_type = 'range';

		/**
		 * Init Attributes defined in individual input class.
		 *
		 * @since 1.1.4
		 */
		public static function init_attribute() {
			add_filter( 'tripzzy_filter_field_attributes', array( 'Tripzzy\Core\Forms\Inputs\Range', 'register_attribute' ) );
		}

		/**
		 * Callback to init attributes.
		 *
		 * @param array $attribute Field data along with attributes.
		 * @since 1.1.4
		 */
		public static function register_attribute( $attribute ) {
			$attribute[ self::$field_type ] = array(
				'label' => __( 'Range', 'tripzzy' ),
				'class' => 'Tripzzy\Core\Forms\Inputs\Range',
				'attr'  => array(),
			);
			return $attribute;
		}

		/**
		 * Render.
		 *
		 * @param array $field   Field arguments.
		 * @param bool  $display Display field flag. whether return or display.
		 * @since 1.1.4
		 */
		public static function render( $field = array(), $display = true ) {
			$enabled       = isset( $field['enabled'] ) ? $field['enabled'] : true; // by default ebabled.
			$force_enabled = isset( $field['force_enabled'] ) ? $field['force_enabled'] : false; // by default disabled.
			if ( $enabled || $force_enabled ) {
				$value                = isset( $field['attributes']['value'] ) ? $field['attributes']['value'] : '';
				$value1               = isset( $field['attributes']['value1'] ) ? $field['attributes']['value1'] : '';
				$value2               = isset( $field['attributes']['value2'] ) ? $field['attributes']['value2'] : '';
				$round                = isset( $field['attributes']['round'] ) ? absint( $field['attributes']['round'] ) : 0;
				$generate_labels_unit = isset( $field['attributes']['generate-labels-units'] ) ? $field['attributes']['generate-labels-units'] : '%';
				$unit_position        = isset( $field['attributes']['unit_position'] ) ? $field['attributes']['unit_position'] : 'right';
				$placeholder          = $field['placeholder'] ?? 'Select';

				$attributes = '';
				if ( isset( $field['attributes'] ) ) {
					foreach ( $field['attributes'] as $attribute => $attribute_val ) {
						$attributes .= sprintf( ' %s="%s" ', esc_attr( $attribute ), esc_attr( $attribute_val ) );
					}
				}

				$before_field = '';
				if ( isset( $field['before_field'] ) ) {
					$before_field_class = isset( $field['before_field_class'] ) ? $field['before_field_class'] : '';
					$before_field       = sprintf( '<span class="tripzzy-before-field %s">%s</span>', esc_attr( $before_field_class ), wp_kses_post( $field['before_field'] ) );
				}

				$css_id        = ! empty( $field['id'] ) ? $field['id'] : 'tz-range-slider';
				$wrapper_class = ! empty( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';

				$assets_url      = sprintf( '%sassets/', TRIPZZY_PLUGIN_DIR_URL );
				$fontawesome_url = $assets_url . 'styles/fontawesome/css/all.min.css';

				if ( isset( $field['attributes']['value2'] ) ) {
					// multiple.
					$hidden_input = sprintf(
						'
						<input style="display:none" class="tripzzy-range-slider-input" type="checkbox" checked id="%s" name="%s" value="%s" />
						<input style="display:none" class="tripzzy-range-slider-input" type="checkbox" checked id="%s" name="%s" value="%s" />',
						$css_id . '-val1',
						$field['name'] . '[]',
						esc_attr( $value1 ),
						$css_id . '-val2',
						$field['name'] . '[]',
						esc_attr( $value2 )
					);
				} else {
					// single.
					$hidden_input = sprintf( '<input style="display:none" class="tripzzy-range-slider-input" type="checkbox" checked id="%s" name="%s" value="%s" />', $css_id . '-val1', $field['name'], esc_attr( $value ) );
				}

				$output = sprintf(
					'%s<div class="tripzzy-range-slider-input-wrapper %s">
					%s
					<tc-range-slider
					id="%s"
					class="tripzzy-tc-range-slider"
					generate-labels="true"
					generate-labels-units="%s"
					css-links="%s;"
					pointers-min-distance="1"
					keyboard-disabled="true"
					mousewheel-disabled="true"

					// Additional attrs
					round="%s"
					unit_position="%s"
					before_field="%s"
					placeholder="%s"
					%s
					>
					</tc-range-slider></div>',
					$before_field,
					$wrapper_class,
					$hidden_input,
					$css_id,
					$generate_labels_unit,
					$fontawesome_url,
					$round,
					$unit_position,
					esc_attr( $before_field ),
					$placeholder,
					$attributes
				);

				$allowed_html = EscapeHelper::get_allowed_html();
				if ( ! $display ) {
					return $output;
				}
				echo wp_kses( $output, $allowed_html );
			}
		}
	}
}
