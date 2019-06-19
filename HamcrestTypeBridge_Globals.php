<?php

use Hamcrest\Matcher;

require_once('HamcrestTypeBridge.php');

function argOfTypeThat($type, Matcher $matcher) {
	return HamcrestTypeBridge::argOfTypeThat($type, $matcher);
}