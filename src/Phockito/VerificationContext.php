<?php

namespace Phockito;


class VerificationContext
{
    private $_mockInstanceId;
    private $_methodToVerify;
    private $_argumentsToVerify;

    /**
     * @param string $mockInstanceId
     * @param string $methodToVerify
     * @param array $argumentsToVerify
     */
    function __construct($mockInstanceId, $methodToVerify, array $argumentsToVerify)
    {
        $this->_mockInstanceId = $mockInstanceId;
        $this->_methodToVerify = $methodToVerify;
        $this->_argumentsToVerify = $argumentsToVerify;
    }

    /**
     * @return Invocation[]
     */
    function getAllInvocationsOnMock()
    {
        return $invocationsForMock = array_filter(
            Phockito::$_invocation_list,
            function (Invocation $invocation) {
                return $invocation->matchesInstance($this->_mockInstanceId);
            }
        );
    }

    /**
     * @return Invocation[]
     */
    function getMatchingInvocations()
    {
        return $invocationsForMock = array_filter(
            Phockito::$_invocation_list,
            function (Invocation $invocation) {
                return $invocation->matchesInstanceAndMethod($this->_mockInstanceId, $this->_methodToVerify)
                && $invocation->matchesArguments($this->_argumentsToVerify);
            }
        );
    }

    function markMatchingInvocationsAsVerified()
    {
        foreach ($this->getMatchingInvocations() as $invocation) {
            $invocation->verified = true;
        }
    }

    /**
     * @return string
     */
    public function getMethodToVerify()
    {
        return $this->_methodToVerify;
    }

    /**
     * @return array
     */
    public function getArgumentsToVerify()
    {
        return $this->_argumentsToVerify;
    }
}