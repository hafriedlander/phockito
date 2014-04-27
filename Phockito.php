<?php

require_once('Phockito_NumericalVerificationModes.php');
require_once('Phockito_NoMoreInteractionsVerificationMode.php');

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
class Phockito {
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

	/** Array of most-recent-first calls. Each item is an array of (instance, method, args) named hashes. @var Phockito_Invocation[] */
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
	public static function _has_namespaces() {
		return version_compare(PHP_VERSION, '5.3.0', '>=');
	}

	/**
	 * Checks if the two argument sets (passed as arrays) match. Simple serialized check for now, to be replaced by
	 * something that can handle anyString etc matchers later
	 */
	public static function _arguments_match($mockclass, $method, $a, $b) {
		// See if there are any defaults for the given method
		if (isset(self::$_defaults[$mockclass][$method])) {
			// If so, get them
			$defaults = self::$_defaults[$mockclass][$method];
			// And merge them with the passed args
			$a = $a + $defaults; $b = $b + $defaults;
		}

		// If two argument arrays are different lengths, automatic fail
		if (count($a) != count($b)) return false;

		// Step through each item
		$i = count($a);
		while($i--) {
			$u = $a[$i]; $v = $b[$i];

			// If the argument in $a is a hamcrest matcher, call match on it. WONTFIX: Can't check if function was passed a hamcrest matcher
			if (interface_exists('Hamcrest_Matcher') && ($u instanceof Hamcrest_Matcher || isset($u->__phockito_matcher))) {
				// The matcher can either be passed directly, or wrapped in a mock (for type safety reasons)
				$matcher = null;
				if ($u instanceof Hamcrest_Matcher) {
					$matcher = $u;
				} elseif (isset($u->__phockito_matcher)) {
					$matcher = $u->__phockito_matcher;
				}
				if ($matcher != null && !$matcher->matches($v)) return false;
			}
			// Otherwise check for equality by checking the equality of the serialized version
			else {
				if (serialize($u) != serialize($v)) return false;
			}
		}
		
		return true;
	}

	/**
	 * Called by the mock instances when a method is called. Records the call and returns a response if one has been
	 * stubbed in
	 */
	public static function __called($class, $instance, $method, $args) {
		// Record the call as most recent first
		$invocation = new Phockito_Invocation($class, $instance, $method, $args, debug_backtrace(0));
		array_unshift(self::$_invocation_list, $invocation);

		// Look up any stubbed responses
		if (isset(self::$_responses[$instance][$method])) {
			// Find the first one that matches the called-with arguments
			foreach (self::$_responses[$instance][$method] as $i => &$matcher) {
				if (self::_arguments_match($class, $method, $matcher['args'], $args)) {
					// Consume the next response - except the last one, which repeats indefinitely
					if (count($matcher['steps']) > 1) return array_shift($matcher['steps']);
					else return reset($matcher['steps']);
				}
			}
		}
	}

	public static function __perform_response($response, $args) {
		if ($response['action'] == 'return') return $response['value'];
		else if ($response['action'] == 'throw') {
			/** @var Exception $class */
			$class = $response['value'];
			throw (is_object($class) ? $class : new $class());
		}
		else if ($response['action'] == 'callback') return call_user_func_array($response['value'], $args);
		else user_error("Got unknown action {$response['action']} - how did that happen?", E_USER_ERROR);
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
	protected static function build_test_double($partial, $mockedClass) {
		// Bail if we were passed a classname that doesn't exist
		if (!class_exists($mockedClass) && !interface_exists($mockedClass)) user_error("Can't mock non-existent class $mockedClass", E_USER_ERROR);

		// How to get a reference to the Phockito class itself
		$phockito = self::_has_namespaces() ? '\\Phockito' : 'Phockito';

		// Reflect on the mocked class
		$reflect = new ReflectionClass($mockedClass);

		if ($reflect->isFinal()) user_error("Can't mock final class $mockedClass", E_USER_ERROR);

		// Build up an array of php fragments that make the mocking class definition
		$php = array();

		// Get the namespace & the shortname of the mocked class
		if (self::_has_namespaces()) {
			$mockedNamespace = $reflect->getNamespaceName();
			$mockedShortName = $reflect->getShortName();
		}
		else {
			$mockedNamespace = '';
			$mockedShortName = $mockedClass;
		}

		// Build the short name of the mocker class based on the mocked classes shortname
		$mockerShortName = self::MOCK_PREFIX.$mockedShortName.($partial ? '_Spy' : '_Mock');
		// And build the full class name of the mocker by prepending the namespace if appropriate
		$mockerClass = (self::_has_namespaces() ? $mockedNamespace.'\\' : '') . $mockerShortName;

		// If we've already built this test double, just return it
		if (class_exists($mockerClass, false)) return $mockerClass;

		// If the mocked class is in a namespace, the test double goes in the same namespace
		$namespaceDeclaration = $mockedNamespace ? "namespace $mockedNamespace;" : '';

		// The only difference between mocking a class or an interface is how the mocking class extends from the mocked
		$extends = $reflect->isInterface() ? 'implements' : 'extends';
		$marker = $reflect->isInterface() ? ", {$phockito}_MockMarker" : "implements {$phockito}_MockMarker";

		// When injecting the class as a string, need to escape the "\" character.
		$mockedClassString = "'".str_replace('\\', '\\\\', $mockedClass)."'";

		// Add opening class stanza
		$php[] = <<<EOT
$namespaceDeclaration
class $mockerShortName $extends $mockedShortName $marker {
  public \$__phockito_instanceid;

  function __construct() {
    \$this->__phockito_instanceid = $mockedClassString.':'.(++{$phockito}::\$_instanceid_counter);
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
			if ($method->isPrivate()) continue;

			// Either skip or throw error on final methods.
			if ($method->isFinal()) {
				if (self::$ignore_finals) continue;
				else user_error("Class $mockedClass has final method {$method->name}, which we can\'t mock", E_USER_WARNING);
			}

			// Get the modifiers for the function as a string (static, public, etc) - ignore abstract though, all mock methods are concrete
			$modifiers = implode(' ', Reflection::getModifierNames($method->getModifiers() & ~(ReflectionMethod::IS_ABSTRACT)));

			// See if the method is return byRef
			$byRef = $method->returnsReference() ? "&" : "";

			// PHP fragment that is the arguments definition for this method
			$defparams = array(); $callparams = array();

			// Array of defaults (sparse numeric)
			self::$_defaults[$mockedClass][$method->name] = array();
			
			foreach ($method->getParameters() as $i => $parameter) {
				// Turn the method arguments into a php fragment that calls a function with them
				$callparams[] = '$'.$parameter->getName();

				// Get the type hint of the parameter
				if ($parameter->isArray()) $type = 'array ';
				else if ($parameterClass = $parameter->getClass()) $type = '\\'.$parameterClass->getName().' ';
				else $type = '';

				try {
					$defaultValue = $parameter->getDefaultValue();
				}
				catch (ReflectionException $e) {
					$defaultValue = null;
				}

				// Turn the method arguments into a php fragment the defines a function with them, including possibly the by-reference "&" and any default
				$defparams[] =
					$type .
					($parameter->isPassedByReference() ? '&' : '') .
					'$'.$parameter->getName() .
					($parameter->isOptional() ? '=' . var_export($defaultValue, true) : '')
				;

				// Finally cache the default value for matching against later
				if ($parameter->isOptional()) self::$_defaults[$mockedClass][$method->name][$i] = $defaultValue;
			}

			// Turn that array into a comma seperated list
			$defparams = implode(', ', $defparams); $callparams = implode(', ', $callparams);

			// What to do if there's no stubbed response
			if ($partial && !$method->isAbstract()) {
				$failover = "call_user_func_array(array($mockedClassString, '{$method->name}'), \$args)";
			}
			else {
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
			}
			elseif ($method->name == '__call') {
				$has__call = true;
			}
			elseif ($method->name == '__toString') {
				$has__toString = true;
			}
			// Build an overriding method that calls Phockito::__called, and never calls the parent
			else {
				$php[] = <<<EOT
  $modifiers function $byRef {$method->name}( $defparams ){
    \$args = func_get_args();

    \$backtrace = debug_backtrace();
    \$instance = \$backtrace[0]['type'] == '::' ? ('::'.$mockedClassString) : \$this->__phockito_instanceid;

    \$response = {$phockito}::__called($mockedClassString, \$instance, '{$method->name}', \$args);
  
    \$result = \$response ? {$phockito}::__perform_response(\$response, \$args) : ($failover);
    return \$result;
  }
EOT;
			}
		}

		// Always add a __call method to catch any calls to undefined functions
		$failover = ($partial && $has__call) ? "parent::__call(\$name, \$args)" : "null";

		$php[] = <<<EOT
  function __call(\$name, \$args) {
    \$response = {$phockito}::__called($mockedClassString, \$this->__phockito_instanceid, \$name, \$args);

    if (\$response) return {$phockito}::__perform_response(\$response, \$args);
    else return $failover;
  }
EOT;

		// Always add a __toString method
		if ($partial) {
			if ($has__toString) $failover = "parent::__toString()";
			else $failover = "user_error('Object of class '.$mockedClassString.' could not be converted to string', E_USER_ERROR)";
		}
		else $failover = "''";

		$php[] = <<<EOT
  function __toString() {
    \$args = array();
    \$response = {$phockito}::__called($mockedClassString, \$this->__phockito_instanceid, "__toString", \$args);

    if (\$response) return {$phockito}::__perform_response(\$response, \$args);
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
	static function mock_class($class) {
		$mockClass = self::build_test_double(false, $class);

		// If we've been given a type registrar, call it (we need to do this even if class exists, since PHPUnit resets globals, possibly de-registering between tests)
		$type_registrar = self::$type_registrar;
		if ($type_registrar) $type_registrar::register_double($mockClass, $class, self::$_is_interface[$class]);

		return $mockClass;
	}

	/**
	 * Given a class name as a string, return a new instance which acts as a mock of that class
	 *
	 * @static
	 * @param string $class - The class to mock
	 * @return Object - A mock of that class
	 */
	static function mock_instance($class) {
		$mockClass = self::mock_class($class);
		return new $mockClass();
	}

	/**
	 * Aternative name for mock_instance
	 */
	static function mock($class) {
		return self::mock_instance($class);
	}

	static function spy_class($class) {
		$spyClass = self::build_test_double(true, $class);

		// If we've been given a type registrar, call it (we need to do this even if class exists, since PHPUnit resets globals, possibly de-registering between tests)
		$type_registrar = self::$type_registrar;
		if ($type_registrar) $type_registrar::register_double($spyClass, $class, self::$_is_interface[$class]);

		return $spyClass;
	}

	const DONT_CALL_CONSTRUCTOR = '__phockito_dont_call_constructor';

	static function spy_instance($class /*, $constructor_arg_1, ... */) {
		$spyClass = self::spy_class($class);
		
		$res = new $spyClass();

		// Find the constructor args
		$constructor_args = func_get_args();
		array_shift($constructor_args);

		// Call the constructor (maybe)
		if (count($constructor_args) != 1 || $constructor_args[0] !== self::DONT_CALL_CONSTRUCTOR) {
			$constructor = array($res, '__phockito_parent_construct');
			if (!is_callable($constructor)) {
				if ($constructor_args) user_error("Tried to create spy of $class with constructor args, but that $class doesn't have a constructor defined", E_USER_ERROR);
			}
			else {
				call_user_func_array($constructor, $constructor_args);
			}
		}
		
		// And done
		return $res;
	}

	static function spy() {
		$args = func_get_args();
		return call_user_func_array(array(__CLASS__, 'spy_instance'), $args);
	}

	/**
	 * When builder. Starts stubbing the method called to build the argument passed to when
	 *
	 * @static
	 * @return Phockito_WhenBuilder
	 */
	static function when($arg = null) {
		if ($arg instanceof Phockito_MockMarker) {
			return new Phockito_WhenBuilder($arg->__phockito_instanceid);
		}
		else {
			/** @var Phockito_Invocation $invocation */
			$invocation = array_shift(self::$_invocation_list);
			return new Phockito_WhenBuilder($invocation->instanceId, $invocation->methodName, $invocation->args);
		}
	}

	/**
	 * Verify builder. Takes a mock instance and an optional number of times to verify against. Returns a
	 * DSL object that catches the method to verify
	 *
	 * @static
	 * @param Phockito_Mock $mock - The mock instance to verify
	 * @param string|int|Phockito_VerificationMode $mode - The verification mode, number, or number followed by '+'
	 * 													   describing how many times the methods should be called
	 * @return Phockito_VerifyBuilder
	 */
	static function verify($mock, $mode = 1) {
		return new Phockito_VerifyBuilder($mock->__phockito_instanceid, $mode);
	}

	/**
	 * @param int $times
	 * @return Phockito_VerificationMode
	 */
	static function times($times) {
		return new Phockito_Times($times);
	}

	/**
	 * @return Phockito_VerificationMode
	 */
	static function never() {
		return self::times(0);
	}

	/**
	 * @param int $times
	 * @return Phockito_VerificationMode
	 */
	static function atLeast($times) {
		return new Phockito_AtLeast($times);
	}

	/**
	 * @return Phockito_VerificationMode
	 */
	static function atLeastOnce() {
		return self::atLeast(1);
	}

	/**
	 * @param int $times
	 * @return Phockito_VerificationMode
	 */
	static function atMost($times) {
		return new Phockito_AtMost($times);
	}

	static function only() {
		return new Phockito_Only();
	}

	/**
	 * Reset a mock instance. Forget all calls and stubbed responses for a given instance
	 * @static
	 * @param Phockito_Mock $mock - The mock instance to reset
	 */
	static function reset($mock, $method = null) {
		// Get the instance ID. Only resets instance-specific info ATM
		$instance = $mock->__phockito_instanceid;
		
		// Remove any stored returns
		if ($method) unset(self::$_responses[$instance][$method]);
		else unset(self::$_responses[$instance]);
		
		// Remove all call history
		/** @var Phockito_Invocation $invocation */
		foreach (self::$_invocation_list as $i => $invocation) {
			if (($method && $invocation->matchesInstanceAndMethod($instance, $method)) ||
				($method == null && $invocation->matchesInstance($instance))
			) {
				array_splice(self::$_invocation_list, $i, 1);
			}
		}
	}

	/**
	 * @param Phockito_MockMarker|Object|array $mocks
	 */
	static function verifyNoMoreInteractions($mocks) {
		if (!is_array($mocks)) {
			$mocks = array($mocks);
		}

		$noMoreInteractionsVerificationMode = new Phockito_NoMoreInteractions();

		/** @var Phockito_MockMarker $mock */
		foreach ($mocks as $mock) {
			$verificationContext = new Phockito_VerificationContext($mock->__phockito_instanceid, null, array());
			$verificationResult = $noMoreInteractionsVerificationMode->verify($verificationContext);
			if ($verificationResult instanceof Phockito_UnsuccessfulVerificationResult) {
				(new Phockito_UnsuccessfulVerificationReporter())->reportUnsuccessfulVerification($verificationResult);
			}
		}
	}

	/**
	 * Includes the Hamcrest matchers. You don't have to, but if you don't you can't to nice generic stubbing and verification
	 * @static
	 * @param bool $as_globals - When true (the default) the hamcrest matchers are available as global functions. If false, they're only available as static methods on Hamcrest_Matchers
	 */
	static function include_hamcrest($include_globals = true) {
		set_include_path(get_include_path().PATH_SEPARATOR.dirname(__FILE__).'/hamcrest-php/hamcrest');
		
		if ($include_globals) {
			require_once('Hamcrest.php');
			require_once('HamcrestTypeBridge_Globals.php');
		} else {
			require_once('Hamcrest/Matchers.php');
			require_once('HamcrestTypeBridge.php');
		}
	}
}

/**
 * Marks all mocks for easy identification
 */
interface Phockito_MockMarker {

}

/**
 * A builder than is returned by Phockito::when to capture the methods that specify the stubbed responses
 * for a particular mocked method / arguments set
 *
 * @method Phockito_WhenBuilder return($value) thenReturn($value)
 * @method Phockito_WhenBuilder throw($exception) thenThrow($exception)
 * @method Phockito_WhenBuilder callback($callback) thenCallback($callback)
 * @method Phockito_WhenBuilder then($arg)
 */
class Phockito_WhenBuilder {

	protected $instance;
	protected $method;
	protected $i;

	protected $lastAction = null;

	/**
	 * Store the method and args we're stubbing
	 */
	private function __phockito_setMethod($method, $args) {
		$instance = $this->instance;
		$this->method = $method;

		if (!isset(Phockito::$_responses[$instance])) Phockito::$_responses[$instance] = array();
		if (!isset(Phockito::$_responses[$instance][$method])) Phockito::$_responses[$instance][$method] = array();

		$this->i = count(Phockito::$_responses[$instance][$method]);
		Phockito::$_responses[$instance][$method][] = array(
			'args' => $args,
			'steps' => array()
		);
	}

	function __construct($instance, $method = null, $args = null) {
		$this->instance = $instance;
		if ($method) $this->__phockito_setMethod($method, $args);
	}

	/**
	 * Either record the method we're stubbing, or record the next stubbed response in the sequence if we know the stubbed method already
	 *
	 * To be as flexible as possible, we accept _any_ method with "return" in it as a return response, and anything with
	 * throw in it as a throw response.
	 */
	function __call($called, $args) {
		if (!$this->method) {
			$this->__phockito_setMethod($called, $args);
		}
		else {
			if (count($args) !== 1) user_error("$called requires exactly one argument", E_USER_ERROR);
			$value = $args[0]; $action = null;

			if (preg_match('/return/i', $called)) $action = 'return';
			else if (preg_match('/throw/i', $called)) $action = 'throw';
			else if (preg_match('/callback/i', $called)) $action = 'callback';
			else if ($called == 'then') {
				if ($this->lastAction) {
					$action = $this->lastAction;
				} else {
					user_error(
						"Cannot use then without previously invoking a \"return\", \"throw\", or \"callback\" action",
						E_USER_ERROR
					);
				}
			}
			else user_error(
				"Unknown when action $called - should contain \"return\", \"throw\" or \"callback\" somewhere in method name",
				E_USER_ERROR
			);

			Phockito::$_responses[$this->instance][$this->method][$this->i]['steps'][] = array(
				'action' => $action,
				'value' => $value
			);

			$this->lastAction = $action;
		}

		return $this;
	}
}

/**
 * A builder than is returned by Phockito::verify to capture the method that specifies the verified method
 * Throws an exception if the verified method hasn't been called "$times" times, either a PHPUnit exception
 * or just an Exception if PHPUnit doesn't exist
 */
class Phockito_VerifyBuilder {
	protected $instance;
	protected $mode;

	function __construct($instance, $mode) {
		$this->instance = $instance;
		$this->mode = $mode;
	}

	function __call($called, $args) {
		if ($this->mode instanceof Phockito_VerificationMode) {
			$verificationMode = $this->mode;
		}
		else if (preg_match('/([0-9]+)\+/', $this->mode, $match)) {
			$verificationMode = Phockito::atLeast((int)$match[1]);
		}
		else {
			$verificationMode = Phockito::times($this->mode);
		}

		$verificationContext = new Phockito_VerificationContext($this->instance, $called, $args);

		$verificationResult = $verificationMode->verify($verificationContext);

		if ($verificationResult instanceof Phockito_SuccessfulVerificationResult) {
			$verificationContext->markMatchingInvocationsAsVerified();
			return;
		}

		(new Phockito_UnsuccessfulVerificationReporter())->reportUnsuccessfulVerification($verificationResult);
	}
}

class Phockito_UnsuccessfulVerificationReporter {
	static $exception_class = null;

	function __construct() {
		if (self::$exception_class === null) {
			self::$exception_class = class_exists('PHPUnit_Framework_AssertionFailedError') ?
				"PHPUnit_Framework_AssertionFailedError" :
				"Exception";
		}
	}

	function reportUnsuccessfulVerification(Phockito_UnsuccessfulVerificationResult $verificationResult) {
		$message = $verificationResult->describeConstraintFailure();
		$exceptionClass = self::$exception_class;
		throw new $exceptionClass($message);
	}
}

class Phockito_VerificationContext {
	private $_mockInstanceId;
	private $_methodToVerify;
	private $_argumentsToVerify;

	/**
	 * @param string $mockInstanceId
	 * @param string $methodToVerify
	 * @param array $argumentsToVerify
	 */
	function __construct($mockInstanceId, $methodToVerify, array $argumentsToVerify) {
		$this->_mockInstanceId = $mockInstanceId;
		$this->_methodToVerify = $methodToVerify;
		$this->_argumentsToVerify = $argumentsToVerify;
	}

	/**
	 * @return Phockito_Invocation[]
	 */
	function getAllInvocationsOnMock() {
		return $invocationsForMock = array_filter(
			Phockito::$_invocation_list,
			function(Phockito_Invocation $invocation) {
				return $invocation->matchesInstance($this->_mockInstanceId);
			}
		);
	}

	/**
	 * @return Phockito_Invocation[]
	 */
	function getMatchingInvocations() {
		return $invocationsForMock = array_filter(
			Phockito::$_invocation_list,
			function(Phockito_Invocation $invocation) {
				return $invocation->matchesInstanceAndMethod($this->_mockInstanceId, $this->_methodToVerify)
					&& $invocation->matchesArguments($this->_argumentsToVerify);
			}
		);
	}

	function markMatchingInvocationsAsVerified() {
		foreach ($this->getMatchingInvocations() as $invocation) {
			$invocation->verified = true;
		}
	}

	/**
	 * @return string
	 */
	public function getMethodToVerify() {
		return $this->_methodToVerify;
	}

	/**
	 * @return array
	 */
	public function getArgumentsToVerify() {
		return $this->_argumentsToVerify;
	}
}

class Phockito_Invocation {
	public $className;
	public $instanceId;
	public $methodName;
	public $args;
	public $backtrace;

	public $verified = false;

	function __construct($className, $instanceId, $methodName, $args, array $backtrace) {
		$this->className = $className;
		$this->instanceId = $instanceId;
		$this->methodName = $methodName;
		$this->args = $args;
		$this->backtrace = $backtrace;
	}

	public function matchesInstance($instanceId) {
		return $this->instanceId == $instanceId;
	}

	public function matchesInstanceAndMethod($instanceId, $methodName) {
		return $this->matchesInstance($instanceId) && $this->methodName == $methodName;
	}

	/**
	 * Checks if the given arguments list matches that of the invocation. Simple serialized check for now, to be
	 * replaced by something that can handle anyString etc matchers later
	 */
	public function matchesArguments($args) {
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

	private function _argumentListsMatch($invocationArgs, $passedArgs) {
		// If two argument arrays are different lengths, automatic fail
		if (count($invocationArgs) != count($passedArgs)) return false;

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

	private function _argumentsMatch($invocationArg, $passedArg) {
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
		}
		// Otherwise check for equality by checking the equality of the serialized version
		else {
			return serialize($invocationArg) != serialize($passedArg);
		}
	}
}
