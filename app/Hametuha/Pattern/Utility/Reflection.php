<?php

namespace Hametuha\Pattern\Utility;



use Hametuha\Pattern\Singleton;


/**
 * Reflection helper
 *
 * @package pattern
 */
trait Reflection {

	/**
	 * Check if specified class is singleton
	 *
	 * @param string $class_name
	 * @return bool
	 */
	protected function is_singleton( $class_name ) {
		return $this->is_sub_class_of( $class_name, Singleton::class );
	}

	/**
	 * Detect if specifies class is subclass
	 *
	 * @param string $class_name
	 * @param string $should Parent class name
	 * @param bool   $allow_abstract Default false
	 * @return bool
	 */
	protected function is_sub_class_of( $class_name, $should, $allow_abstract = false ) {
		if ( ! class_exists( $class_name ) ) {
			return false;
		}
		// Check if this is subclass
		try {
			$refl = new \ReflectionClass( $class_name );
			return ( $allow_abstract || ! $refl->isAbstract() ) && $refl->isSubclassOf( $should );
		} catch ( \Exception $e ) {
			return false;
		}
	}

	/**
	 * Returns all defined constants
	 *
	 * @return array
	 */
	public static function get_all_constants() {
		try {
			$refl = new \ReflectionClass( get_called_class() );
			return $refl->getConstants();
		} catch ( \Exception $e ) {
			return [];
		}
	}
}
