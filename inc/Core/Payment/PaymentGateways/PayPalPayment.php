<?php
/**
 * Payment Gateway : Paypal Express.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Payment\PaymentGateways;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Payment\PaymentGateways; // Base.
use Tripzzy\Core\Helpers\Currencies;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Traits\SingletonTrait;
use Tripzzy\Core\Traits\GatewayTrait;

if ( ! class_exists( 'Tripzzy\Core\Payment\PaymentGateways\PayPalPayment' ) ) {
	/**
	 * Payment Gateway : Paypal Express.
	 *
	 * @since 1.0.0
	 */
	class PayPalPayment extends PaymentGateways {
		use SingletonTrait;
		use GatewayTrait;

		/**
		 * Payment Gateway type.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $payment_gateway = 'paypal_payment'; // key/slug.

		/**
		 * Payment Gateway name.
		 *
		 * @since 1.0.0
		 * @var string
		 */
		protected static $payment_gateway_title; // name initialized from constructor.

		/**
		 * Settings
		 *
		 * @since 1.0.0
		 * @var array
		 */
		protected static $settings;

		/**
		 * Constructor.
		 */
		public function __construct() {
			self::$payment_gateway_title = __( 'PayPal Payment', 'tripzzy' );
			self::$settings              = Settings::get(); // for traits.
			add_filter( 'tripzzy_filter_payment_gateways_args', array( $this, 'init_args' ) );

			// Gateway Script.
			add_filter( 'tripzzy_filter_gateway_scripts', array( $this, 'init_gateway_scripts' ) );
		}

		/**
		 * Payment gateway arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function payment_gateway_args() {
			$args = array(
				'title'  => self::$payment_gateway_title,
				'name'   => self::$payment_gateway,
				'fields' => array(
					'enabled'        => array( // this key is for php side.
						'name'  => 'enabled', // Input field name. key and name must be identical.
						'label' => __( 'Enabled', 'tripzzy' ),
						'value' => true,
					),
					// Gateway specific fields.
					'client_id'      => array(
						'name'        => 'client_id',
						'label'       => __( 'Live Client ID', 'tripzzy' ),
						'value'       => '',
						'description' => 'Get API credentials from here <a href="https://developer.paypal.com/developer/applications/">here</a>. Please <strong>do not put test client id</strong> here.',
					),
					'test_client_id' => array(
						'name'        => 'test_client_id',
						'label'       => __( 'Test Client ID', 'tripzzy' ),
						'value'       => '',
						'description' => 'Get API credentials from here <a href="https://developer.paypal.com/developer/applications/">here</a>. Please <strong>do not put live client id</strong> here.',
					), // Sandbox id.
				),
			);
			return $args;
		}

		/**
		 * Gateway scripts arguments.
		 *
		 * @since 1.0.0
		 */
		protected static function gateway_scripts() {
			$data = self::geteway_data();
			$args = array();
			if ( ! empty( $data ) ) {
				$test_mode = $data['test_mode'];
				$config    = $data['config']; // Payment gateway configuration.

				$client_id = $config['client_id'] ?? '';
				if ( $test_mode ) {
					$client_id = $config['test_client_id'] ?? '';
				}
				$script_url = sprintf( '%sassets/dist/', TRIPZZY_PLUGIN_DIR_URL );

				$currency = Currencies::get_code();

				$paypal_live_src  = sprintf( 'https://www.paypal.com/sdk/js?client-id=%s&components=buttons&currency=%s', $client_id, $currency );
				$paypal_local_src = $script_url . 'paypal.js';
				$args[]           = $paypal_live_src;
				$args[]           = $paypal_local_src;
			}
			return $args;
		}
	}
}
