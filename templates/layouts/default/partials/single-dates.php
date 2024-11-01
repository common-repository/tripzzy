<?php
/**
 * Template for dates on single page.
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Helpers\Loading;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\Trip;

$trip = $args['trip'];

$labels         = Strings::get()['labels'] ?? array();
$section_titles = Trip::get_section_titles( get_the_ID() );
$section_title  = $section_titles['trip_date'] ?? __( 'Availability', 'tripzzy' );
?>
<div class="tripzzy-section tripzzy-availability-section"  id="tripzzy-availability-section">
	<?php if ( ! empty( $section_title ) ) : ?>
		<h3 class="tripzzy-section-title"><?php echo esc_html( $section_title ); ?></h3>
	<?php endif; ?>
	<div class="tripzzy-section-inner tripzzy-pricing-date-list">
		<?php
		$dates            = $trip->dates();
		$departure_months = $dates->departure_months();
		$trip_dates       = $dates->get_dates();
		$packages         = $trip->packages(); // all Packages.
		$price_per_key    = $trip->price_per;

		if ( isset( $trip_dates[0] ) && $packages->total() > 0 ) :

			$default_package_id = $packages->default_package_id;
			$default_package    = $packages->get_package();

			$itineraries = $trip::get_itineraries( get_the_ID() );
			?>
			<div class="tripzzy-departure-months" id="tripzzy-departure-months">
				<ul>
					<li class="selected-departure" data-departure-month='' ><button><?php echo esc_html( $labels['all'] ?? '' ); ?><span><?php echo esc_html( $labels['dep'] ?? '' ); ?></span></button></li>
					<?php
					foreach ( $departure_months as $departure_month ) :
						$departure_date = new \DateTime( $departure_month );
						?>
						<li data-departure-month="<?php echo esc_attr( $departure_date->format( 'Y-n-j' ) ); ?>"><button ><?php echo esc_html( $departure_date->format( 'M' ) ); ?> <span><?php echo esc_html( $departure_date->format( 'Y' ) ); ?></span></button></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="tripzzy-dates-list">
				<input type="hidden" id="tripzzy-departure-month" value="" />
				<input type="hidden" id="tripzzy-is-all-departure" value="1" />  <!-- May be this is not required now. Helps to fetch either all dates or only specific month date on load more -->
				<input type="hidden" id="tripzzy-next-start-date" value="" /> <!-- Helps to fetch load more departure -->
				<input type="hidden" id="tripzzy-dates-current-page" value="1" /> 
				<div id="tripzzy-trip-dates" class="tripzzy-trip-dates tripzzy-is-processing">
				loading..
				</div>
				<script type="text/html" id="tmpl-tripzzy-booking-categories-content">
					<div class='tripzzy-packages-title'><?php echo esc_html( $labels['packages'] ?? '' ); ?></div>	
					<div class='tripzzy-packages-content'>
						<ul class='tripzzy-packages-list' >

						<?php
						foreach ( $packages as $package ) {
							$categories = $package->get_categories();
							if ( ! count( $categories ) ) {
								continue;
							}
							$package_info = array(
								'trip_id'    => get_the_ID(),
								'package_id' => (int) $package->get_id(),
								'start_date' => '{{{data.StartDate}}}',
							);
							?>
							<li data-package="<?php echo esc_attr( wp_json_encode( $package_info ) ); ?>" class="tripzzy__package-name <?php echo esc_attr( (int) $package->get_id() === (int) $default_package_id ? 'selected-package' : '' ); ?>" ><?php echo esc_html( $package->get_title() ); ?></li>
							<?php
						}
						?>
						</ul>
						<?php do_action( 'tripzzy_date_availability_after_packages', $trip ); ?>
						<div class="tripzzy__category-items">
							<?php
							foreach ( $default_package as $package_category ) {
								$package_category_id = $package_category->get_id();
								if ( ! get_term( $package_category_id ) ) {
									continue;
								}
								?>
								<div class="tripzzy__category-item" style="display:flex;justify-content:space-between;">
									<div class="tripzzy__category-title">
										<?php echo esc_html( $package_category->get_title() ); ?>
									</div>
									<div class="tripzzy__category-counter">
										<span class="qty"><?php echo esc_html( $labels['qty'] ?? 'Qty' ); ?></span>
										<input min="0" type="number" data-category-counter="<?php echo absint( $package_category_id ); ?>"/>
									</div>
									<?php if ( 'person' === $price_per_key ) : ?>
										<div class="tripzzy__category-price">
											<?php
											if ( $package_category->has_sale() ) {
												?>
												<del><?php echo esc_html( \Tripzzy\Core\Helpers\Amount::display( $package_category->get_regular_price() ) ); ?></del>
												<?php
											}
											echo esc_html( \Tripzzy\Core\Helpers\Amount::display( $package_category->get_price() ) );
											?>
										</div>
									<?php endif; ?>
								</div>
								<?php
							}
							?>
						</div>
					</div>
					<div class='tripzzy-checkout-button-wrapper'>
						<div class='tripzzy-error'></div>
						<div class="tripzzy-checkout-button-loader-wrapper">
							<?php Loading::render(); ?>
							<button class="tripzzy-checkout-button tz-btn tz-btn-solid" role="button" data-action-checkout>
								<?php echo esc_html( $labels['checkout'] ?? '' ); ?>
							</button>
						</div>
					</div>
				</script>
			</div>
			<!-- Load More -->
			<div class="tripzzy-load-more-link">
				<?php Loading::render( array( 'id' => 'tripzzy-departure-list-loader-wrapper' ) ); ?>
				<a href="#" class="tz-btn tz-btn-solid tripzzy-load-more" id="tripzzy-load-more-departure">
					<?php echo esc_html( $labels['view_more_dep'] ?? '' ); ?>
				</a>
			</div>
	<?php else : ?>
		<button data-tripzzy-drawer-trigger aria-controls="tripzzy-enquiry-form-wrapper" aria-expanded="false" type="button" id="tripzzy-enquiry-button" class='tz-btn tz-btn-outline'><?php echo esc_html( $labels['make_enquiry'] ?? '' ); ?></button>
	<?php endif; ?>
	</div>
</div>
