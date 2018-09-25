<?php
/**
 * Background Process
 *
 * Uses https://github.com/A5hleyRich/wp-background-processing to handle DB
 * updates in the background.
 *
 * @class    Give_Stats_Background_Updater
 * @version  2.3.0
 * @package  Give/Classes
 * @category Class
 * @author   WordImpress
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Give_Stats_Background_Updater Class.
 */
class Give_Stats_Background_Updater extends WP_Background_Process {
	/**
	 * Prefix
	 *
	 * @since  2.3.0
	 * @var string
	 * @access protected
	 */
	protected $prefix = 'give';


	/**
	 * Action
	 *
	 * @since 2.3.0
	 * @var string
	 */
	protected $action = 'stats_updater';

	/**
	 * Get query URL
	 *
	 * @return string
	 */
	protected function get_query_url() {
		return home_url( 'give-api/stats-updater' );
	}

	/**
	 * Task
	 *
	 * @since  2.3.0
	 * @access protected
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return array|bool
	 */
	protected function task( $item ) {
		switch ( $item['type'] ) {
			case 'donor':
				break;

			case 'form':
				break;
		}

		return false;
	}
}
