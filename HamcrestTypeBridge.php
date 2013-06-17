<?php

class HamcrestTypeBridge {
	/**
	 * Creates a special mock of $type which wraps the given $matcher.
	 *
	 * @param string $type Name of the class to subtype
	 * @param Hamcrest_Matcher $matcher The matcher to proxy
	 * @return Object A special mock of type $type that wraps $matcher, circumventing type issues.
	 */
	public static function argOfTypeThat($type, Hamcrest_Matcher $matcher) {
		$mockOfType = Phockito::mock($type);
		$mockOfType->__phockito_matcher = $matcher;
		return $mockOfType;
	}
}