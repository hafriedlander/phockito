<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
Phockito::include_hamcrest();

class PhockitoHamcrestTypeBridgeTest_MockMe {
    function Foo(PhockitoHamcrestTypeBridgeTest_PassMe $a) { throw new Exception('Base method Foo was called'); }
    function Bar(array $a) { throw new Exception('Base method Bar was called'); }
}

class PhockitoHamcrestTypeBridgeTest_MockMe_Constructor {
	function __construct(PhockitoHamcrestTypeBridgeTest_PassMe $passMe) { throw new Exception('Base constructor was called'); }
	function Foo(PhockitoHamcrestTypeBridgeTest_PassMe $a) { throw new Exception('Base method Foo was called'); }
}

final class PhockitoHamcrestTypeBridgeTest_MockMe_Final {
}

class PhockitoHamcrestTypeBridgeTest_PassMe {}

class PhockitoHamcrestTypeBridgeTest_PassMe_MatcherMethods {
	public function matches($item) { throw new Exception('Base method matches was called'); }
}

class PhockitoHamcrestTypeBridgeTest extends PHPUnit_Framework_TestCase {
    function testCanStubUsingMatchersForTypeHintedObjectArguments() {
        $mock = Phockito::mock('PhockitoHamcrestTypeBridgeTest_MockMe');

        Phockito::when($mock->Foo(
			HamcrestTypeBridge::argOfTypeThat('PhockitoHamcrestTypeBridgeTest_PassMe',
				anInstanceOf('PhockitoHamcrestTypeBridgeTest_PassMe'))))
			->return('PassMe');

        $this->assertEquals($mock->Foo(new PhockitoHamcrestTypeBridgeTest_PassMe()), 'PassMe');
    }

	function testCanBridgeTypeWithTypeHintedConstructor() {
		$mock = Phockito::mock('PhockitoHamcrestTypeBridgeTest_MockMe_Constructor');

		Phockito::when($mock->Foo(
			HamcrestTypeBridge::argOfTypeThat('PhockitoHamcrestTypeBridgeTest_PassMe',
				anInstanceOf('PhockitoHamcrestTypeBridgeTest_PassMe'))))
			->return('PassMe');

		$this->assertEquals($mock->Foo(new PhockitoHamcrestTypeBridgeTest_PassMe()), 'PassMe');
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 * @expectedExceptionMessage Can't mock non-existent class NotAClass
	 */
	function testBridgingInvalidTypeThrowsException() {
		$mock = Phockito::mock('PhockitoHamcrestTypeBridgeTest_MockMe');

		Phockito::when($mock->Foo(
			HamcrestTypeBridge::argOfTypeThat('NotAClass',
				anInstanceOf('NotAClass'))))
			->return('PassMe');
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testCannotBridgeFinalType() {
		HamcrestTypeBridge::argOfTypeThat('PhockitoHamcrestTypeBridgeTest_MockMe_Final', anArray());
	}
}
