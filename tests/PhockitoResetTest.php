<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

/** Base class to mock */

class PhockitoResetTest_MockMe {
	function Foo() { }
	function Bar() { }
}

/** And the tests themselves */

class PhockitoResetTest extends PHPUnit_Framework_TestCase {

	function testCanResetStubbedResults() {
		$mock = Phockito::mock('PhockitoResetTest_MockMe');
		
		Phockito::when($mock)->Foo()->return(1);
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(), 1);
		
		Phockito::reset($mock);
		$this->assertNull($mock->Foo());
	}

	function testCanResetStubbedResultsForSpecificMethod() {
		$mock = Phockito::mock('PhockitoResetTest_MockMe');
		
		Phockito::when($mock)->Foo()->return(1);
		Phockito::when($mock)->Bar()->return(2);
		
		$this->assertEquals($mock->Foo(), 1);
		$this->assertEquals($mock->Foo(), 1);
		
		$this->assertEquals($mock->Bar(), 2);
		$this->assertEquals($mock->Bar(), 2);

		Phockito::reset($mock, 'Foo');
		$this->assertNull($mock->Foo());
		$this->assertEquals($mock->Bar(), 2);
	}
	
	function testCanResetCallRecord() {
		$mock = Phockito::mock('PhockitoResetTest_MockMe');
		
		$mock->Foo();
		Phockito::verify($mock)->Foo();
		
		Phockito::reset($mock);
		Phockito::verify($mock, 0)->Foo();
	}
	
	function testCanResetCallRecordForSpecificMethod() {
		$mock = Phockito::mock('PhockitoResetTest_MockMe');
		
		$mock->Foo();
		$mock->Bar();
		Phockito::verify($mock)->Foo();
		Phockito::verify($mock)->Bar();
		
		Phockito::reset($mock, 'Foo');
		Phockito::verify($mock, 0)->Foo();
		Phockito::verify($mock)->Bar();
	}
	
}
