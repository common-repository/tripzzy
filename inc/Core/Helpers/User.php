<?php
/**
 * User Class to manage User.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Settings;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\User' ) ) {
	/**
	 * User Class Defination.
	 *
	 * @since 1.0.0
	 */
	class User {

		use DataTrait;

		/**
		 * Initialize User class.
		 *
		 * @return void
		 */
		public static function init() {
			add_filter( 'wp_login', array( __CLASS__, 'user_logged_in_action' ), 10, 2 );

			// Create a user while making a booking.
			add_action( 'tripzzy_customer_data_updated', array( __CLASS__, 'create_user_on_booking' ), 10, 2 );

			/**
			 * Send password reset email after user created.
			 */
			add_action( 'tripzzy_user_created', array( __CLASS__, 'send_user_creation_email' ) );
		}

		/**
		 * Returns WP_Roles instance.
		 *
		 * @return \WP_Roles
		 */
		public static function get_wp_roles() {
			global $wp_roles;

			if ( ! isset( $wp_roles ) && class_exists( 'WP_Roles' ) ) {
				$wp_roles = new \WP_Roles(); // @codingStandardsIgnoreLine
			}

			return $wp_roles;
		}

		/**
		 * List of available User roles for Tripzzy.
		 *
		 * @return array
		 */
		public static function roles() {
			$roles = array(
				'manager'  => array(
					'role'         => 'manager',
					'display_name' => __( 'Manager', 'tripzzy' ),
					'capabilities' => array(
						'edit_posts'   => true,
						'delete_posts' => true,
					),
				),
				'customer' => array(
					'role'         => 'customer',
					'display_name' => __( 'Customer', 'tripzzy' ),
					'capabilities' => array( 'read' => true ),
				),
			);

			return apply_filters( 'tripzzy_filter_user_roles', $roles );
		}

		/**
		 * Add all Tripzzy User roles.
		 * Need to call only once so called it in activation hook.
		 *
		 * @return void
		 */
		public static function add_roles() {
			self::get_wp_roles();
			$roles = self::roles();
			foreach ( $roles as $user ) {
				/* translators: %s is the unprefixed display name of a role. */
				add_role( self::get_prefix( $user['role'] ), sprintf( __( 'Tripzzy %s', 'tripzzy' ), esc_html( $user['display_name'] ) ), $user['capabilities'] );
			}
		}

		/**
		 * Remove all Tripzzy User roles.
		 * Need to remove on plugin deactivation.
		 *
		 * @return void
		 */
		public static function remove_roles() {
			self::get_wp_roles();
			$roles = self::roles();
			foreach ( $roles as $user ) {
				remove_role( self::get_prefix( $user['role'] ) );
			}
		}

		/**
		 * Fetch all user list
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_list() {
			// User Lists.
			$users     = get_users();
			$user_list = array();
			foreach ( $users as $i => $user_data ) {
				$data  = $user_data->data;
				$label = $data->user_login;  // fallback.
				if ( $data->display_name ) {
					$label = $data->display_name;
				}
				$user_list[ $i ]['label'] = $label;
				$user_list[ $i ]['value'] = $data->ID;
			}
			return $user_list;
		}

		/**
		 * Create User while made a booking if not logged in.
		 *
		 * @param int $customer_id Customer id.
		 * @param int $booking_id Booking id.
		 * @return void
		 */
		public static function create_user_on_booking( $customer_id, $booking_id ) {
			$user_id     = get_current_user_id();
			$settings    = Settings::get();
			$create_user = ! ! $settings['create_user_on_booking'] ?? true;

			if ( ! $user_id && $create_user ) {
				$checkout_info = MetaHelpers::get_post_meta( $booking_id, 'checkout_info' );
				$email         = $checkout_info['billing_email'] ?? '';
				$username      = '';
				if ( $email ) {
					$username = current( explode( '@', $email ) );
				}
				// User created or error.
				$user_id = self::create( $email, $username );

				// Update and sync user and customer id once customer and user created successfully.
				if ( ! is_wp_error( $user_id ) ) {
					// update user id in customer meta.
					MetaHelpers::update_post_meta( $customer_id, 'user_id', $user_id );
					// Add/Update customer id in user meta.
					MetaHelpers::update_user_meta( $user_id, 'customer_id', $customer_id );
				}
			}

			// User created or logged in.
			if ( $user_id && ! is_wp_error( $user_id ) ) {
				// Update user id in booking meta to find who booked this trip.
				MetaHelpers::update_post_meta( $booking_id, 'user_id', $user_id );
			}
		}

		/**
		 * Create a new User.
		 *
		 * @param  string $email User email.
		 * @param  string $username User username.
		 * @param  string $password User password.
		 * @return int|WP_Error Returns WP_Error on failure, Int (user ID) on success.
		 */
		public static function create( $email, $username = '', $password = '' ) {
			// Error handling part remaining.
			// Check the email address.
			if ( empty( $email ) || ! is_email( $email ) ) {
				return;
			}

			if ( email_exists( $email ) ) {
				return;
			}

			$new_user_data = array(
				'user_login' => $username,
				'user_pass'  => $password,
				'user_email' => $email,
				'role'       => self::get_prefix( 'customer' ),
			);
			$new_user_data = apply_filters( 'tripzzy_filter_new_user_data', $new_user_data );

			$user_id = wp_insert_user( $new_user_data );

			if ( is_wp_error( $user_id ) ) {
				return;
			}

			do_action( 'tripzzy_user_created', $user_id, $new_user_data );

			return $user_id;
		}

		/**
		 * Notify user about user created.
		 *
		 * @param int $user_id User Id.
		 * @return void
		 */
		public static function send_user_creation_email( $user_id ) {
			$user = get_userdata( $user_id );
			$key  = get_password_reset_key( $user );

			if ( is_wp_error( $key ) ) {
				return;
			}

			$domain_name     = tripzzy_domain_name();
			$from_email      = 'localhost' === $domain_name ? get_option( 'admin_email' ) : sprintf( 'wordpress@%s', $domain_name );
			$blogname        = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
			$switched_locale = switch_to_user_locale( $user_id );

			/* translators: %s: User login. */
			$message  = sprintf( __( 'Username: %s', 'tripzzy' ), $user->user_login ) . "\r\n\r\n";
			$message .= __( 'To set your password, visit the following address:', 'tripzzy' ) . "\r\n\r\n";
			$message .= network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user->user_login ), 'login' ) . "\r\n\r\n";

			$message .= wp_login_url() . "\r\n";

			$headers  = 'MIME-Version: 1.0' . "\r\n";
			$headers .= 'From: ' . $from_email . "\r\n";
			$headers .= 'Reply-To: ' . $from_email . "\r\n" . 'X-Mailer: PHP/' . phpversion();

			$new_email_args = array(
				'to'      => $user->user_email,
				/* translators: Login details notification email subject. %s: Site title. */
				'subject' => __( '[%s] Login Details', 'tripzzy' ),
				'message' => $message,
				'headers' => $headers,
			);
			wp_mail(
				$new_email_args['to'],
				wp_specialchars_decode( sprintf( $new_email_args['subject'], $blogname ) ),
				$new_email_args['message'],
				$new_email_args['headers']
			);

			if ( $switched_locale ) {
				restore_previous_locale();
			}
		}

		/**
		 * Generate Unique Username.
		 *
		 * @param string $username Username to create user.
		 * @return string
		 */
		public static function generate_username( $username = '' ) {

			if ( empty( $username ) ) {
				$username = bin2hex( random_bytes( 4 ) );
			}

			// check for unique username.
			$index         = 1;
			$prev_username = $username;

			while ( username_exists( $username ) ) {
				$username = $prev_username . $index;
				++$index;
			}
			return sanitize_user( $username, true );
		}

		/**
		 * Generate new password
		 *
		 * @param string $password Password.
		 * @return string
		 */
		public static function generate_password( $password = '' ) {
			if ( empty( $password ) ) {
				$password = wp_generate_password();
			}
			return $password;
		}

		/**
		 * Update logic triggered on login.
		 *
		 * @since 1.0.0
		 * @param string $user_login User login.
		 * @param object $user       User.
		 */
		public static function user_logged_in_action( $user_login, $user ) {
			if ( ! $user_login ) {
				return;
			}
			self::update_user_last_active( $user->ID );
			update_user_meta( $user->ID, '_tripzzy_load_saved_cart_after_login', 1 );
		}

		/**
		 * Set the user last active timestamp to now.
		 *
		 * @since 1.0.0
		 * @param int $user_id User ID to mark active.
		 */
		public static function update_user_last_active( $user_id ) {
			if ( ! $user_id ) {
				return;
			}
			update_user_meta( $user_id, 'tripzzy_last_active', (string) strtotime( gmdate( 'Y-m-d', time() ) ) );
		}
	}
}
