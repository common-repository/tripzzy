<?php
/**
 * Strings.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Strings' ) ) {

	/**
	 * Our main helper class that provides.
	 *
	 * @since 1.0.0
	 */
	class Strings {
		/**
		 * Get All Strings Value.
		 *
		 * @since 1.0.0
		 */
		public static function get() {

			return array(
				'labels'       => self::labels(),
				'descriptions' => self::descriptions(),
				'messages'     => self::messages(),
				'queries'      => self::queries(),
			);
		}

		/**
		 * Label strings.
		 *
		 * @since 1.0.0
		 */
		public static function labels() {
			$strings = array(
				'enable'             => __( 'Enable', 'tripzzy' ),
				'field_label'        => __( 'Field Label', 'tripzzy' ),
				'field_name'         => __( 'Field Name', 'tripzzy' ),
				'field_type'         => __( 'Field Type', 'tripzzy' ),
				'placeholder'        => __( 'Placeholder', 'tripzzy' ),
				'required'           => __( 'Required', 'tripzzy' ),
				'sub_fields'         => __( 'Sub Fields', 'tripzzy' ),
				'min'                => __( 'Min', 'tripzzy' ),
				'max'                => __( 'Max', 'tripzzy' ),
				'reset_fields'       => __( 'Reset Fields', 'tripzzy' ),
				'reset_settings'     => __( 'Reset Settings', 'tripzzy' ),
				'check_to_reset'     => __( 'Check to reset.', 'tripzzy' ),
				'options'            => __( 'Options', 'tripzzy' ),
				'settings'           => __( 'Settings', 'tripzzy' ),
				'loading'            => __( 'Loading...', 'tripzzy' ),
				'custom_filters'     => __( 'Custom Filters', 'tripzzy' ),
				'system_status'      => __( 'System Status', 'tripzzy' ),
				'system_info'        => __( 'System Info', 'tripzzy' ),
				'documentation'      => __( 'Documentation', 'tripzzy' ),
				'support'            => __( 'Support', 'tripzzy' ),
				'update_filter'      => __( 'Update Filter', 'tripzzy' ),
				'add_new_filter'     => __( 'Add new filter', 'tripzzy' ),
				'filter_label'       => __( 'Filter/Category Label', 'tripzzy' ),
				'filter_slug'        => __( 'Filter/Category Slug', 'tripzzy' ),
				'show_in_filters'    => __( 'Show in Filters', 'tripzzy' ),
				'yes'                => __( 'Yes', 'tripzzy' ),
				'no'                 => __( 'No', 'tripzzy' ),
				'hierarchical'       => __( 'Hierarchical', 'tripzzy' ),
				'slug'               => __( 'Slug', 'tripzzy' ),
				'label'              => __( 'Label', 'tripzzy' ),
				'status'             => __( 'Status', 'tripzzy' ),
				'all'                => __( 'All', 'tripzzy' ),
				'dep'                => __( 'Dep', 'tripzzy' ),
				'packages'           => __( 'Packages', 'tripzzy' ),
				'checkout'           => __( 'Checkout', 'tripzzy' ),
				'view_more_dep'      => __( 'View more departure', 'tripzzy' ),
				'load_more'          => __( 'Load more', 'tripzzy' ),
				'duration'           => __( 'Duration', 'tripzzy' ),
				'trip_code'          => __( 'Trip Code', 'tripzzy' ),
				'from'               => __( 'From', 'tripzzy' ),
				'enquiry'            => __( 'Enquiry', 'tripzzy' ),
				'na'                 => __( 'N/A', 'tripzzy' ),
				'view'               => __( 'View', 'tripzzy' ),
				'filter_by'          => __( 'Filter by', 'tripzzy' ),
				'clear'              => __( 'Clear', 'tripzzy' ),
				'more_photos'        => __( 'More Photos', 'tripzzy' ),
				'more_videos'        => __( 'More Videos', 'tripzzy' ),
				'trip_types'         => __( 'Trip Types', 'tripzzy' ),
				'view_itinerary'     => __( 'View Itineraries', 'tripzzy' ),
				'check_availability' => __( 'Check availability', 'tripzzy' ),
				'make_enquiry'       => __( 'Make an Enquiry', 'tripzzy' ),
				'submit_enquiry'     => __( 'Submit Enquiry', 'tripzzy' ),
				'featured'           => __( 'Featured', 'tripzzy' ),
				'view_details'       => __( 'View Details', 'tripzzy' ),
				'book_now'           => __( 'Book Now', 'tripzzy' ),
				'qty'                => __( 'Qty', 'tripzzy' ),
				'offset'             => __( 'Offset', 'tripzzy' ),
				'themes'             => __( 'Themes', 'tripzzy' ),
			);
			return $strings;
		}

		/**
		 * List of descriptions.
		 *
		 * @since 1.0.0
		 */
		public static function descriptions() {
			$strings = array(
				'field_label'    => __( 'This is the name which will appear on the form.', 'tripzzy' ),
				'field_name'     => __( 'Single word, no spaces. Underscores and dashes allowed.', 'tripzzy' ),
				'field_type'     => __( 'Note: You can not modify the type for default field.', 'tripzzy' ),
				'reset_fields'   => __( 'This option will reset your form. This action can not be undone.', 'tripzzy' ),
				'reset_settings' => __( 'This option will reset your entire settings. This action can not be undone.', 'tripzzy' ),
			);
			return $strings;
		}

		/**
		 * Message strings. can directly used in ajax response etc.
		 *
		 * @since 1.0.0
		 */
		public static function messages() {
			$strings = array(
				'error'                     => __( 'An Error has occur!!', 'tripzzy' ), // default.
				'nonce_verification_failed' => __( 'Nonce verification failed!!', 'tripzzy' ),
				'invalid_cart_request'      => __( 'Please select atleast one category!!', 'tripzzy' ),
				'unable_to_add_cart_item'   => __( 'Unable to add trip in the cart!!', 'tripzzy' ),
				'page_expired'              => __( 'This link has been expired.', 'tripzzy' ),
				'coupon_required'           => __( 'Please add coupon code!', 'tripzzy' ),
			);
			return $strings;
		}

		/**
		 * Query strings.
		 *
		 * @since 1.0.0
		 */
		public static function queries() {
			$strings = array(
				'have_coupon_code' => __( 'Have a Coupon code?', 'tripzzy' ),
			);
			return $strings;
		}

		/**
		 * Checks to see whether or not a string starts with another.
		 *
		 * @param string $string_value The string we want to check.
		 * @param string $starts_with The string we're looking for at the start of $string_value.
		 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
		 *
		 * @return bool True if the $string_value starts with $starts_with, false otherwise.
		 */
		public static function starts_with( $string_value, $starts_with, $case_sensitive = true ) {
			$len = strlen( $starts_with );
			if ( $len > strlen( $string_value ) ) {
				return false;
			}

			$string_value = substr( $string_value, 0, $len );

			if ( $case_sensitive ) {
				return strcmp( $string_value, $starts_with ) === 0;
			}

			return strcasecmp( $string_value, $starts_with ) === 0;
		}

		/**
		 * Checks to see whether or not a string ends with another.
		 *
		 * @param string $string_value The string we want to check.
		 * @param string $ends_with The string we're looking for at the end of $string_value.
		 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
		 *
		 * @return bool True if the $string_value ends with $ends_with, false otherwise.
		 */
		public static function ends_with( $string_value, $ends_with, $case_sensitive = true ) {
			$len = strlen( $ends_with );
			if ( $len > strlen( $string_value ) ) {
				return false;
			}

			$string_value = substr( $string_value, -$len );

			if ( $case_sensitive ) {
				return strcmp( $string_value, $ends_with ) === 0;
			}

			return strcasecmp( $string_value, $ends_with ) === 0;
		}

		/**
		 * Checks if one string is contained into another at any position.
		 *
		 * @param string $string_value The string we want to check.
		 * @param string $contained The string we're looking for inside $string_value.
		 * @param bool   $case_sensitive Indicates whether the comparison should be case-sensitive.
		 * @return bool True if $contained is contained inside $string_value, false otherwise.
		 */
		public static function contains( $string_value, $contained, $case_sensitive = true ) {
			if ( $case_sensitive ) {
				return false !== strpos( $string_value, $contained );
			} else {
				return false !== stripos( $string_value, $contained );
			}
		}

		/**
		 * Get the name of a plugin in the form 'directory/file.php', as in the keys of the array returned by 'get_plugins'.
		 *
		 * @param string $plugin_file_path The path of the main plugin file (can be passed as __FILE__ from the plugin itself).
		 * @return string The name of the plugin in the form 'directory/file.php'.
		 */
		public static function plugin_name_from_plugin_file( $plugin_file_path ) {
			return basename( dirname( $plugin_file_path ) ) . DIRECTORY_SEPARATOR . basename( $plugin_file_path );
		}

		/**
		 * Remove Line Break, HTML Comments, and tab (\t), enter characters (\n) from the string.
		 *
		 * @since 1.0.6
		 *
		 * @param string $content String to trim.
		 * @return string
		 */
		public static function trim_nl( $content ) {
			// Remove HTML new line and tab chars.
			$content = str_replace( array( "\r", "\n", "\t" ), '', $content );
			// Remove HTML comments.
			$content = preg_replace( '/<!--.*?-->/', '', $content );
			return $content;
		}
	}
}
