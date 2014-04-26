<?php
require_once('Phockito_VerificationMode.php');

abstract class Phockito_NumericalVerificationMode implements Phockito_VerificationMode {
	protected $_wantedNumberOfCalls;

	function __construct($_wantedNumberOfCalls) {
		$this->_wantedNumberOfCalls = $_wantedNumberOfCalls;
	}

	function verify(Phockito_VerificationContext $verificationContext) {
		$success = $this->_numberOfInvocationsSatisfiesConstraint(count($verificationContext->getMatchingInvocations()));

		if ($success) {
			return new Phockito_SuccessfulVerificationResult();
		} else {
			return new Phockito_UnsuccessfulNumericalVerificationResult($verificationContext, $this->_describeExpectation());
		}
	}

	protected abstract function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations);

	protected abstract function _describeExpectation();
}

class Phockito_Times extends Phockito_NumericalVerificationMode {
	protected function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations) {
		return $this->_wantedNumberOfCalls == $numberOfInvocations;
	}

	protected function _describeExpectation() {
		return "called {$this->_wantedNumberOfCalls} times";
	}
}

class Phockito_AtLeast extends Phockito_NumericalVerificationMode {
	protected function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations) {
		return $numberOfInvocations >= $this->_wantedNumberOfCalls;
	}

	protected function _describeExpectation() {
		return "called at least {$this->_wantedNumberOfCalls} times";
	}
}

class Phockito_AtMost extends Phockito_NumericalVerificationMode {
	protected function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations) {
		return $numberOfInvocations <= $this->_wantedNumberOfCalls;
	}

	protected function _describeExpectation() {
		return "called at most {$this->_wantedNumberOfCalls} times";
	}
}


class Phockito_UnsuccessfulNumericalVerificationResult implements Phockito_UnsuccessfulVerificationResult {
	private $_verificationContext;
	private $_expectation;

	/**
	 * @param Phockito_VerificationContext $verificationContext
	 * @param string $expectation
	 */
	function __construct(Phockito_VerificationContext $verificationContext, $expectation) {
		$this->_verificationContext = $verificationContext;
		$this->_expectation = $expectation;
	}

	/**
	 * @return string
	 */
	function describeConstraintFailure() {
		$method = $this->_verificationContext->getMethodToVerify();
		$invocations = $this->_verificationContext->getMatchingInvocations();
		$invocationsCount = count($invocations);

		$message  = "Failed asserting that method $method was {$this->_expectation}";
		$message .= " - actually called $invocationsCount times.\n";
		$message .= "Wanted call:\n";
		$message .= print_r($this->_verificationContext->getArgumentsToVerify(), true);

		$message .= "Calls:\n";

		foreach ($invocations as $invocation) {
			$message .= print_r($invocation->args, true);
		}

		return $message;
	}
}