<?php

namespace Phockito;


use Phockito\Test\FinalClass;
use Phockito\Test\FooHasArrayDefaultArgument;
use Phockito\Test\FooHasByReferenceArgument;
use Phockito\Test\FooHasIntegerDefaultArgument;
use Phockito\Test\FooIsProtected;
use Phockito\Test\FooIsStatic;
use Phockito\Test\FooReturnsByReferenceImplements;
use Phockito\Test\FooReturnsByReferenceNoImplements;
use Phockito\Test\MockInterface;
use Phockito\Test\MockMe;
use Phockito\Test\MockSubclass;
use Phockito\Test\StubResponse;
use Phockito\Test\VerificationFailure;
use PHPUnit_Framework_Error;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use stdClass;

class PhockitoTest extends PHPUnit_Framework_TestCase
{
    static function setUpBeforeClass()
    {
        UnsuccessfulVerificationReporter::$exception_class = VerificationFailure::class;
    }

    /* * Test creation of mock classes * */

    function testCanCreateBasicMockClass()
    {
        $mock = Phockito::mock(MockMe::class);
        $this->assertInstanceOf(MockMe::class, $mock);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Bar());
    }

    function testCanCreateMockOfChildClass()
    {
        $mock = Phockito::mock(MockSubclass::class);
        $this->assertInstanceOf(MockMe::class, $mock);
        $this->assertInstanceOf(MockSubclass::class, $mock);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Bar());
        $this->assertNull($mock->Baz());
    }

    function testCanCreateMockOfInterface()
    {
        $mock = Phockito::mock(MockInterface::class);
        $this->assertInstanceOf(MockInterface::class, $mock);
        $this->assertNull($mock->Foo());
    }

    function testCanCreateMockOfStatic()
    {
        $mock = Phockito::mock_class(FooIsStatic::class);
        $this->assertNull($mock::Foo());
    }

    function testCanCreateMockOfProtected()
    {
        $mock = Phockito::mock(FooIsProtected::class);
    }

    function testCanCreateMockMethodWithIntegerDefaultArgument()
    {
        $mock = Phockito::mock(FooHasIntegerDefaultArgument::class);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Foo(1));
    }

    function testCanCreateMockMethodWithArrayDefaultArgument()
    {
        $mock = Phockito::mock(FooHasArrayDefaultArgument::class);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Foo(array()));
    }

    function testCanCreateMockMethodWithByReferenceArgument()
    {
        $mock = Phockito::mock(FooHasByReferenceArgument::class);
        $a = 1;
        $this->assertNull($mock->Foo($a));
    }

    function testCanCreateMockMethodWithReturnByReference()
    {
        //this call will succeed even if the derived type's method doesn't also return by ref
        //If the return by ref doesn't come from an interface derived types can override it
        $mock = Phockito::mock(FooReturnsByReferenceNoImplements::class);
        $res = &$mock->Foo();
        $this->assertNull($res);

        //we need to ensure derived type returns by ref
        $clazz = new ReflectionClass($mock);
        $fooMethod = $clazz->getMethod("Foo");
        $this->assertTrue($fooMethod->returnsReference());
    }

    function testCanCreateMockMethodWithReturnByReferenceImplementingInterfaceWithReturnByRef()
    {
        //this call will fatal error if the derived type's method doesn't also return by ref
        //This is because it's defined like this in the interface (weird..)
        $mock = Phockito::mock(FooReturnsByReferenceImplements::class);

        $res = &$mock->Foo();
        $this->assertNull($res);

        //we need to ensure derived type returns by ref
        $clazz = new ReflectionClass($mock);
        $fooMethod = $clazz->getMethod('Foo');
        $this->assertTrue($fooMethod->returnsReference());
    }

    /** Test stubbing **/

    function testCanSpecifySingleReturnValue()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->return(1);
        $this->assertEquals($mock->Foo(), 1);
    }

    function testCanSpecifySingleReturnValueWithAlternateAPI()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock)->Foo()->return(1);
        $this->assertEquals($mock->Foo(), 1);
    }

    function testCanSpecifyMultipleReturnValues()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->return(1)->thenReturn(2);
        $this->assertEquals($mock->Foo(), 1);
        $this->assertEquals($mock->Foo(), 2);
    }

    function testCanSpecifyDifferentReturnsValuesForDifferentArgs()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Bar('a'))->return(1);
        Phockito::when($mock->Bar('b'))->return(2);

        $this->assertEquals($mock->Bar('a'), 1);
        $this->assertEquals($mock->Bar('b'), 2);
    }

    function testMocksHaveIndependentReturnValueLists()
    {
        $mock1 = Phockito::mock(MockMe::class);
        Phockito::when($mock1->Foo())->return(1);

        $mock2 = Phockito::mock(MockMe::class);
        Phockito::when($mock2->Foo())->return(2);

        $this->assertEquals($mock1->Foo(), 1);
        $this->assertEquals($mock1->Foo(), 1);

        $this->assertEquals($mock2->Foo(), 2);
        $this->assertEquals($mock2->Foo(), 2);
    }

    function testCanOverWriteReturnValue()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->thenReturn(0);
        Phockito::when($mock->Foo())->thenReturn(1);
        Phockito::when($mock->Foo())->thenReturn(2);

        $this->assertEquals(2, $mock->Foo());
        $this->assertEquals(2, $mock->Foo());
        $this->assertEquals(2, $mock->Foo());
    }

    function testNoSpecForOptionalArgumentMatchesDefault()
    {
        $mock = Phockito::mock(FooHasIntegerDefaultArgument::class);

        Phockito::when($mock->Foo())->return(1);
        $this->assertEquals($mock->Foo(), 1);
        $this->assertEquals($mock->Foo(1), 1);
        $this->assertNull($mock->Foo(2), 1);
    }

    function testSpecForOptionalArgumentDoesntAlsoMatchDefault()
    {
        $mock = Phockito::mock(FooHasIntegerDefaultArgument::class);

        Phockito::when($mock->Foo(2))->return(1);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Foo(1));
        $this->assertEquals($mock->Foo(2), 1);
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\StubResponse
     */
    function testCanSpecifyThrowResponse()
    {
        $this->setExpectedException(StubResponse::class);

        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->throw(StubResponse::class);
        $mock->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\StubResponse
     */
    function testCanSpecifyThrowInstanceResponse()
    {
        $this->setExpectedException(StubResponse::class);

        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->throw(new StubResponse());
        $mock->Foo();
    }

    function _testCanSpecifyCallbackResponse_callback()
    {
        return 'Foo';
    }

    function testCanSpecifyCallbackResponse()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->callback(array($this, '_testCanSpecifyCallbackResponse_callback'));
        $this->assertEquals($mock->Foo(), 'Foo');
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\StubResponse
     */
    function testCanSpecifySequenceOfValuesAndExceptionsAsResponse()
    {
        $this->setExpectedException(StubResponse::class);

        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock->Foo())->return(1)->then(2)->thenThrow(StubResponse::class);
        $this->assertEquals($mock->Foo(), 1);
        $this->assertEquals($mock->Foo(), 2);
        $mock->Foo();
    }

    function testCanSpecifyReturnValueForUndefinedFunction()
    {
        $mock = Phockito::mock(MockMe::class);
        Phockito::when($mock->Quux())->return('Quux');

        $this->assertEquals('Quux', $mock->Quux());
    }


    /**
     * The raised error will be wrapped in an exception by PHPUnit
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionCode E_USER_ERROR
     */
    function testCannotUseThenWithoutAPreviousAction()
    {
        $mock = Phockito::mock(MockMe::class);
        Phockito::when($mock->Foo())->then(1);
    }

    /**
     * The raised error will be wrapped in an exception by PHPUnit
     * @expectedException PHPUnit_Framework_Error
     * @expectedExceptionCode E_USER_ERROR
     */
    function testUnknownStubbingActionThrowsAnError()
    {
        $mock = Phockito::mock(MockMe::class);
        Phockito::when($mock->Foo())->thenDoSomethingUndefined(1);
    }

    /**
     * The raised error will be wrapped in an exception by PHPUnit
     * @expectedException \PHPUnit_Framework_Error
     * @expectedExceptionCode E_USER_ERROR
     */
    function testProvidingTooFewArgumentsToStubbingActionThrowsAnError()
    {
        $mock = Phockito::mock(MockMe::class);
        call_user_func([Phockito::when($mock->Foo()), 'return']); // equals to Phockito::when($mock->Foo())->return();
    }

    /**
     * The raised error will be wrapped in an exception by PHPUnit
     * @expectedException \PHPUnit_Framework_Error
     * @expectedExceptionCode E_USER_ERROR
     */
    function testProvidingTooManyArgumentsToStubbingActionThrowsAnError()
    {
        $mock = Phockito::mock(MockMe::class);
        Phockito::when($mock->Foo())->return(1, 2);
    }

    function testCanSpecifyReturnValueForReferenceNoInterfaceImplemented()
    {
        //this call will succeed even if the derived type's method doesn't also return by ref
        //If the return by ref doesn't come from an interface derived types can override it
        $mock = Phockito::mock(FooReturnsByReferenceNoImplements::class);
        Phockito::when($mock->Foo())->return(4);
        $res = &$mock->Foo();
        $this->assertEquals(4, $res);

    }

    function testCanSpecifyReturnObjectForReferenceNoInterfaceImplemented()
    {
        //this call will fatal error if the derived type's method doesn't also return by ref
        //This is because it's defined like this in the interface (weird..)
        $mock = Phockito::mock(FooReturnsByReferenceImplements::class);
        $obj = new MockMe();
        Phockito::when($mock->Foo())->return($obj);
        $res = $mock->Foo();
        $this->assertEquals($obj, $res);
    }

    function testCanSpecifyReturnValueForReferenceInterfaceImplemented()
    {
        //this call will fatal error if the derived type's method doesn't also return by ref
        //This is because it's defined like this in the interface (weird..)
        $mock = Phockito::mock(FooReturnsByReferenceImplements::class);
        Phockito::when($mock->Foo())->return(4);
        $res = &$mock->Foo();
        $this->assertEquals(4, $res);
    }

    function testCanSpecifyReturnObjectForReferenceInterfaceImplemented()
    {
        //this call will fatal error if the derived type's method doesn't also return by ref
        //This is because it's defined like this in the interface (weird..)
        $mock = Phockito::mock(FooReturnsByReferenceImplements::class);
        $obj = new stdClass();

        Phockito::when($mock->Foo())->return($obj);
        $res = &$mock->Foo();
        $this->assertEquals($obj, $res);
    }

    /** Test validating **/

    /**   Against 0 */

    function testNoCallsCorrectlyPassesVerificationAgainst0()
    {
        $mock = Phockito::mock(MockMe::class);
        Phockito::verify($mock, 0)->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testSingleCallCorrectlyFailsVerificationAgainst0()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, 0)->Foo();
    }

    /**   Against 1 */

    function testSingleCallCorrectlyPassesVerificationAgainst1()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock)->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testNoCallCorrectlyFailsVerificationAgainst1()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        Phockito::verify($mock)->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testTwoCallsCorrectlyFailsVerificationAgainst1()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock)->Foo();
    }

    /**   Against 2 */

    function testTwoCallsCorrectlyPassesVerificationAgainst2()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, 2)->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testSingleCallCorrectlyFailsVerificationAgainst2()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, 2)->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testThreeCallsCorrectlyFailsVerificationAgainst2()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, 2)->Foo();
    }

    /**   Against 2+ */

    function testTwoCallsCorrectlyPassesVerificationAgainstTwoOrMore()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, '2+')->Foo();
    }

    function testThreeCallsCorrectlyPassesVerificationAgainstTwoOrMore()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, '2+')->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testSingleCallCorrectlyFailsVerificationAgainstTwoOrMore()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, '2+')->Foo();
    }

    /**   Against times() */

    function testTwoCallsCorrectlyPassesVerificationAgainstExactlyTwo()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::times(2))->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testThreeCallsCorrectlyFailsVerificationAgainstExactlyTwo()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::times(2))->Foo();
    }

    /**   Against never() */

    function testNoCallsCorrectlyPassesVerificationAgainstNever()
    {
        $mock = Phockito::mock(MockMe::class);
        Phockito::verify($mock, Phockito::never())->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testSingleCallCorrectlyFailsVerificationAgainstNever()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, Phockito::never())->Foo();
    }

    /**   Against atLeast() */

    function testTwoCallsCorrectlyPassesVerificationAgainstAtLeastTwo()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeast(2))->Foo();
    }

    function testThreeCallsCorrectlyPassesVerificationAgainstAtLeastTwo()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeast(2))->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testSingleCallCorrectlyFailsVerificationAgainstAtLeastTwo()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeast(2))->Foo();
    }

    /**   Against atLeastOnce() */

    function testTwoCallsCorrectlyPassesVerificationAgainstAtLeastOnce()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeastOnce())->Foo();
    }

    function testSingleCallsCorrectlyPassesVerificationAgainstAtLeastOnce()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeastOnce())->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testNoCallsCorrectlyFailsVerificationAgainstAtLeastOnce()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        Phockito::verify($mock, Phockito::atLeastOnce())->Foo();
    }

    /**   Against atMost() */

    function testTwoCallsCorrectlyPassesVerificationAgainstAtMostTwo()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atMost(2))->Foo();
    }

    function testSingleCallCorrectlyPassesVerificationAgainstAtMostTwo()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, Phockito::atMost(2))->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testThreeCallsCorrectlyFailsVerificationAgainstAtMostTwo()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atMost(2))->Foo();
    }

    /**   Against only() */

    function testSingleCallCorrectlyPassesVerificationAgainstOnly()
    {
        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        Phockito::verify($mock, Phockito::only())->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testDifferentCallCorrectlyFailsVerificationAgainstOnly()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Bar();
        Phockito::verify($mock, Phockito::only())->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testNoCallsCorrectlyFailsVerificationAgainstOnly()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        Phockito::verify($mock, Phockito::only())->Foo();
    }

    /**
     * @expectedException \\Phockito\\Phockito\\Test\\VerificationFailure
     */
    function testTwoCallsCorrectlyFailsVerificationAgainstOnly()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(MockMe::class);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::only())->Foo();
    }

    function testSingleCallsToMultipleMocksCorrectlyPassesVerificationAgainstOnly()
    {
        $mock1 = Phockito::mock(MockMe::class);
        $mock2 = Phockito::mock(MockMe::class);
        $mock1->Foo();
        $mock2->Bar();
        Phockito::verify($mock1, Phockito::only())->Foo();
        Phockito::verify($mock2, Phockito::only())->Bar();
    }

    /**
     * @expectedException \PHPUnit_Framework_Error
     * @expectedExceptionCode E_USER_ERROR
     */
    function testCannotMockFinalClass()
    {
        Phockito::mock(FinalClass::class);
    }
}