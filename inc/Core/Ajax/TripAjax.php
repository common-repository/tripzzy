<?php
/**
 * Trip ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Template;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\FilterPlus;
use Tripzzy\Core\Helpers\Cookie;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Pagination;

if ( ! class_exists( 'Tripzzy\Core\Ajax\TripAjax' ) ) {
	/**
	 * Trip Ajax class.
	 *
	 * @since 1.0.0
	 */
	class TripAjax {
		use SingletonTrait;

		/**
		 * All available messages.
		 *
		 * @var array
		 */
		private $messages;
		/**
		 * All available labels.
		 *
		 * @var array
		 */
		private $labels;


		/**
		 * Constructor.
		 */
		public function __construct() {
			$this->messages = Strings::messages();
			$this->labels   = Strings::labels();
			// Admin side Ajax.
			add_action( 'wp_ajax_tripzzy_get_trip', array( $this, 'get' ) );
			add_action( 'wp_ajax_tripzzy_update_trip', array( $this, 'update' ) );
			add_action( 'wp_ajax_tripzzy_check_trip_code_exist', array( $this, 'check_trip_code_exist' ) );

			// Frontend side Ajax.
			// Archive page list trip.
			add_action( 'wp_ajax_nopriv_tripzzy_get_trips', array( $this, 'render_trips' ) );
			add_action( 'wp_ajax_tripzzy_get_trips', array( $this, 'render_trips' ) );

			// Archive page set view mode.
			add_action( 'wp_ajax_nopriv_tripzzy_set_view_mode', array( $this, 'set_view_mode' ) );
			add_action( 'wp_ajax_tripzzy_set_view_mode', array( $this, 'set_view_mode' ) );

			// List trip available Dates.
			add_action( 'wp_ajax_nopriv_tripzzy_render_trip_dates', array( $this, 'render_trip_dates' ) );
			add_action( 'wp_ajax_tripzzy_render_trip_dates', array( $this, 'render_trip_dates' ) );

			// Change Package category as per package id provided.
			add_action( 'wp_ajax_nopriv_tripzzy_get_package_categories', array( $this, 'render_package_categories' ) );
			add_action( 'wp_ajax_tripzzy_get_package_categories', array( $this, 'render_package_categories' ) );

			// Set Featured trip.
			add_action( 'wp_ajax_tripzzy_set_featured_trip', array( $this, 'set_featured_trip' ) );
		}

		/**
		 * Ajax callback to get trip data.
		 *
		 * @since 1.0.0
		 */
		public function get() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->messages['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}

			// Nonce already verified using Nonce::verify method.
			$trip_id = isset( $_GET['trip_id'] ) ? absint( $_GET['trip_id'] ) : ''; // @codingStandardsIgnoreLine

			$response_data = Trip::get( $trip_id );
			$response      = array(
				'trip' => $response_data,
			);
			wp_send_json_success( $response, 200 );
		}

		/**
		 * Ajax callback to set form data.
		 *
		 * @since 1.0.0
		 */
		public function update() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->messages['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}

			$trip_id       = isset( $_GET['trip_id'] ) ? absint( $_GET['trip_id'] ) : 0; // @codingStandardsIgnoreLine
			$response_data = Trip::update( $trip_id );
			if ( $trip_id && $response_data ) {
				wp_send_json_success( $response_data, 200 );
			}
		}

		/**
		 * Check Trip code exists.
		 *
		 * @since 1.0.0
		 */
		public function check_trip_code_exist() {
			if ( ! Nonce::verify() ) {
				$message = array( 'message' => $this->messages['nonce_verification_failed'] );
				wp_send_json_error( $message );
			}
			// Nonce already verified using Nonce::verify method.
			$trip_id   = isset( $_GET['trip_id'] ) ? absint( $_GET['trip_id'] ) : 0; // @codingStandardsIgnoreLine
			$trip_code = isset( $_GET['trip_code'] ) ? sanitize_text_field( wp_unslash( $_GET['trip_code'] ) ) : ''; // @codingStandardsIgnoreLine
			$res       = Trip::get_trip_id_by_code( $trip_code );
			if ( ! $res || absint( $trip_id ) === absint( $res ) ) { // If empty or current post=response post.
				// Check if trip code consist another trip/post id.
				$temp_data = explode( '-', $trip_code );
				if ( count( $temp_data ) > 1 ) {
					$temp_trip_id   = (int) $temp_data[1];
					$temp_trip_code = Trip::get_code( $temp_trip_id );
					if ( (int) $trip_id !== (int) $temp_trip_id && $trip_code === $temp_trip_code ) {
						wp_send_json_error( $trip_code );
					}
				}
				wp_send_json_success( $trip_code );
			}
			wp_send_json_error( $trip_code );
		}

		/**
		 * Ajax Callback to render all trips with Markups. Need To move logic in helper file.
		 *
		 * @since 1.0.0
		 * @since 1.0.6 Implemented get_trips_query method to fetch query.
		 * @since 1.0.9 No Trips found logic changed.
		 * @since 1.1.3 Added Sticky trips logic.
		 * @since 1.1.5 Fix sticky trip logic on taxonomy page.
		 * @since 1.1.6 Implemented Request::sanitize_input to get data and checked $load_filtered_data.
		 */
		public function render_trips() {
			$data  = Request::sanitize_input( 'INPUT_PAYLOAD' );
			$paged = isset( $data['paged'] ) ? $data['paged'] : 1;
			$query = self::get_trips_query();

			$is_trips           = $data['is_trips'] ?? false;
			$is_taxonomy        = $data['is_taxonomy'] ?? false;
			$load_filtered_data = ! ! ( $data['loadDataFromFilters'] ?? false );
			$load_more_click    = ! ! ( $data['loadMoreClicked'] ?? false ); // After filter if load more trip button is clicked.
			$sticky_posts       = get_option( 'sticky_posts' );
			ob_start();
			if ( $is_trips && ! $is_taxonomy && ! $load_filtered_data && ! $load_more_click && is_array( $sticky_posts ) && count( $sticky_posts ) > 0 ) {
				$args         = array(
					'post_type'   => 'tripzzy',
					'paged'       => $paged,
					'post_status' => array( 'publish' ),
					'post__in'    => get_option( 'sticky_posts' ),
				);
				$sticky_query = new \WP_Query( $args );

				while ( $sticky_query->have_posts() ) {
					$sticky_query->the_post();
					Template::get_template_part( 'content', 'archive-tripzzy' );
				}
				wp_reset_postdata();
			}

			while ( $query->have_posts() ) {
				$query->the_post();
				Template::get_template_part( 'content', 'archive-tripzzy' );
			}
			$found_posts      = $query->found_posts;
			$found_posts_html = esc_html__( 'No trips found.', 'tripzzy' );
			wp_reset_postdata();
			$trips = ob_get_clean();

			if ( $found_posts > 0 ) {
				/*
				 * Translators: %s Found Posts.
				 */
				$found_posts_html = sprintf( _n( '%s trip found.', '%s trips found.', $found_posts, 'tripzzy' ), number_format_i18n( $found_posts ) );
			}

			$response = array(
				'trips'            => Strings::trim_nl( $trips ),
				'found_posts_html' => $found_posts_html,
				'found_posts'      => $found_posts,
				'paged'            => $paged,
				'max_num_pages'    => $query->max_num_pages,
			);
			wp_send_json_success( $response );
		}

		/**
		 * Render Trip Dates.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get single data.
		 * @since 1.1.7 Check for has_seasonal_pricing
		 * @return void
		 */
		public function render_trip_dates() {

			$trip_id = Request::sanitize_input( 'INPUT_PAYLOAD', 'trip_id' );
			$trip    = new Trip( $trip_id );

			$packages  = $trip->packages();
			$package   = $packages->get_package();
			$category  = $package ? $package->get_category() : null;
			$price     = $category ? $category->get_price() : 0;
			$price_per = $trip->get_price_per();

			$dates      = $trip->dates();
			$trip_dates = $dates->get_dates();

			$has_seasonal_pricing = $trip->has_seasonal_pricing();
			ob_start();
			foreach ( $trip_dates as $trip_date ) :
				$start_date = $trip_date['start_date'];
				$end_date   = $trip_date['end_date'] ?? $trip_date['start_date'];
				if ( $has_seasonal_pricing ) {
					$packages = $trip->packages( null, compact( 'start_date' ) );
					$package  = $packages->get_package();
					$category = $package ? $package->get_category() : null;
					$price    = $category ? $category->get_price() : 0;
				}
				$booking_data = array(
					'trip_id'    => $trip_id,
					'start_date' => $start_date,
				);
				$times_data   = '';
				if ( isset( $trip_date['times'] ) && ! empty( $trip_date['times'] ) ) {
					$times_data = sprintf( 'data-times=%s', wp_json_encode( $trip_date['times'] ) );
				}
				?>
				<div class="tripzzy-dates-content" data-initial-price="<?php echo esc_attr( Amount::display( $price ) ); ?>" <?php echo esc_attr( $times_data ); ?> data-trip-booking='<?php echo esc_attr( wp_json_encode( $booking_data ) ); ?>'> <!-- Repeator -->
					<ul>
						<li class="tz-departure-list-start-date-wrapper">
							<span class="label"><?php echo esc_html( $this->labels['from'] ?? 'From' ); ?></span>
							<strong><?php echo esc_html( $dates->format( $start_date, 'j F Y' ) ); ?></strong>
						</li>
						<?php if ( $end_date ) : ?>
							<li class="tz-departure-list-end-date-wrapper">
								<span class="label">To</span>
								<strong><?php echo esc_html( $dates->format( $end_date, 'j F Y' ) ); ?></strong>
							</li>
						<?php endif; ?>
						<li class="tz-departure-list-price-wrapper">
							<span class="label"><?php echo esc_html( $this->labels['from'] ?? 'From' ); ?></span>
							<strong class="tz-departure-list-from-price"><?php echo esc_html( Amount::display( $price ) ); ?></strong><span> / <?php echo esc_html( $price_per ); ?></span>
						</li>
						<li class="tz-departure-list-book-now-wrapper">
							<div class="tz-departure-list-book-now">
								<a href="#" class="tripzzy__booking-button tz-btn tz-btn-outline tz-btn-full" data-booknow-text="<?php echo esc_attr_x( 'Book Now', 'Display Price Category for checkout.', 'tripzzy' ); ?>" data-alt-text=<?php echo esc_attr_x( 'Cancel', 'Booking cancel button.', 'tripzzy' ); ?> data-trip-booking-btn><?php echo esc_attr_x( 'Book Now', 'Book Now Button', 'tripzzy' ); ?></a>
							</div>
						</li>
					</ul>

					<div class="tripzzy__booking-categories-wrapper hidden" data-trip-booking-categories></div>
				</div>
				<?php
			endforeach;
			$content = ob_get_contents();
			ob_end_clean();

			$response = array(
				'dates'             => $content,
				'next_start_date'   => $dates->next_start_date, // for recurring date.
				'date_limit_exceed' => $dates->date_limit_exceed, // For recurring date.
				'pagination'        => $dates->pagination, // For fixed departure date.
			);
			wp_send_json_success( $response );
		}

		/**
		 * Render package categories as per package id.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get data.
		 * @return void
		 */
		public function render_package_categories() {
			$data       = Request::sanitize_input( 'INPUT_PAYLOAD' );
			$trip_id    = $data['trip_id'];
			$package_id = $data['package_id'];
			$start_date = $data['start_date'];
			$trip       = new Trip( $trip_id );

			$price_per_key        = $trip->price_per;
			$has_seasonal_pricing = $trip->has_seasonal_pricing();

			$packages = $trip->packages();
			if ( $has_seasonal_pricing ) {
				$packages = $trip->packages( null, compact( 'start_date' ) );
			}
			$package    = $packages->get_package( $package_id );
			$categories = $package ? $package->get_categories() : null;

			// Display From price.
			$category   = $package->get_category();
			$from_price = 0;
			if ( $category ) {
				$from_price = $category->get_price();
			}
			ob_start();

			foreach ( $categories as $package_category ) {
				$package_category_id = $package_category->get_id();
				if ( ! get_term( $package_category_id ) ) {
					continue;
				}

				?>
				<div class="tripzzy__category-item" style="display:flex;justify-content:space-between;">
					<div class="tripzzy__category-title">
						<?php echo esc_html( $package_category->get_title() ); ?>
					</div>
					<div class="tripzzy__category-counter">
						<span class="qty">Qty:</span>
						<input min="0" type="number" data-category-counter="<?php echo absint( $package_category_id ); ?>"/>
					</div>
					<?php if ( 'person' === $price_per_key ) : ?>
						<div class="tripzzy__category-price">
							<?php
							if ( $package_category->get_sale_price() ) {
								?>
								<del><?php echo esc_html( Amount::display( $package_category->get_regular_price() ) ); ?></del>
								<?php
							}
							echo esc_html( Amount::display( $package_category->get_price() ) );
							?>
							</div>
					<?php endif; ?>
				</div>
				<?php
			}

			$content = ob_get_contents();
			ob_end_clean();

			$response = array(
				'categories' => $content,
				'from_price' => Amount::display( $from_price ),
			);
			wp_send_json_success( $response );
		}

		/**
		 * Set View Mode.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get single data.
		 */
		public function set_view_mode() {
			$view_mode = Request::sanitize_input( INPUT_POST, 'view_mode' );
			Cookie::set( 'view_mode', $view_mode );
		}

		/**
		 * Set Featured Trip.
		 *
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input to get single data.
		 */
		public function set_featured_trip() {
			if ( ! Nonce::verify() ) {
				$message = array(
					'message' => $this->messages['nonce_verification_failed'],
				);
				wp_send_json_error( $message );
			}

			$trip_id = Request::sanitize_input( 'INPUT_PAYLOAD', 'trip_id' );
			$trip    = new Trip( $trip_id );

			$is_featured = $trip->is_featured();

			MetaHelpers::update_post_meta( $trip_id, 'featured', ! $is_featured );
			$response = array( 'trip_id' => $trip_id );
			wp_send_json_success( $response );
		}

		/**
		 * Get Query object to fetch trips.
		 *
		 * @param array $data Query param.
		 *
		 * @since 1.0.6
		 * @since 1.1.3 Added Sticky trips logic.
		 * @since 1.1.5 Fix sticky logic on taxonomy page.
		 * @since 1.1.6 Implemented Request::sanitize_input to get data and checked $load_filtered_data.
		 * @return object
		 */
		public static function get_trips_query( $data = array() ) {
			if ( ! $data ) {
				$data = Request::sanitize_input( 'INPUT_PAYLOAD' );
			}
			$paged = isset( $data['paged'] ) ? $data['paged'] : 1;
			$args  = array(
				'post_type'   => 'tripzzy',
				'paged'       => $paged,
				'post_status' => array( 'publish' ),
			);

			$is_trips           = $data['is_trips'] ?? false;
			$is_taxonomy        = $data['is_taxonomy'] ?? false;
			$load_filtered_data = ! ! ( $data['loadDataFromFilters'] ?? false ); // Trip filter.
			$load_more_click    = ! ! ( $data['loadMoreClicked'] ?? false ); // After filter if load more trip button is clicked.
			if ( $is_trips && ! $is_taxonomy && ! $load_filtered_data && ! $load_more_click ) {
				$args['post__not_in'] = get_option( 'sticky_posts' );
			}

			if ( $data ) {
				$args['tax_query']['relation'] = 'AND';

				$taxonomies = TaxonomyBase::get_args();
				foreach ( $taxonomies as $taxonomy => $taxonomy_args ) {
					if ( isset( $data[ $taxonomy ] ) && ! empty( $data[ $taxonomy ] ) ) {
						if ( is_array( $data[ $taxonomy ] ) ) {
							// Filter empty value from the taxonomy list to perform tax query.
							$terms = array_filter(
								$data[ $taxonomy ],
								function ( $value ) {
									return ! empty( $value );
								}
							);
						} else {
							$terms = array( $data[ $taxonomy ] );
						}
						if ( ! empty( $terms ) ) {
							$args['tax_query'][] = array(
								'taxonomy' => $taxonomy,
								'field'    => 'slug',
								'terms'    => $terms,
							);
						}
					}
				}

				$custom_taxonomies = FilterPlus::get();
				if ( is_array( $custom_taxonomies ) && count( $custom_taxonomies ) > 0 ) {
					foreach ( $custom_taxonomies as $slug => $custom_taxonomy ) {
						if ( isset( $data[ $slug ] ) && ! empty( $data[ $slug ] ) ) {
							$args['tax_query'][] = array(
								'taxonomy' => $slug,
								'field'    => 'slug',
								'terms'    => is_array( $data[ $slug ] ) ? $data[ $slug ] : array( $data[ $slug ] ),
							);
						}
					}
				}

				// Meta Query @since 1.1.4. Temp solution for filters. Data from filter request is comes with extra [].
				if ( isset( $data['tripzzy_price'] ) || isset( $data['tripzzy_price[]'] ) ) {
					if ( isset( $data['tripzzy_price[]'] ) ) {
						$price_data = $data['tripzzy_price[]'];
					} else {
						$price_data = $data['tripzzy_price'];
					}
					$min_price = $price_data[0] ?? 0;
					$max_price = $price_data[1] ?? 20000;

					$args['meta_query'] = array( // @phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						'relation' => 'AND',
						array(
							'key'     => 'tripzzy_trip_price',
							'value'   => $min_price,
							'type'    => 'numeric',
							'compare' => '>=',
						),
						array(
							'key'     => 'tripzzy_trip_price',
							'value'   => $max_price,
							'type'    => 'numeric',
							'compare' => '<=',
						),
					);
				}
			}
			return new \WP_Query( $args );
		}
	}
}
