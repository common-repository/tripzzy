<?php
/**
 * The template layout for displaying all content of single trip.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package tripzzy
 */

use Tripzzy\Core\Forms\EnquiryForm;
use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Fontawesome;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Loading;
use Tripzzy\Core\Helpers\Strings;
use Tripzzy\Core\Helpers\TripFeatures;
use Tripzzy\Core\Template;
use Tripzzy\Core\Helpers\Reviews;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
$trip        = new Trip( get_the_ID() );
$gallery     = Trip::get_gallery();
$trip_types  = Trip::get_types();
$highlights  = Trip::get_highlights();
$overview    = Trip::get_overview();
$itineraries = Trip::get_itineraries();
$faqs        = Trip::get_faqs();
$duration    = Trip::get_duration();

$section_titles   = Trip::get_section_titles();
$highlights_title = $section_titles['highlights'] ?? '';

$destinations     = $trip->get_destinations();
$price_per        = $trip->get_price_per();
$price_per_key    = $trip->price_per;
$packages         = $trip->packages();
$package          = $packages->get_package();
$categories       = $package ? $package->get_categories() : null;
$default_category = $trip->package_category();

$tripzzy_logged_in             = is_user_logged_in();
$tripzzy_wishlist_button_attr  = ! $tripzzy_logged_in ? 'disabled' : '';
$tripzzy_wishlist_button_title = ! $tripzzy_logged_in ? 'Please login to add your wishlists.' : 'Please click to add your wishlists';

$in_wishlists    = Trip::in_wishlists( get_the_ID() );
$has_itineraries = is_array( $itineraries ) && count( $itineraries ) > 0;
$has_faqs        = is_array( $faqs ) && count( $faqs ) > 0;
$images_url      = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );

$strings = Strings::get();
$labels  = $strings['labels'] ?? array();
/**
 * Filter to show/hide single page title.
 *
 * @since 1.0.9
 */
$show_page_title = apply_filters( 'tripzzy_filter_display_single_page_title', true );
?>
<header class="tripzzy-entry-header">
	<?php if ( $show_page_title ) : ?>
	<h2 class="entry-title" itemprop="name"><?php the_title(); ?></h2>
	<?php endif; ?>
	<div class="tripzzy-after-title">
		<div class="tripzzy-review-wrapper">
			<?php Reviews::ratings_average_html( Reviews::get_trip_ratings_average( get_the_ID() ) ); ?>
		</div>
		<div class="tripzzy-meta-container">
			<div class="tripzzy-meta-wrapper">
				<div class="tripzzy-meta-item">
					<span class="tripzzy-meta destination" title="Destination">
						<svg class="icon">
							<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Pin_light"></use>
						</svg>
						<span>
							<?php if ( is_array( $destinations ) && count( $destinations ) > 0 ) : ?>
								<?php foreach ( $destinations as $destination ) : ?>
									<?php
									$destination_name = $destination->name;
									$destination_link = get_term_link( $destination->term_id );
									?>
								<a href="<?php echo esc_url( $destination_link, 'tripzzy' ); ?>">
									<?php echo esc_html( $destination_name ); ?>
								</a>
								<?php endforeach; ?>
							<?php else : ?>
								<?php
								echo esc_html( $labels['na'] ?? '' );
							endif;
							?>
						</span>
					</span>
				</div>
			</div>
		</div>
	</div>
</header>
<div class="tripzzy-entry-content">
	<div class="tz-row">
		<div class="tz-col tz-cols-8-lg">
			<div class="site-main">
				<article id="trip-<?php the_ID(); ?>" <?php post_class(); ?>>
					<div class="tripzzy-banner-section">
						<div class="swiper tripzzy-gallery-slides">
							<?php if ( count( $gallery ) > 1 ) : ?>
								<!-- Swiper -->
								<div class="swiper-wrapper">
									<?php foreach ( $gallery as $slide ) : ?>
									<div class="swiper-slide"><?php Image::get( $slide['id'], 'tripzzy_slider_thumbnail' ); ?></div>
									<?php endforeach; ?>
								</div>
								<div class="swiper-pagination"></div>
								<!-- Add Arrows -->
								<div class="swiper-button-next"></div>
								<div class="swiper-button-prev"></div>
							<?php else : ?>
								<div class="swiper-slide">
									<?php Image::get_thumbnail( get_the_ID() ); ?>
								</div>
							<?php endif; ?>
						</div>
						<div class="tripzzy-wishlist">
							<button <?php echo esc_attr( $tripzzy_wishlist_button_attr ); ?> title="<?php echo esc_attr( $tripzzy_wishlist_button_title ); ?>" class="tripzzy-wishlist-button  <?php echo esc_attr( $in_wishlists ? 'in-list' : '' ); ?>" data-trip-id="<?php the_ID(); ?>" ><i class="fa-regular fa-heart"></i></button>
						</div>
						<div class="tripzzy-gallery-buttons">
							<?php if ( count( $gallery ) > 1 ) : ?>
								<a href="#tripzzy-gallery-section" type="button" data-tripzzy-smooth-scroll class="tz-btn tz-btn-sm">
									<svg class="icon">
										<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#gallery-icon"></use>
									</svg>
									<span class="text"><?php echo esc_html( $labels['more_photos'] ?? '' ); ?></span>
								</a>
							<?php endif; ?>
							<!-- <a href="#" type="button" class="tz-btn tz-btn-sm">
								<svg class="icon">
									<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#play-icon"></use>
								</svg>
								<span class="text">Tour Video</span>
							</a> -->
						</div>
					</div>

					<div class="tripzzy-trip-type-list-container" >
						<?php if ( is_array( $trip_types ) && count( $trip_types ) > 0 ) : ?>
						<div class="tripzzy-trip-type-list-content" id="tripzzy-trip-type-list-content" >
							<span class="tripzzy-trip-type-title"><?php echo esc_html( $labels['trip_types'] ?? '' ); ?></span>
							<div class="tripzzy-trip-type-content">
								<ul class="tripzzy-trip-type-list">
								<?php foreach ( $trip_types as $trip_type ) : ?>
									<li><a href="<?php echo esc_url( get_term_link( $trip_type->term_id ) ); ?>"><?php echo esc_html( $trip_type->name ); ?></a></li>
								<?php endforeach; ?>
								</ul>
							</div>
						</div>
						<?php endif; ?>
						<?php if ( $has_itineraries ) : ?>
							<span class='tripzzy-view-itinerary' ><a href='#tripzzy-itineraries-section' data-tripzzy-smooth-scroll><?php echo esc_html( $labels['view_itinerary'] ?? '' ); ?></a></span>
						<?php endif; ?>
					</div>
					<?php do_action( 'tripzzy_single_page_content' ); ?>
				</article><!-- /article -->
			</div>
		</div>
		<div class="tz-col tz-cols-4-lg">
			<div class="tripzzy-check-availability tripzzy-stiky-box" id="tripzzy-check-availability">
				<div class="tripzzy-check-availability-content">

					<div class="tripzzy-check-availability-top">
						<div class="tripzzy-booking-top-area">
							<div class="tripzzy-duration">
								<span class="tripzzy-duration-label"><?php echo esc_html( $labels['duration'] ?? '' ); ?></span>
								<strong><?php printf( '%s %s', esc_html( $duration['duration'][0] ), esc_html( $duration['duration_unit'][0] ) ); ?></strong>
							</div>
							<div class="tripzzy-trip-code">
								<span><?php echo esc_html( $labels['trip_code'] ?? '' ); ?></span> : <code><?php echo esc_html( Trip::get_code() ); ?></code>
							</div>
						</div>
						<div class="tripzzy-booking-price-area tripzzy-price-per-<?php echo esc_attr( $price_per_key ); ?>">
							<?php
							if ( is_array( $categories ) && count( $categories ) > 0 ) {
								?>
								<div class="tripzzy-price-item-wrapper">
									<?php
									foreach ( $categories as $category ) {
										?>
										<div class="tripzzy-price-item">
											<span class="tripzzy-price-label">
											<?php echo esc_html( $category->get_title() ); ?>
											<?php if ( $category->has_sale() && $category->get_sale_percent() > 0 ) : ?>
													<span class="tripzzy-discount">-<?php echo esc_html( $category->get_sale_percent() ); ?>%</span>
												<?php endif; ?>
											</span>
											<?php if ( 'person' === $price_per_key ) : ?>
												<div class="tripzzy-price">
													<span class="tripzzy-price-from-text">
														<?php echo esc_html( $labels['from'] ?? '' ); ?>
														<?php if ( $category->has_sale() ) : ?>
															<del class="tripzzy-striked-price"><?php echo esc_html( Amount::display( $category->get_regular_price() ) ); ?></del>
														<?php endif; ?>
													</span>
													<span>
														<span class="tripzzy-booking-price"><?php echo esc_html( Amount::display( $category->get_price() ) ); ?></span> / <?php echo esc_html( $price_per ); ?>
													</span>
												</div>
											<?php endif; ?>
										</div>
										<?php
									}
									?>
								</div>
								<?php if ( 'group' === $price_per_key ) : ?>
									<div class="tripzzy-price">
										<span class="tripzzy-price-from-text">
											<?php echo esc_html( $labels['from'] ?? '' ); ?>
											<?php if ( $default_category->has_sale() ) : ?>
												<del class="tripzzy-striked-price"><?php echo esc_html( Amount::display( $default_category->get_regular_price() ) ); ?></del>
											<?php endif; ?>
										</span>
										<span>
											<span class="tripzzy-booking-price"><?php echo esc_html( Amount::display( $default_category->get_price() ) ); ?> </span> / <?php echo esc_html( $price_per ); ?>
										</span>
									</div>
									<?php
								endif;
							}
							?>
						</div>
						<?php TripFeatures::render( get_the_ID() ); ?>
					</div>
					<div class="tripzzy-booking-actions">
						<div class="tripzzy-button-group vertical">
							<a href='#tripzzy-availability-section' class='tz-btn tz-btn-solid' data-tripzzy-smooth-scroll><?php echo esc_html( $labels['check_availability'] ?? '' ); ?></a>
							<button data-tripzzy-drawer-trigger aria-controls="tripzzy-enquiry-form-wrapper" aria-expanded="false" type="button" id="tripzzy-enquiry-button" class='tz-btn tz-btn-outline'><?php echo esc_html( $labels['make_enquiry'] ?? '' ); ?></button>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="tripzzy-enquiry-form-wrapper tripzzy-drawer" id="tripzzy-enquiry-form-wrapper" data-tripzzy-drawer-target >
	<div class="tripzzy-drawer__overlay" data-tripzzy-drawer-close tabindex="-1"></div>
	<div class="tripzzy-drawer__wrapper">
		<div class="tripzzy-drawer__header">
			<div class="tripzzy-drawer__title">
			<strong><?php echo esc_html( $labels['enquiry'] ?? '' ); ?> :</strong> <?php the_title(); ?>
			</div>
			<button class="tripzzy-drawer__close" data-tripzzy-drawer-close aria-label="Close Tripzzy Drawer"></button>
		</div>
		<div class="tripzzy-drawer__content">
			<form method="post" id="tripzzy-enquiry-form">
				<?php EnquiryForm::render(); ?>
				<div class="tripzzy-enquiry-submit" style="display:flex;align-items:center;gap:20px">
					<input class="tz-btn tz-btn-solid" type="submit" value="<?php echo esc_attr( $labels['submit_enquiry'] ?? '' ); ?>" />
					<?php Loading::render(); ?>
				</div>
				<div class="tripzzy-message tripzzy-enquiry-message" id="tripzzy-enquiry-message">
				</div>
			</form>
		</div>
	</div>
</div>
