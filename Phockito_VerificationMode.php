<?php

interface Phockito_VerificationMode {
	/**
	 * @param Phockito_VerificationContext $verificationContext
	 * @return Phockito_SuccessfulVerificationResult|Phockito_UnsuccessfulVerificationResult
	 */
	function verify(Phockito_VerificationContext $verificationContext);
}

class Phockito_SuccessfulVerificationResult {
}

interface Phockito_UnsuccessfulVerificationResult {
	/**
	 * @return string
	 */
	function describeConstraintFailure();
}