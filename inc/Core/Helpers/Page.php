<?php
/**
 * Check the current page type.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Helpers\TripFilter;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Shortcodes\DashboardShortcode;
use Tripzzy\Core\Shortcodes\CheckoutPageShortcode;
use Tripzzy\Core\Shortcodes\ThankyouPageShortcode;
use Tripzzy\Core\Shortcodes\TripSearchResultPageShortcode;
use Tripzzy\Core\PostTypes\TripzzyPostType;

if ( ! class_exists( 'Tripzzy\Core\Helpers\Page' ) ) {
	/**
	 * Class For Page
	 *
	 * @since 1.0.0
	 */
	class Page {
		/**
		 * Check whether current/requested page is Tripzzy pages or not.
		 *
		 * @param string  $slug       page slug.
		 * @param boolean $admin_page check if page is admin page.
		 *
		 * @since 1.0.0
		 * @since 1.0.6 Search page slug changed to 'search-result' from 'search'.
		 * @since 1.0.8 Added site editor page for admin.
		 * @since 1.1.2 Added Themes page.
		 * @since 1.1.5 Added Taxonomy page.
		 * @return boolean
		 */
		public static function is( $slug, $admin_page = false ) {
			$settings = Settings::get();
			if ( $admin_page ) {
				if ( ! Request::is( 'admin' ) ) {
					return;
				}
				if ( ! function_exists( 'get_current_screen' ) ) {
					return;
				}
				$screen = get_current_screen();
				if ( ! $screen ) {
					return;
				}
				$all_pages = self::admin_page_ids();
				$pages     = array();
				switch ( $slug ) {
					case 'settings':
						$pages = $all_pages['settings'];
						return in_array( $screen->id, $pages, true );
					case 'homepage':
						$pages = $all_pages['homepage'];
						return in_array( $screen->id, $pages, true );
					case 'custom-categories':
						$pages = $all_pages['custom-categories'];
						return in_array( $screen->id, $pages, true );
					case 'coupons':
						$pages = $all_pages['coupons'];
						return in_array( $screen->id, $pages, true );
					case 'trips':
						$pages = $all_pages['trips'];
						return in_array( $screen->id, $pages, true );
					case 'forms':
						$pages = $all_pages['forms'];
						return in_array( $screen->id, $pages, true );
					case 'bookings':
						$pages = $all_pages['bookings'];
						return in_array( $screen->id, $pages, true );
					case 'enquiry':
						$pages = $all_pages['enquiry'];
						return in_array( $screen->id, $pages, true );
					case 'customers':
						$pages = $all_pages['customers'];
						return in_array( $screen->id, $pages, true );
					case 'system-info':
						$pages = $all_pages['system-info'];
						return in_array( $screen->id, $pages, true );
					case 'themes':
						$pages = $all_pages['themes'];
						return in_array( $screen->id, $pages, true );
						// Taxonomies.
					case 'trip-type':
						$pages = $all_pages['trip-type'];
						return in_array( $screen->id, $pages, true );
					case 'trip-includes':
						$pages = $all_pages['trip-includes'];
						return in_array( $screen->id, $pages, true );
					case 'trip-excludes':
						$pages = $all_pages['trip-excludes'];
						return in_array( $screen->id, $pages, true );
					case 'trip-destination':
						$pages = $all_pages['trip-destination'];
						return in_array( $screen->id, $pages, true );
					case 'trip-price-category':
						$pages = $all_pages['trip-price-category'];
						return in_array( $screen->id, $pages, true );
					case 'trip-keywords':
						$pages = $all_pages['trip-keywords'];
						return in_array( $screen->id, $pages, true );
					case 'site-editor':
						$pages = $all_pages['trip-keywords'];
						return 'site-editor' === $screen->id;
					case $slug:
						return \apply_filters( 'tripzzy_filter_is_admin_page', false, $slug, $screen->id );
				}
				return;
			} else {
				$post_types = array( 'tripzzy' );
				$taxonomies = array_keys( TripFilter::taxonomy_filters() );
				switch ( $slug ) {
					case 'trip': // Single trip page.
						return is_singular( $post_types );
					case 'search-result':
						return self::has_shortcode( TripSearchResultPageShortcode::get_key() );
					case 'trips': // Archive page including taxonomy pages.
						return is_post_type_archive( $post_types ) || is_tax( $taxonomies );
					case 'taxonomy': // taxonomy pages only.
						return is_tax( $taxonomies );
					case 'dashboard':
						return self::has_shortcode( DashboardShortcode::get_key() );
					case 'checkout':
						return self::has_shortcode( CheckoutPageShortcode::get_key() );
					case 'thankyou':
						return self::has_shortcode( ThankyouPageShortcode::get_key() );
				}
				return;
			}
			return false;
		}

		/**
		 * Return the page url of provided slug.
		 *
		 * @param mixed $slug Page Id or slug.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public static function get_url( $slug ) {

			if ( ! $slug ) {
				return;
			}

			if ( is_numeric( $slug ) ) {
				$page_id = $slug;
			} else {
				$page_id  = null;
				$settings = Settings::get();
				switch ( $slug ) {
					case 'thankyou':
						$page_id = isset( $settings['thankyou_page_id'] ) ? $settings['thankyou_page_id'] : 0;
						break;
					case 'checkout':
						$page_id = isset( $settings['checkout_page_id'] ) ? $settings['checkout_page_id'] : 0;
						break;
					case 'dashboard':
						$page_id = isset( $settings['dashboard_page_id'] ) ? $settings['dashboard_page_id'] : 0;
						break;
					case 'search-result':
							$page_id = isset( $settings['search_result_page_id'] ) ? $settings['search_result_page_id'] : 0;
						break;
					case 'trips':
						return get_post_type_archive_link( TripzzyPostType::get_key() );
				}
			}

			return $page_id ? get_the_permalink( $page_id ) : '';
		}


		/**
		 * Check whether current page is  Tripzzy admin page or not.
		 *
		 * @return boolean
		 */
		public static function is_admin_pages() {

			if ( ! Request::is( 'admin' ) ) {
				return;
			}

			$screen = get_current_screen();
			if ( ! $screen ) {
				return;
			}
			$admin_pages = self::admin_page_ids();
			$admin_pages = ArrayHelper::array_values( $admin_pages );
			return in_array( $screen->id, $admin_pages, true );
		}


		/**
		 * Return Admin Page ids.
		 *
		 * @since 1.0.0
		 * @since 1.1.2 Added Themes page.
		 */
		private static function admin_page_ids() {
			$pages = array(
				// Pages.
				'homepage'            => array( 'tripzzy_booking_page_tripzzy-homepage' ),
				'settings'            => array( 'tripzzy_booking_page_tripzzy-settings' ),
				'system-info'         => array( 'tripzzy_booking_page_tripzzy-system-info' ),
				'custom-categories'   => array( 'tripzzy_booking_page_tripzzy-custom-categories' ),
				'themes'              => array( 'tripzzy_booking_page_tripzzy-themes' ),
				// Post Types.
				'coupons'             => array( 'tripzzy_coupon', 'edit-tripzzy_coupon' ),
				'trips'               => array( 'tripzzy', 'edit-tripzzy' ),
				'forms'               => array( 'tripzzy_form', 'edit-tripzzy_form' ),
				'bookings'            => array( 'tripzzy_booking', 'edit-tripzzy_booking' ),
				'enquiry'             => array( 'tripzzy_enquiry', 'edit-tripzzy_enquiry' ),
				'customer'            => array( 'tripzzy_customer', 'edit-tripzzy_customer' ),

				// Taxonomies.
				'trip-type'           => array( 'edit-tripzzy_trip_type' ),
				'trip-includes'       => array( 'edit-tripzzy_trip_includes' ),
				'trip-excludes'       => array( 'edit-tripzzy_trip_excludes' ),
				'trip-destination'    => array( 'edit-tripzzy_trip_destination' ),
				'trip-price-category' => array( 'edit-tripzzy_price_category' ),
				'trip-keywords'       => array( 'edit-tripzzy_keywords' ),
			);
			return apply_filters( 'tripzzy_filter_admin_page_ids', $pages );
		}

		/**
		 * Get all available page list.
		 *
		 * @since 1.0.0
		 * @return array
		 */
		public static function get_all() {
			// Page Lists.
			$lists     = get_posts(
				array(
					'numberposts' => -1,
					'post_type'   => 'page',
					'orderby'     => 'title',
					'order'       => 'asc',
				)
			);
			$page_list = array();
			$i         = 0;
			foreach ( $lists as $page_data ) {
				$page_list[ $i ]['label'] = sprintf( '%s (#%d)', $page_data->post_title, $page_data->ID );
				$page_list[ $i ]['value'] = $page_data->ID;
				++$i;
			}
			return $page_list;
		}

		/**
		 * Check whether page content has provided tags or not.
		 *
		 * @param string $tag Shortcode tag.
		 * @since 1.0.0
		 * @return boolean
		 */
		public static function has_shortcode( $tag = '' ) {
			if ( ! $tag ) {
				return;
			}
			global $post;

			return is_singular() && is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, $tag );
		}
	}
}
