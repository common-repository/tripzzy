<?php
/**
 * Trip Dates.
 *
 * @package tripzzy
 * @since 1.0.0
 */

namespace Tripzzy\Core\Helpers;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use Tripzzy\Core\Traits\TripTrait;
use Tripzzy\Core\Traits\DataTrait;
use Tripzzy\Core\Http\Request;
use Tripzzy\Core\Template;

use Carbon\Carbon;
use RRule\RRule;

if ( ! class_exists( 'Tripzzy\Core\Helpers\TripDates' ) ) {

	/**
	 * Trip Dates class
	 *
	 * @since 1.0.0
	 */
	class TripDates {
		use TripTrait;
		use DataTrait;

		/**
		 * Trip Object.
		 *
		 * @var $trip.
		 */
		protected $trip = null;


		/**
		 * Transient key related to all dates data.
		 *
		 * @var string $transient_key
		 */
		public $transient_key = 'dates_data';

		/**
		 * Holds Dates data.
		 *
		 * @var string
		 */
		protected $dates_data = null;

		/**
		 * Holds Dates data.
		 *
		 * @var string
		 */
		protected $trip_dates = array();

		/**
		 * Holds Dates data.
		 *
		 * @var string
		 */
		protected $start_dates = null;

		/**
		 * Date Type.
		 *
		 * @var string
		 */
		protected $trip_date_type = null;

		/**
		 * Date per page.
		 *
		 * @var int
		 */
		protected $dates_per_page = 5;

		/**
		 * Pagination data.
		 *
		 * @var int
		 */
		public $pagination = array();

		/**
		 * Next Start date to fetch more dates.
		 *
		 * @note Only for recurring dates.
		 *
		 * @var string
		 */
		public $next_start_date = null;


		/**
		 * Next Start date to fetch more dates.
		 *
		 * @note Only for recurring dates.
		 *
		 * @var string
		 */
		public $date_limit_exceed = false;



		/**
		 * Last trip date.
		 *
		 * @var string
		 */
		protected $date_until = null;

		/**
		 * All Departure Months.
		 *
		 * @var array
		 */
		protected $departure_months = array();

		/**
		 * Trip Init.
		 *
		 * @param mixed $trip either trip id or trip object.
		 */
		public function __construct( $trip ) {
			if ( is_numeric( $trip ) ) {
				$this->trip = new Trip( $trip );
			} elseif ( $trip instanceof Trip ) {
				$this->trip = $trip;
			}

			// Calculate next start date. for view more departure date. or need to insert these into set_trip_dates.
			$this->set_data();

			// Fixed date consist of all dates in array. so need to splice data as per pagination vars.
			if ( 'fixed_dates' === $this->trip_date_type ) {
				$trip_dates = $this->trip_dates;

				$pagination     = $this->pagination;
				$current_page   = $pagination['current_page'];
				$dates_per_page = $this->dates_per_page;

				// Return current page dates array.
				$start_index = ( $current_page - 1 ) * $dates_per_page;

				$tmp_dates  = $trip_dates;
				$result     = array_splice( $trip_dates, $start_index, $dates_per_page );
				$trip_count = count( $result );

				if ( $trip_count ) {
					$last_date             = $result[ $trip_count - 1 ];
					$last_date             = $last_date['start_date']->format( 'Y-m-d' );
					$this->next_start_date = Carbon::createFromFormat( 'Y-m-d', $last_date )->addDays( 1 )->format( 'Y-m-d' );
				}

				$data['trip_dates'] = $tmp_dates;

			} else {
				$trip_count = count( $this->trip_dates );

				if ( $trip_count ) {
					$last_date             = $this->trip_dates[ $trip_count - 1 ];
					$last_start_date       = $last_date['start_date'];
					$this->next_start_date = Carbon::createFromFormat( 'Y-m-d', $last_start_date )->addDays( 1 )->format( 'Y-m-d' );
				}
			}
		}

		/**
		 * Get Trip dates as per argument passed
		 *
		 * @param int $trip_id Trip id.
		 */
		public function get_dates( $trip_id = null ) {

			$data = $this->get_data( $trip_id );
			if ( ! $data ) {
				return;
			}

			// Fixed date consist of all dates in array. so need to splice data as per pagination vars.
			if ( 'fixed_dates' === $this->trip_date_type ) {
				$trip_dates = $data['trip_dates'];

				$pagination     = $this->pagination;
				$current_page   = $pagination['current_page'];
				$dates_per_page = $this->dates_per_page;

				// Return current page dates array.
				$start_index = ( $current_page - 1 ) * $dates_per_page;

				$tmp_dates          = $trip_dates;
				$result             = array_splice( $trip_dates, $start_index, $dates_per_page );
				$data['trip_dates'] = $tmp_dates;
				return $result;

			} else {
				return $data['trip_dates'];
			}
		}

		/**
		 * Get Available list of departure months.
		 *
		 * @param int $trip_id Trip id.
		 * @return array
		 */
		public function departure_months( $trip_id = null ) {
			$data = $this->get_data( $trip_id );

			if ( ! $data ) {
				return;
			}
			return $data['departure_months'];
		}

		/**
		 * Set all dates related data.
		 *
		 * @param int $trip_id Trip id.
		 * @since 1.0.0
		 * @since 1.1.6 Implemented Request::sanitize_input.
		 */
		protected function set_data( $trip_id = null ) {

			// Set trip object if call pricing function directly.
			if ( is_null( $this->trip ) ) {
				if ( ! $trip_id ) {
					return;
				}
				$this->trip = new Trip( $trip_id );
			}

			$payload = Request::sanitize_input( 'INPUT_PAYLOAD' );
			// Set Datas.
			$this->trip_date_type = $this->trip->get_meta( 'trip_date_type', 'fixed_dates' );
			$this->dates_per_page = apply_filters( 'tripzzy_filter_dates_per_page', 5 );
			$this->set_departure_months( $payload );
			$this->set_trip_dates( $payload );

			$dates_data = array(
				'departure_months' => $this->departure_months,
				'trip_dates'       => $this->trip_dates,
			);

			$this->dates_data = $dates_data;
		}

		/**
		 * Get Trip dates as per argument passed
		 *
		 * @param int $trip_id Trip id.
		 * @return array
		 */
		public function get_data( $trip_id = null ) {
			// Set trip object if call pricing function directly.
			if ( is_null( $this->trip ) ) {
				if ( ! $trip_id ) {
					return;
				}
				$this->trip = new Trip( $trip_id );
			}

			// Get Final Trip ID.
			$trip_id = $this->trip->get_id();

			$transient_key = $this->transient_key( $trip_id );
			$dates_data    = Transient::get( $transient_key );
			$dates_data    = false;
			if ( $dates_data ) {
				return $dates_data;
			} elseif ( is_null( $this->dates_data ) ) {

				if ( ! $trip_id ) {
					return;
				}
					$this->set_data( $trip_id );
			}
			$data = array(
				'departure_months' => $this->dates_data['departure_months'],
				'trip_dates'       => $this->dates_data['trip_dates'],
			);
			Transient::set( $transient_key, $data );
			return $data;
		}

		/**
		 * Set Departure month.
		 *
		 * @param array $payload Payload data.
		 * @return void
		 */
		protected function set_departure_months( $payload = array() ) {
			if ( 'fixed_dates' === $this->trip_date_type ) {
				$start_dates = $this->set_fixed_departure_months();
			} else {
				$start_dates = $this->set_recurring_departure_months( $payload );
			}

			$this->departure_months = $start_dates;
		}

		/**
		 * Get Recurring departure months list.
		 *
		 * @param array $payload Payload data.
		 * @since 1.0.0
		 * @since 1.1.7 Filter past departure month and skip month if no date is available on that month.
		 * @return array
		 */
		protected function set_recurring_departure_months( $payload = array() ) {
			$start_dates = array();

			$args = $this->trip->get_meta( 'recurring_dates', array() );
			// Calculation of start year and end year.
			$date_now  = new \DateTime();
			$year_now  = $date_now->format( 'Y' );
			$month_now = $date_now->format( 'm' );
			$dtstart   = isset( $args['dtstart'] ) && ! empty( $args['dtstart'] ) ? new \DateTime( $args['dtstart'] ) : $date_now;
			// Avoid Past Start Date.
			if ( self::compare( $date_now->format( 'Y-m-d' ), $dtstart->format( 'Y-m-d' ), '>' ) ) {
				$dtstart = $date_now;
			}

			$until = new \DateTime();
			$until->modify( '+2 year' );
			if ( isset( $args['until'] ) && ! empty( $args['until'] ) ) {
				$until = new \DateTime( $args['until'] );
			}

			$start_year  = $dtstart->format( 'Y' );
			$start_month = $dtstart->format( 'm' );
			$end_year    = $until->format( 'Y' );
			// End of Calculation of start year and end year.
			$dtstart_month = new \DateTime( sprintf( '%s-%s', $start_year, $start_month ) );

			$all_months = array( 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12 );
			$months     = isset( $args['bymonth'] ) && count( $args['bymonth'] ) > 0 ? $args['bymonth'] : $all_months;
			sort( $months );
			$recurring_dates  = $this->get_recurring_trip_dates( $payload );
			$rrule_start_date = null;
			if ( ! empty( $recurring_dates ) ) {
				$rrule_start_date = $recurring_dates[0]['start_date'];
			}

			// To compare date in loop below.
			$current_date     = sprintf( '%s-%s', $year_now, $month_now );
			$current_date_obj = new \DateTime( $current_date ); // Current Date with year and month only.

			for ( $year = $start_year; $year <= $end_year; $year++ ) {
				$lenth = count( $months );
				for ( $j = 0; $j < $lenth; $j++ ) {
					$month = $months[ $j ];

					$available_month     = sprintf( '%s-%s', $year, $month );
					$available_month_obj = new \DateTime( $available_month ); // Trip Date with year and month only.

					$month_date_end_timestamp = strtotime( $available_month );
					$month_date_end           = gmdate( 'Y-m-t', $month_date_end_timestamp );
					$month_date_end_obj       = new \DateTime( $month_date_end );

					if ( $available_month_obj >= $dtstart_month ) {
						if ( $rrule_start_date ) {
							$rrule_start_month = new \DateTime( $rrule_start_date );
							$rrule_start_month->modify( 'first day of this month' );
							if ( $available_month_obj >= $rrule_start_month ) {
								$start_dates[] = $available_month;
							}
						} else {
							$start_dates[] = $available_month;
						}
					}
				}
			}
			$start_dates = array_unique( $start_dates );
			return array_values( $start_dates );
		}

		/**
		 * Get Fixed Departure months.
		 *
		 * @return array
		 */
		protected function set_fixed_departure_months() {
			$start_dates = array();

			$args  = $this->trip->get_meta( 'fixed_dates', array() );
			$dates = array_column( $args, 'start_date' );
			$dates = $this->sort_asc( $dates, true );

			foreach ( $dates as $date ) {
				$year      = gmdate( 'Y', strtotime( $date ) );
				$month     = gmdate( 'n', strtotime( $date ) );
				$trip_date = sprintf( '%s-%s', $year, $month );
				if ( ! in_array( $trip_date, $start_dates, true ) ) {
					$start_dates[] = $trip_date;
				}
			}

			return $start_dates;
		}

		/**
		 * Set Trip Dates list.
		 *
		 * @param array $payload Payload data.
		 * @return void
		 */
		protected function set_trip_dates( $payload = array() ) {
			if ( 'fixed_dates' === $this->trip_date_type ) {
				$trip_dates = $this->get_fixed_trip_dates( $payload ); // @todo new changes
			} else {
				$trip_dates = $this->get_recurring_trip_dates( $payload );
			}

			$this->trip_dates = $trip_dates;
		}

		/**
		 * Get Fixed trip dates list.
		 *
		 * @param string $payload Payload data.
		 * @return array
		 */
		protected function get_fixed_trip_dates( $payload = array() ) {
			$departure_month  = isset( $payload['departure_month'] ) ? $payload['departure_month'] : '';
			$is_all_departure = ! ! ( ! $departure_month ); // whether all departure clicked or month clicked.

			$trip    = $this->trip;
			$trip_id = $trip->get_id();

			$dates_data = $trip->get_meta( 'fixed_dates', array() );
			$dates_data = $this->sort_multi_dim_asc( $dates_data, true );
			$dates      = array_column( $dates_data, 'start_date' ); // only start dates in array.

			// Filter as per departure month selected and also pagination.
			if ( $departure_month && ! $is_all_departure ) {
				$departure_month_timestamp = strtotime( $departure_month );
				$month_date_end_timestamp  = strtotime( gmdate( 'Y-m-t', $departure_month_timestamp ) );

				$dates = array_filter(
					$dates,
					function ( $date ) use ( &$departure_month_timestamp, &$month_date_end_timestamp ) {
						$td = strtotime( $date );

						return $td >= $departure_month_timestamp && $td <= $month_date_end_timestamp;
					},
					ARRAY_FILTER_USE_BOTH
				);
				$dates = array_values( $dates );
			}
			// End of departure month filter.

			$duration   = $trip::get_duration( $trip_id );
			$trip_dates = array_map(
				function ( $start_date, $index ) use ( $duration, $trip, $dates_data ) {
					$_date['start_date'] = new Carbon( $start_date );

					list( $duration1, $duration2 ) = $duration['duration'];
					$duration_unit                 = $duration['duration_unit'];

					if ( (int) $duration1 > 0 ) {
						switch ( $duration_unit[0] ) {
							case 'hours':
								$end_date = Carbon::createFromFormat( 'Y-m-d', $start_date )->addHours( (int) $duration1 )->addMinutes( (int) $duration2 );
								break;
							case 'days':
							default:
								$end_date   = Carbon::createFromFormat( 'Y-m-d', $start_date )->startOfDay();
								$no_of_days = (int) $duration1 - 1;
								if ( $no_of_days > 0 ) {
									$end_date->addDays( $no_of_days );
								}
								break;
						}
						$_date['end_date'] = $end_date;
					}
					/**
					 * Each fixed date to fix or add any data like time in each date.
					 *
					 * @since 1.1.1
					 */
					$_date = apply_filters( 'tripzzy_filter_fixed_date', $_date, $trip, $dates_data, $index );
					return $_date;
				},
				$dates,
				array_keys( $dates )
			);
			// page num calculation.
			$dates_per_page = $this->dates_per_page;
			$current_page   = isset( $payload['current_page'] ) ? $payload['current_page'] : 1;
			$total_page     = count( $trip_dates ) / $dates_per_page;

			$pagination       = array(
				'current_page'   => absint( $current_page ),
				'dates_per_page' => $dates_per_page,
				'total_page'     => ceil( $total_page ),
			);
			$this->pagination = $pagination;

			return $trip_dates;
		}

		/**
		 * Get Recurring trip dates list.
		 *
		 * @param array $payload  Payload data.
		 * @return array
		 */
		protected function get_recurring_trip_dates( $payload = array() ) {
			$date_now_obj    = new \DateTime();
			$date_now        = gmdate( 'Y-m-d' );
			$trip            = $this->trip;
			$recurring_dates = $trip->get_meta( 'recurring_dates', array() );

			$start_date      = isset( $payload['start_date'] ) ? $payload['start_date'] : ''; // for load more.
			$departure_month = isset( $payload['departure_month'] ) ? $payload['departure_month'] : '';

			$is_all_departure = ! ! ( ! $departure_month ); // whether all departure clicked or month clicked.
			$dtstart          = $start_date ? $start_date : $departure_month;
			if ( ! $dtstart ) { // it is empty initial load if not set start date from backend, so need to assign today's date.
				$dtstart = isset( $recurring_dates['dtstart'] ) && ! empty( $recurring_dates['dtstart'] ) ? $recurring_dates['dtstart'] : $date_now;
			}

			$trip_id  = $trip->get_id();
			$duration = $trip::get_duration( $trip_id );

			$rrule_data = $recurring_dates;
			// set it if accedently deleted this value. because it is required in rrule.
			if ( ! isset( $rrule_data['freq'] ) ) {
				$rrule_data['freq'] = 'daily';
			}

			// Pagination args.
			$dates_per_page = $this->dates_per_page;

			$rrule_data['count'] = $dates_per_page;

			$byday = $rrule_data['byweekday'] ?? array(); // Backward compatibility. byweekday param is used in javascript.
			unset( $rrule_data['byweekday'] );
			if ( ! empty( $byday ) ) {
				$rrule_data['byday'] = $byday;
			}

			// Start of date calculation.
			$dtstart_obj = new \DateTime( $dtstart );
			if ( $dtstart_obj < $date_now_obj ) {
				$dtstart = $date_now;
			}
			$rrule_data['dtstart'] = $dtstart;
			$date_limit_exceed     = false; // Check for next recurring dates to show/hide view more departure.

			if ( ! $is_all_departure ) {
				unset( $rrule_data['until'] ); // if monthly departure list then until should be removed and add month end as until.
				$timestamp      = strtotime( $departure_month );
				$month_date_end = gmdate( 'Y-m-t', $timestamp );
				$until          = new \DateTime( $month_date_end );
				// Temp solution for 'until and count' conflict in rrule.
				$temp_rule  = new RRule( $rrule_data );
				$temp_dates = $temp_rule->getOccurrences();

				// Array.
				$rrule_data['until'] = $month_date_end; // only after get temp dates.
				$temp_date_count     = count( $temp_dates );

				if ( $temp_date_count ) {
					$temp_last_date    = $temp_dates[ $temp_date_count - 1 ];
					$date_limit_exceed = $temp_last_date >= $until;
				}

				if ( $date_limit_exceed ) {
					unset( $rrule_data['count'] );
					$rrule_data['until'] = $month_date_end;
				} else {
					unset( $rrule_data['until'] );
					$rrule_data['count'] = $dates_per_page;
				}
			} else {

				// Temp solution for 'until and count' conflict in rrule.
				$_tmp_until = $rrule_data['until'] ?? ''; // if until is set from admin.
				unset( $rrule_data['until'] ); // unset first and check value and then lastly set until.
				$temp_rule  = new RRule( $rrule_data );
				$temp_dates = $temp_rule->getOccurrences();
				// Array.
				$rrule_data['until'] = $_tmp_until; // only after get temp dates.
				if ( $this->departure_months ) {
					$recurring_end_date = end( $this->departure_months );
					$timestamp          = strtotime( $recurring_end_date );
					$recurring_end_date = gmdate( 'Y-m-t', $timestamp );
					$_tmp_until         = new \DateTime( $recurring_end_date );
				}

				$temp_date_count = count( $temp_dates );
				if ( $temp_date_count ) {
					$temp_last_date    = $temp_dates[ $temp_date_count - 1 ];
					$date_limit_exceed = $temp_last_date >= $_tmp_until;
				}
				if ( $date_limit_exceed && $_tmp_until ) {
					unset( $rrule_data['count'] );
					$rrule_data['until'] = $_tmp_until;
				} else {
					unset( $rrule_data['until'] );
					$rrule_data['count'] = $dates_per_page;
				}
			}
			$this->date_limit_exceed = $date_limit_exceed;
			// End of departure month filter.

			$rrule = new RRule( $rrule_data );
			$dates = $rrule->getOccurrences();

			$trip_dates = array_map(
				function ( $start_date ) use ( $duration, $trip ) {
					$_date['start_date']           = $start_date->format( 'Y-m-d' );
					list( $duration1, $duration2 ) = $duration['duration'];
					$duration_unit                 = $duration['duration_unit'];

					if ( (int) $duration1 > 0 ) {
						switch ( $duration_unit[0] ) {
							case 'hours':
								$end_date = Carbon::instance( $start_date )->addHours( (int) $duration1 )->addMinutes( (int) $duration2 );
								break;
							case 'days':
							default:
								$end_date   = Carbon::instance( $start_date )->startOfDay();
								$no_of_days = (int) $duration1 - 1;
								if ( $no_of_days > 0 ) {
									$end_date->addDays( $no_of_days );
								}
								break;
						}
						$_date['end_date'] = $end_date->format( 'Y-m-d' );
					}
					/**
					 * Each fixed date to fix or add any data like time in each date.
					 *
					 * @since 1.1.1
					 */
					$_date = apply_filters( 'tripzzy_filter_recurring_date', $_date, $trip );
					return $_date;
				},
				$dates
			);
			return $trip_dates;
		}

		/**
		 * Sort Date in asc order
		 *
		 * @param array   $dates list of dates.
		 * @param boolean $skip_past_date wheter filter past date or not.
		 * @return array
		 */
		public function sort_asc( $dates, $skip_past_date = false ) {
			if ( $skip_past_date ) {
				$dates = $this->sort_desc( $dates, $skip_past_date ); // This will skip past date as well.
			}

			usort(
				$dates,
				function ( $date1, $date2 ) {
					$t1 = strtotime( $date1 );
					$t2 = strtotime( $date2 );
					return ( $t1 - $t2 );
				}
			);

			return $dates;
		}

		/**
		 * Sort Date in desc order
		 *
		 * @param array   $dates list of dates.
		 * @param boolean $skip_past_date wheter filter past date or not.
		 * @return array
		 */
		public function sort_desc( $dates, $skip_past_date = false ) {
			usort(
				$dates,
				function ( $date1, $date2 ) {
					$t1 = strtotime( $date1 );
					$t2 = strtotime( $date2 );
					return ( $t2 - $t1 );
				}
			);

			if ( $skip_past_date ) {
				$today        = strtotime( gmdate( 'Y-m-d' ) );
				$future_dates = array();
				foreach ( $dates as $date ) {
					if ( strtotime( $date ) >= $today ) {
						$future_dates[] = $date;
					} else {
						break;
					}
				}
				return $future_dates;
			}

			return $dates;
		}

		/**
		 * Sort multidimentional Date in asc order
		 *
		 * @param array   $dates list of dates.
		 * @param boolean $skip_past_date wheter filter past date or not.
		 * @param string  $multi_dim_key default sort date key.
		 * @since 1.1.3
		 * @return array
		 */
		public function sort_multi_dim_asc( $dates, $skip_past_date = false, $multi_dim_key = 'start_date' ) {
			if ( $skip_past_date ) {
				$dates = $this->sort_multi_dim_desc( $dates, $skip_past_date, $multi_dim_key ); // This will skip past date as well.
			}

			usort(
				$dates,
				function ( $date1, $date2 ) use ( $multi_dim_key ) {
					$t1 = strtotime( $date1[ $multi_dim_key ] );
					$t2 = strtotime( $date2[ $multi_dim_key ] );
					return ( $t1 - $t2 );
				}
			);

			return $dates;
		}

		/**
		 * Sort multidimentional Date in desc order.
		 *
		 * @param array   $dates list of dates.
		 * @param boolean $skip_past_date wheter filter past date or not.
		 * @param string  $multi_dim_key default sort date key.
		 * @since 1.1.3
		 * @return array
		 */
		public function sort_multi_dim_desc( $dates, $skip_past_date = false, $multi_dim_key = 'start_date' ) {
			usort(
				$dates,
				function ( $date1, $date2 ) use( $multi_dim_key ) {
					$t1 = strtotime( $date1[ $multi_dim_key ] );
					$t2 = strtotime( $date2[ $multi_dim_key ] );
					return ( $t2 - $t1 );
				}
			);

			if ( $skip_past_date ) {
				$today        = strtotime( gmdate( 'Y-m-d' ) );
				$future_dates = array();
				foreach ( $dates as $date ) {
					if ( strtotime( $date[ $multi_dim_key ] ) >= $today ) {
						$future_dates[] = $date;
					} else {
						break;
					}
				}
				return $future_dates;
			}

			return $dates;
		}

		/**
		 * Get Key as per param provided
		 *
		 * @param int $trip_id Trip ID.
		 * @return string
		 */
		public function transient_key( $trip_id = null ) {
			$key = $this->transient_key;

			if ( $trip_id ) { // append trip id.
				$key = sprintf( '%s_%s', $key, $trip_id );
			}

			return $key;
		}

		/**
		 * Delete Transitent data related to price.
		 *
		 * @param int $trip_id Trip ID.
		 * @return bool True if the transient was deleted, false otherwise.
		 */
		public static function delete_transient( $trip_id = null ) {
			$dates = new TripDates( $trip_id );
			$key   = $dates->transient_key( $trip_id );

			$keys = Transient::get_all_keys( $trip_id );
			if ( is_array( $keys ) && count( $keys ) > 0 ) {
				foreach ( $keys as $k ) {
					Transient::delete( $k );
				}
			}
			return Transient::delete( $key );
		}

		/**
		 * Render dates.
		 *
		 * @return void
		 */
		public static function render() {
			$trip = new Trip( get_the_ID() );
			Template::get_template_part( 'layouts/default/partials/single', 'dates', compact( 'trip' ) );
		}

		/**
		 * Format the date string as per date format provided.
		 *
		 * @param string $date Date string.
		 * @param string $format Date format.
		 * @since 1.1.2
		 * @return string;
		 */
		public static function format( $date, $format = 'Y-m-d' ) {
			$date_time = new \DateTime( $date );
			return $date_time->format( $format );
		}

		/**
		 * Compare 2 Dates.
		 *
		 * @param string $date1 Date one to compare.
		 * @param string $date2 Date two to compare.
		 * @param string $operator Compare operator.
		 * @since 1.1.7
		 * @return string;
		 */
		public static function compare( $date1, $date2, $operator = '=' ) {
			$d1 = new \DateTime( $date1 );
			$d2 = new \DateTime( $date2 );
			switch ( $operator ) {
				case '=':
					return $d1->getTimestamp() === $d2->getTimestamp();
				case '<':
					return $d1 < $d2;
				case '>':
					return $d1 > $d2;
				case '>=':
					return $d1 >= $d2;
				case '<=':
					return $d1 <= $d2;
			}
		}
	}
}
