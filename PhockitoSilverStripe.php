<?php

class PhockitoSilverStripe {

	static $_all_classes = '_ALL_CLASSES';

	static function register_double($double, $of, $isDoubleOfInterface = false) {
		$_all_classes = self::$_all_classes;
		
		global ${$_all_classes};
		if (${$_all_classes} && is_array(${$_all_classes}) && !isset(${$_all_classes}['exists'][$double])) {

			// Mark as exists
			${$_all_classes}['exists'][$double] = $double;

			if ($isDoubleOfInterface) {
				// If we're doubling an interface, mark the double as an implementor
				if (!isset(${$_all_classes}['implementors'][$of])) ${$_all_classes}['implementors'][$of] = array();
				${$_all_classes}['implementors'][$of][$double] = $double;

				// And don't have any parents
				${$_all_classes}['parents'][$double] = array();
			}
			else {
				// Otherwise parents are same as good twin's parents + good twin itself
				${$_all_classes}['parents'][$double] = array_merge(${$_all_classes}['parents'][$of], array($of => $of));

				// And see if good twin is marked as implementor of any interfaces - we should be too
				foreach (${$_all_classes}['implementors'] as $interface => $implementors) {
					if (array_key_exists($of, $implementors)) ${$_all_classes}['implementors'][$interface][$double] = $double;
				}
			}
		}
	}

}

Phockito::$type_registrar = 'PhockitoSilverStripe';