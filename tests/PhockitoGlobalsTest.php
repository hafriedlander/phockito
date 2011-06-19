<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito_Globals.php');

class PhockitoGlobalsTest_MockMe {
	function Foo() { return 'Foo'; }
	function Bar() { return 'Bar'; }
}

/** And the tests themselves */

class PhockitoGlobalsTest extends PHPUnit_Framework_TestCase {

	function testCanBuildMock() {
		$mock = mock('PhockitoGlobalsTest_MockMe');
		$this->assertInstanceOf('PhockitoGlobalsTest_MockMe', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
	}

	function testCanBuildSpy() {
		$spy = spy('PhockitoGlobalsTest_MockMe');
		$this->assertInstanceOf('PhockitoGlobalsTest_MockMe', $spy);
		$this->assertEquals($spy->Foo(), 'Foo');
		$this->assertEquals($spy->Bar(), 'Bar');
	}

	function testCanStub() {
		$mock = mock('PhockitoGlobalsTest_MockMe');

		when($mock->Foo())->return(1);
		$this->assertEquals($mock->Foo(), 1);
	}

	function testCanVerify() {
		$mock = mock('PhockitoGlobalsTest_MockMe');
		
		$mock->Foo();
		verify($mock)->Foo();
	}
}
