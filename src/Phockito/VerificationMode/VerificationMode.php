<?php

namespace Phockito\VerificationMode;


use Phockito\SuccessfulVerificationResult;
use Phockito\UnsuccessfulVerificationResult;
use Phockito\VerificationContext;

interface VerificationMode
{
    /**
     * @param VerificationContext $verificationContext
     * @return SuccessfulVerificationResult|UnsuccessfulVerificationResult
     */
    function verify(VerificationContext $verificationContext);
}
