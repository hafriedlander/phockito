<?php

namespace Phockito\VerificationMode;


class AtMost extends NumericalVerificationMode
{
    protected function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations)
    {
        return $numberOfInvocations <= $this->_wantedNumberOfCalls;
    }

    protected function _describeExpectation()
    {
        return "called at most {$this->_wantedNumberOfCalls} times";
    }
}