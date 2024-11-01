<?php
/**
 * Additional Hooks for admin pages, the hooks are other than post types and taxonomy.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Http\Nonce;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Seeder\PageSeeder;
if ( ! class_exists( 'Tripzzy\Admin\Hooks' ) ) {
	/**
	 * Admin Info Hooks Class
	 */
	class Hooks {

		use SingletonTrait;


		/**
		 * Constructor.
		 *
		 * @since 1.0.0
		 * @since 1.1.5 Added admin help tab.
		 */
		public function __construct() {
			if ( Page::is_admin_pages() ) {
				add_filter( 'admin_body_class', array( $this, 'admin_body_class' ) );
				add_action( 'in_admin_header', 'tripzzy_get_admin_header' );
				tripzzy_set_admin_help_tab();
			}
			add_filter( 'display_post_states', array( $this, 'display_post_states' ), 10, 2 );
		}

		/**
		 * Add admin body class.
		 *
		 * @param string $classes Admin class names.
		 * @return string
		 */
		public function admin_body_class( $classes ) {
			$classes .= ' tripzzy-admin-page'; // Common class for all tripzzy admin page.
			return $classes;
		}

		/**
		 * Display Post state for Tripzzy pages
		 *
		 * @param array  $states List of post states.
		 * @param object $post Post Object.
		 * @return array
		 */
		public function display_post_states( $states, $post ) {
			if ( 'page' !== $post->post_type ) {
				return $states;
			}
			$settings_key = MetaHelpers::get_post_meta( $post->ID, 'settings_key' );
			if ( ! $settings_key ) {
				return $states;
			}
			$settings_pages_data = PageSeeder::get_pages();
			$settings_pages      = array_reduce(
				$settings_pages_data,
				function ( $result, $item ) {
					$result[ $item['settings_key'] ] = $item['title'];
					return $result;
				},
				array()
			);
			$settings            = Settings::get();
			$page_id             = absint( $post->ID );
			$settings_page_id    = absint( $settings[ $settings_key ] ?? 0 );
			if ( isset( $settings_pages[ $settings_key ] ) && $page_id === $settings_page_id ) {
				$states[] = $settings_pages[ $settings_key ];
			}
			return $states;
		}
	}
}
