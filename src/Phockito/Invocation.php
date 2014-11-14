<?php

namespace Phockito;


class Invocation
{
    public $className;
    public $instanceId;
    public $methodName;
    public $args;
    public $backtrace;

    public $verified = false;

    function __construct($className, $instanceId, $methodName, $args, array $backtrace)
    {
        $this->className = $className;
        $this->instanceId = $instanceId;
        $this->methodName = $methodName;
        $this->args = $args;
        $this->backtrace = $backtrace;
    }

    public function matchesInstance($instanceId)
    {
        return $this->instanceId == $instanceId;
    }

    public function matchesInstanceAndMethod($instanceId, $methodName)
    {
        return $this->matchesInstance($instanceId) && $this->methodName == $methodName;
    }

    /**
     * Checks if the given arguments list matches that of the invocation. Simple serialized check for now, to be
     * replaced by something that can handle anyString etc matchers later
     *
     * @param mixed $args
     * @return bool
     */
    public function matchesArguments($args)
    {
        $invocationArgs = $this->args;
        $passedArgs = $args;

        // See if there are any defaults for the given method
        if (isset(Phockito::$_defaults[$this->className][$this->methodName])) {
            // If so, get them
            $defaults = Phockito::$_defaults[$this->className][$this->methodName];
            // And merge them with the passed args
            $invocationArgs = $invocationArgs + $defaults;
            $passedArgs = $passedArgs + $defaults;
        }

        return $this->_argumentListsMatch($invocationArgs, $passedArgs);
    }

    private function _argumentListsMatch($invocationArgs, $passedArgs)
    {
        // If two argument arrays are different lengths, automatic fail
        if (count($invocationArgs) != count($passedArgs)) {
            return false;
        }

        // Step through each item
        $argIndex = count($invocationArgs);
        while ($argIndex--) {
            $invocationArg = $invocationArgs[$argIndex];
            $passedArg = $passedArgs[$argIndex];

            if (!$this->_argumentsMatch($invocationArg, $passedArg)) {
                return false;
            }
        }

        return true;
    }

    private function _argumentsMatch($invocationArg, $passedArg)
    {
        // If the argument in $invocationArg is a hamcrest matcher, call match on it.
        // WONTFIX: Can't check if function was passed a hamcrest matcher
        if (interface_exists('Hamcrest_Matcher') &&
            ($invocationArg instanceof Hamcrest_Matcher || isset($invocationArg->__phockito_matcher))
        ) {
            // The matcher can either be passed directly, or wrapped in a mock (for type safety reasons)
            $matcher = null;
            if ($invocationArg instanceof Hamcrest_Matcher) {
                $matcher = $invocationArg;
            } elseif (isset($invocationArg->__phockito_matcher)) {
                $matcher = $invocationArg->__phockito_matcher;
            }
            return $matcher != null && !$matcher->matches($passedArg);
        } // Otherwise check for equality by checking the equality of the serialized version
        else {
            return serialize($invocationArg) != serialize($passedArg);
        }
    }
}