<?php
/**
 * Tripzzy Bookings.
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
use Tripzzy\Core\Helpers\ErrorMessage;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\EscapeHelper;
use Tripzzy\Core\Forms\Form;
use Tripzzy\Core\Forms\CheckoutForm;
use Tripzzy\Core\PostTypes\BookingPostType;
use Tripzzy\Core\Payment;
use Tripzzy\Core\Bases\EmailBase;

if ( ! class_exists( 'Tripzzy\Core\Bookings' ) ) {
	/**
	 * Bookings main class.
	 */
	class Bookings {

		/**
		 * Post Type name
		 *
		 * @var string
		 */
		private static $post_type = 'tripzzy_booking';

		/**
		 * Initialize bookings.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'template_redirect', array( __CLASS__, 'init_bookings' ), 100 );
		}

		/**
		 * Start Booking Process.
		 * Always listen for booking.
		 *
		 * @return mixed
		 */
		public static function init_bookings() {

			if ( ! Nonce::verify() ) {
				return;
			}

			// Nonce already verified using Nonce::verify method.
			$tripzzy_action = isset($_POST[ 'tripzzy_action' ]) ?  sanitize_text_field( wp_unslash( $_POST[ 'tripzzy_action' ] ) ) : ''; // @codingStandardsIgnoreLine
			if ( 'tripzzy_book_now' !== $tripzzy_action ) {
				return;
			}
			$nonce_name           = Nonce::get_nonce_name();
			$field_names          = array(
				$nonce_name, // nonce need to pass in thankyou page url.
				'tripzzy_action',
				'payment_details',
				'currency',
				'payment_amount',
				'payment_mode',
			);
			$checkout_field_names = CheckoutForm::get_field_names();

			$field_names = wp_parse_args( $checkout_field_names, $field_names );

			$data = array(); // Add Post data array from booking form fields.
			foreach ( $field_names as $field_name ) {
				switch ( $field_name ) {
					case 'billing_email':
						// Nonce already verified using Nonce::verify method.
						$data[ $field_name ] = isset( $_POST[ $field_name ] ) ? sanitize_email( wp_unslash( $_POST[ $field_name ] ) ) : ''; // @codingStandardsIgnoreLine
						break;
					default:
						// Nonce already verified using Nonce::verify method.
						$data[ $field_name ] = isset( $_POST[ $field_name ] ) ? sanitize_text_field( wp_unslash( $_POST[ $field_name ] ) ) : ''; // @codingStandardsIgnoreLine
						break;
				}
			}

			// Book Now.
			$cart          = tripzzy()->cart;
			$cart_contents = $cart->get_cart_contents();
			$totals        = $cart->get_totals();

			do_action( 'tripzzy_before_booking', $data );

			$booking_id = self::insert_booking( $data );

			// Update additional metas.
			MetaHelpers::update_post_meta( $booking_id, 'cart_contents', $cart_contents ); // Cart Data.
			MetaHelpers::update_post_meta( $booking_id, 'totals', $totals ); // Cart Total Data.
			// To protect other booking data.
			MetaHelpers::update_post_meta( $booking_id, 'key', $data['tripzzy_nonce'] ); // Save nonce value as key to verify booking.

			/**
			 * Hook: tripzzy_after_booking.
			 *
			 * @hooked Tripzzy\Core\Payment->add_payment_data - 10
			 * @hooked Tripzzy\Core\Cart->empty_cart - 20;
			 * @hooked Tripzzy\Core\SendEmails->send_booking_emails - 30;
			 * @hooked Tripzzy\Core\Helpers\Customer->create_customer_on_booking - 40;
			 */
			do_action( 'tripzzy_after_booking', $booking_id, $data );

			$thankyou_page_url = Page::get_url( 'thankyou' );
			$thankyou_page_url = add_query_arg( 'tripzzy_key', $data['tripzzy_nonce'], $thankyou_page_url );
			$thankyou_page_url = add_query_arg( 'booking_id', $booking_id, $thankyou_page_url );
			$thankyou_page_url = apply_filters( 'tripzzy_filter_thankyou_page_url', $thankyou_page_url );

			wp_safe_redirect( $thankyou_page_url );
			exit;
		}

		/**
		 * Insert Booking.
		 *
		 * This will not handle (save post meta) cart_content and totals (amount) data. So need to insert this by self.
		 *
		 * Method seperated from Bookings::init_bookings method.
		 *
		 * @param array $data Date need to insert in bookings.
		 *
		 * @since 1.0.7
		 * @return int $booking_id
		 */
		public static function insert_booking( $data ) {
			if ( ! is_array( $data ) || ( is_array( $data ) && ! count( $data ) ) ) {
				return;
			}
			// Add New Booking.
			$post_args  = array(
				'post_title'   => 'Book now',
				'post_content' => '',
				'post_status'  => 'publish',
				'post_slug'    => uniqid(),
				'post_type'    => self::$post_type,
			);
			$booking_id = wp_insert_post( $post_args, true );

			// Update Booking Data.
			$first_name = isset( $data['billing_first_name'] ) ? $data['billing_first_name'] : '';
			$last_name  = isset( $data['billing_last_name'] ) ? $data['billing_last_name'] : '';
			$fullname   = trim( sprintf( '%s %s', $first_name, $last_name ) );
			$post_args  = array(
				'ID'         => $booking_id,
				'post_title' => sprintf( '#%s %s', $booking_id, $fullname ),
			);
			wp_update_post( $post_args );

			// Update Post metas. Common metas.
			MetaHelpers::update_post_meta( $booking_id, 'checkout_info', $data ); // checkout info.
			MetaHelpers::update_post_meta( $booking_id, 'booking_status', 'pending' );

			/**
			 * Update user data in bookings if logged in.
			 * If not logged in. 'Core\Helpers\User::create_user_on_booking' will create user and update meta.
			 * create_user_on_booking will create user if create user on booking is enabled in settings.
			 */
			$user_id = get_current_user_id();
			if ( $user_id ) {
				MetaHelpers::update_post_meta( $booking_id, 'user_id', $user_id );
			}

			return $booking_id;
		}

		/**
		 * It returns an array of trip ids from a booking id.
		 *
		 * @param int $booking_id The booking ID.
		 *
		 * @return array|null Array of trip ids.
		 */
		public static function get_trip_ids( $booking_id ) {
			if ( $booking_id ) {
				$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );

				if ( $cart_contents ) {
					return array_values( wp_list_pluck( $cart_contents, 'trip_id' ) );
				}
				return array();
			}
		}

		/**
		 * Return the total amount data for booked trips. like: gross_total, discount, net total etc.
		 *
		 * @param int $booking_id Booking Id.
		 * @return number
		 */
		public static function get_totals( $booking_id ) {
			$totals = MetaHelpers::get_post_meta( $booking_id, 'totals' );
			if ( ! $totals || empty( $totals ) ) { // Default data from cart. @todo need to fetch it from cart default data.
				$totals = array(
					'gross_total'    => 0, // i.e 1000  Item Total.
					'discount_total' => 0, // i.e -100  Total applicable discount amount (assuming 10%).
					'sub_total'      => 0, // i.e  900  ( Item Total - Total applicable discount amount).
					'tax_total'      => 0, // i.e +119  // Assuming 13% tax.
					'net_total'      => 0, // i.e 1019.
				);

			}
			return $totals;
		}

		/**
		 * Return the total amount for booked trips.
		 *
		 * @param int $booking_id Booking Id.
		 * @return number
		 */
		public static function get_total( $booking_id ) {
			$totals = self::get_totals( $booking_id );
			return $totals['net_total'] ?? 0;
		}

		/**
		 * Render Booking details as per booking id.
		 *
		 * @note Need to use inline style here because this method is also used in email.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @since 1.0.0
		 * @since 1.0.8 Check category exist before returning term name. if ( $category ).
		 * @since 1.1.3 Added time support.
		 * @return void
		 */
		public static function render_booking_details( $booking_id, $has_return = false ) {

			if ( ! $booking_id ) {
				return;
			}

			$cart_contents = MetaHelpers::get_post_meta( $booking_id, 'cart_contents' );
			$totals        = MetaHelpers::get_post_meta( $booking_id, 'totals' );

			ob_start();
			$i = 1;
			?>
			<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Booking Detail', 'tripzzy' ); ?></label>
			<table style="border-collapse:collapse; background:#fff;width:100%; margin:10px 0 20px; border-radius:5px; overflow:hidden">
				<thead>
					<tr>
						<th style="width:150px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-left:1px solid #e1e1e1">Trip Date</th>
						<th style="width:400px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6;">Trip Name</th>
						<th style="width:100px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-right:1px solid #e1e1e1">Total</th>
					</tr>
				</thead>
				<tbody>
					<?php
					if ( is_array( $cart_contents ) && ! empty( $cart_contents ) ) {
						foreach ( $cart_contents as $cart_key => $item ) :
							$trip_id    = $item['trip_id'];
							$package_id = $item['package_id'];
							$trip       = new Trip( $trip_id );
							$packages   = $trip->packages();
							$package    = $packages->get_package( $package_id );

							$package_title = '';
							if ( $package ) {
								$package_title = $package->get_title();
							}
							$row_style = 0 === $i % 2 ? 'background-color:#eaf8e6' : '';
							?>
							<tr valign="top" style="<?php echo esc_attr( $row_style ); ?>" >
								<td style="width:150px; padding:10px; font-family:Montserrat-Medium; font-size:14px; border-left:1px solid #e1e1e1">
									<?php
									$time         = $item['time'] ?? '';
									$date_strings = sprintf( '%s %s', $item['start_date'], $time );
									echo esc_html( $date_strings );
									?>
								</td>
								<td style="width:400px; padding:10px; font-family:Montserrat-Medium; font-size:14px;">
									<p style="margin:0"><strong style="display:inline-block; width:60px">Trip</strong><a href="<?php echo esc_url( get_permalink( $trip_id ) ); ?>" target="_blank"><?php echo esc_html( $item['title'] ); ?></a></p>
									<p style="margin:0"><strong style="display:inline-block; width:60px">Package</strong><?php echo esc_html( $package_title ); ?></p>
									<ul style="list-style:none; padding-left:10px;max-width:200px">
										<?php
										$categories       = $item['categories'];
										$categories_price = $item['categories_price'];
										$price_per        = $item['price_per'];
										if ( is_array( $categories ) && count( $categories ) > 0 ) {
											foreach ( $categories as $category_id => $no_of_person ) {
												$category = get_term( $category_id );
												if ( $category ) {
													if ( 'person' === $price_per ) :
														?>
														<li>
															<?php echo esc_html( $category->name ); ?>
															<span style="display:inline-block;">( <?php echo esc_html( $no_of_person ); ?> * <?php echo esc_html( Amount::display( $categories_price[ $category_id ] ) ); ?> )</span>
														</li>
													<?php else : ?>
														<li><?php echo esc_html( $category->name ); ?> <span style="display:inline-block;">( <?php echo esc_html( $no_of_person ); ?> )</span></li>
														<?php
													endif;
												}
											}
										}

										?>
									</ul>
								</td>
								<td style="width:100px; padding:10px; font-family:Montserrat-Medium; font-size:14px; border-right:1px solid #e1e1e1"><strong><?php echo esc_html( Amount::display( $item['item_total'] ) ); ?></strong></td>
							</tr>
							<?php
							++$i;
						endforeach;
					}
					?>
					<?php if ( is_array( $totals ) && $totals['discount_total'] > 0 ) : ?>
						<tr>
							<td colspan="2" style="width:550px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1;border-top:1px solid #e1e1e1;  border-left:1px solid #e1e1e1"><?php esc_html_e( 'Gross Total', 'tripzzy' ); ?></td>
							<td style="width:100px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1;border-top:1px solid #e1e1e1;  border-right:1px solid #e1e1e1"><?php echo esc_html( Amount::display( $totals['gross_total'] ) ); ?></td>
						</tr>
						<tr>
							<td colspan="2" style="width:550px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1; border-left:1px solid #e1e1e1"><?php esc_html_e( 'Discount', 'tripzzy' ); ?></td>
							<td style="width:100px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#fff;text-transform:uppercase; padding:10px; text-align:left; line-height:1; border-right:1px solid #e1e1e1">(<?php echo esc_html( Amount::display( $totals['discount_total'] ) ); ?>)</td>
						</tr>
					<?php endif; ?>
					<tr>
						<td colspan="2" style="width:150px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-left:1px solid #e1e1e1"><b><?php esc_html_e( 'Total', 'tripzzy' ); ?></b></td>
						<td style="width:100px; font-family:Montserrat-Medium; font-size:12px; color:#2e2e2e;background-color:#eceff1;text-transform:uppercase; padding:10px; text-align:left; line-height:1.6; border-right:1px solid #e1e1e1;"><b><?php echo esc_html( Amount::display( $totals['net_total'] ?? 0 ) ); ?></b></td>
					</tr>
				</tbody>
			</table>
			<?php
			$contents = ob_get_contents();
			ob_end_clean();
			if ( $has_return ) {
				return $contents;
			}
			echo wp_kses_post( $contents );
		}

		/**
		 * Render payment details as per booking id.
		 *
		 * @note Need to use inline style here because this method is also used in email.
		 *
		 * @param int  $booking_id Booking ID.
		 * @param bool $has_return Whether return or echo the markups.
		 *
		 * @return void
		 */
		public static function render_payment_details( $booking_id, $has_return = false ) {

			if ( ! $booking_id ) {
				return;
			}

			$payment_ids = MetaHelpers::get_post_meta( $booking_id, 'payment_ids' );

			ob_start();
			if ( ! empty( $payment_ids ) ) :
				?>
				<label class="tripzzy-form-label tripzzy-form-label-wrapper"><?php esc_html_e( 'Payment Detail', 'tripzzy' ); ?></label> 
				<?php
				Payment::render( $booking_id );
				endif;
				$contents = ob_get_contents();
				ob_end_clean();
			if ( $has_return ) {
				return $contents;
			}
			echo wp_kses_post( $contents );
		}

		/**
		 * It renders the form fields for the booking meta fields
		 *
		 * @param int  $booking_id The ID of the booking post.
		 * @param bool $has_return If true, the function will return the output instead of echoing it.
		 * @param bool $for_email Whether is it for email or not.
		 *
		 * @since 1.0.0
		 * @since 1.0.4 $for_email param added.
		 * @return the fields that are being passed to the Form::render function.
		 */
		public static function render_customer_details( $booking_id, $has_return = false, $for_email = false ) {
			$fields = BookingPostType::get_booking_metafield_fields( $booking_id, $for_email );
			if ( ! $fields ) {
				return;
			}

			ob_start();
			// current render_customer_details method also used in email template. so rendered style from email template.
			EmailBase::email_style();

			Form::render( compact( 'fields' ) );

			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $contents, $allowed_html );
		}

		/**
		 * It renders the booking details and traveler details.
		 *
		 * @param int  $booking_id The booking ID.
		 * @param bool $has_return Whether to return the contents or echo them.
		 *
		 * @return string booking details and traveler details are being returned.
		 */
		public static function render( $booking_id, $has_return = false ) {
			if ( ! $booking_id ) {
				return;
			}

			ob_start();

			self::render_booking_details( $booking_id );
			self::render_customer_details( $booking_id );

			$contents = ob_get_contents();
			ob_end_clean();

			if ( $has_return ) {
				return $contents;
			}

			$allowed_html = EscapeHelper::get_allowed_html();
			echo wp_kses( $contents, $allowed_html );
		}

		/**
		 * Get Booking status Dropdown option.
		 *
		 * @return array
		 */
		public static function get_booking_status_options() {
			$status = array(
				'pending'  => __( 'Pending', 'tripzzy' ),
				'booked'   => __( 'Booked', 'tripzzy' ),
				'canceled' => __( 'Canceled', 'tripzzy' ),
				'refunded' => __( 'Refunded', 'tripzzy' ),
			);
			return $status;
		}

		/**
		 * Get Booking status.
		 *
		 * @param int $booking_id Booking Id.
		 *
		 * @return string
		 */
		public static function get_booking_status( $booking_id ) {
			if ( ! $booking_id ) {
				return __( 'N/A', 'tripzzy' );
			}
			$key            = MetaHelpers::get_post_meta( $booking_id, 'booking_status' );
			$status_options = self::get_booking_status_options();

			if ( ! $key ) {
				$key = 'pending'; // fallback.
			}
			return isset( $status_options[ $key ] ) ? $status_options[ $key ] : __( 'N/A', 'tripzzy' );
		}
	}
}

