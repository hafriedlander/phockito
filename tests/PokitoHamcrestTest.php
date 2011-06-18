<?php

// Include Pokito
require_once(dirname(dirname(__FILE__)) . '/Pokito.php');
Pokito::include_hamcrest();

class PokitoHamcrestTest_MockMe {
	function Foo($a, $b) { throw new Exception('Base method Foo was called'); }
	function Bar($a) { throw new Exception('Base method Bar was called'); }
}

class PokitoHamcrestTest extends PHPUnit_Framework_TestCase {

	/** Test stubbing **/

	function testCanStubByType() {
		$mock = Pokito::mock('PokitoHamcrestTest_MockMe');
		
		Pokito::when($mock->Foo(intValue(), stringValue()))->return('int,string');
		Pokito::when($mock->Foo(stringValue(), stringValue()))->return('string,string');
		
		$this->assertNull($mock->Foo(1, 1));		
		$this->assertEquals($mock->Foo(1, 'a'), 'int,string');
		$this->assertNull($mock->Foo('a', 1));		
		$this->assertEquals($mock->Foo('a', 'a'), 'string,string');
	}

	function testCanVerifyByType() {
		$mock = Pokito::mock('PokitoHamcrestTest_MockMe');

		$mock->Bar('Pow!');
		$mock->Bar('Bam!');
		
		Pokito::verify($mock, 2)->Bar(stringValue());
	}
}
