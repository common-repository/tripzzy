<?php
/**
 * User dashboard ajax class.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Ajax;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bookings;
use Tripzzy\Core\Payment;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Reviews;
use Tripzzy\Core\Helpers\UserProfile;
use Tripzzy\Core\Helpers\Wishlists;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Image;
use Tripzzy\Core\Traits\SingletonTrait;

if ( ! class_exists( 'Tripzzy\Core\Ajax\DashboardAjax' ) ) {
	/**
	 * User dashboard ajax class.
	 *
	 * @since 1.0.0
	 */
	class DashboardAjax {
		use SingletonTrait;

		/**
		 * Initialize ajax class.
		 */
		public function __construct() {
			add_action( 'wp_ajax_tripzzy_dashboard_profile', array( $this, 'profile' ) );
			add_action( 'wp_ajax_tripzzy_dashboard_bookings', array( $this, 'bookings' ) );
			add_action( 'wp_ajax_tripzzy_dashboard_wishlists', array( $this, 'wishlists' ) );
			add_action( 'wp_ajax_tripzzy_dashboard_reviews', array( $this, 'reviews' ) );
		}

		/**
		 * Handle user dashboard profile requests.
		 *
		 * @return void
		 */
		public function profile() {

			if ( ! is_user_logged_in() ) {
				wp_send_json_error( __( 'You are not logged in.', 'tripzzy' ) );
			}

			$message = '';
			$success = true;

			$userprofile  = new UserProfile();
			$profile_data = $userprofile->get();

			$get_requested_data = function ( $fields ) {
				if ( ! Nonce::verify() ) {
					return array();
				}
				$post_data = array();
				foreach ( $fields as $field ) {
					// Nonce already verified using Nonce::verify method.
					if ( isset( $_POST[ $field ] ) ) { // @codingStandardsIgnoreLine
						switch ( $field ) {
							case 'user_email':
							case 'billing_email':
								$post_data[ $field ] = sanitize_email( wp_unslash( $_POST[ $field ] ) ); // @codingStandardsIgnoreLine
								break;
							default:
							$post_data[ $field ] = sanitize_text_field( wp_unslash( $_POST[ $field ] ) ); // @codingStandardsIgnoreLine
								break;
						}
					}
				}
				return $post_data;
			};

			$data = $get_requested_data( array_keys( $profile_data ) ); // Anonymous function to get data of $_POST.
			if ( ! empty( $data ) ) {
				$data['ID'] = $userprofile->get_user_id();
				$validate   = $userprofile->validate( $data );

				if ( is_wp_error( $validate ) ) {

					$success = false;

					wp_send_json(
						array(
							'success' => $success,
							'message' => $validate->get_error_message(),
							'data'    => $userprofile->get(),
						)
					);
				}

				if ( $userprofile->get() !== $data ) {
					$success = $userprofile->update( $data );
					$message = $success ? __( 'Profile updated successfully.', 'tripzzy' ) : __( 'Something went wrong. Please try again later.', 'tripzzy' );
				} else {
					$success = false;
					$message = __( 'No changes were made to your profile.', 'tripzzy' );
				}
			}

			wp_send_json(
				array(
					'success' => $success,
					'message' => $message,
					'data'    => $userprofile->get(),
				)
			);
		}

		/**
		 * Render Booking datas.
		 */
		public function bookings() {

			if ( ! is_user_logged_in() ) {
				wp_send_json_error( __( 'You are not logged in.', 'tripzzy' ) );
			}

			$bookings = array();

			$args = array(
				'post_type'      => 'tripzzy_booking',
				'posts_per_page' => -1,
				'meta_key'       => MetaHelpers::get_prefix( 'user_id' ), // @phpcs:ignore
				'meta_value'     => get_current_user_id(), // @phpcs:ignore
			);

			$query = new \WP_Query( $args );

			while ( $query->have_posts() ) {
				$query->the_post();
				$booking_id = get_the_ID();
				$trip_ids   = Bookings::get_trip_ids( $booking_id );

				$trip_names = array_map(
					function ( $trip_id ) {
						return get_the_title( $trip_id );
					},
					$trip_ids
				);
				$trip_id    = null;
				if ( ! empty( $trip_ids ) ) {
					$trip_id = $trip_ids[0];
				}
				$total_booking = Bookings::get_total( $booking_id );
				$total_payment = Payment::get_total( $booking_id );
				$total_due     = $total_booking - $total_payment;
				$bookings[]    = array(
					'id'                   => $booking_id,
					'title'                => get_the_title(),
					'status'               => Bookings::get_booking_status( $booking_id ),
					'status_key'           => MetaHelpers::get_post_meta( $booking_id, 'booking_status' ),
					'trips'                => implode( ', ', $trip_names ),
					'trip_ids'             => $trip_ids,
					'render'               => Bookings::render( $booking_id, true ),
					'booking_date'         => get_the_date( 'U', $booking_id ),
					'img_url'              => Image::get_thumbnail_url( $trip_id ),
					'total_booking'        => $total_booking,
					'total_booking_markup' => Amount::display( $total_booking ),
					'total_payment'        => $total_payment,
					'total_payment_markup' => Amount::display( $total_payment ),
					'total_due'            => $total_due,
					'total_due_markup'     => Amount::display( $total_due ),
				);
			}

			wp_reset_postdata();

			wp_send_json( $bookings );
		}

		/**
		 * Handle user dashboard wishlists requests.
		 *
		 * @return void
		 */
		public function wishlists() {

			if ( ! is_user_logged_in() ) {
				wp_send_json_error( __( 'You are not logged in.', 'tripzzy' ) );
			}

			$get_requested_data = function () {
				if ( ! Nonce::verify() ) {
					return array();
				}
				$post_data = array();
				// Nonce already verified using Nonce::verify method.
				if ( isset( $_POST['trip_id'] ) ) { // @codingStandardsIgnoreLine
					$post_data['trip_id'] = absint( $_POST['trip_id'] ); // @codingStandardsIgnoreLine
				}
				return $post_data;
			};

			$data = $get_requested_data();

			if ( ! empty( $data ) ) {
				$data['value'] = false;

				$trip_ids = Wishlists::update( get_current_user_id(), $data );
			} else {
				$trip_ids = Wishlists::get( get_current_user_id() );
			}

			$trips = array();

			if ( is_array( $trip_ids ) && ! empty( $trip_ids ) ) {
				foreach ( $trip_ids as $trip_id ) {
					$trips[] = array(
						'id'      => $trip_id,
						'url'     => get_the_permalink( $trip_id ),
						'img_url' => Image::get_thumbnail_url( $trip_id ),
						'title'   => get_the_title( $trip_id ),
						'excerpt' => get_the_excerpt( $trip_id ),
					);
				}
			}

			wp_send_json( $trips );
		}

		/**
		 * Handle user dashboard reviews requests.
		 *
		 * @return void
		 */
		public function reviews() {

			if ( ! is_user_logged_in() ) {
				wp_send_json_error( __( 'You are not logged in.', 'tripzzy' ) );
			}

			wp_send_json( Reviews::get_user_reviews() );
		}
	}

	DashboardAjax::instance();
}
