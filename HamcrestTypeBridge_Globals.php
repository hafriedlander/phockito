<?php
require_once('HamcrestTypeBridge.php');

function argOfTypeThat($type, \Hamcrest_Matcher $matcher) {
    return HamcrestTypeBridge::argOfTypeThat($type, $matcher);
}
