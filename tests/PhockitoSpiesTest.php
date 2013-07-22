<?php

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

class PhockitoSpiesTest_MockMe {
	public $constructor_arg = false;
	function __construct($arg = true) { $this->constructor_arg = $arg; }

	function Foo() { throw new Exception('Base method Foo was called'); }
	function Bar() { return $this->Foo(); }
	function Baz($response) { return $response; }
}

class PhockitoSpiesTest extends PHPUnit_Framework_TestCase {

	/** Test stubbing **/

	function testCanPartiallyStub() {
		$spy = Phockito::spy('PhockitoSpiesTest_MockMe');
		Phockito::when($spy)->Foo()->return(1);

		$this->assertEquals($spy->Foo(), 1);
		$this->assertEquals($spy->Bar(), 1);
	}

	function testStubMethodWithArgumentNamedResponse() {
		$spy = Phockito::spy('PhockitoSpiesTest_MockMe');
		$this->assertEquals($spy->Baz(1), 1);
	}

	/** Test constructor calling */

	function testConstructorCalledByDefault() {
		$spy = Phockito::spy('PhockitoSpiesTest_MockMe');
		$this->assertTrue($spy->constructor_arg);
	}

	function testConstructorCalledWhenArgumentsPassed() {
		$spy = Phockito::spy('PhockitoSpiesTest_MockMe', 'Bang!');
		$this->assertEquals($spy->constructor_arg, 'Bang!');
	}

	function testConstructorSupressedWhenDesired() {
		$spy = Phockito::spy('PhockitoSpiesTest_MockMe', Phockito::DONT_CALL_CONSTRUCTOR);
		$this->assertFalse($spy->constructor_arg);
	}

}
