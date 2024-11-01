<?php
/**
 * Tripzzy Send Emails.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Emails\AdminBookingEmail;
use Tripzzy\Core\Emails\AdminEnquiryEmail;
use Tripzzy\Core\Emails\CustomerBookingEmail;
use Tripzzy\Core\Emails\CustomerBookingCancelationEmail;
use Tripzzy\Core\Emails\CustomerBookingRefundedEmail;


if ( ! class_exists( 'Tripzzy\Core\SendEmails' ) ) {
	/**
	 * SendEmails main class.
	 */
	class SendEmails {

		/**
		 * Settings.
		 *
		 * @var array
		 */
		protected static $settings;

		/**
		 * Initialize SendEmails.
		 *
		 * @return void
		 */
		public static function init() {
			self::$settings = Settings::get();
			add_action( 'tripzzy_after_booking', array( __CLASS__, 'send_booking_emails' ), 30, 2 );
			add_action( 'tripzzy_after_enquiry', array( __CLASS__, 'send_enquiry_emails' ) );

			add_action( 'tripzzy_booking_status_changed', array( __CLASS__, 'send_update_booking_notification' ), 10, 3 );
		}

		/**
		 * Send booking-related emails while making a booking like booking emails to admin, customer, etc.
		 *
		 * @param int   $booking_id Booking ID.
		 * @param array $data Booking related data.
		 *
		 * @return void
		 */
		public static function send_booking_emails( $booking_id, $data ) {
			$has_payment                   = ! ! $data['payment_details'] ?? false;
			$disable_admin_notification    = ! ! self::$settings['disable_admin_notification'] ?? false;
			$disable_customer_notification = ! ! self::$settings['disable_customer_notification'] ?? false;

			if ( ! $disable_admin_notification ) {
				$email     = new AdminBookingEmail( $booking_id );
				$mail_sent = $email->send();
			}

			if ( ! $disable_customer_notification ) {
				$email     = new CustomerBookingEmail( $booking_id );
				$mail_sent = $email->send();
			}
		}

		/**
		 * Send enquiry emails.
		 *
		 * @param int $enquiry_id Enquiry ID.
		 *
		 * @return void
		 */
		public static function send_enquiry_emails( $enquiry_id ) {
			$disable_admin_notification   = ! ! self::$settings['disable_admin_notification'] ?? false;
			$disable_enquiry_notification = ! ! self::$settings['disable_enquiry_notification'] ?? false;

			if ( $disable_admin_notification || $disable_enquiry_notification ) {
				return;
			}
			$email     = new AdminEnquiryEmail( $enquiry_id );
			$mail_sent = $email->send();
		}

		/**
		 * Send Email on booking status update.
		 *
		 * @param int   $booking_id Booking Id.
		 * @param boool $send_notification Whether send notification or not.
		 * @param boool $updated_booking_status Changed booking status.
		 *
		 * @return void
		 */
		public static function send_update_booking_notification( $booking_id, $send_notification, $updated_booking_status ) {

			if ( $send_notification ) {
				$disable_customer_notification = ! ! self::$settings['disable_customer_notification'] ?? false;

				switch ( $updated_booking_status ) {
					case 'canceled':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingCancelationEmail( $booking_id );
							$email->send();
						}
						break;
					case 'refunded':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingRefundedEmail( $booking_id );
							$email->send();
						}
						break;
					case 'booked':
						if ( ! $disable_customer_notification ) {
							$email = new CustomerBookingEmail( $booking_id );
							$email->send();
						}
						break;

				}
			}
		}
	}
}
