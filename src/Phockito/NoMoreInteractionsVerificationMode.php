<?php

namespace Phockito;


use Phockito\VerificationMode\VerificationMode;

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





