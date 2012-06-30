<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

class PhockitoTostringTest_MockWithToString {
	function Foo() { }
	function __toString() { return 'Foo'; }
}

class PhockitoTostringTest_MockWithoutToString {
	function Foo() { }
}

class PhockitoToStringTest extends PHPUnit_Framework_TestCase {

	function testCanMockAndOverrideExistingToString() {
		$mock = Phockito::mock('PhockitoTostringTest_MockWithToString');

		$this->assertEquals('', ''.$mock);

		Phockito::when($mock->__toString())->return('NewReturnValue');
		$this->assertEquals('NewReturnValue', ''.$mock);
	}

	function testCanSpyAndOverrideExistingToString() {
		$mock = Phockito::spy('PhockitoTostringTest_MockWithToString');

		$this->assertEquals('Foo', ''.$mock);

		Phockito::when($mock->__toString())->return('NewReturnValue');
		$this->assertEquals('NewReturnValue', ''.$mock);
	}

	function testCanMockAndOverrideUndefinedToString() {
		$mock = Phockito::mock('PhockitoTostringTest_MockWithoutToString');

		$this->assertEquals('', ''.$mock);

		Phockito::when($mock->__toString())->return('NewReturnValue');
		$this->assertEquals('NewReturnValue', ''.$mock);
	}

	function testCanSpyAndOverrideUndefinedToString() {
		$mock = Phockito::spy('PhockitoTostringTest_MockWithoutToString');

		Phockito::when($mock)->__toString()->return('NewReturnValue');
		$this->assertEquals('NewReturnValue', ''.$mock);
	}

}

