<?php
/**
 * The template layout for displaying all content of archive trip.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @since 1.0.0
 * @since 1.0.9 Template structure updated. Title shifted just below the thumbnail and also changed the position of review.
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Image;
use Tripzzy\Core\Helpers\Trip;
use Tripzzy\Core\Helpers\Amount;
use Tripzzy\Core\Helpers\Reviews;
use Tripzzy\Core\Helpers\Settings;
use Tripzzy\Core\Helpers\Strings;
$tripzzy_logged_in             = is_user_logged_in();
$tripzzy_wishlist_button_attr  = ! $tripzzy_logged_in ? 'disabled' : '';
$tripzzy_wishlist_button_title = ! $tripzzy_logged_in ? 'Please login to add your wishlists.' : 'Please click to add your wishlists';

$settings          = Settings::get();
$trip              = new Trip( get_the_ID() );
$difficulty        = $trip::get_difficulty();
$in_wishlists      = $trip->in_wishlists();
$destinations      = $trip->get_destinations();
$duration          = $trip->get_duration();
$difficulty_levels = $settings['trip_difficulties'];
$difficulty_index  = $difficulty ? ( absint( $difficulty ) ) - 1 : 1;
$difficulty_level  = isset( $difficulty_levels[ $difficulty_index ]['label'] ) ? $difficulty_levels[ $difficulty_index ]['label'] : '';
$has_difficulties  = isset( $settings['enable_trip_difficulties'] ) && $settings['enable_trip_difficulties'];
// Price related datas.
$category      = $trip->package_category();
$has_sale      = false;
$price         = 0;
$regular_price = 0;
$sale_percent  = 0;
if ( $category ) {
	$has_sale      = $category->has_sale();
	$price         = $category->get_price();
	$regular_price = $category->get_regular_price();
	$sale_percent  = $category->get_sale_percent();
}
$images_url = sprintf( '%sassets/images', esc_url( TRIPZZY_PLUGIN_DIR_URL ) );
$labels     = Strings::get()['labels'];

$args           = $args ?? array();
$has_post_class = $args['has_post_class'] ?? true; // Skip post class in shortcode/block rendered trips.
?>
<div class="tz-col">
	<article id="trip-<?php the_ID(); ?>" <?php $has_post_class ? post_class() : ''; ?>>
		<div class="tripzzy-trip">
			<div class="tz-row tz-m-0">
				<div class="tz-col tz-cols-5-md tripzzy-thumbnail-wrapper">
					<div class="tripzzy-thumbnail tz-shine-overlay">
						<a href="<?php the_permalink(); ?>"><?php Image::get_thumbnail( get_the_ID() ); ?></a>
						<div class="tripzzy-wishlist">
							<button <?php echo esc_attr( $tripzzy_wishlist_button_attr ); ?>
								title="<?php echo esc_attr( $tripzzy_wishlist_button_title ); ?>"
								class="tripzzy-wishlist-button  <?php echo esc_attr( $in_wishlists ? 'in-list' : '' ); ?>"
								data-trip-id="<?php the_ID(); ?>"><i class="<?php echo esc_attr( $in_wishlists ? 'fa-solid' : 'fa-regular' ); ?> fa-heart"></i></button>
						</div>
						<div class="tripzzy-ribbon-group vertical">
							<?php if ( $trip->is_featured() ) : ?>
								<div class="tripzzy-ribbon ribbon-featured">
									<span class="tripzzy-ribbon-text"><?php echo esc_attr( $labels['featured'] ?? '' ); ?></span>
								</div>
							<?php endif; ?>
							<?php if ( $sale_percent ) : ?>
							<div class="tripzzy-ribbon ribbon-discount">
								<span class="tripzzy-ribbon-text">-<?php echo esc_html( $sale_percent ); ?>%</span>
							</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="tz-col tz-cols-7-md">
					<div class="tripzzy-content-wrapper">
						<h3 class="tripzzy-trip-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						</h3>
						<div class="tripzzy-after-title">
							<div class="tripzzy-review-price-wrapper">
								<div class="tripzzy-review-wrapper">
									<?php Reviews::ratings_average_html( Reviews::get_trip_ratings_average( get_the_ID() ) ); ?>
								</div>
								<div class="tripzzy-trip-price">
									<div class="tripzzy-price-wrapper">
										<?php if ( $has_sale ) : ?>
										<div class="tripzzy-regular-price">
											<?php echo esc_html( Amount::display( $regular_price ) ); ?></div>
										<?php endif; ?>
										<div class="tripzzy-price"><?php echo esc_html( Amount::display( $price ) ); ?></div>
									</div>
								</div>
							</div>
							<div class="tripzzy-meta-container">
								<div class="tripzzy-meta-wrapper">
									<div class="tripzzy-meta-item">
										<span class="tripzzy-meta destination" >
											<svg class="icon">
												<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Pin_light"></use>
											</svg>
											<span>
												<?php if ( count( $destinations ) > 0 ) : ?>
													<?php foreach ( $destinations as $destination ) : ?>
														<?php
														$destination_name = $destination->name;
														$destination_link = get_term_link( $destination->term_id );
														?>
														<a href="<?php echo esc_url( $destination_link, 'tripzzy' ); ?>" title="Destination <?php echo esc_attr( $destination_name ); ?>"><?php echo esc_html( $destination_name ); ?></a>
													<?php endforeach; ?>
												<?php else : ?>
													<?php echo esc_html( $labels['na'] ?? '' ); ?>
												<?php endif; ?>
											</span>
										</span>
									</div>
								</div>
							</div>
						</div>
						<hr class="tripzzy-divider">
						<div class="tripzzy-before-content">
							<div class="tripzzy-meta-container">
								<div class="tripzzy-meta-wrapper">
									<?php
										$duration_value = $duration['duration'];
										$duration_unit  = $duration['duration_unit'];
									?>
									<div class="tripzzy-meta-item">
										<span class="tripzzy-meta duration" title="Trip duration">
											<svg class="icon">
												<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Alarmclock_light">
												</use>
											</svg>
											<span>
												<?php
												if ( is_array( $duration_value ) && isset( $duration_value[0] ) && absint( $duration_value[0] ) > 0 ) :
													printf( '%s %s', esc_html( $duration_value[0] ), esc_html( $duration_unit[0] ) );
												else :
													echo esc_html( $labels['na'] ?? '' );
												endif;
												?>
											</span>
										</span>
									</div>
									<?php if ( $has_difficulties ) : ?>
										<div class="tripzzy-meta-item">
											<span class="tripzzy-meta difficulty" title="Trip Difficulty">
												<svg class="icon">
													<use xlink:href="<?php echo esc_url( $images_url ); ?>/sprite.svg#Waterfall_light">
													</use>
												</svg>
												<span><?php echo esc_html( $difficulty_level ); ?></span>
											</span>
										</div>
									<?php endif; ?>
								</div>
							</div>
						</div>
						<div class="tripzzy-trip-content">
							<?php the_excerpt(); ?>
						</div>
						<div class="tripzzy-trip-button-wrapper">
							<a href="<?php the_permalink(); ?>#tripzzy-availability-section" class="tz-btn tz-btn-solid tm-book-now-btn"><?php echo esc_attr( $labels['book_now'] ?? '' ); ?></a>
							<a href="<?php the_permalink(); ?>" class="tz-btn tz-btn-outline"><?php echo esc_attr( $labels['view_details'] ?? '' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
	</article><!-- /article -->
</div>
