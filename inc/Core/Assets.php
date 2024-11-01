<?php
/**
 * Tripzzy Assets.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\TripMap;
use Tripzzy\Core\Helpers\Fontawesome;
use Tripzzy\Core\Helpers\ArrayHelper;
use Tripzzy\Core\Localize;

if ( ! class_exists( 'Tripzzy\Core\Assets' ) ) {
	/**
	 * Tripzzy Assets Class.
	 *
	 * @since 1.0.0
	 */
	class Assets {
		use SingletonTrait;

		/**
		 * Assets path.
		 *
		 * @var string
		 */
		private static $assets_url;

		/**
		 * Tripzzy Settings.
		 *
		 * @var array
		 */
		private static $settings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$settings   = Settings::get();
			self::$assets_url = sprintf( '%sassets/', TRIPZZY_PLUGIN_DIR_URL );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_assets' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'public_assets' ) );
			add_filter( 'safe_style_css', array( $this, 'safe_style_css' ) );
			add_action( 'enqueue_block_editor_assets', array( $this, 'block_editor_assets' ) );
		}
		/**
		 * Admin Assets.
		 *
		 * @since 1.0.0
		 */
		public function admin_assets() {
			self::register_scripts();

			// Common Scripts & Styles for admin pages.
			$var = Localize::get_var();
			wp_localize_script( 'tripzzy-admin-main', 'tripzzy', $var );
			wp_enqueue_editor();
			wp_enqueue_style( 'tripzzy-admin-main' );
			wp_enqueue_script( 'tripzzy-admin-main' );
			wp_enqueue_style( 'tripzzy-fontawesome' );
			wp_enqueue_style( 'tripzzy-datepicker' );

			// Page Specific Scripts & Styles for admin pages.
			if ( Page::is( 'homepage', true ) ) :
				wp_enqueue_style( 'tripzzy-admin-homepage' );
				wp_enqueue_script( 'tripzzy-admin-homepage' );
			endif;
			if ( Page::is( 'settings', true ) ) :
				wp_enqueue_style( 'tripzzy-admin-settings' );
				wp_enqueue_script( 'tripzzy-admin-settings' );
			endif;
			if ( Page::is( 'forms', true ) ) :
				wp_enqueue_style( 'tripzzy-admin-forms' );
				wp_enqueue_script( 'tripzzy-admin-forms' );
			endif;

			if ( Page::is( 'coupons', true ) ) :
				wp_enqueue_style( 'tripzzy-admin-coupons' );
				wp_enqueue_script( 'tripzzy-admin-coupons' );
			endif;
			if ( Page::is( 'trips', true ) ) :
				if ( TripMap::is_enabled( 'google_map' ) ) {
					wp_enqueue_script( 'google-map-api' ); // Search place autocomplete.
				}
				wp_enqueue_style( 'tripzzy-admin-trips' );
				wp_enqueue_script( 'tripzzy-admin-trips' );
			endif;
		}

		/**
		 * Public Assets.
		 *
		 * @since 1.0.0
		 */
		public function public_assets() {
			self::register_scripts();
			// Common Scripts & Styles for public pages.
			$var = Localize::get_var();
			wp_localize_script( 'tripzzy-trips', 'tripzzy', $var );
			wp_enqueue_style( 'tripzzy-fontawesome' );
			wp_enqueue_style( 'tripzzy-trips' );
			wp_enqueue_script( 'tripzzy-multi-select-dropdown' );
			wp_enqueue_script( 'tripzzy-trips' );

			if ( Page::is( 'dashboard' ) ) {
				wp_enqueue_script( 'tripzzy-dashboard' );
			}

			wp_enqueue_style( 'tripzzy-swiper' );
			wp_enqueue_script( 'tripzzy-swiper' );
		}

		/**
		 * Add Safe styles for wp_kses.
		 *
		 * @param array $styles List of styles.
		 * @since 1.0.1
		 * @return array
		 */
		public function safe_style_css( $styles ) {
			$styles[] = 'display';
			return $styles;
		}

		/**
		 * Block Editor Assets.
		 *
		 * @since 1.0.8
		 */
		public function block_editor_assets() {
			self::register_scripts();
			$var = array(
				'fa_icons' => Fontawesome::get_dropdown_options(),
			);
			// Icon Picker script.
			wp_localize_script( 'tripzzy-icon-picker-editor-script', 'tripzzy_block', $var );
		}

		/**
		 * Registered Scripts to enqueue.
		 *
		 * @since 1.0.0
		 * @since 1.1.4 Range Slider script added.
		 */
		private static function register_scripts() {

			// General.
			$scripts = array();
			$styles  = array();

			// Admin Scripts and styles.
			$scripts['tripzzy-admin-settings'] = array(
				'src'       => self::$assets_url . 'dist/settings.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-admin-settings']  = array(
				'src'     => self::$assets_url . 'dist/settings.css',
				'deps'    => array( 'wp-components' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);
			$scripts['tripzzy-admin-main']     = array(
				'src'       => self::$assets_url . 'dist/admin-main.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill', 'wp-pointer', 'tripzzy-rs' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-admin-main']      = array(
				'src'     => self::$assets_url . 'dist/admin-main.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$styles['tripzzy-admin-block-editor'] = array(
				'src'     => self::$assets_url . 'dist/block-editor.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$scripts['tripzzy-admin-homepage'] = array(
				'src'       => self::$assets_url . 'dist/admin-homepage.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill', 'wp-pointer' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-admin-homepage']  = array(
				'src'     => self::$assets_url . 'dist/admin-homepage.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$scripts['tripzzy-admin-forms'] = array(
				'src'       => self::$assets_url . 'dist/admin-forms.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill', 'wp-pointer' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-admin-forms']  = array(
				'src'     => self::$assets_url . 'dist/admin-forms.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$scripts['tripzzy-admin-coupons'] = array(
				'src'       => self::$assets_url . 'dist/admin-coupons.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill', 'wp-pointer' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-admin-coupons']  = array(
				'src'     => self::$assets_url . 'dist/admin-coupons.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);
			$scripts['tripzzy-admin-trips']   = array(
				'src'       => self::$assets_url . 'dist/admin-trips.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill', 'wp-pointer' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-admin-trips']    = array(
				'src'     => self::$assets_url . 'dist/admin-trips.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			// Frontend Scripts and Styles.
			$scripts['tripzzy-trips']     = array(
				'src'       => self::$assets_url . 'dist/trips.js',
				'deps'      => array( 'jquery', 'wp-util', 'wp-dom-ready', 'wp-element', 'tripzzy-lightbox', 'tripzzy-rs-custom' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$scripts['tripzzy-dashboard'] = array(
				'src'       => self::$assets_url . 'dist/dashboard.js',
				'deps'      => array( 'wp-api-fetch', 'wp-components', 'wp-data', 'wp-dom-ready', 'wp-element', 'wp-hooks', 'wp-i18n', 'wp-polyfill' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-trips']      = array(
				'src'     => self::$assets_url . 'dist/trips.css',
				'deps'    => array( 'tripzzy-lightbox' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			// Third Party Libs.
			$styles['tripzzy-fontawesome'] = array(
				'src'     => self::$assets_url . 'styles/fontawesome/css/all.min.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$styles['tripzzy-datepicker'] = array(
				'src'     => self::$assets_url . 'styles/react-datepicker/react-datepicker.min.css',
				'deps'    => array( 'wp-components', 'wp-pointer' ),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$scripts['tripzzy-swiper'] = array(
				'src'       => self::$assets_url . 'js/swiper/swiper.min.js',
				'deps'      => array( 'jquery' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-swiper']  = array(
				'src'     => self::$assets_url . 'styles/swiper/swiper.min.css',
				'deps'    => array(),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$scripts['tripzzy-multi-select-dropdown'] = array(
				'src'       => self::$assets_url . 'dist/multi-select-dropdown.js',
				'deps'      => array(),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);

			$scripts['tripzzy-lightbox'] = array(
				'src'       => self::$assets_url . 'js/glightbox/glightbox.min.js',
				'deps'      => array( 'jquery' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$styles['tripzzy-lightbox']  = array(
				'src'     => self::$assets_url . 'styles/glightbox/glightbox.min.css',
				'deps'    => array(),
				'version' => TRIPZZY_VERSION,
				'media'   => 'all',
			);

			$scripts['tripzzy-rs-labels'] = array(
				'src'       => self::$assets_url . 'js/tcrs/tcrs-generated-labels.min.js',
				'deps'      => array(),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$scripts['tripzzy-rs']        = array(
				'src'       => self::$assets_url . 'js/tcrs/tcrs.min.js',
				'deps'      => array( 'tripzzy-rs-labels' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => true,
			);
			$scripts['tripzzy-rs-custom'] = array(
				'src'       => self::$assets_url . 'js/tcrs/tcrs-custom.js',
				'deps'      => array( 'tripzzy-rs' ),
				'version'   => TRIPZZY_VERSION,
				'in_footer' => false,
			);

			if ( TripMap::is_enabled( 'google_map' ) ) {
				$api_key                   = isset( self::$settings['google_map_api_key'] ) ? self::$settings['google_map_api_key'] : '';
				$scripts['google-map-api'] = array(
					'src'       => 'https://maps.googleapis.com/maps/api/js?libraries=places&key=' . $api_key,
					'deps'      => array(),
					'version'   => TRIPZZY_VERSION,
					'in_footer' => true,
				);
			}

			/**
			 * Filter hook to modifiy all Tripzzy script before register it.
			 *
			 * @since 1.0.0
			 */
			$scripts = apply_filters( 'tripzzy_filter_registered_scripts', $scripts );

			/**
			 * Filter hook to modifiy all Tripzzy style before register it.
			 *
			 * @since 1.0.0
			 */
			$styles = apply_filters( 'tripzzy_filter_registered_styles', $styles );

			// Register Styles.
			foreach ( $styles as $handler => $style ) {
				wp_register_style( $handler, $style['src'], $style['deps'], $style['version'] ?? '', $style['media'] ?? '' );
			}
			// Register Scripts.
			foreach ( $scripts as $handler => $script ) {
				wp_register_script( $handler, $script['src'], $script['deps'], $script['version'] ?? '', $script['in_footer'] ?? true );
			}
		}

		/**
		 * Convert the array of styles data into css. whereas ArrayHelper::array_to_css is only convert single style without any selector.
		 *
		 * @param array $styles All Styles data to convert into css.
		 * @since 1.0.5
		 * @return string
		 */
		public static function array_to_css( $styles = array() ) {
			$all_styles = '';
			foreach ( $styles as $style ) {
				$selector = $style['selector'] ?? '';
				$css      = $style['css'] ?? array();
				if ( ! $selector || count( $css ) <= 0 ) {
					continue;
				}
				// Only convert array properties to css string.
				$css = ArrayHelper::array_to_css( $css );

				// Adding selector.
				$all_styles .= sprintf( '%s { %s }', $selector, $css );
			}
			return $all_styles;
		}
	}
}
