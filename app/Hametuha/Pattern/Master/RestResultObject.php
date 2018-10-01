<?php

namespace Hametuha\Pattern\Master;

/**
 * REST result object.
 *
 * @package pattern
 */
interface RestResultObject {

	/**
	 * Returns REST ready resulting array.
	 *
	 * @return array
	 */
	public function to_array();

}
