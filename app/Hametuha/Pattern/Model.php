<?php
namespace Hametuha\Pattern;


use Hametuha\Pattern\DB\Engine;

/**
 * Model pattern
 *
 * @package karma
 * @property-read \wpdb  $db
 * @property-read string $table
 * @property-read string $charset
 */
abstract class Model extends Singleton {

	protected $version = '';

	protected $name = '';

	protected $default_placeholder = [];

	protected $models = [];

	protected $priority = 10;

	protected $prefix = 'hametuha_';

	protected $engine = Engine::INNODB;

	private static $initialized = false;

	public static $list = [];

	/**
	 * Get current table version.
	 *
	 * @return string
	 */
	public function current_version() {
		return get_option( "{$this->prefix}version_{$this->table}", '0' );
	}

	/**
	 * Get row
	 *
	 * @param string $query   Query.
	 * @param mixed  $vars... Replace.
	 * @return \stdClass
	 */
	protected function get_row( $query, $vars = '' ) {
		$query = $this->make_query( func_get_args() );
		return $this->db->get_row( $query );
	}

	/**
	 * Get var
	 *
	 * @param string $query   Query.
	 * @param mixed  $vars... Replace.
	 * @return string
	 */
	protected function get_var( $query, $vars = '' ) {
		return $this->db->get_var( $this->make_query( func_get_args() ) );
	}

	/**
	 * Get found ros.
	 *
	 * @return int
	 */
	public function found_rows() {
		return (int) $this->get_var( 'SELECT FOUND_ROWS()' );
	}

	/**
	 * Get var
	 *
	 * @param string $query   Query.
	 * @param mixed $vars... Replace.
	 * @return array
	 */
	protected function get_results( $query, $vars = '' ) {
		return $this->db->get_results( $this->make_query( func_get_args() ) );
	}

	/**
	 * Get col
	 *
	 * @param string $query
	 * @param mixed $var
	 * @return array
	 */
	protected function get_col( $query, $var = '' ) {
		return $this->db->get_col( $this->make_query( func_get_args() ) );
	}

	/**
	 * Prepare method shorthand
	 *
	 * @param string $arg...
	 * @return string
	 */
	protected function make_query( $args = [] ) {
		if ( 1 < count( $args ) ) {
			return call_user_func_array( [ $this->db, 'prepare' ], $args );
		} else {
			return $args[0];
		}
	}

	/**
	 * Detect if table should be updated.
	 *
	 * @return bool
	 */
	protected function should_update() {
		return version_compare( $this->current_version(), $this->version, '<' );
	}

	/**
	 * Generate default table name
	 *
	 * @return string
	 */
	protected function default_table_name() {
		$class_name = explode( '\\', get_called_class() );
		return $this->prefix . strtolower( $class_name[ count( $class_name ) - 1 ] );
	}

	/**
	 * Register table if needed.
	 */
	protected function init() {
		$this->register_cli();
		if ( ! $this->name ) {
			$this->name = $this->default_table_name();
		}
		// On admin screen, should check db version.
		add_action( 'admin_init', function () {
			// If this is Ajax request, do nothing.
			if ( wp_doing_ajax() ) {
				return;
			}
			$this->create_table();
		}, $this->priority );
	}

	/**
	 * Update db
	 */
	public function create_table() {
		if ( ! $this->should_update() ) {
			return;
		}
		$query = $this->get_tables_schema( $this->current_version() );
		if ( ! $query ) {
			return;
		}
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $query );
		update_option( "{$this->prefix}version_{$this->table}", $this->version );
	}

	/**
	 * Get table query
	 *
	 * @param string $prev_version
	 * @return string
	 */
	abstract public function get_tables_schema( $prev_version );

	/**
	 * Get place holders.
	 *
	 * @param array $args
	 * @return array
	 * @throws \Exception
	 */
	protected function get_place_holders( $args ) {
		$wheres = [];
		foreach ( $args as $key => $val ) {
			if ( ! isset( $this->default_placeholder[ $key ] ) ) {
				// translators: %1$s is class name, %2$s is column name.
				throw new \Exception( sprintf( __( 'Model %1$s has no column "%2$s".', 'karma' ), get_called_class(), $key ), 500 );
			}
			$wheres[] = $this->default_placeholder[ $key ];
		}
		return $wheres;
	}

	/**
	 * Get placeholder for key.
	 *
	 * @param string $key
	 * @return string
	 */
	protected function get_place_holder( $key ) {
		return isset( $this->default_placeholder[ $key ] ) ? $this->default_placeholder[ $key ] : '%s';
	}

	/**
	 * Insert multiple values
	 *
	 * @param array $records
	 * @return false|int
	 */
	public function bulk_insert( array $records ) {
		// Build cols.
		$cols = [];
		foreach ( $records as $record ) {
			foreach ( $record as $key => $value ) {
				$cols[] = $key;
			}
			break;
		}
		$current_time = current_time( 'mysql', $this->use_gmt() );
		foreach ( [ 'created', 'updated' ] as $key ) {
			if ( ! in_array( $key, $cols, true ) ) {
				$cols[]  = $key;
				$records = array_map( function( $values ) use ( $key ) {
					$values[ $key ] = current_time( 'mysql', true );
					return $values;
				}, $records );
			}
		}
		$value_expression = implode( ', ', array_map( function( $values ) {
			$escaped_values = [];
			foreach ( $values as $key => $value ) {
				$escaped_values[] = $this->db->prepare( $this->get_place_holder( $key ), $value );
			}
			return sprintf( '(%s)', implode( ', ', $escaped_values ) );
		}, $records ) );
		$cols             = implode( ', ', array_map( function( $col ) {
			return "`{$col}`";
		}, $cols ) );
		$query            = <<<SQL
			INSERT INTO {$this->table} ( {$cols} ) VALUES {$value_expression}
SQL;
		return $this->db->query( $query );
	}

	/**
	 * Insert record
	 *
	 * @param array $values
	 * @return int|\WP_Error Returns record ID or WP_Error on failure.
	 */
	final public function insert( $values ) {
		$current_time = current_time( 'mysql', $this->use_gmt() );
		foreach ( [ 'created', 'updated' ] as $col ) {
			if ( ! isset( $values[ $col ] ) ) {
				$values[ $col ] = $current_time;
			}
		}
		try {
			$place_holders = $this->get_place_holders( $values );
			$result        = $this->db->insert( $this->table, $values, $place_holders );
			if ( ! $result ) {
				return new \WP_Error( 'db_error', __( 'Failed to insert data. Something is wrong with database.', 'karma' ) );
			} else {
				return (int) $this->db->insert_id;
			}
		} catch ( \Exception $e ) {
			return new \WP_Error( 'db_error', $e->getMessage() );
		}
	}

	/**
	 * Update table
	 *
	 * @param $values
	 * @param $where
	 * @return false|int
	 */
	final public function update( $values, $where ) {
		if ( ! isset( $values['updated'] ) ) {
			$values['updated'] = current_time( 'mysql', $this->use_gmt() );
		}
		try {
			return $this->db->update( $this->table, $values, $where, $this->get_place_holders( $values ), $this->get_place_holders( $where ) );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Delete data.
	 *
	 * @param array  $where
	 * @param string $table         Default, this table.
	 * @param array  $place_holders Default, this table's where format.
	 * @return false|int
	 */
	final public function delete( array $where, $table = '', $place_holders = [] ) {
		if ( ! $place_holders ) {
			try {
				$place_holders = $this->get_place_holders( $where );
			} catch ( \Exception $e ) {
				return false;
			}
		}
		return $this->db->delete( $table ?: $this->table, $where, $place_holders );
	}

	/**
	 * Detect if table exists
	 *
	 * @return bool
	 */
	public function table_exists() {
		return (bool) $this->db->get_row( $this->db->prepare( 'SHOW TABLES LIKE %s', $this->table ) );
	}

	/**
	 * If model use GMT
	 *
	 * @return bool
	 */
	protected function use_gmt() {
		return (bool) apply_filters( 'hametuha_pattern_use_gmt', true, $this->table );
	}

	/**
	 * Register CLI command
	 */
	public function register_cli() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		self::$list[ $this->table ] = get_called_class();
		if ( self::$initialized ) {
			return;
		}
		\WP_CLI::add_command( 'schema', \Hametuha\Pattern\Command::class );
		self::$initialized = false;
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'db':
				global $wpdb;
				return $wpdb;
				break;
			case 'table':
				return $this->db->prefix . $this->prefix . $this->name;
				break;
			case 'charset':
				return defined( 'DB_CHARSET' ) && DB_CHARSET ? DB_CHARSET : 'utf8';
				break;
			case 'version':
				return $this->version;
				break;
			default:
				if ( isset( $this->models[ $name ] ) ) {
					$model_class = $this->models[ $name ];
					return $model_class::get_instance();
				} else {
					return null;
				}
				break;
		}
	}
}
