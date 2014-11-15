<?php

namespace Phockito\VerificationMode;


use Phockito\SuccessfulVerificationResult;
use Phockito\UnsuccessfulNoMoreInteractionsVerificationResult;
use Phockito\VerificationContext;

class NoMoreInteractions implements VerificationMode
{
    function verify(VerificationContext $verificationContext)
    {
        foreach ($verificationContext->getAllInvocationsOnMock() as $invocation) {
            if (!$invocation->verified) {
                return new UnsuccessfulNoMoreInteractionsVerificationResult($invocation);
            }
        }
        return new SuccessfulVerificationResult();
    }
}





