<?php
/**
 * Tripzzy Checkout Form.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Forms;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Bases\FormBase;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Page;
use Tripzzy\Core\Helpers\DropdownOptions;
use Tripzzy\Core\Forms\Form;

if ( ! class_exists( 'Tripzzy\Core\Forms\EnquiryForm' ) ) {
	/**
	 * Tripzzy Checkout Form Class.
	 *
	 * @since 1.0.0
	 */
	class EnquiryForm extends FormBase {

		/**
		 * Current form id. Required.
		 */
		public static function get_form_id() {
			return Settings::get( 'enquiry_form_id' ); // Key name must be same as settings default fields.
		}

		/**
		 * Default fields.
		 *
		 * @since 1.0.0
		 * @since 1.1.7 Added tripzzy_filter_enquiry_form_fields hook to modify default fields.
		 */
		protected static function default_fields() {

			$fields = array(
				'trip_id'   =>
				array(
					'type'          => 'hidden',
					'label'         => __( 'Trip Name', 'tripzzy' ),
					'name'          => 'trip_id',
					'id'            => 'trip-name',
					'class'         => 'trip-name',
					'required'      => true,
					'priority'      => 5,
					'value'         => get_the_ID(),
					// Additional configurations.
					'is_new'        => false, // Whether it is new field just recently added or not? Always Need to set false for default fields.
					'is_default'    => true, // Whether it is Default field or not.
					'enabled'       => true, // soft enable. this field can be disabled.
					'force_enabled' => true, // You can not disable if this set to true.
				),
				'full_name' =>
				array(
					'type'          => 'text',
					'label'         => __( 'Full Name', 'tripzzy' ),
					'name'          => 'full_name',
					'id'            => 'full-name',
					'class'         => 'full-name',
					'placeholder'   => __( 'Your Full name', 'tripzzy' ),
					'required'      => true,
					'priority'      => 10,
					'value'         => '',
					// Additional configurations.
					'is_new'        => false, // Whether it is new field just recently added or not? Always Need to set false for default fields.
					'is_default'    => true, // Whether it is Default field or not.
					'enabled'       => true, // soft enable. this field can be disabled.
					'force_enabled' => true, // You can not disable if this set to true.
				),
				'email'     =>
				array(
					'type'          => 'email',
					'label'         => __( 'Email', 'tripzzy' ),
					'name'          => 'email',
					'id'            => 'email',
					'class'         => 'email',
					'placeholder'   => __( 'Your Email', 'tripzzy' ),
					'required'      => true,
					'priority'      => 20,
					'value'         => '',
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => true,
				),
				'message'   =>
				array(
					'type'          => 'textarea',
					'label'         => __( 'Message', 'tripzzy' ),
					'name'          => 'message',
					'id'            => 'message',
					'class'         => 'message',
					'placeholder'   => __( 'Your message', 'tripzzy' ),
					'required'      => true,
					'priority'      => 30,
					'value'         => '',
					'attributes'    => array( 'rows' => 5 ),
					// Additional configurations.
					'is_new'        => false,
					'is_default'    => true,
					'enabled'       => true,
					'force_enabled' => true,
				),
			);

			if ( Page::is( 'enquiry', true ) ) { // if admin enquiry page.
				$fields['trip_id']['type']    = 'dropdown';
				$fields['trip_id']['options'] = DropdownOptions::get()['trips'];
			}
			/**
			 * Hook to modify enquiry form fields.
			 *
			 * @since 1.1.7
			 */
			return apply_filters( 'tripzzy_filter_enquiry_form_fields', $fields );
		}
	}
}
