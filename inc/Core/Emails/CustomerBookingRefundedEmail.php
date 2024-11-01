<?php
/**
 * Class related to bookings.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Emails;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bookings;
use Tripzzy\Core\Bases\EmailBase;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Page;

if ( ! class_exists( 'Tripzzy\Core\Emails\CustomerBookingRefundedEmail' ) ) {

	/**
	 * Class related to bookings.
	 *
	 * @since 1.0.0
	 */
	class CustomerBookingRefundedEmail extends EmailBase {

		/**
		 * Trip booking id.
		 *
		 * @var int
		 */
		protected $booking_id = 0;

		/**
		 * From email id.
		 *
		 * @var string
		 */
		protected $from_email = '';

		/**
		 * To Email Ids.
		 *
		 * @var string
		 */
		protected $to_emails = '';

		/**
		 * Travelers data.
		 *
		 * @var array
		 */
		protected $checkout_info = array();

		/**
		 * Settings.
		 *
		 * @var array
		 */
		protected static $settings = array();

		/**
		 * Email Type
		 *
		 * @var string
		 */
		protected static $email_type = 'admin_booking_email';

		/**
		 * {@inheritDoc}
		 *
		 * @param integer $booking_id Trip booking id.
		 */
		public function __construct( $booking_id = 0 ) {

			self::$settings = Settings::get();

			if ( ! empty( self::$settings['disable_customer_notification'] ) ) {
				return;
			}

			$this->booking_id    = $booking_id;
			$this->checkout_info = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );

			// Config start.
			$this->init_from_email();
			$this->init_to_emails();

			// Email args.
			$args = array(
				'to'        => $this->to_emails,
				'from'      => $this->from_email,
				'subject'   => '',
				'trackback' => true,
			);
			parent::__construct( $args );
		}

		/**
		 * {@inheritDoc}
		 */
		public static function email_subject() {
			return self::$settings['customer_booking_refunded_notification_subject'] ?? 'Booking Refunded';
		}

		/**
		 * {@inheritDoc}
		 */
		public static function email_content() {

			$settings = self::$settings;
			$content  = $settings['customer_booking_refunded_notification_content'] ?? '';

			if ( $content ) {
				return $content;
			}

			return '<div class="tripzzy-admin" style="color: #5a5a5a; font-family: Roboto, sans-serif; margin: auto;">
			<p style="line-height: 1.55; font-size: 14px;">Hello %CUSTOMER_NAME%,</p>
			<p style="line-height: 1.55; font-size: 14px;">Your Payment has been refunded for booking id #%BOOKING_ID%.</p>

			<p style="text-align: center;">%SITENAME%</p>
			</div>';
		}

		/**
		 * List of admin booking email tags.
		 *
		 * @since 1.0.0
		 */
		public static function get_tags() {

			$tags = array(
				'%BOOKING_DETAILS%'     => __( 'Trip Booking details.', 'tripzzy' ),
				'%BOOKING_ID%'          => __( 'Booking ID of trip.', 'tripzzy' ),
				'%CUSTOMER_DETAILS%'    => __( 'Details of customers who booked the trip.', 'tripzzy' ),
				'%CUSTOMER_NAME%'       => __( 'Full name of the customer.', 'tripzzy' ),
				'%CUSTOMER_FIRST_NAME%' => __( 'First name of the customer.', 'tripzzy' ),
				'%CUSTOMER_LAST_NAME%'  => __( 'Last name of the customer.', 'tripzzy' ),
				'%DASHBOARD_URL%'       => __( 'URL of Dashboard where list of bookings are listed.', 'tripzzy' ),
				'%SITENAME%'            => __( 'Current website name.', 'tripzzy' ),
			);

			return apply_filters( 'tripzzy_filter_customer_booking_refunded_email_tags', $tags );
		}

		/**
		 * {@inheritDoc}
		 */
		public function init_tags() {
			$tag_keys = array_keys( self::get_tags() );

			foreach ( $tag_keys as $tag_key ) {
				switch ( $tag_key ) {
					case '%BOOKING_ID%':
						$this->set_tag_value( $tag_key, $this->booking_id );
						break;
					case '%BOOKING_DETAILS%':
						$this->set_tag_value( $tag_key, Bookings::render_booking_details( $this->booking_id, true ) );
						break;
					case '%CUSTOMER_DETAILS%':
						$this->set_tag_value( $tag_key, Bookings::render_customer_details( $this->booking_id, true, true ) );
						break;

					case '%CUSTOMER_NAME%':
						$first_name = isset( $this->checkout_info['billing_first_name'] ) ? $this->checkout_info['billing_first_name'] : '';
						$last_name  = isset( $this->checkout_info['billing_last_name'] ) ? $this->checkout_info['billing_last_name'] : '';
						$name       = trim( sprintf( '%s %s', $first_name, $last_name ) );
						$this->set_tag_value( $tag_key, $name );
						break;
					case '%CUSTOMER_FIRST_NAME%':
						$name = isset( $this->checkout_info['billing_first_name'] ) ? $this->checkout_info['billing_first_name'] : '';
						$this->set_tag_value( $tag_key, $name );
						break;
					case '%CUSTOMER_LAST_NAME%':
						$name = isset( $this->checkout_info['billing_last_name'] ) ? $this->checkout_info['billing_last_name'] : '';
						$this->set_tag_value( $tag_key, $name );
						break;
					case '%SITENAME%':
						$this->set_tag_value( $tag_key, wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES ) );
						break;
					case '%DASHBOARD_URL%':
						$this->set_tag_value( $tag_key, Page::get_url( 'dashboard' ) );
						break;
					default:
						break;
				}
			}
		}

		/**
		 * Init From Email ID.
		 *
		 * @return void
		 */
		public function init_from_email() {
			$from_email = self::$settings['customer_from_email'];
			if ( empty( $from_email ) ) {
				$from_email = get_bloginfo( 'admin_email' );
			}
			$this->from_email = $from_email;
		}

		/**
		 * Init To Email IDs.
		 *
		 * @return void
		 */
		public function init_to_emails() {
			$to_emails       = isset( $this->checkout_info['billing_email'] ) ? $this->checkout_info['billing_email'] : '';
			$this->to_emails = $to_emails;
		}
	}
}