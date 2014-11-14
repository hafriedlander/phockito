<?php

namespace Phockito;


class UnsuccessfulVerificationReporter
{
    static $exception_class = null;

    function __construct()
    {
        if (self::$exception_class === null) {
            self::$exception_class = class_exists('PHPUnit_Framework_AssertionFailedError') ?
                "PHPUnit_Framework_AssertionFailedError" :
                "Exception";
        }
    }

    function reportUnsuccessfulVerification(UnsuccessfulVerificationResult $verificationResult)
    {
        $message = $verificationResult->describeConstraintFailure();
        $exceptionClass = self::$exception_class;
        throw new $exceptionClass($message);
    }
}