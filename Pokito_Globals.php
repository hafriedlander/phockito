<?php

require_once(dirname(__FILE__) . '/Pokito.php');

function mock() {
	$args = func_get_args();
	return call_user_func_array(array('Pokito', 'mock'), $args);
}

function spy() {
	$args = func_get_args();
	return call_user_func_array(array('Pokito', 'spy'), $args);
}

function when() {
	$args = func_get_args();
	return call_user_func_array(array('Pokito', 'when'), $args);
}

function verify() {
	$args = func_get_args();
	return call_user_func_array(array('Pokito', 'verify'), $args);
}
