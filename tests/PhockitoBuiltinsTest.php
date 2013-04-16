<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

class PhockitoBuiltinsTest extends PHPUnit_Framework_TestCase {

	/** Test creation of mock class for builtins **/

	function testCanCreateBasicMockClassOfBuiltin() {
		$mock = Phockito::mock('SoapClient');
		$this->assertInstanceOf('SoapClient', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
	}

}