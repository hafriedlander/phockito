<?php
error_reporting( E_ALL|E_STRICT );

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

spl_autoload_register(function ($class) {
	if (0 === strncmp($class, Phockito::MOCK_PREFIX, strlen(Phockito::MOCK_PREFIX))) {
		throw new RuntimeException('Autoload attempted on a phockito mock class');
	}
}, true);


class PhockitoNoMoreInteractionsVerificationFailureMessageTest extends PHPUnit_Framework_TestCase {
	/** @var Phockito_Invocation */
	private $_mockInvocation;
	/** @var Phockito_VerificationContext */
	private $_mockContext;
	/** @var array */
	private $_wantedArgs;

	function setUp() {
		$this->_mockInvocation = Phockito::mock('Phockito_Invocation');
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

		$this->_mockContext = Phockito::mock('Phockito_VerificationContext');
		Phockito::when($this->_mockContext->getMethodToVerify())->return('Foo');
		$this->_wantedArgs = array('wantedArg');
		Phockito::when($this->_mockContext->getArgumentsToVerify())->return($this->_wantedArgs);
	}

	function testNoMoreInteractionsFailureMessageIncludesHelpfulErrorMessageWithStackTrace() {
		$this->_setAllInvocationsTo(2);
		$this->_setMatchingInvocationsTo(1);

		$noMoreInteractions = new Phockito_NoMoreInteractions();
		$result = $noMoreInteractions->verify($this->_mockContext);
		$failureMessage = $result->describeConstraintFailure();

		$this->assertThat($failureMessage, $this->stringContains("No more interactions wanted"));
		$this->assertThat($failureMessage, $this->stringContains("found this interaction"));
		$this->assertThat($failureMessage, $this->stringContains('#0 someFile.php(123): SomeClass->someMethod()'));
	}

	private function _setAllInvocationsTo($count) {
		$allInvocations = array();
		for ($i=0; $i < $count; $i++) {
			$allInvocations[] = $this->_mockInvocation;
		}
		Phockito::when($this->_mockContext->getAllInvocationsOnMock())->return($allInvocations);
	}

	private function _setMatchingInvocationsTo($count) {
		$matchingInvocations = array();
		for ($i=0; $i < $count; $i++) {
			$matchingInvocations[] = $this->_mockInvocation;
		}
		Phockito::when($this->_mockContext->getMatchingInvocations())->return($matchingInvocations);
	}
}