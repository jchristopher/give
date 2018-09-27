<?php
/**
 * Stats DB
 *
 * @package     Give
 * @subpackage  Classes/Give_DB_Stats
 * @copyright   Copyright (c) 2016, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       2.3.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_DB_Stats Class
 *
 * This class is for interacting with the donor database table.
 *
 * @since 1.0
 */
class Give_DB_Stats extends Give_DB {

	/**
	 * Give_DB_Stats constructor.
	 *
	 * Set up the Give DB Donor class.
	 *
	 * @since  2.3.0
	 * @access public
	 */
	public function __construct() {
		/* @var WPDB $wpdb */
		global $wpdb;

		$wpdb->give_stats  = $this->table_name = "{$wpdb->prefix}give_stats";
		$this->primary_key = 'id';
		$this->version     = '1.0';

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
			'amount'      => '%f',
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
		amount bigint(20) NOT NULL,
		PRIMARY KEY  (id)
		) CHARACTER SET utf8 COLLATE utf8_general_ci;";

		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version, false );
	}
}
