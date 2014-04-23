<?php

interface VerificationMode {
	/**
	 * @param int $numberOfCallsMade
	 * @return bool
	 */
	function verify($numberOfCallsMade);

	/**
	 * @return string
	 */
	function describeCondition();
}

class Times implements VerificationMode {
	private $_wantedNumberOfCalls;

	function __construct($_wantedNumberOfCalls) {
		$this->_wantedNumberOfCalls = $_wantedNumberOfCalls;
	}

	function verify($numberOfCallsMade) {
		return $this->_wantedNumberOfCalls == $numberOfCallsMade;
	}

	function describeCondition() {
		return "was called {$this->_wantedNumberOfCalls} times";
	}
}

class AtLeast implements VerificationMode {
	private $_wantedMinimumNumberOfCalls;

	function __construct($_wantedMinimumNumberOfCalls) {
		$this->_wantedMinimumNumberOfCalls = $_wantedMinimumNumberOfCalls;
	}

	function verify($numberOfCallsMade) {
		return $numberOfCallsMade >= $this->_wantedMinimumNumberOfCalls;
	}

	function describeCondition() {
		return "was called at least {$this->_wantedMinimumNumberOfCalls} times";
	}
}

class AtMost implements VerificationMode {
	private $_wantedMaximumNumberOfCalls;

	function __construct($_wantedMaximumNumberOfCalls) {
		$this->_wantedMaximumNumberOfCalls = $_wantedMaximumNumberOfCalls;
	}

	function verify($numberOfCallsMade) {
		return $numberOfCallsMade <= $this->_wantedMaximumNumberOfCalls;
	}

	function describeCondition() {
		return "was called at most {$this->_wantedMaximumNumberOfCalls} times";
	}
}