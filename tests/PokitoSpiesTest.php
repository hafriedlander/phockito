<?php

require_once(dirname(dirname(__FILE__)) . '/Pokito.php');

class PhockitoSpiesTest_MockMe {
	function Foo() { throw new Exception('Base method Foo was called'); }
	function Bar() { return $this->Foo(); }
}

class PhockitoSpiesTest extends PHPUnit_Framework_TestCase {

	/** Test stubbing **/

	function testCanPartiallyStub() {
		$spy = Phockito::spy('PhockitoSpiesTest_MockMe');
		Phockito::when($spy)->Foo()->return(1);

		$this->assertEquals($spy->Foo(), 1);
		$this->assertEquals($spy->Bar(), 1);
	}
}
