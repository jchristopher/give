<?php
/**
 * Stats DB
 *
 * @package     Give
 * @subpackage  Classes/Give_DB_Donation_Stats
 * @copyright   Copyright (c) 2018, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       2.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_DB_Donation_Stats Class
 *
 * This class is for interacting with the donor database table.
 *
 * @since 1.0
 */
class Give_DB_Donation_Stats extends Give_DB {
	/**
	 * Object of Give_Stats
	 *
	 * @since 2.3.0
	 * @var Give_Stats
	 */
	private $stats;

	/**
	 * Object of Give_Stats_Background_Updater
	 *
	 * @since 2.3.0
	 *
	 * @var Give_Stats_Background_Updater
	 */
	public $updater;

	/**
	 * Give_DB_Donation_Stats constructor.
	 *
	 * Set up the Give DB Donor class.
	 *
	 * @since  2.3.0
	 * @access public
	 */
	public function __construct() {
		/* @var WPDB $wpdb */
		global $wpdb;

		$wpdb->give_donation_stats  = $this->table_name = "{$wpdb->prefix}give_donation_stats";
		$this->primary_key = 'id';
		$this->version     = '1.0';

		$this->stats   = new Give_Stats();
		$this->updater = require_once GIVE_PLUGIN_DIR . 'includes/payments/class-give-stats-background-updater.php';

		// Install table.
		$this->register_table();

		parent::__construct();
	}

	/**
	 * Get columns and formats
	 *
	 * @since  2.3.0
	 * @access public
	 *
	 * @return array  Columns and formats.
	 */
	public function get_columns() {
		return array(
			'id'          => '%d',
			'form_id'     => '%d',
			'donation_id' => '%s',
			'donor_id'    => '%s',
			'date'        => '%s',
			'amount'      => '%s',
			'anonymous'   => '%s',
			'type'        => '%s',
		);
	}

	/**
	 * Get default column values
	 *
	 * @since  2.3.0
	 * @access public
	 *
	 * @return array  Default column values.
	 */
	public function get_column_defaults() {
		return array(
			'form_id'     => 0,
			'donation_id' => 0,
			'donor_id'    => 0,
			'amount'      => 0,
			'anonymous'   => 0,
			'type'        => '',
		);
	}

	/**
	 * Create the table
	 *
	 * @since  2.3.0
	 * @access public
	 *
	 * @return void
	 */
	public function create_table() {

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

		$sql = "CREATE TABLE " . $this->table_name . " (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		form_id bigint(20) NOT NULL,
		donation_id bigint(20) NOT NULL,
		donor_id bigint(20) NOT NULL,
		date longtext NOT NULL,
		amount mediumtext NOT NULL,
		anonymous tinyint NOT NULL,
		type longtext NOT NULL,
		PRIMARY KEY  (id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version, false );
	}

	/**
	 * Add/Update a donor
	 *
	 * @param  array $data List of donor data to add.
	 *
	 * @since  2.3.0
	 * @access public
	 *
	 * @return int|bool
	 */
	public function add( $data = array() ) {
		$args = wp_parse_args(
			$data,
			$this->get_column_defaults()
		);

		// Bailout.
		if (
			empty( $args['donation_id'] )
			|| empty( $args['donor_id'] )
			|| empty( $args['form_id'] )
		) {
			return false;
		}

		$stat = $this->get_results_by( array( 'donation_id' => $args['donation_id'] ) );

		// Update an existing donor.
		if ( ! empty( $stat ) ) {
			/* @var stdClass $stat */
			$stat = current( $stat );
			$status = $this->update( $stat->id, $args );

			return $status ? $stat->id : false;

		} else {

			return $this->insert( $args, 'donor' );

		}

	}

	/**
	 * Get earnings
	 *
	 * @since 2.3.0
	 *
	 * @param array $args
	 *
	 * @return string Sanitize amount
	 */
	public function get_earnings( $args = array() ) {
		$args['stat']   = 'earning';
		$args['fields'] = 'amount';

		return $this->get_stats( $args );
	}

	/**
	 * Get sales
	 *
	 * @since 2.3.0
	 *
	 * @param array $args
	 *
	 * @return string Sanitize amount
	 */
	public function get_sales( $args = array() ) {
		$args['stat']  = 'sale';
		$args['field'] = 'id';

		return $this->get_stats( $args );
	}

	/**
	 * Get earnings
	 *
	 * @since 2.3.0
	 *
	 * @param array $args
	 *
	 * @return string Sanitize amount
	 */
	private function get_stats( $args ) {
		global $wpdb;

		$args['pages']  = 0;
		$args['number'] = - 1;

		$fields = $this->get_field_query( $args );
		$where  = $this->get_where_query( $args );
		$limit  = $this->get_limit_query( $args );

		$sql = "SELECT {$fields} from {$this->table_name} {$where} {$limit}";
		$sql = trim( $sql );

		$amount = $wpdb->get_var( $sql );

		return $amount;
	}

	/**
	 * Get where query string
	 *
	 * @since 2.3.0
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	private function get_where_query( $args ) {
		$where = array();

		// Remove empty data.
		$args = array_filter( $args );

		// Form ID.
		if( array_key_exists( 'form_id', $args ) ) {
			$where[] = "form_id={$args['form_id']}";
		}

		// Donor ID.
		if( array_key_exists( 'donor_id', $args ) ) {
			$where[] = "donor_id={$args['donor_id']}";
		}

		// Date.
		if ( array_key_exists( 'date', $args ) ) {
			$this->stats->setup_dates( $args['date'] );

			$where[] = 'date > ' . date( 'Y-m-d H:i:s', $this->stats->start_date );
			$where[] = 'date < ' . date( 'Y-m-d H:i:s', $this->stats->end_date );
		}

		if( empty( $where ) ) {
			return '';
		}

		return 'WHERE ' . implode( ' AND ', $where );
	}

	/**
	 * Get field query
	 *
	 * @since 2.3.0
	 *
	 * @param $args
	 *
	 * @return string
	 */
	private function get_field_query( $args ) {
		// Set field query
		$fields = ! empty( $args['fields'] ) ? $args['fields'] : 'id';

		if ( array_key_exists( 'stat', $args ) && -1 === $args['number'] ) {

			if ( 'sale' === $args['stat'] ) {
				$fields = 'COUNT(id)';
			} elseif ( 'earning' === $args['stat'] ) {
				$fields = 'SUM(amount)';
			}
		}

		return $fields;
	}

	/**
	 * Get limit query
	 *
	 * @since 2.3.0
	 *
	 * @param $args
	 *
	 * @return string
	 */
	private function get_limit_query( $args ) {
		$limit = '';

		$page = ! empty( $args['paged'] ) ? $args['paged'] : 0;
		$number = ! empty( $args['number'] ) ? $args['number'] : -1;
		$offset = $page ? $number * ( $page - 1 ) : 0;

		if( -1 !== $number ){
			$limit = "LIMIT {$number}";
			$limit = $offset ? "{$limit} OFFSET {$offset}" : $limit;
		}

		return $limit;
	}

	/**
	 * Dispatch stat counter request
	 * Note: only for internal use
	 *
	 * @since 2.3.0
	 *
	 * @param array $args     {
	 *
	 * @type int    $donation Donation ID.
	 * @type string hash Unique string to validate request.
	 * }
	 *
	 * @return bool
	 */
	public function dispatch( $args ) {
		// Bailout.
		if( empty( $args['donation_id'] ) || empty( $args['hash'] ) ) {
			return false;
		}

		$this->updater->push_to_queue( $args )
		              ->save()
		              ->dispatch();

		return true;
	}
}
