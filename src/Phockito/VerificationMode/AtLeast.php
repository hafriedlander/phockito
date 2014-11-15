<?php

namespace Phockito\VerificationMode;


class AtLeast extends NumericalVerificationMode
{
    protected function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations)
    {
        return $numberOfInvocations >= $this->_wantedNumberOfCalls;
    }

    protected function _describeExpectation()
    {
        return "called at least {$this->_wantedNumberOfCalls} times";
    }
}