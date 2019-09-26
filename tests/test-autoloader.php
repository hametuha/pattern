<?php

use Hametuha\RestPatternTest\RestSample;


/**
 * Test autoloader.
 *
 * @package rest-pattern
 */
class AutoloaderTest extends WP_UnitTestCase {

	public function test_register() {
		$this->assertTrue( class_exists( 'Hametuha\Pattern\Model' ) );
	}
}
