<?php
namespace Hametuha\Pattern\DB;


use Hametuha\Pattern\Utility\Reflection;

/**
 * Database engine
 *
 * @package pattern
 */
class Engine {

	use Reflection;

	const MYISAM = 'MyISAM';

	const INNODB = 'InnoDB';

	const MROONGA = 'mroonga';

	/**
	 * Detect if engine name is valid
	 *
	 * @param string $engine
	 * @return bool
	 */
	public static function is_valid( $engine ) {
		$engines = self::get_all_constants();
		return false !== array_search( $engine, $engines, true );
	}
}
