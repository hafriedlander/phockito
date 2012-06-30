<?php

namespace {

	// Include Phockito
	require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
	Phockito::include_hamcrest();

}

namespace org\phockito\tests {

	class PhockitoNamespaceTest_MockMe {
		public function Foo() { return 'Foo'; }
	}

}

namespace {

	class PhockitoNamespaceTest extends PHPUnit_Framework_TestCase {

		function testCanMockNamespacedClass() {
			$mock = Phockito::mock('\org\phockito\tests\PhockitoNamespaceTest_MockMe');

			$this->assertNull($mock->Foo());

			Phockito::when($mock->Foo())->return('Bar');
			$this->assertEquals($mock->Foo(), 'Bar');
		}
	}
}
