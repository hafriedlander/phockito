<?php

namespace Phockito;


use Hamcrest\Matcher;
use Phockito\VerificationMode\AtLeast;
use Phockito\VerificationMode\AtMost;
use Phockito\VerificationMode\NoMoreInteractions;
use Phockito\VerificationMode\Only;
use Phockito\VerificationMode\Times;
use Phockito\VerificationMode\VerificationMode;
use Reflection;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;


/**
 * Phockito - Mockito for PHP
 *
 * Mocking framework based on Mockito for Java
 *
 * (C) 2011 Hamish Friedlander / SilverStripe. Distributable under the same license as SilverStripe.
 *
 * Example usage:
 *
 *   // Create the mock
 *   $iterator = Phockito.mock('ArrayIterator);
 *
 *   // Use the mock object - doesn't do anything, functions return null
 *   $iterator->append('Test');
 *   $iterator->asort();
 *
 *   // Selectively verify execution
 *   Phockito::verify($iterator)->append('Test');
 *   // 1 is default - can also do 2, 3  for exact numbers, or 1+ for at least one, or 0 for never
 *   Phockito::verify($iterator, 1)->asort();
 *
 * Example stubbing:
 *
 *   // Create the mock
 *   $iterator = Phockito.mock('ArrayIterator);
 *
 *   // Stub in a value
 *   Phockito::when($iterator->offsetGet(0))->return('first');
 *
 *   // Prints "first"
 *   print_r($iterator->offsetGet(0));
 *
 *   // Prints null, because get(999) not stubbed
 *   print_r($iterator->offsetGet(999));
 *
 *
 * Note that several functions are declared as public so that builder classes can access them. Anything
 * starting with an "_" is for internal consumption only
 */
class Phockito
{
    const MOCK_PREFIX = '__phockito_';

    /* ** Static Configuration *
        Feel free to change these at any time.
    */

    /** @var bool - If true, don't warn when doubling classes with final methods, just ignore the methods. If false, throw warnings when final methods encountered */
    public static $ignore_finals = true;

    /** @var string - Class name of a class with a static "register_double" method that will be called with any double to inject into some other type tracking system */
    public static $type_registrar = null;

    /* ** INTERNAL INTERFACES START **
        These are declared as public so that mocks and builders can access them,
        but they're for internal use only, not actually for consumption by the general public
    */

    /** Each mock instance needs a unique string ID, which we build by incrementing this counter @var int */
    public static $_instanceid_counter = 0;

    /** Array of most-recent-first calls. Each item is an array of (instance, method, args) named hashes. @var Invocation[] */
    public static $_invocation_list = array();

    /**
     * Array of stubs responses
     * Nested as [instance][method][0..n], each item is an array of ('args' => the method args, 'responses' => stubbed responses)
     * @var array
     */
    public static $_responses = array();

    /**
     * Array of defaults for a given class and method
     * @var array
     */
    public static $_defaults = array();

    /**
     * Records whether a given class is an interface, to avoid repeatedly generating reflection objects just to re-call type registrar
     * @var array
     */
    public static $_is_interface = array();

    /*
     * Should we attempt to support namespaces? Is PHP >= 5.3, basically
     */
    public static function _has_namespaces()
    {
        return version_compare(PHP_VERSION, '5.3.0', '>=');
    }

    /**
     * Checks if the two argument sets (passed as arrays) match. Simple serialized check for now, to be replaced by
     * something that can handle anyString etc matchers later
     *
     * @param $mockclass
     * @param $method
     * @param $a
     * @param $b
     * @return bool
     */
    public static function _arguments_match($mockclass, $method, $a, $b)
    {
        // See if there are any defaults for the given method
        if (isset(self::$_defaults[$mockclass][$method])) {
            // If so, get them
            $defaults = self::$_defaults[$mockclass][$method];
            // And merge them with the passed args
            $a = $a + $defaults;
            $b = $b + $defaults;
        }

        // If two argument arrays are different lengths, automatic fail
        if (count($a) != count($b)) {
            return false;
        }

        // Step through each item
        $i = count($a);
        while ($i--) {
            $u = $a[$i];
            $v = $b[$i];

            // If the argument in $a is a hamcrest matcher, call match on it. WONTFIX: Can't check if function was passed a hamcrest matcher
            if (interface_exists(Matcher::class) && ($u instanceof Matcher || isset($u->__phockito_matcher))
            ) {
                // The matcher can either be passed directly, or wrapped in a mock (for type safety reasons)
                $matcher = null;
                if ($u instanceof Matcher) {
                    $matcher = $u;
                } elseif (isset($u->__phockito_matcher)) {
                    $matcher = $u->__phockito_matcher;
                }
                if ($matcher != null && !$matcher->matches($v)) {
                    return false;
                }
            } // Otherwise check for equality by checking the equality of the serialized version
            else {
                if (serialize($u) != serialize($v)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Called by the mock instances when a method is called. Records the call and returns a response if one has been
     * stubbed in
     *
     * @param $class
     * @param $instance
     * @param $method
     * @param $args
     * @return mixed|null
     */
    public static function __called($class, $instance, $method, $args)
    {
        // Record the call as most recent first
        $invocation = new Invocation($class, $instance, $method, $args, debug_backtrace(0));
        array_unshift(self::$_invocation_list, $invocation);

        // Look up any stubbed responses
        if (isset(self::$_responses[$instance][$method])) {
            // Find the first one that matches the called-with arguments
            foreach (self::$_responses[$instance][$method] as &$matcher) {
                if (self::_arguments_match($class, $method, $matcher['args'], $args)) {
                    // Consume the next response - except the last one, which repeats indefinitely
                    if (count($matcher['steps']) > 1) {
                        return array_shift($matcher['steps']);
                    } else {
                        return reset($matcher['steps']);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @noinspection PhpInconsistentReturnPointsInspection
     * @param $response
     * @param $args
     * @return mixed|null
     * @throws \Exception
     */
    public static function __perform_response($response, $args)
    {
        if ($response['action'] == 'return') {
            return $response['value'];
        } else {
            if ($response['action'] == 'throw') {
                /** @var \Exception $class */
                $class = $response['value'];
                throw (is_object($class) ? $class : new $class());
            } else {
                if ($response['action'] == 'callback') {
                    return call_user_func_array($response['value'], $args);
                } else {
                    user_error("Got unknown action {$response['action']} - how did that happen?", E_USER_ERROR);
                }
            }
        }

        return null;
    }

    /* ** INTERNAL INTERFACES END ** */

    /**
     * Passed a class as a string to create the mock as, and the class as a string to mock,
     * create the mocking class php and eval it into the current running environment
     *
     * @static
     * @param bool $partial - Should test double be a partial or a full mock
     * @param string $mockedClass - The name of the class (or interface) to create a mock of
     * @return string The name of the mocker class
     */
    protected static function build_test_double($partial, $mockedClass)
    {
        // Bail if we were passed a classname that doesn't exist
        if (!class_exists($mockedClass) && !interface_exists($mockedClass)) {
            user_error(
                "Can't mock non-existent class $mockedClass",
                E_USER_ERROR
            );
        }

        // Reflect on the mocked class
        $reflect = new ReflectionClass($mockedClass);

        if ($reflect->isFinal()) {
            user_error("Can't mock final class $mockedClass", E_USER_ERROR);
        }

        // Build up an array of php fragments that make the mocking class definition
        $php = array();

        // Get the namespace & the shortname of the mocked class
        if (self::_has_namespaces()) {
            $mockedNamespace = $reflect->getNamespaceName();
            $mockedShortName = $reflect->getShortName();
        } else {
            $mockedNamespace = '';
            $mockedShortName = $mockedClass;
        }

        // Build the short name of the mocker class based on the mocked classes shortname
        $mockerShortName = self::MOCK_PREFIX . $mockedShortName . ($partial ? '_Spy' : '_Mock');
        // And build the full class name of the mocker by prepending the namespace if appropriate
        $mockerClass = (self::_has_namespaces() ? $mockedNamespace . '\\' : '') . $mockerShortName;

        // If we've already built this test double, just return it
        if (class_exists($mockerClass, false)) {
            return $mockerClass;
        }

        // If the mocked class is in a namespace, the test double goes in the same namespace
        $namespaceDeclaration = $mockedNamespace ? "namespace $mockedNamespace;" : '';

        // The only difference between mocking a class or an interface is how the mocking class extends from the mocked
        $extends = $reflect->isInterface() ? 'implements' : 'extends';
        $marker = $reflect->isInterface() ? ', \Phockito\MockMarker' : 'implements \Phockito\MockMarker';

        // When injecting the class as a string, need to escape the "\" character.
        $mockedClassString = "'\\\\" . str_replace('\\', '\\\\', $mockedClass) . "'";
        $mockedClassClass = '\\' . $mockedClass . '::class';

        // Add opening class stanza
        $php[] = <<<EOT
$namespaceDeclaration
class $mockerShortName $extends $mockedShortName $marker {
  public \$__phockito_instanceid;
  public \$__phockito_class = $mockedClassClass;

  function __construct() {
    \$this->__phockito_instanceid = $mockedClassString.':'.(++\\Phockito\\Phockito::\$_instanceid_counter);
  }
EOT;

        // And record the defaults at the same time
        self::$_defaults[$mockedClass] = array();
        // And whether it's an interface
        self::$_is_interface[$mockedClass] = $reflect->isInterface();

        // Track if the mocked class defines either of the __call and/or __toString magic methods
        $has__call = $has__toString = false;

        // Step through every method declared on the object
        foreach ($reflect->getMethods() as $method) {
            // Skip private methods. They shouldn't ever be called anyway
            if ($method->isPrivate()) {
                continue;
            }

            // Either skip or throw error on final methods.
            if ($method->isFinal()) {
                if (self::$ignore_finals) {
                    continue;
                } else {
                    user_error(
                        "Class $mockedClass has final method {$method->name}, which we can\'t mock",
                        E_USER_WARNING
                    );
                }
            }

            // Get the modifiers for the function as a string (static, public, etc) - ignore abstract though, all mock methods are concrete
            $modifiers = implode(
                ' ',
                Reflection::getModifierNames($method->getModifiers() & ~(ReflectionMethod::IS_ABSTRACT))
            );

            // See if the method is return byRef
            $byRef = $method->returnsReference() ? "&" : "";

            // PHP fragment that is the arguments definition for this method
            $defparams = array();
            $callparams = array();

            // Array of defaults (sparse numeric)
            self::$_defaults[$mockedClass][$method->name] = array();

            foreach ($method->getParameters() as $i => $parameter) {
                // Turn the method arguments into a php fragment that calls a function with them
                $callparams[] = '$' . $parameter->getName();

                // Get the type hint of the parameter
                if ($parameter->isArray()) {
                    $type = 'array ';
                } else {
                    if ($parameterClass = $parameter->getClass()) {
                        $type = '\\' . $parameterClass->getName() . ' ';
                    } else {
                        $type = '';
                    }
                }

                try {
                    $defaultValue = $parameter->getDefaultValue();
                } catch (ReflectionException $e) {
                    $defaultValue = null;
                }

                // Turn the method arguments into a php fragment the defines a function with them, including possibly the by-reference "&" and any default
                $defparams[] =
                    $type .
                    ($parameter->isPassedByReference() ? '&' : '') .
                    '$' . $parameter->getName() .
                    ($parameter->isOptional() ? '=' . var_export($defaultValue, true) : '');

                // Finally cache the default value for matching against later
                if ($parameter->isOptional()) {
                    self::$_defaults[$mockedClass][$method->name][$i] = $defaultValue;
                }
            }

            // Turn that array into a comma seperated list
            $defparams = implode(', ', $defparams);
            $callparams = implode(', ', $callparams);

            // What to do if there's no stubbed response
            if ($partial && !$method->isAbstract()) {
                $failover = "call_user_func_array(array($mockedClassString, '{$method->name}'), \$args)";
            } else {
                $failover = "null";
            }

            // Constructor is handled specially. For spies, we do call the parent's constructor. For mocks we ignore
            if ($method->name == '__construct') {
                if ($partial) {
                    $php[] = <<<EOT
  function __phockito_parent_construct( $defparams ){
    parent::__construct( $callparams );
  }
EOT;
                }
            } elseif ($method->name == '__call') {
                $has__call = true;
            } elseif ($method->name == '__toString') {
                $has__toString = true;
            } // Build an overriding method that calls Phockito::__called, and never calls the parent
            else {
                $php[] = <<<EOT
  $modifiers function $byRef {$method->name}( $defparams ){
    \$args = func_get_args();

    \$backtrace = debug_backtrace();

    \$instance = \$backtrace[0]['type'] == '::' ? ('::'.$mockedClassClass) : \$this->__phockito_instanceid;

    \$response = \\Phockito\\Phockito::__called($mockedClassClass, \$instance, '{$method->name}', \$args);

    \$result = \$response ? \\Phockito\\Phockito::__perform_response(\$response, \$args) : ($failover);

    return \$result;
  }
EOT;
            }
        }

        // Always add a __call method to catch any calls to undefined functions
        $failover = ($partial && $has__call) ? "parent::__call(\$name, \$args)" : "null";

        $php[] = <<<EOT
  function __call(\$name, \$args) {
    \$response = \\Phockito\\Phockito::__called($mockedClassString, \$this->__phockito_instanceid, \$name, \$args);

    if (\$response) return \\Phockito\\Phockito::__perform_response(\$response, \$args);
    else return $failover;
  }
EOT;

        // Always add a __toString method
        if ($partial) {
            if ($has__toString) {
                $failover = "parent::__toString()";
            } else {
                $failover = "user_error('Object of class '.$mockedClassString.' could not be converted to string', E_USER_ERROR)";
            }
        } else {
            $failover = "''";
        }

        $php[] = <<<EOT
  function __toString() {
    \$args = array();
    \$response = \\Phockito\\Phockito::__called($mockedClassString, \$this->__phockito_instanceid, "__toString", \$args);

    if (\$response) return \\Phockito\\Phockito::__perform_response(\$response, \$args);
    else return $failover;
  }
EOT;

        // Close off the class definition and eval it to create the class as an extant entity.
        $php[] = '}';

        // Debug: uncomment to spit out the code we're about to compile to stdout
        // echo "\n" . implode("\n\n", $php) . "\n";
        eval(implode("\n\n", $php));
        return $mockerClass;
    }

    /**
     * Given a class name as a string, return a new class name as a string which acts as a mock
     * of the passed class name. Probably not useful by itself until we start supporting static method stubbing
     *
     * @static
     * @param string $class - The class to mock
     * @return string - The class that acts as a Phockito mock of the passed class
     */
    static function mock_class($class)
    {
        $mockClass = self::build_test_double(false, $class);

        // If we've been given a type registrar, call it (we need to do this even if class exists, since PHPUnit resets globals, possibly de-registering between tests)
        $type_registrar = self::$type_registrar;
        if ($type_registrar) /** @noinspection PhpUndefinedMethodInspection */ {
            $type_registrar::register_double($mockClass, $class, self::$_is_interface[$class]);
        }

        return $mockClass;
    }

    /**
     * Given a class name as a string, return a new instance which acts as a mock of that class
     *
     * @static
     * @param string $class - The class to mock
     * @return Object - A mock of that class
     */
    static function mock_instance($class)
    {
        $mockClass = self::mock_class($class);
        return new $mockClass();
    }

    /**
     * Alternative name for mock_instance
     *
     * @param $class
     * @return Object
     */
    static function mock($class)
    {
        return self::mock_instance($class);
    }

    static function spy_class($class)
    {
        $spyClass = self::build_test_double(true, $class);

        // If we've been given a type registrar, call it (we need to do this even if class exists, since PHPUnit resets globals, possibly de-registering between tests)
        $type_registrar = self::$type_registrar;
        if ($type_registrar) /** @noinspection PhpUndefinedMethodInspection */ {
            $type_registrar::register_double($spyClass, $class, self::$_is_interface[$class]);
        }

        return $spyClass;
    }

    const DONT_CALL_CONSTRUCTOR = '__phockito_dont_call_constructor';

    static function spy_instance($class /*, $constructor_arg_1, ... */)
    {
        $spyClass = self::spy_class($class);

        $res = new $spyClass();

        // Find the constructor args
        $constructor_args = func_get_args();
        array_shift($constructor_args);

        // Call the constructor (maybe)
        if (count($constructor_args) != 1 || $constructor_args[0] !== self::DONT_CALL_CONSTRUCTOR) {
            $constructor = array($res, '__phockito_parent_construct');
            if (!is_callable($constructor)) {
                if ($constructor_args) {
                    user_error(
                        "Tried to create spy of $class with constructor args, but that $class doesn't have a constructor defined",
                        E_USER_ERROR
                    );
                }
            } else {
                call_user_func_array($constructor, $constructor_args);
            }
        }

        // And done
        return $res;
    }

    static function spy()
    {
        $args = func_get_args();
        return call_user_func_array(array(__CLASS__, 'spy_instance'), $args);
    }

    /**
     * When builder. Starts stubbing the method called to build the argument passed to when
     *
     * @static
     * @param MockMarker|object|mixed|null $arg
     * @return WhenBuilder|Object
     */
    static function when($arg = null)
    {
        if ($arg instanceof MockMarker) {
            return new WhenBuilder($arg->__phockito_instanceid, $arg->__phockito_class);
        } else {

            /** @var Invocation $invocation */
            $invocation = array_shift(self::$_invocation_list);
            return new WhenBuilder($invocation->instanceId, $invocation->className, $invocation->methodName, $invocation->args);
        }
    }

    /**
     * Verify builder. Takes a mock instance and an optional number of times to verify against. Returns a
     * DSL object that catches the method to verify
     *
     * @static
     * @param MockMarker|object $mock - The mock instance to verify
     * @param string|int $times - The number of times the method should be called, either a number, or a number followed by "+"
     * @return mixed|VerifyBuilder
     */
    static function verify($mock, $times = 1)
    {
        return new VerifyBuilder($mock->__phockito_instanceid, $times);
    }

    /**
     * @param int $times
     * @return VerificationMode
     */
    static function times($times)
    {
        return new Times($times);
    }

    /**
     * @return VerificationMode
     */
    static function never()
    {
        return self::times(0);
    }

    /**
     * @param int $times
     * @return VerificationMode
     */
    static function atLeast($times)
    {
        return new AtLeast($times);
    }

    /**
     * @return VerificationMode
     */
    static function atLeastOnce()
    {
        return self::atLeast(1);
    }

    /**
     * @param int $times
     * @return VerificationMode
     */
    static function atMost($times)
    {
        return new AtMost($times);
    }

    static function only()
    {
        return new Only();
    }

    /**
     * Reset a mock instance. Forget all calls and stubbed responses for a given instance
     * @static
     * @param MockMarker|Object $mock - The mock instance to reset
     * @param string $method
     */
    static function reset($mock, $method = null)
    {
        // Get the instance ID. Only resets instance-specific info ATM
        $instance = $mock->__phockito_instanceid;

        // Remove any stored returns
        if ($method) {
            unset(self::$_responses[$instance][$method]);
        } else {
            unset(self::$_responses[$instance]);
        }

        // Remove all call history
        /** @var Invocation $invocation */
        foreach (self::$_invocation_list as $i => $invocation) {
            if (($method && $invocation->matchesInstanceAndMethod($instance, $method)) ||
                ($method == null && $invocation->matchesInstance($instance))
            ) {
                array_splice(self::$_invocation_list, $i, 1);
            }
        }
    }

    /**
     * @param MockMarker|Object|array $mocks
     */
    static function verifyNoMoreInteractions($mocks)
    {
        if (!is_array($mocks)) {
            $mocks = array($mocks);
        }

        $noMoreInteractionsVerificationMode = new NoMoreInteractions();

        /** @var MockMarker $mock */
        foreach ($mocks as $mock) {
            $verificationContext = new VerificationContext($mock->__phockito_instanceid, null, array());
            $verificationResult = $noMoreInteractionsVerificationMode->verify($verificationContext);
            if ($verificationResult instanceof UnsuccessfulVerificationResult) {
                (new UnsuccessfulVerificationReporter())->reportUnsuccessfulVerification($verificationResult);
            }
        }
    }
}





