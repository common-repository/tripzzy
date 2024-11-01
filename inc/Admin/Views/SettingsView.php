<?php
/**
 * Views: Tripzzy Settings.
 *
 * @package tripzzy
 */

namespace Tripzzy\Admin\Views;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Loading;

if ( ! class_exists( 'Tripzzy\Admin\Views\SettingsView' ) ) {
	/**
	 * SettingsView Class.
	 *
	 * @since 1.0.0
	 */
	class SettingsView {

		/**
		 * Settings page html.
		 *
		 * @since 1.0.0
		 */
		public static function render() {
			?>
			<div class="wrap">
				<hr class="wp-header-end">
				<div class="tripzzy-page-wrapper">
					<div id="tripzzy-settings-page" class="tripzzy-page tripzzy-settings-page" >
						<?php Loading::render(); ?>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
