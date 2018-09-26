<?php

namespace Hametuha\Pattern;

use Hametuha\Pattern\Utility\Reflection;

/**
 * CLI command
 *
 * @package pattern
 */
class Command extends \WP_CLI_Command {

	use Reflection;

	/**
	 * Display table schema SQL
	 *
	 * ## OPTIONS
	 *
	 * <model_class>
	 * : Model class name of Hametuha\Pattern\Model
	 *
	 * ## EXAMPLES
	 *
	 *     wp schema wp_custom_table
	 *
	 * @synopsis <table_name>
	 * @param array $args
	 */
	public function sql( $args ) {
		list( $table_name ) = $args;
		$rows = Model::$list;
		if ( ! isset( $rows[ $table_name ] ) ) {
			\WP_CLI::error( sprintf( '%s: table is not registered.', $table_name ) );
		}
		$model = $rows[ $table_name ];
		if ( ! class_exists( $model ) ) {
			\WP_CLI::error( sprintf( '%s: Model class does not exist.', $model ) );
		}
		if ( ! $this->is_sub_class_of( $model, Model::class ) ) {
			\WP_CLI::error( sprintf( '%s is not sub class of %s.', $model, Model::class ) );
		}
		/** @var Model $instance */
		$instance = $model::get_instance();
		\WP_CLI::line( sprintf( '%s: Version %s', $instance->table, $instance->version ) );
		$query = $instance->get_tables_schema( $instance->current_version() );
		echo "\n" . $query . "\n\n";
		\WP_CLI::success( sprintf( '%s letters', number_format_i18n( strlen( $query ) ) ) );
	}

	/**
	 * Get list of tables which generated by Hametuha\Pattern\Model
	 */
	public function tables() {
		$table = new \cli\Table();
		$rows  = Model::$list;
		if ( ! $rows ) {
			\WP_CLI::error( 'No model is regsitered.' );
		}
		$table->setHeaders( [ 'Table Name', 'Model Class' ] );
		foreach ( $rows as $table_name => $class) {
			$table->addRow( [ $table_name, $class ] );
		}
		$table->display();
	}

}