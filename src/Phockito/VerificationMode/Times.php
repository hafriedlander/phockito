<?php

namespace Phockito\VerificationMode;


class Times extends NumericalVerificationMode
{
    protected function _numberOfInvocationsSatisfiesConstraint($numberOfInvocations)
    {
        return $this->_wantedNumberOfCalls == $numberOfInvocations;
    }

    protected function _describeExpectation()
    {
        return "called {$this->_wantedNumberOfCalls} times";
    }
}