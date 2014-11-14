<?php

namespace Phockito;


use Phockito\VerificationMode\VerificationMode;


/**
 * A builder than is returned by Phockito::verify to capture the method that specifies the verified method
 * Throws an exception if the verified method hasn't been called "$times" times, either a PHPUnit exception
 * or just an Exception if PHPUnit doesn't exist
 */
class VerifyBuilder
{
    protected $instance;
    protected $mode;

    function __construct($instance, $mode)
    {
        $this->instance = $instance;
        $this->mode = $mode;
    }

    function __call($called, $args)
    {
        if ($this->mode instanceof VerificationMode) {
            $verificationMode = $this->mode;
        } else {
            if (preg_match('/([0-9]+)\+/', $this->mode, $match)) {
                $verificationMode = Phockito::atLeast((int)$match[1]);
            } else {
                $verificationMode = Phockito::times($this->mode);
            }
        }

        $verificationContext = new VerificationContext($this->instance, $called, $args);

        $verificationResult = $verificationMode->verify($verificationContext);

        if ($verificationResult instanceof SuccessfulVerificationResult) {
            $verificationContext->markMatchingInvocationsAsVerified();
            return;
        }

        (new UnsuccessfulVerificationReporter())->reportUnsuccessfulVerification($verificationResult);
    }
}