<?php
error_reporting( E_ALL|E_STRICT );

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

spl_autoload_register(function ($class) {
	if (0 === strncmp($class, Phockito::MOCK_PREFIX, strlen(Phockito::MOCK_PREFIX))) {
		throw new RuntimeException('Autoload attempted on a phockito mock class');
	}
}, true);


class PhockitoNumericalVerificationFailureMessageTest extends PHPUnit_Framework_TestCase {
	/** @var Phockito_Invocation */
	private $_mockInvocation;
	/** @var Phockito_VerificationContext */
	private $_mockContext;
	/** @var array */
	private $_wantedArgs;

	function setUp() {
		$this->_mockInvocation = Phockito::mock('Phockito_Invocation');
		$this->_mockInvocation->args = array('actualArg');

		$this->_mockContext = Phockito::mock('Phockito_VerificationContext');
		Phockito::when($this->_mockContext->getMethodToVerify())->return('Foo');
		$this->_wantedArgs = array('wantedArg');
		Phockito::when($this->_mockContext->getArgumentsToVerify())->return($this->_wantedArgs);
	}

	function testTimesFailureMessageIncludesWantedAndActualCallsDetails() {
		$this->_setMatchingInvocationsTo(1);

		$times = new Phockito_Times(3);
		$result = $times->verify($this->_mockContext);
		$failureMessage = $result->describeConstraintFailure();

		$this->_assertMessageHasStandardDetailsAndExpectation($failureMessage, 'was called 3 times');
	}

	function testAtLeastFailureMessageIncludesWantedAndActualCallsDetails() {
		$this->_setMatchingInvocationsTo(1);

		$atLeast = new Phockito_AtLeast(3);
		$result = $atLeast->verify($this->_mockContext);
		$failureMessage = $result->describeConstraintFailure();

		$this->_assertMessageHasStandardDetailsAndExpectation($failureMessage, 'was called at least 3 times');
	}

	function testAtMostFailureMessageIncludesWantedAndActualCallsDetails() {
		$this->_setMatchingInvocationsTo(3);

		$atMost = new Phockito_AtMost(2);
		$result = $atMost->verify($this->_mockContext);
		$failureMessage = $result->describeConstraintFailure();

		$this->_assertMessageHasStandardDetailsAndExpectation($failureMessage, 'was called at most 2 times');
	}

	private function _setMatchingInvocationsTo($count) {
		$matchingInvocations = array();
		for ($i=0; $i < $count; $i++) {
			$matchingInvocations[] = $this->_mockInvocation;
		}
		Phockito::when($this->_mockContext->getMatchingInvocations())->return($matchingInvocations);
	}

	private function _assertMessageHasStandardDetailsAndExpectation($failureMessage, $expectation) {
		$this->assertThat($failureMessage, $this->stringContains('method Foo'));
		$this->assertThat($failureMessage, $this->stringContains($expectation));
		$this->assertThat($failureMessage, $this->stringContains('actually called ' . count($this->_mockContext->getMatchingInvocations()) . ' times'));
		$this->assertThat($failureMessage, $this->stringContains("Wanted call:\n" . print_r($this->_wantedArgs, true)));
		$this->assertThat($failureMessage, $this->stringContains("Calls:\n" . print_r($this->_mockInvocation->args, true)));
	}
}