<?php

// Include Pokito
require_once(dirname(dirname(__FILE__)) . '/Pokito_Globals.php');

class PokitoGlobalsTest_MockMe {
	function Foo() { return 'Foo'; }
	function Bar() { return 'Bar'; }
}

/** And the tests themselves */

class PokitoGlobalsTest extends PHPUnit_Framework_TestCase {

	function testCanBuildMock() {
		$mock = mock('PokitoGlobalsTest_MockMe');
		$this->assertInstanceOf('PokitoGlobalsTest_MockMe', $mock);
		$this->assertNull($mock->Foo());
		$this->assertNull($mock->Bar());
	}

	function testCanBuildSpy() {
		$spy = spy('PokitoGlobalsTest_MockMe');
		$this->assertInstanceOf('PokitoGlobalsTest_MockMe', $spy);
		$this->assertEquals($spy->Foo(), 'Foo');
		$this->assertEquals($spy->Bar(), 'Bar');
	}

	function testCanStub() {
		$mock = mock('PokitoGlobalsTest_MockMe');

		when($mock->Foo())->return(1);
		$this->assertEquals($mock->Foo(), 1);
	}

	function testCanVerify() {
		$mock = mock('PokitoGlobalsTest_MockMe');
		
		$mock->Foo();
		verify($mock)->Foo();
	}
}
