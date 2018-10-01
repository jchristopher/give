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
			'nonce'       => wp_create_nonce( $this->identifier ),
		);
	}

	/**
	 * Get post args
	 *
	 * @return array
	 */
	protected function get_post_args() {
		return array(
			'timeout'   => 0.01,
			'blocking'  => false,
			// 'body'      => $this->data,  Do not add batch data to post request.
			'cookies'   => $_COOKIE,
			'sslverify' => apply_filters( 'https_local_ssl_verify', false ),
		);
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
		// Bailout.
		if(
			empty( $item['donation_id'] )
			|| empty( $item['hash'] )
		) {
			return false;
		}

		$hash = give_get_payment_key( $item['donation_id'] );

		// Bailout.
		if(
			$hash !== $item['hash']
			|| 'publish' != get_post_status( $item['donation_id'] )
		) {
			return false;
		}

		$donation = new Give_Payment( $item['donation_id'] );

		Give()->donation_stats_db->add(
			array(
				'form_id'     => $donation->form_id,
				'donation_id' => $donation->ID,
				'donor_id'    => $donation->donor_id,
				'date'        => $donation->date,
				'amount'      => give_sanitize_amount_for_db( $donation->total ),
				'anonymous'   => (int) give_is_anonymous_donation( $donation->ID ),
				'type'        => 'donation',
			)
		);

		return false;
	}
}

return new Give_Stats_Background_Updater();
