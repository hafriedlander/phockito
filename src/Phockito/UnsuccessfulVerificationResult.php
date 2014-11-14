<?php

namespace Phockito;


interface UnsuccessfulVerificationResult
{
    /**
     * @return string
     */
    function describeConstraintFailure();
}