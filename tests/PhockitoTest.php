<?php
error_reporting( E_ALL|E_STRICT );

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

spl_autoload_register(function ($class) {
	if (0 === strncmp($class, Phockito::MOCK_PREFIX, strlen(Phockito::MOCK_PREFIX))) {
		throw new RuntimeException('Autoload attempted on a phockito mock class');
	}
}, true);

/** Base class to mock */

class PhockitoTest_MockMe {
	function Foo() { throw new Exception('Base method Foo was called'); }
	function Bar() { throw new Exception('Base method Bar was called'); }
}

/** Classes with different types of hierarchy */

class PhockitoTest_MockSubclass extends PhockitoTest_MockMe {
	function Baz() { throw new Exception('Base method Baz was called'); }
}

interface PhockitoTest_MockInterface {
	function Foo();
	function Bar($a);
}

/** Classes with different types of modifiers */
final class PhockitoTest_Final {}


/** Classes with different types of methods */
class PhockitoTest_FooIsStatic { static function Foo() { } }
class PhockitoTest_FooIsProtected { protected function Foo() { } }
class PhockitoTest_FooIsFinal { final function Foo() { } }

class PhockitoTest_FooHasIntegerDefaultArgument { function Foo($a = 1) { } }
class PhockitoTest_FooHasArrayDefaultArgument { function Foo($a = array(1,2,3)) { } }
class PhockitoTest_FooHasByReferenceArgument { function Foo(&$a) { } }

/** Classes that return byRef */
class PhockitoTest_FooReturnsByReference_NoImplements { function &Foo() { return 5;} }
interface PhockitoTest_MockInterfaceFooReturnsByReference{ function &Foo(); }
class PhockitoTest_FooReturnsByReference_Implements implements PhockitoTest_MockInterfaceFooReturnsByReference { function &Foo() { return 5;} }

/** A class to get Phockito to throw when verification fails, to tell difference between Phockito failure and other PHPUnit assert failures */

class PhockitoTest_VerificationFailure extends Exception {}
class PhockitoTest_StubResponse extends Exception {}

/** And the tests themselves */

class PhockitoTest extends PHPUnit_Framework_TestCase {

	static function setUpBeforeClass() {
		Phockito_VerifyBuilder::$exception_class = 'PhockitoTest_VerificationFailure';
	}

	/** Test creation of mock classes **/

	function testCanCreateBasicMockClass() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$this->assertInstanceOf('PhockitoTest_MockMe', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
	}

	function testCanCreateMockOfChildClass() {
		$mock = Phockito::mock('PhockitoTest_MockSubclass');
		$this->assertInstanceOf('PhockitoTest_MockMe', $mock);
		$this->assertInstanceOf('PhockitoTest_MockSubclass', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
		$this->assertNull($mock->Baz());
	}

	function testCanCreateMockOfInterface() {
		$mock = Phockito::mock('PhockitoTest_MockInterface');
		$this->assertInstanceOf('PhockitoTest_MockInterface', $mock);
		$this->assertNull($mock->Foo());
	}

	function testCanCreateMockOfStatic() {
		$mock = Phockito::mock_class('PhockitoTest_FooIsStatic');
		$this->assertNull($mock::Foo());
	}

	function testCanCreateMockOfProtected() {
		$mock = Phockito::mock('PhockitoTest_FooIsProtected');
	}

	function testCanCreateMockMethodWithIntegerDefaultArgument() {
		$mock = Phockito::mock('PhockitoTest_FooHasIntegerDefaultArgument');
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Foo(1));
	}

	function testCanCreateMockMethodWithArrayDefaultArgument() {
		$mock = Phockito::mock('PhockitoTest_FooHasArrayDefaultArgument');
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Foo(array()));
	}

	function testCanCreateMockMethodWithByReferenceArgument() {
		$mock = Phockito::mock('PhockitoTest_FooHasByReferenceArgument');
		$a = 1;
		$this->assertNull($mock->Foo($a));
	}

	function testCanCreateMockMethodWithReturnByReference() {
		//this call will succeed even if the derived type's method doesn't also return by ref
		//If the return by ref doesn't come from an interface derived types can override it
		$mock = Phockito::mock('PhockitoTest_FooReturnsByReference_NoImplements');
		$res = &$mock->Foo();
		$this->assertNull($res);

		//we need to ensure derived type returns by ref
		$clazz = new ReflectionClass($mock);
		$fooMethod = $clazz->getMethod("Foo");
		$this->assertTrue($fooMethod->returnsReference());
	}

	function testCanCreateMockMethodWithReturnByReferenceImplementingInterfaceWithReturnByRef() {
		//this call will fatal error if the derived type's method doesn't also return by ref
		//This is because it's defined like this in the interface (weird..)
		$mock = Phockito::mock('PhockitoTest_FooReturnsByReference_Implements');

		$res = &$mock->Foo();
		$this->assertNull($res);

		//we need to ensure derived type returns by ref
		$clazz = new ReflectionClass($mock);
		$fooMethod = $clazz->getMethod("Foo");
		$this->assertTrue($fooMethod->returnsReference());
	}

	/** Test stubbing **/

	function testCanSpecifySingleReturnValue() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Foo())->return(1);
		$this->assertEquals($mock->Foo(), 1);
	}

	function testCanSpecifySingleReturnValueWithAlternateAPI(){
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock)->Foo()->return(1);
		$this->assertEquals($mock->Foo(), 1);
	}

	function testCanSpecifyMultipleReturnValues() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Foo())->return(1)->thenReturn(2);
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(), 2);
	}

	function testCanSpecifyDifferentReturnsValuesForDifferentArgs() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Bar('a'))->return(1);
		Phockito::when($mock->Bar('b'))->return(2);

		$this->assertEquals($mock->Bar('a'), 1);
		$this->assertEquals($mock->Bar('b'), 2);
	}

	function testMocksHaveIndependentReturnValueLists() {
		$mock1 = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock1->Foo())->return(1);

		$mock2 = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock2->Foo())->return(2);

		$this->assertEquals($mock1->Foo(), 1);
		$this->assertEquals($mock1->Foo(), 1);

		$this->assertEquals($mock2->Foo(), 2);
		$this->assertEquals($mock2->Foo(), 2);
	}

	function testNoSpecForOptionalArgumentMatchesDefault() {
		$mock = Phockito::mock('PhockitoTest_FooHasIntegerDefaultArgument');
		
		Phockito::when($mock->Foo())->return(1);
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(1), 1);
		$this->assertNull($mock->Foo(2), 1);
	}

	function testSpecForOptionalArgumentDoesntAlsoMatchDefault() {
		$mock = Phockito::mock('PhockitoTest_FooHasIntegerDefaultArgument');

		Phockito::when($mock->Foo(2))->return(1);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Foo(1));
		$this->assertEquals($mock->Foo(2), 1);
	}

	/** @expectedException PhockitoTest_StubResponse
	 */
	function testCanSpecifyThrowResponse() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Foo())->throw('PhockitoTest_StubResponse');
		$mock->Foo();
	}

	/** @expectedException PhockitoTest_StubResponse
	 */
	function testCanSpecifyThrowInstanceResponse() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Foo())->throw(new PhockitoTest_StubResponse());
		$mock->Foo();
	}

	function _testCanSpecifyCallbackResponse_callback() {
		return 'Foo';
	}

	function testCanSpecifyCallbackResponse() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Foo())->callback(array($this, '_testCanSpecifyCallbackResponse_callback'));
		$this->assertEquals($mock->Foo(), 'Foo');
	}

	/** @expectedException PhockitoTest_StubResponse
	 */
	function testCanSpecifySequenceOfValuesAndExceptionsAsResponse() {
		$mock = Phockito::mock('PhockitoTest_MockMe');

		Phockito::when($mock->Foo())->return(1)->then(2)->thenThrow('PhockitoTest_StubResponse');
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(), 2);
		$mock->Foo();
	}

	function testCanSpecifyReturnValueForUndefinedFunction() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock->Quux())->return('Quux');

		$this->assertEquals('Quux', $mock->Quux());
	}


	/**
	 * The raised error will be wrapped in an exception by PHPUnit
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testCannotUseThenWithoutAPreviousAction() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock->Foo())->then(1);
	}

	/**
	 * The raised error will be wrapped in an exception by PHPUnit
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testUnknownStubbingActionThrowsAnError() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock->Foo())->thenDoSomethingUndefined(1);
	}

	/**
	 * The raised error will be wrapped in an exception by PHPUnit
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testProvidingTooFewArgumentsToStubbingActionThrowsAnError() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock->Foo())->return();
	}

	/**
	 * The raised error will be wrapped in an exception by PHPUnit
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testProvidingTooManyArgumentsToStubbingActionThrowsAnError() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::when($mock->Foo())->return(1, 2);
	}

	function testCanSpecifyReturnValueForReferenceNoInterfaceImplemented() {
		//this call will succeed even if the derived type's method doesn't also return by ref
		//If the return by ref doesn't come from an interface derived types can override it
		$mock = Phockito::mock('PhockitoTest_FooReturnsByReference_NoImplements');
		Phockito::when($mock->Foo())->return(4);
		$res = &$mock->Foo();
		$this->assertEquals(4, $res);

	}

	function testCanSpecifyReturnObjectForReferenceNoInterfaceImplemented() {
		//this call will fatal error if the derived type's method doesn't also return by ref
		//This is because it's defined like this in the interface (weird..)
		$mock = Phockito::mock('PhockitoTest_FooReturnsByReference_Implements');
		$obj = new PhockitoTest_MockMe();
		Phockito::when($mock->Foo())->return($obj);
		$res = $mock->Foo();
		$this->assertEquals($obj, $res);
	}

	function testCanSpecifyReturnValueForReferenceInterfaceImplemented() {
		//this call will fatal error if the derived type's method doesn't also return by ref
		//This is because it's defined like this in the interface (weird..)
		$mock = Phockito::mock('PhockitoTest_FooReturnsByReference_Implements');
		Phockito::when($mock->Foo())->return(4);
		$res = &$mock->Foo();
		$this->assertEquals(4, $res);
	}

	function testCanSpecifyReturnObjectForReferenceInterfaceImplemented() {
		//this call will fatal error if the derived type's method doesn't also return by ref
		//This is because it's defined like this in the interface (weird..)
		$mock = Phockito::mock('PhockitoTest_FooReturnsByReference_Implements');
		$obj = new stdClass();

		Phockito::when($mock->Foo())->return($obj);
		$res = &$mock->Foo();
		$this->assertEquals($obj, $res);
	}

	/** Test validating **/

	/**   Against 0 */

	function testNoCallsCorrectlyPassesVerificationAgainst0() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::verify($mock, 0)->Foo();
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testSingleCallCorrectlyFailsVerificationAgainst0() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		Phockito::verify($mock, 0)->Foo();
	}

	/**   Against 1 */

	function testSingleCallCorrectlyPassesVerificationAgainst1() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		Phockito::verify($mock)->Foo();
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testNoCallCorrectlyFailsVerificationAgainst1() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		Phockito::verify($mock)->Foo();
	}

	/** @expectedException PhockitoTest_VerificationFailure 
	 */
	function testTwoCallsCorrectlyFailsVerificationAgainst1() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock)->Foo();
	}

	/**   Against 2 */

	function testTwoCallsCorrectlyPassesVerificationAgainst2() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, 2)->Foo();
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testSingleCallCorrectlyFailsVerificationAgainst2() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		Phockito::verify($mock, 2)->Foo();
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testThreeCallsCorrectlyFailsVerificationAgainst2() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, 2)->Foo();
	}

	/**   Against 2+ */

	function testTwoCallsCorrectlyPassesVerificationAgainstTwoOrMore() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, '2+')->Foo();
	}

	function testThreeCallsCorrectlyPassesVerificationAgainstTwoOrMore() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, '2+')->Foo();
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testSingleCallCorrectlyFailsVerificationAgainstTwoOrMore() {
		$mock = Phockito::mock('PhockitoTest_MockMe');
		$mock->Foo();
		Phockito::verify($mock, '2+')->Foo();
	}

	/**
	 * @expectedException PHPUnit_Framework_Error
	 * @expectedExceptionCode E_USER_ERROR
	 */
	function testCannotMockFinalClass() {
		Phockito::mock('PhockitoTest_Final');
	}

}
