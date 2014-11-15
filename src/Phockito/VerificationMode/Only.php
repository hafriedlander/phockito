<?php

namespace Phockito\VerificationMode;


use Phockito\SuccessfulVerificationResult;
use Phockito\UnsuccessfulNoMoreInteractionsVerificationResult;
use Phockito\UnsuccessfulNumericalVerificationResult;
use Phockito\VerificationContext;

class Only implements VerificationMode
{
    function verify(VerificationContext $verificationContext)
    {

        $allInvocations = $verificationContext->getAllInvocationsOnMock();
        $matchingInvocations = $verificationContext->getMatchingInvocations();

        $allInvocationsCount = count($allInvocations);
        $matchingInvocationsCount = count($matchingInvocations);
        if ($allInvocationsCount != 1 && $matchingInvocationsCount > 0) {
            return new UnsuccessfulNoMoreInteractionsVerificationResult($matchingInvocations[0]);
        } elseif ($allInvocationsCount != 1 || $matchingInvocationsCount == 0) {
            $expectation = "called exactly once, and nothing else was";
            return new UnsuccessfulNumericalVerificationResult($verificationContext, $expectation);
        }

        return new SuccessfulVerificationResult();
    }
}