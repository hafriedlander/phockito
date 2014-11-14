<?php

use Hamcrest\Matcher;
use Phockito\HamcrestTypeBridge;
use Phockito\MockMarker;
use Phockito\Phockito;
use Phockito\VerificationMode\VerificationMode;
use Phockito\VerifyBuilder;
use Phockito\WhenBuilder;

/**
 * Given a class name as a string, return a new instance which acts as a mock of that class
 *
 * @param string $class - The class to mock
 * @return Object - A mock of that class
 */
function mock($class) {
    return Phockito::mock($class);
}

/**
 * Given a class name as a string, return a new instance which acts as a spy of that class
 *
 * @param string $class - The class to spy
 * @return Object - A spy of that class
 */
function spy($class) {
    return Phockito::spy($class);
}

/**
 * When builder. Starts stubbing the method called to build the argument passed to when
 *
 * @param MockMarker|Object|null $arg
 * @return WhenBuilder|Object
 */
function when($arg) {
    return Phockito::when($arg);
}

/**
 * Verify builder. Takes a mock instance and an optional number of times to verify against. Returns a
 * DSL object that catches the method to verify
 *
 * @param MockMarker|Object $mock - The mock instance to verify
 * @param string|int $times - The number of times the method should be called, either a number, or a number followed by "+"
 * @return mixed|VerifyBuilder
 */
function verify($mock, $times = 1) {
    return Phockito::verify($mock, $times);
}

/**
 * @param $times
 * @return VerificationMode
 */
function times($times) {
	return Phockito::times($times);
}

/**
 * @return VerificationMode
 */
function never() {
	return Phockito::never();
}

/**
 * @param $times
 * @return VerificationMode
 */
function atLeast($times) {
	return Phockito::atLeast($times);
}

/**
 * @return VerificationMode
 */
function atLeastOnce() {
	return Phockito::atLeastOnce();
}

/**
 * @param $times
 * @return VerificationMode
 */
function atMost($times) {
	return Phockito::atMost($times);
}

/**
 * @return VerificationMode
 */
function only() {
	return Phockito::only();
}

/**
 * @param $type
 * @param Matcher $matcher
 * @return Object
 */
function argOfTypeThat($type, Matcher $matcher) {
    return HamcrestTypeBridge::argOfTypeThat($type, $matcher);
}