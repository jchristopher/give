<?php
/**
 * Give DB Meta
 *
 * @package     Give
 * @subpackage  Classes/Give_DB_Meta
 * @copyright   Copyright (c) 2017, WordImpress
 * @license     https://opensource.org/licenses/gpl-license GNU Public License
 * @since       2.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Give_DB_Meta extends Give_DB {
	/**
	 * Post type
	 *
	 * @since  2.0
	 * @access protected
	 * @var bool
	 */
	protected $post_type = '';

	/**
	 * Meta type
	 *
	 * @since  2.0
	 * @access protected
	 * @var bool
	 */
	protected $meta_type = '';

	/**
	 * Flag to handle result type
	 *
	 * @since  2.0
	 * @access protected
	 */
	protected $raw_result = false;

	/**
	 * Flag for short circuit of meta function
	 *
	 * @since  2.0
	 * @access protected
	 */
	protected $check = false;


	/**
	 * Meta supports.
	 *
	 * @since  2.0
	 * @access protected
	 * @var array
	 */
	protected $supports = array(
		'add_post_metadata',
		'get_post_metadata',
		'update_post_metadata',
		'delete_post_metadata',
		'posts_where',
		'posts_join',
		'posts_groupby',
		'posts_orderby'
	);

	/**
	 * Give_DB_Meta constructor.
	 *
	 * @since 2.0
	 */
	function __construct() {
		parent::__construct();

		// Bailout.
		if ( empty( $this->supports ) || ! $this->is_custom_meta_table_active() ) {
			return;
		}
	}


	/**
	 * Retrieve payment meta field for a payment.
	 *
	 * @access  public
	 * @since   2.0
	 *
	 * @param   int    $id       Pst Type  ID.
	 * @param   string $meta_key The meta key to retrieve.
	 * @param   bool   $single   Whether to return a single value.
	 *
	 * @return  mixed                 Will be an array if $single is false. Will be value of meta data field if $single
	 *                                is true.
	 */
	public function get_meta( $id = 0, $meta_key = '', $single = false ) {
		$id = $this->sanitize_id( $id );

		if ( $this->raw_result ) {
			if ( ! ( $value = get_metadata( $this->meta_type, $id, $meta_key, false ) ) ) {
				$value = '';
			}

			// Reset flag.
			$this->raw_result = false;

		} else {
			$value = get_metadata( $this->meta_type, $id, $meta_key, $single );
		}

		return $value;
	}


	/**
	 * Add meta data field to a payment.
	 *
	 * For internal use only. Use Give_Payment->add_meta() for public usage.
	 *
	 * @access  private
	 * @since   2.0
	 *
	 * @param   int    $id         Post Type ID.
	 * @param   string $meta_key   Metadata name.
	 * @param   mixed  $meta_value Metadata value.
	 * @param   bool   $unique     Optional, default is false. Whether the same key should not be added.
	 *
	 * @return  int|bool                  False for failure. True for success.
	 */
	public function add_meta( $id = 0, $meta_key = '', $meta_value = '', $unique = false ) {
		$id = $this->sanitize_id( $id );

		$meta_id = add_metadata( $this->meta_type, $id, $meta_key, $meta_value, $unique );

		if ( $meta_id ) {
			$this->delete_cache( $id );
		}

		return $meta_id;
	}

	/**
	 * Update payment meta field based on Post Type ID.
	 *
	 * For internal use only. Use Give_Payment->update_meta() for public usage.
	 *
	 * Use the $prev_value parameter to differentiate between meta fields with the
	 * same key and Post Type ID.
	 *
	 * If the meta field for the payment does not exist, it will be added.
	 *
	 * @access  public
	 * @since   2.0
	 *
	 * @param   int    $id         Post Type ID.
	 * @param   string $meta_key   Metadata key.
	 * @param   mixed  $meta_value Metadata value.
	 * @param   mixed  $prev_value Optional. Previous value to check before removing.
	 *
	 * @return  int|bool                  False on failure, true if success.
	 */
	public function update_meta( $id = 0, $meta_key = '', $meta_value = '', $prev_value = '' ) {
		$id = $this->sanitize_id( $id );

		$meta_id = update_metadata( $this->meta_type, $id, $meta_key, $meta_value, $prev_value );

		if ( $meta_id ) {
			$this->delete_cache( $id );
		}

		return $meta_id;
	}

	/**
	 * Remove metadata matching criteria from a payment.
	 *
	 * You can match based on the key, or key and value. Removing based on key and
	 * value, will keep from removing duplicate metadata with the same key. It also
	 * allows removing all metadata matching key, if needed.
	 *
	 * @access  public
	 * @since   2.0
	 *
	 * @param   int    $id         Post Type ID.
	 * @param   string $meta_key   Metadata name.
	 * @param   mixed  $meta_value Optional. Metadata value.
	 * @param   mixed  $delete_all Optional.
	 *
	 * @return  bool                  False for failure. True for success.
	 */
	public function delete_meta( $id = 0, $meta_key = '', $meta_value = '', $delete_all = '' ) {
		$id = $this->sanitize_id( $id );

		$is_meta_deleted = delete_metadata( $this->meta_type, $id, $meta_key, $meta_value, $delete_all );

		if ( $is_meta_deleted ) {
			$this->delete_cache( $id );
		}

		return $is_meta_deleted;
	}


	/**
	 * Check if current query for post type or not.
	 *
	 * @since  2.0
	 * @access protected
	 *
	 * @param WP_Query $wp_query
	 *
	 * @return bool
	 */
	protected function is_post_type_query( $wp_query ) {
		$status = false;

		// Check if it is payment query.
		if ( ! empty( $wp_query->query['post_type'] ) ) {
			if (
				is_string( $wp_query->query['post_type'] ) &&
				$this->post_type === $wp_query->query['post_type']
			) {
				$status = true;
			} elseif (
				is_array( $wp_query->query['post_type'] ) &&
				in_array( $this->post_type, $wp_query->query['post_type'] )
			) {
				$status = true;
			}
		}

		return $status;
	}

	/**
	 * Check if current id of post type or not
	 *
	 * @since  2.0
	 * @access protected
	 *
	 * @param $ID
	 *
	 * @return bool
	 */
	protected function is_valid_post_type( $ID ) {
		return $ID && ( $this->post_type === get_post_type( $ID ) );
	}

	/**
	 * check if custom meta table enabled or not.
	 *
	 * @since  2.0
	 * @access protected
	 * @return bool
	 */
	protected function is_custom_meta_table_active() {
		return false;
	}


	/**
	 * Update last_changed key
	 *
	 * @since  2.0
	 * @access private
	 *
	 * @param int    $id
	 * @param string $meta_type
	 *
	 * @return void
	 */
	private function delete_cache( $id, $meta_type = '' ) {
		$meta_type = empty( $meta_type ) ? $this->meta_type : $meta_type;

		$group = array(
			'payment'  => 'give-donations', // Backward compatibility
			'donation' => 'give-donations',
			'donor'    => 'give-donors',
			'customer' => 'give-donors', // Backward compatibility for pre upgrade in 2.0
		);

		if ( array_key_exists( $meta_type, $group ) ) {
			Give_Cache::delete_group( $id, $group[ $meta_type ] );
		}
	}

	/**
	 * Create Meta Tables.
	 *
	 * @since  2.0.1
	 * @access public
	 */
	public function create_table() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$this->table_name} (
			meta_id bigint(20) NOT NULL AUTO_INCREMENT,
			{$this->meta_type}_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT NULL,
			meta_value longtext,
			PRIMARY KEY  (meta_id),
			KEY {$this->meta_type}_id ({$this->meta_type}_id),
			KEY meta_key (meta_key({$this->min_index_length}))
			) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );

		update_option( $this->table_name . '_db_version', $this->version, false );
	}


	/**
	 * Get meta type
	 *
	 * @since  2.0.4
	 * @access public
	 *
	 * @return string
	 */
	public function get_meta_type() {
		return $this->meta_type;
	}

	/**
	 * Remove all meta data matching criteria from a meta table.
	 *
	 * @since   2.1.3
	 * @access  public
	 *
	 * @param   int $id ID.
	 *
	 * @return  bool  False for failure. True for success.
	 */
	public function delete_all_meta( $id = 0 ) {
		global $wpdb;
		$status = $wpdb->delete( $this->table_name, array( "{$this->meta_type}_id" => $id ), array( '%d' ) );

		if ( $status ) {
			$this->delete_cache( $id, $this->meta_type );
		}

		return $status;
	}
}
