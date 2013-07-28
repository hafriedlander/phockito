<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
Phockito::include_hamcrest();

class PhockitoHamcrestTest_MockMe {
	function Foo($a, $b) { throw new Exception('Base method Foo was called'); }
	function Bar($a) { throw new Exception('Base method Bar was called'); }
	function Baz(PhockitoHamcrestTest_PassMe $a) { throw new Exception('Base method Baz was called'); }
}

class PhockitoHamcrestTest_PassMe {}

class PhockitoHamcrestTest extends PHPUnit_Framework_TestCase {

	/** Test stubbing **/

	function testCanStubByType() {
		$mock = Phockito::mock('PhockitoHamcrestTest_MockMe');
		
		Phockito::when($mock->Foo(intValue(), stringValue()))->return('int,string');
		Phockito::when($mock->Foo(stringValue(), stringValue()))->return('string,string');
		
		$this->assertNull($mock->Foo(1, 1));		
		$this->assertEquals($mock->Foo(1, 'a'), 'int,string');
		$this->assertNull($mock->Foo('a', 1));		
		$this->assertEquals($mock->Foo('a', 'a'), 'string,string');
	}

	function testCanVerifyByType() {
		$mock = Phockito::mock('PhockitoHamcrestTest_MockMe');

		$mock->Bar('Pow!');
		$mock->Bar('Bam!');
		
		Phockito::verify($mock, 2)->Bar(stringValue());
	}

	function testCanStubTypeHintedMethodsByPassingOnlyMockIntoWhen() {
		$mock = Phockito::mock('PhockitoHamcrestTest_MockMe');

		Phockito::when($mock)->Baz(anything())->return('PassMe');

		$this->assertEquals($mock->Baz(new PhockitoHamcrestTest_PassMe()), 'PassMe');
	}
}
