<?php

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
Phockito::include_hamcrest();

class PhockitoHamcrestTest_PassMe {}

class PhockitoHamcrestTest_MockMe {
	function Foo($a, $b) { throw new Exception('Base method Foo was called'); }
	function Bar($a) { throw new Exception('Base method Bar was called'); }
	function Typehinted(PhockitoHamcrestTest_PassMe $a) { throw new Exception('Base method Typehinted was called'); }
}

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

	function testCanStubUsingMatchersForTypeHintedArguments() {
		$mock = Phockito::mock('PhockitoHamcrestTest_MockMe');

		Phockito::when($mock->Typehinted(anInstanceOf('PhockitoHamcrestTest_PassMe')))->return('PassMe');

		$this->assertEquals('PassMe', $mock->Typehinted(new PhockitoHamcrestTest_PassMe()));

		Phockito::verify($mock, 1)->Typehinted(new PhockitoHamcrestTest_PassMe());
	}
}
