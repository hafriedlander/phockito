<?php

// Include Pokito
require_once(dirname(dirname(__FILE__)) . '/Pokito.php');

/** Base class to mock */

class PokitoTest_MockMe {
	function Foo() { throw new Exception('Base method Foo was called'); }
	function Bar() { throw new Exception('Base method Bar was called'); }
}

/** Classes with different types of hierarchy */

class PokitoTest_MockSubclass extends PokitoTest_MockMe {
	function Baz() { throw new Exception('Base method Baz was called'); }
}

interface PokitoTest_MockInterface {
	function Foo();
}

/** Classes with different types of methods */

class PokitoTest_FooIsStatic { static function Foo() { } }
class PokitoTest_FooIsProtected { protected function Foo() { } }
class PokitoTest_FooIsFinal { final function Foo() { } }

class PokitoTest_FooHasIntegerDefaultArgument { function Foo($a = 1) { } }
class PokitoTest_FooHasArrayDefaultArgument { function Foo($a = array(1,2,3)) { } }
class PokitoTest_FooHasByReferenceArgument { function Foo(&$a) { } }

/** A class to get Pokito to throw when verification fails, to tell difference between Pokito failure and other PHPUnit assert failures */

class PokitoTest_VerificationFailure extends Exception {}

/** And the tests themselves */

class PokitoTest extends PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		Pokito_VerifyBuilder::$exception_class = 'PokitoTest_VerificationFailure';
	}

	/** Test creation of mock classes **/

	function testCanCreateBasicMockClass() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$this->assertInstanceOf('PokitoTest_MockMe', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
	}

	function testCanCreateMockOfChildClass() {
		$mock = Pokito::mock('PokitoTest_MockSubclass');
		$this->assertInstanceOf('PokitoTest_MockMe', $mock);
		$this->assertInstanceOf('PokitoTest_MockSubclass', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
		$this->assertNull($mock->Baz());
	}

	function testCanCreateMockOfInterface() {
		$mock = Pokito::mock('PokitoTest_MockInterface');
		$this->assertInstanceOf('PokitoTest_MockInterface', $mock);
		$this->assertNull($mock->Foo());
	}

	function testCanCreateMockOfStatic() {
		$mock = Pokito::mock_class('PokitoTest_FooIsStatic');
		$this->assertNull($mock::Foo());
	}

	function testCanCreateMockOfProtected() {
		$mock = Pokito::mock('PokitoTest_FooIsProtected');
	}

	function testCanCreateMockMethodWithIntegerDefaultArgument() {
		$mock = Pokito::mock('PokitoTest_FooHasIntegerDefaultArgument');
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Foo(1));
	}

	function testCanCreateMockMethodWithArrayDefaultArgument() {
		$mock = Pokito::mock('PokitoTest_FooHasArrayDefaultArgument');
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Foo(array()));
	}

	function testCanCreateMockMethodWithByReferenceArgument() {
		$mock = Pokito::mock('PokitoTest_FooHasByReferenceArgument');
		$a = 1;
		$this->assertNull($mock->Foo($a));
	}

	/** Test stubbing **/

	function testCanSpecifySingleReturnValue() {
		$mock = Pokito::mock('PokitoTest_MockMe');

		Pokito::when($mock->Foo())->return(1);
		$this->assertEquals($mock->Foo(), 1);
	}

	function testCanSpecifySingleReturnValueWithAlternateAPI(){
		$mock = Pokito::mock('PokitoTest_MockMe');

		Pokito::when($mock)->Foo()->return(1);
		$this->assertEquals($mock->Foo(), 1);
	}

	function testCanSpecifyMultipleReturnValues() {
		$mock = Pokito::mock('PokitoTest_MockMe');

		Pokito::when($mock->Foo())->return(1)->thenReturn(2);
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(), 2);
	}

	function testCanSpecifyDifferentReturnsValuesForDifferentArgs() {
		$mock = Pokito::mock('PokitoTest_MockMe');

		Pokito::when($mock->Bar('a'))->return(1);
		Pokito::when($mock->Bar('b'))->return(2);

		$this->assertEquals($mock->Bar('a'), 1);
		$this->assertEquals($mock->Bar('b'), 2);
	}

	function testMocksHaveIndependantReturnValueLists() {
		$mock1 = Pokito::mock('PokitoTest_MockMe');
		Pokito::when($mock1->Foo())->return(1);

		$mock2 = Pokito::mock('PokitoTest_MockMe');
		Pokito::when($mock2->Foo())->return(2);

		$this->assertEquals($mock1->Foo(), 1);
		$this->assertEquals($mock1->Foo(), 1);

		$this->assertEquals($mock2->Foo(), 2);
		$this->assertEquals($mock2->Foo(), 2);
	}

	function testNoSpecForOptionalArgumentMatchesDefault() {
		$mock = Pokito::mock('PokitoTest_FooHasIntegerDefaultArgument');
		
		Pokito::when($mock->Foo())->return(1);
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(1), 1);
		$this->assertNull($mock->Foo(2), 1);
	}

	function testSpecForOptionalArgumentDoesntAlsoMatchDefault() {
		$mock = Pokito::mock('PokitoTest_FooHasIntegerDefaultArgument');

		Pokito::when($mock->Foo(2))->return(1);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Foo(1));
		$this->assertEquals($mock->Foo(2), 1);
	}

	/** Test validating **/

	/**   Against 0 */

	function testNoCallsCorrectlyPassesVerificationAgainst0() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		Pokito::verify($mock, 0)->Foo();
	}

	/** @expectedException PokitoTest_VerificationFailure
	 */
	function testSingleCallCorrectlyFailsVerificationAgainst0() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		Pokito::verify($mock, 0)->Foo();
	}

	/**   Against 1 */

	function testSingleCallCorrectlyPassesVerificationAgainst1() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		Pokito::verify($mock)->Foo();
	}

	/** @expectedException PokitoTest_VerificationFailure
	 */
	function testNoCallCorrectlyFailsVerificationAgainst1() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		Pokito::verify($mock)->Foo();
	}

	/** @expectedException PokitoTest_VerificationFailure 
	 */
	function testTwoCallsCorrectlyFailsVerificationAgainst1() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		Pokito::verify($mock)->Foo();
	}

	/**   Against 2 */

	function testTwoCallsCorrectlyPassesVerificationAgainst2() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		Pokito::verify($mock, 2)->Foo();
	}

	/** @expectedException PokitoTest_VerificationFailure
	 */
	function testSingleCallCorrectlyFailsVerificationAgainst2() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		Pokito::verify($mock, 2)->Foo();
	}

	/** @expectedException PokitoTest_VerificationFailure
	 */
	function testThreeCallsCorrectlyFailsVerificationAgainst2() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		$mock->Foo();
		Pokito::verify($mock, 2)->Foo();
	}

	/**   Against 2+ */

	function testTwoCallsCorrectlyPassesVerificationAgainstTwoOrMore() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		Pokito::verify($mock, '2+')->Foo();
	}

	function testThreeCallsCorrectlyPassesVerificationAgainstTwoOrMore() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		$mock->Foo();
		Pokito::verify($mock, '2+')->Foo();
	}

	/** @expectedException PokitoTest_VerificationFailure
	 */
	function testSingleCallCorrectlyFailsVerificationAgainstTwoOrMore() {
		$mock = Pokito::mock('PokitoTest_MockMe');
		$mock->Foo();
		Pokito::verify($mock, '2+')->Foo();
	}



}
