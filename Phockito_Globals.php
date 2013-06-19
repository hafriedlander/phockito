<?php

require_once(dirname(__FILE__) . '/Phockito.php');

function mock() {
	$args = func_get_args();
	return call_user_func_array(array('Phockito', 'mock'), $args);
}

function spy() {
	$args = func_get_args();
	return call_user_func_array(array('Phockito', 'spy'), $args);
}

/**
 * @return Phockito_WhenBuilder
 */
function when() {
	$args = func_get_args();
	return call_user_func_array(array('Phockito', 'when'), $args);
}

function verify() {
	$args = func_get_args();
	return call_user_func_array(array('Phockito', 'verify'), $args);
}
