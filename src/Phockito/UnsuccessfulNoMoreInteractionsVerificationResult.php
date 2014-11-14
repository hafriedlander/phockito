<?php

namespace Phockito;


class UnsuccessfulNoMoreInteractionsVerificationResult implements UnsuccessfulVerificationResult
{
    /** @var Invocation */
    private $_invocation;

    function __construct(Invocation $_invocation)
    {
        $this->_invocation = $_invocation;
    }

    /**
     * @return string
     */
    function describeConstraintFailure()
    {
        $backtraceFormatter = new BacktraceFormatter();
        return "No more interactions wanted, but found this interaction:\n" .
        $backtraceFormatter->formatBacktrace($this->_invocation->backtrace);
    }
}