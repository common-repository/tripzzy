<?php
/**
 * Trips.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Helpers\Taxonomy;
use Tripzzy\Core\Helpers\FilterPlus;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\Cookie;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Currencies;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;

use Tripzzy\Core\Forms\Inputs\Range;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripFilter' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class TripFilter {

		/**
		 * Get All filters.
		 */
		public static function get() {
			$settings   = Settings::get();
			$defaults   = Settings::default_settings();
			$taxonomies = TaxonomyBase::get_args();

			$filters = self::taxonomy_filters();
			// add settings to show/hide filter.
			foreach ( $filters as $taxonomy => $taxonomy_args ) {

				if ( $taxonomy_args['custom'] ) { // Apply custom filter settings.
					if ( ! isset( $settings['filters']['custom'][ $taxonomy ] ) ) {
						$settings['filters']['custom'][ $taxonomy ] = $defaults['filters']['custom'][ $taxonomy ];
					}
					$show = $settings['filters']['custom'][ $taxonomy ]['show'];
				} else { // Apply default taxonomy filter settings.
					if ( ! isset( $settings['filters']['default'][ $taxonomy ] ) ) {
						$settings['filters']['default'][ $taxonomy ] = $defaults['filters']['default'][ $taxonomy ];
					}
					$show = $settings['filters']['default'][ $taxonomy ]['show'];
				}
				$filters[ $taxonomy ]['show'] = $show;
			}
			$range_filters = self::range_filters( $settings );
			foreach ( $range_filters as $range => $range_args ) {
				if ( ! isset( $settings['filters']['range'][ $range ] ) ) {
					$settings['filters']['range'][ $range ] = $defaults['filters']['range'][ $range ];
				}
				$show                            = $settings['filters']['range'][ $range ]['show'];
				$range_filters[ $range ]['show'] = $show;
			}
			// Merge/add range slider data.
			$filters = wp_parse_args( $filters, $range_filters );

			$filters = apply_filters( 'tripzzy_filter_trip_filters', $filters );
			return ArrayHelper::sort_by_priority( $filters ); // Sort array by priority.
		}

		/**
		 * Get view mode for archive page.
		 */
		public static function get_view_mode() {
			$default_view_mode = 'list';
			$default_view_mode = apply_filters( 'tripzzy_filter_default_view_mode', $default_view_mode );
			$view_mode         = $default_view_mode;
			if ( Cookie::get( 'view_mode' ) ) {
				return Cookie::get( 'view_mode' );
			}
			return $view_mode;
		}

		/**
		 * Check if it has active filter/s or not.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function has_active_filters() {
			$filters = self::get();
			$active  = false;
			foreach ( $filters as $taxonomy => $filter ) {
				if ( $filter['show'] ) {
					$terms = Taxonomy::get_terms_hierarchy( $taxonomy );
					if ( count( $terms ) ) {
						$active = true;
						break;
					}
				}
			}
			return $active;
		}

		/**
		 * Check if it has active filter/s or not.
		 *
		 * @since 1.0.0
		 * @return bool
		 */
		public static function has_filter_button() {
			$settings = Settings::get();
			return $settings['show_filter_button'];
		}



		/**
		 * Render HTML for Taxonomies.
		 *
		 * @param string $taxonomy Taxonomy name.
		 * @param array  $filter Arguments.
		 * @since 1.0.0
		 */
		public static function taxonomies_render( $taxonomy, $filter ) {
			if ( ! $filter['show'] ) {
				return;
			}
			$label = isset( $filter['label'] ) ? $filter['label'] : __( 'Category', 'tripzzy' );
			$terms = Taxonomy::get_terms_hierarchy( $taxonomy );
			if ( ! count( $terms ) ) {
				return;
			}
			?>
			<div class="tz-filter-widget <?php echo esc_attr( $taxonomy ); ?>">
				<h3 class="tz-filter-widget-title"><?php echo esc_html( $label ); ?></h3>
				<div class="tz-filter-widget-content">
					<?php self::get_terms_markup( $terms, $taxonomy ); ?>
				</div>
			</div>

			<?php
		}

		/**
		 * Render HTML for Range Slider.
		 *
		 * @param string $name Slider name.
		 * @param array  $filter Arguments.
		 * @since 1.1.4
		 */
		public static function range_render( $name, $filter ) {
			if ( ! $filter['show'] ) {
				return;
			}
			$label       = isset( $filter['label'] ) ? $filter['label'] : __( 'Range', 'tripzzy' );
			$placeholder = isset( $filter['placeholder'] ) ? $filter['placeholder'] : __( 'Select', 'tripzzy' );

			// All Range Attributes.
			$all_attributes = self::range_filters_attributes();
			?>
			<div class="tz-filter-widget <?php echo esc_attr( $name ); ?>">
				<h3 class="tz-filter-widget-title"><?php echo esc_html( $label ); ?></h3>
				<div class="tz-filter-widget-content">
					<?php
					$field = array(
						'type'          => 'range',
						'name'          => $name,
						'id'            => $name,
						'class'         => $name,
						'placeholder'   => $placeholder,
						'required'      => true,
						'priority'      => $filter['priority'] ?? 10,
						'wrapper_class' => 'sm',

						// Additional configurations.
						'attributes'    => $all_attributes[ $name ] ?? array(),
					);
					Range::render( $field );
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * Render Template markup for taxonomy including hierarchy.
		 *
		 * @param array  $terms List of term.
		 * @param string $taxonomy Taxonomy name.
		 * @param bool   $children Has children or not.
		 */
		public static function get_terms_markup( $terms, $taxonomy = null, $children = false ) {

			$parent_count = 0;
			if ( is_array( $terms ) && count( $terms ) > 0 ) :
				$selected_terms = self::get_requested_taxonomy_terms( $taxonomy );
				if ( ! $children ) {
					?>
					<select name="<?php echo esc_attr( $taxonomy ); ?>" id="<?php echo esc_attr( $taxonomy ); ?>" class="tripzzy-filter-dropdown" multiple search="true" style="display:none">
					<?php
				}
				foreach ( $terms as $term ) {
					$term_class = $children ? 'child-term' : '';
					?>
					<option value="<?php echo esc_attr( $term->slug ); ?>" class="<?php echo esc_attr( $term_class ); ?>" <?php echo esc_attr( in_array( $term->slug, $selected_terms, true ) ? 'selected' : '' ); ?> >
						<?php echo esc_attr( $term->name ); ?> (<?php echo esc_html( $term->count ); ?>)
					</option>
					<?php
					if ( is_array( $term->children ) && count( $term->children ) > 0 ) {
						$_children = array();
						foreach ( $term->children as $term_child ) {
							$_children[ $term_child->term_id ] = $term_child;
						}
						call_user_func( array( __CLASS__, __FUNCTION__ ), $_children, $taxonomy, true ); // recursion if has child.
					}
				}
				if ( ! $children ) {
					?>
					</select>
					<?php
				}
			endif;
		}


		/**
		 * Callback to render Trip Filter section with all the filters.
		 *
		 * @hooked tripzzy_archive_before_content
		 */
		public static function render_trip_filters() {
			$has_filter_button = self::has_filter_button();

			if ( self::has_active_filters() ) :
				$labels = Strings::get()['labels'];
				?>
				<div class="tz-filter-widget-area">
					<div class="multiselect-dropdown-selected" id="dropdownSelected">
						<span>Selected</span>
					</div>
					<form id="tripzzy-filter-form" method="post">
						
						<div class="tz-filter-widget-container">
							<div class="tz-filter-header">
								<h2 class="tz-filter-title"><?php echo esc_html( $labels['filter_by'] ?? '' ); ?></h2>
								<button class="tz-btn tz-btn-sm tz-btn-reset tz-btn-reset-filter" type="reset" style="display:none"><?php echo esc_html( $labels['clear'] ?? '' ); ?></button>
								<input type="hidden" name="paged" value='1' id='tripzzy-paged' /> 
								<input type="hidden" name="has_filter_button" class="tripzzy-has-filter-button" value="<?php echo esc_attr( $has_filter_button ); ?>" />
							</div>
							<?php
							$tripzzy_filters = self::get();
							if ( is_array( $tripzzy_filters ) ) {
								foreach ( $tripzzy_filters as $tripzzy_filter_taxonomy => $tripzzy_filter ) {
									if ( $tripzzy_filter['show'] ) {
										call_user_func( $tripzzy_filter['callback'], $tripzzy_filter_taxonomy, $tripzzy_filter );
									}
								}
							}
							?>
							<?php if ( $has_filter_button ) : ?>
								<button type="submit" class="tz-btn tz-btn-solid w-full" id="tz-filter-form-submit-btn">Show</button>
							<?php endif; ?>
						</div>
					</form>
				</div>
				<?php
			endif;
		}

		/**
		 * All Range filters to add it in settings and filters as well.
		 *
		 * @since 1.1.4
		 */
		public static function range_filters() {
			$filters = array(
				'tripzzy_price' => array(
					'label'       => __( 'Budget', 'tripzzy' ),
					'placeholder' => __( 'Select', 'tripzzy' ),
					'callback'    => array( __CLASS__, 'range_render' ),
					'custom'      => false, // Always false for range.
					'type'        => 'range',
					'priority'    => 10,
				),
			);
			return apply_filters( 'tripzzy_filter_range_filters', $filters );
		}

		/**
		 * All Range filters to add it in settings and filters as well.
		 *
		 * @since 1.1.4
		 */
		private static function range_filters_attributes() {
			$min_price = MetaHelpers::get_option( 'min_price', 0 );
			$max_price = MetaHelpers::get_option( 'max_price', 20000 );

			// Request Data.
			$tripzzy_price = get_query_var( 'tripzzy_price' );
			if ( ! $tripzzy_price ) {
				$tripzzy_price = array();
			}
			$attributes = array(
				'tripzzy_price' => array(
					'min'                   => $min_price,
					'max'                   => $max_price,
					'step'                  => 1,
					'round'                 => 2,
					'generate-labels-units' => Currencies::get_symbol(),
					'unit_position'         => 'left',
					'value1'                => $tripzzy_price[0] ?? $min_price,
					'value2'                => $tripzzy_price[1] ?? $max_price,
				),
			);
			return $attributes;
		}

		/**
		 * All Taxonomy filters to add it in settings and filters as well.
		 *
		 * @since 1.0.0
		 */
		public static function taxonomy_filters() {
			$taxonomies = TaxonomyBase::get_args();

			$filters  = array();
			$priority = 100;
			foreach ( $taxonomies as $taxonomy => $taxonomy_args ) {
				if ( in_array( $taxonomy, self::skipped_taxonomies(), true ) ) {
					continue;
				}
				$filters[ $taxonomy ] = array(
					'label'    => $taxonomy_args['labels']['name'],
					'callback' => array( __CLASS__, 'taxonomies_render' ),
					'custom'   => false, // whether custom filters or not.
					'type'     => 'taxonomy', // To make all taxonomy filter as query args automatically.
					'priority' => $priority,
				);
				$priority            += 10;
			}

			$custom_taxonomies = FilterPlus::get();
			if ( is_array( $custom_taxonomies ) && count( $custom_taxonomies ) > 0 ) {
				foreach ( $custom_taxonomies as $slug => $custom_taxonomy ) {
					$filters[ $slug ] = array(
						'label'    => $custom_taxonomy['label'],
						'callback' => array( __CLASS__, 'taxonomies_render' ),
						'custom'   => true, // whether custom filters or not.
						'type'     => 'taxonomy', // Just for data format consistency. because all custom filters are taxonomy itself.
						'priority' => $priority,
					);
					$priority        += 10;
				}
			}
			return apply_filters( 'tripzzy_filter_taxonomy_filters', $filters );
		}

		/**
		 * Default Settings key for filters.
		 *
		 * @param array $default_settings Default settings keys for filters.
		 * @since 1.0.0
		 * @since 1.1.4 Added Range filter keys in default settings keys.
		 */
		public static function default_settings_keys( $default_settings ) {
			$filters          = array();
			$taxonomy_filters = self::taxonomy_filters();
			$range_filters    = self::range_filters();
			foreach ( $range_filters as $name => $range_args ) {
				$filter                    = array(
					'show'  => true,
					'label' => $range_args['label'],
				);
				$filters['range'][ $name ] = $filter;
			}

			foreach ( $taxonomy_filters as $taxonomy => $taxonomy_args ) {
				if ( in_array( $taxonomy, self::skipped_taxonomies(), true ) ) {
					continue;
				}
				$filter = array(
					'show'  => true,
					'label' => $taxonomy_args['label'],
				);
				if ( isset( $taxonomy_args['custom'] ) && $taxonomy_args['custom'] ) {
					$filters['custom'][ $taxonomy ] = $filter;
				} else {
					$filters['default'][ $taxonomy ] = $filter;
				}
			}

			$default_settings['filters'] = $filters;
			return $default_settings;
		}

		/**
		 * Taxonomy which need to remove from filters.
		 *
		 * @return array
		 */
		public static function skipped_taxonomies() {
			return array( 'tripzzy_price_category', 'tripzzy_trip_includes', 'tripzzy_trip_excludes' );
		}

		/**
		 * Alternative way to whole get request method to get terms.
		 *
		 * @param string $taxonomy Taxonomoy name.
		 * @return array
		 */
		public static function get_requested_taxonomy_terms( $taxonomy = '' ) {
			if ( ! $taxonomy ) {
				return array();
			}

			if ( ! Nonce::verify() ) {
				return array();
			}
			// Nonce already verified using Nonce::verify method.
			$terms = isset( $_GET[ $taxonomy ] ) ? array_map( 'sanitize_text_field', wp_unslash( $_GET[ $taxonomy ] ) ) : array(); // @codingStandardsIgnoreLine
			return $terms;
		}
	}
}
