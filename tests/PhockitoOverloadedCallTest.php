<?php

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

class PhockitoOverloadedCallTest_OverloadedCall {
	function __call($name, $args) { return $name; }
}

class PhockitoOverloadedCallTest extends PHPUnit_Framework_TestCase {

	function testMockingCall() {
		$mock = Phockito::mock('PhockitoOverloadedCallTest_OverloadedCall');

		$this->assertNull($mock->Foo());

		Phockito::when($mock)->Foo()->return(1);
		$this->assertEquals($mock->Foo(), 1);

		Phockito::verify($mock, 2)->Foo();
	}

	function testSpyingCall() {
		$spy = Phockito::spy('PhockitoOverloadedCallTest_OverloadedCall');

		$this->assertEquals($spy->Foo(), 'Foo');

		Phockito::when($spy)->Foo()->return(1);
		$this->assertEquals($spy->Foo(), 1);

		Phockito::verify($spy, 2)->Foo();
	}
}
