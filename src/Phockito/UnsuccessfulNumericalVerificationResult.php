<?php

namespace Phockito;


class UnsuccessfulNumericalVerificationResult implements UnsuccessfulVerificationResult
{
    private $_verificationContext;
    private $_expectation;

    /**
     * @param VerificationContext $verificationContext
     * @param string $expectation
     */
    function __construct(VerificationContext $verificationContext, $expectation)
    {
        $this->_verificationContext = $verificationContext;
        $this->_expectation = $expectation;
    }

    /**
     * @return string
     */
    function describeConstraintFailure()
    {
        $method = $this->_verificationContext->getMethodToVerify();
        $invocations = $this->_verificationContext->getMatchingInvocations();
        $invocationsCount = count($invocations);

        $message = "Failed asserting that method $method was {$this->_expectation}";
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