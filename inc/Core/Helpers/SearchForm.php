<?php
/**
 * Search Form.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\PostTypes\TripzzyPostType;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\SearchForm' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class SearchForm {

		/**
		 * Get search form fields.
		 *
		 * @since 1.0.0
		 * @since 1.1.4 Added Trip Price range slider.
		 */
		public static function get_fields() {
			$min_price = MetaHelpers::get_option( 'min_price', 0 );
			$max_price = MetaHelpers::get_option( 'max_price', 20000 );
			$fields    = array(
				'tripzzy_price'   =>
					array(
						'type'          => 'range',
						'label'         => __( 'Budget', 'tripzzy' ),
						'name'          => 'tripzzy_price',
						'id'            => 'tripzzy_price',
						'class'         => 'tripzzy_price',
						'placeholder'   => __( 'Budget', 'tripzzy' ),
						'required'      => true,
						'priority'      => 10,
						// Additional configurations.
						'is_new'        => false,
						'is_default'    => true,
						'enabled'       => true,
						'force_enabled' => true,
						'before_field'  => '<i class="fa-solid fa-dollar-sign"></i>',
						'attributes'    => array(
							'min'                        => $min_price,
							'max'                        => $max_price,
							'step'                       => 1,
							'value'                      => 1000,
							'value1'                     => $min_price,
							'value2'                     => $max_price,
							'round'                      => 2,
							'generate-labels-units'      => Currencies::get_symbol(),
							'generate-labels-text-color' => 'var(--tripzzy-search-text-color)',
							'unit_position'              => 'left',
						),
					),
				'destination'     =>
				array(
					'type'          => 'taxonomy_dropdown',
					'label'         => __( 'Destination', 'tripzzy' ),
					'name'          => 'tripzzy_trip_destination',
					'id'            => 'tripzzy_trip_destination',
					'class'         => 'tripzzy_trip_destination',
					'placeholder'   => __( 'Destination', 'tripzzy' ),
					'required'      => false,
					'priority'      => 20,
					'value'         => '',
					'taxonomy'      => 'tripzzy_trip_destination',
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
					'before_field'  => '<i class="fa-solid fa-location-dot"></i>',
					'attributes'    => array(
						'multiple',
						'search' => 'true',
					),
				),
				'trip_type'       =>
				array(
					'type'          => 'taxonomy_dropdown',
					'label'         => __( 'Trip Type', 'tripzzy' ),
					'name'          => 'tripzzy_trip_type',
					'id'            => 'tripzzy_trip_type',
					'class'         => 'tripzzy_trip_type',
					'placeholder'   => __( 'Trip Type', 'tripzzy' ),
					'required'      => false,
					'priority'      => 30,
					'value'         => '',
					'taxonomy'      => 'tripzzy_trip_type',
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
					'before_field'  => '<i class="fa-solid fa-suitcase-rolling"></i>',
					'attributes'    => array(
						'multiple',
						'search' => 'true',
					),
				),
				'trip_activities' =>
				array(
					'type'          => 'taxonomy_dropdown',
					'label'         => __( 'Trip Activities', 'tripzzy' ),
					'name'          => 'tripzzy_trip_activities',
					'id'            => 'tripzzy_trip_activities',
					'class'         => 'tripzzy_trip_activities',
					'placeholder'   => __( 'Trip Activities', 'tripzzy' ),
					'required'      => false,
					'priority'      => 40,
					'value'         => '',
					'taxonomy'      => 'tripzzy_trip_activities',
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => false,
					'before_field'  => '<i class="fa-solid fa-person-hiking"></i>',
					'attributes'    => array(
						'multiple',
						'search' => 'true',
					),
				),
			);
			return ArrayHelper::sort_by_priority( $fields, 'priority' );
		}

		/**
		 * Render search form Markup.
		 *
		 * @param array $args Form Arguments.
		 *
		 * @since 1.0.0
		 * @since 1.0.5 Search Text added in $args param.
		 */
		public static function render( $args = array() ) {
			$fields = $args['fields'] ?? array();
			if ( empty( $fields ) ) {
				$fields = self::get_fields();
			}
			$form_args   = array(
				'fields' => $fields,
			);
			$search_text = $args['searchText'] ?? __( 'Search', 'tripzzy' );
			?> 
			<form method="get" name="tripzzy_search" action="<?php echo esc_url( Page::get_url( 'search-result' ) ); ?>" >
				<div class="tripzzy-advanced-search-form">
					<?php Form::render( $form_args ); ?>
					<input type="submit" value="<?php echo esc_html( $search_text ); ?>" class="tz-btn tz-btn-solid" />
				</div>
			</form>
			<?php
		}
	}
}
