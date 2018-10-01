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
	private $stats;

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

		$this->stats = new Give_Stats();

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

		/* @var stdClass $stat */
		$stat = $this->get_results_by( array( 'donation_id' => $args['donation_id'] ) );

		// update an existing donor.
		if ( ! empty( $stat ) ) {

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
	public function get_earnings( $args ) {
		global $wpdb;

		// Setup date from date string.
		if ( is_string( $args['date'] ) ) {
			$this->stats->setup_dates( $args['date'] );
		}

		$sql = $wpdb->prepare(
			"
			SELECT sum(amount) from {$this->table_name}
			WHERE date > %s
			AND date < %s
			",
			date( 'Y-m-d H:i:s', $this->stats->start_date ),
			date( 'Y-m-d H:i:s', $this->stats->end_date )
		);

		$amount = $wpdb->get_var( $sql );

		return $amount;
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
	public function get_sales( $args ) {
		global $wpdb;

		// Setup date from date string.
		if ( is_string( $args['date'] ) ) {
			$this->stats->setup_dates( $args['date'] );
		}

		$sql = $wpdb->prepare(
			"
			SELECT COUNT(id) from {$this->table_name}
			WHERE date > %s
			AND date < %s
			",
			date( 'Y-m-d H:i:s', $this->stats->start_date ),
			date( 'Y-m-d H:i:s', $this->stats->end_date )
		);

		$amount = $wpdb->get_var( $sql );

		return $amount;
	}
}
