<?php
require_once('HamcrestTypeBridge.php');

function argOfTypeThat($type, \Hamcrest_Matcher $matcher) {
	HamcrestTypeBridge::argOfTypeThat($type, $matcher);
}