<?php

class PhockitoSilverStripe {

	static function register_double($double, $of, $isDoubleOfInterface = false) {
		global $_ALL_CLASSES;
		if ($_ALL_CLASSES && is_array($_ALL_CLASSES) && !isset($_ALL_CLASSES['exists'][$double])) {

			// Mark as exists
			$_ALL_CLASSES['exists'][$double] = $double;

			if ($isDoubleOfInterface) {
				// If we're doubling an interface, mark the double as an implementor
				if (!isset($_ALL_CLASSES['implementors'][$of])) $_ALL_CLASSES['implementors'][$of] = array();
				$_ALL_CLASSES['implementors'][$of][$double] = $double;

				// And don't have any parents
				$_ALL_CLASSES['parents'][$double] = array();
			}
			else {
				// Otherwise parents are same as good twin's parents + good twin itself
				$_ALL_CLASSES['parents'][$double] = array_merge($_ALL_CLASSES['parents'][$of], array($of => $of));

				// And see if good twin is marked as implementor of any interfaces - we should be too
				foreach ($_ALL_CLASSES['implementors'] as $interface => $implementors) {
					if (array_key_exists($of, $implementors)) $_ALL_CLASSES['implementors'][$interface][$double] = $double;
				}
			}
		}
	}

}

Phockito::$type_registrar = 'PhockitoSilverStripe';