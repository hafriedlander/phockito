<?php

namespace Phockito;

use Phockito\VerificationMode\NoMoreInteractions;
use PHPUnit_Framework_TestCase;

class NoMoreInteractionsVerificationFailureMessageTest extends PHPUnit_Framework_TestCase
{
    /** @var Invocation */
    private $_mockInvocation;
    /** @var VerificationContext */
    private $_mockContext;
    /** @var array */
    private $_wantedArgs;

    function setUp()
    {
        $this->_mockInvocation = Phockito::mock(Invocation::class);
        $this->_mockInvocation->args = array('actualArg');
        $this->_mockInvocation->backtrace = array(
            array('dummy - eval()\'d code'),
            array(
                'file' => 'someFile.php',
                'line' => 123,
                'class' => 'SomeClass',
                'type' => '->',
                'function' => 'someMethod',
                'args' => array()
            )
        );

        $this->_mockContext = Phockito::mock(VerificationContext::class);
        Phockito::when($this->_mockContext->getMethodToVerify())->return('Foo');
        $this->_wantedArgs = array('wantedArg');
        Phockito::when($this->_mockContext->getArgumentsToVerify())->return($this->_wantedArgs);
    }

    function testNoMoreInteractionsFailureMessageIncludesHelpfulErrorMessageWithStackTrace()
    {
        $this->_setAllInvocationsTo(2);
        $this->_setMatchingInvocationsTo(1);

        $noMoreInteractions = new NoMoreInteractions();
        $result = $noMoreInteractions->verify($this->_mockContext);
        $failureMessage = $result->describeConstraintFailure();

        $this->assertThat($failureMessage, $this->stringContains("No more interactions wanted"));
        $this->assertThat($failureMessage, $this->stringContains("found this interaction"));
        $this->assertThat($failureMessage, $this->stringContains('#0 someFile.php(123): SomeClass->someMethod()'));
    }

    private function _setAllInvocationsTo($count)
    {
        $allInvocations = array();
        for ($i = 0; $i < $count; $i++) {
            $allInvocations[] = $this->_mockInvocation;
        }
        Phockito::when($this->_mockContext->getAllInvocationsOnMock())->return($allInvocations);
    }

    private function _setMatchingInvocationsTo($count)
    {
        $matchingInvocations = array();
        for ($i = 0; $i < $count; $i++) {
            $matchingInvocations[] = $this->_mockInvocation;
        }
        Phockito::when($this->_mockContext->getMatchingInvocations())->return($matchingInvocations);
    }
}