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
	 * Setup
	 */
	public function __construct() {
		$this->identifier               = $this->prefix . '_' . $this->action;
		$this->cron_hook_identifier     = $this->identifier . '_cron';
		$this->cron_interval_identifier = $this->identifier . '_cron_interval';

		add_action( "give_$this->identifier", array( $this, 'maybe_handle' ) );
		add_action( $this->cron_hook_identifier, array( $this, 'handle_cron_healthcheck' ) );
		add_filter( 'cron_schedules', array( $this, 'schedule_cron_healthcheck' ) );
	}

	/**
	 * Get query URL
	 *
	 * @return string
	 */
	protected function get_query_url() {
		return home_url( 'give-api/stats-updater' );
	}

	/**
	 * Get query args
	 *
	 * @return array
	 */
	protected function get_query_args() {
		return array(
			'give-action' => $this->identifier,
			'nonce'  => wp_create_nonce( $this->identifier ),
		);
	}

	/**
	 * Maybe process queue
	 *
	 * Checks whether data exists within the queue and that
	 * the process is not already running.
	 */
	public function maybe_handle() {
		// Don't lock up other requests while processing
		session_write_close();

		if ( $this->is_process_running() ) {
			// Background process already running.
			// Create another batch to process in future
			// @todo: create new batch if cron running already.

			wp_die();
		}

		if ( $this->is_queue_empty() ) {
			// No data to process.
			wp_die();
		}

		check_ajax_referer( $this->identifier, 'nonce' );

		$this->handle();

		wp_die();
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
		// switch ( $item['type'] ) {
		// 	case 'donor':
		// 		break;
		//
		// 	case 'form':
		// 		break;
		// }

		return false;
	}
}

new Give_Stats_Background_Updater();
