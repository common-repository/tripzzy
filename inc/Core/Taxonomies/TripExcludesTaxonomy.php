<?php
/**
 * Tripzzy Taxonomy.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Taxonomies;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Admin\Permalinks;

if ( ! class_exists( 'Tripzzy\Core\Taxonomies\TripExcludesTaxonomy' ) ) {
	/**
	 * Tripzzy Trip Excludes Taxonomy Class.
	 *
	 * @since 1.0.0
	 */
	class TripExcludesTaxonomy extends TaxonomyBase {
		/**
		 * Taxonomy Key to register Taxonomy.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $taxonomy = 'trip_excludes';

		/**
		 * Taxonomy Depth
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $depth = 1;

		/**
		 * Object Types.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $object_types = array( 'tripzzy' );

		/**
		 * Permalinks.
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $slugs;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$slugs = Permalinks::get();
			add_filter( 'tripzzy_filter_taxonomy_args', array( $this, 'init_args' ) );
			// Term meta.
			add_filter( 'tripzzy_filter_term_meta_form_fields', array( $this, 'init_term_meta_form_fields' ) );
		}

		/**
		 * Taxonomy arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function taxonomy_args() {
			$labels = array(
				'name'              => _x( 'Trip Excludes', 'taxonomy general name', 'tripzzy' ),
				'singular_name'     => _x( 'Trip Exclude', 'taxonomy singular name', 'tripzzy' ),
				'search_items'      => __( 'Search Trip Excludes', 'tripzzy' ),
				'all_items'         => __( 'All Trip Excludes', 'tripzzy' ),
				'parent_item'       => __( 'Parent Trip Exclude', 'tripzzy' ),
				'parent_item_colon' => __( 'Parent Trip Exclude:', 'tripzzy' ),
				'edit_item'         => __( 'Edit Trip Exclude', 'tripzzy' ),
				'update_item'       => __( 'Update Trip Exclude', 'tripzzy' ),
				'add_new_item'      => __( 'Add New Trip Exclude', 'tripzzy' ),
				'new_item_name'     => __( 'New Trip Exclude', 'tripzzy' ),
				'menu_name'         => __( 'Trip Excludes', 'tripzzy' ),
				'back_to_items'     => '← ' . __( 'Go to Trip Excludes', 'tripzzy' ),
			);

			$args = array(
				'hierarchical'      => true,
				'public'            => true,
				'labels'            => $labels,
				'show_ui'           => true,
				'show_admin_column' => false,
				'show_in_rest'      => true,
				'query_var'         => true,
				'rewrite'           => array( 'slug' => 'trip-excludes' ),
				'object_types'      => self::$object_types, // Where to add This taxonomy.
				'icon'              => 'fa-solid fa-circle-xmark', // only for trip info section default icon.
				'priority'          => 60,
			);
			return $args;
		}

		/**
		 * Taxonomy arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function term_meta_form_fields() {

			$fields = array(
				'fa_class' =>
				array(
					'type'                => 'text',
					'label'               => __( 'Fontawesome Class', 'tripzzy' ),
					'name'                => 'fa_class',
					'id'                  => 'fa-class',
					'class'               => 'fa-class',
					'placeholder'         => __( 'fa-solid fa-book', 'tripzzy' ),
					'required'            => false,
					'priority'            => 10,
					'value'               => '',
					'input_wrapper'       => 'span',
					'input_wrapper_class' => 'term-meta-input',
					'input_description'   => __( 'Type any fontawesome 5 class here', 'tripzzy' ),
					// Additional configurations.
					'is_new'              => true, // Whether it is new field just recently added or not? Always Need to set false for default fields.
					'is_default'          => true, // Whether it is Default field or not.
					'enabled'             => true, // soft enable. this field can be disabled.
					'force_enabled'       => true, // You can not disable if this set to true.
				),
			);
			return $fields;
		}
	}
}