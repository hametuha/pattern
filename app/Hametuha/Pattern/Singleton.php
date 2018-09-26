<?php

namespace Hametuha\Pattern;

/**
 * Pattern
 *
 * @package pattern
 */
abstract class Singleton {

	/**
	 * @var array
	 */
	private static $instances = [];

	/**
	 * Singleton constructor.
	 */
	final protected function __construct() {
		$this->init();
	}

	/**
	 * Executed inside constructor.
	 */
	protected function init() {
		// Do something.
	}

	/**
	 * Get instance.
	 *
	 * @return static
	 */
	public static function get_instance() {
		$class_name = get_called_class();
		if ( ! isset( self::$instances[ $class_name ] ) ) {
			self::$instances[ $class_name ] = new $class_name();
		}
		return self::$instances[ $class_name ];
	}

}
