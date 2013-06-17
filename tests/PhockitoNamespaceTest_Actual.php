<?php

namespace {
	// Include Phockito
	require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
	require_once(dirname(dirname(__FILE__)) . '/HamcrestTypeBridge.php');
	Phockito::include_hamcrest();

	// Turn on strict error checking - this makes sure that argument types are checked properly, etc
	error_reporting(E_ALL | E_STRICT);
}

namespace org\phockito\tests {
	class PhockitoNamespaceTest_MockMe {
		public function Foo() { return 'Foo'; }
	}
}

namespace org\phockito\tests {
	class PhockitoNamespaceTest_Type {
		/* NOP */
	}

	class PhockitoNamespaceTest_HasLocallyTypedArguments {
		public function Foo(PhockitoNamespaceTest_Type $a) { return 'Foo'; }
	}
}

namespace org\phockito\tests\foo {
	class PhockitoNamespaceTest_Type {
		/* NOP */
	}
}

namespace org\phockito\tests {
	use org\phockito\tests\foo;

	class PhockitoNamespaceTest_HasUseResolvedTypedArguments {
		public function Foo(foo\PhockitoNamespaceTest_Type $a) { return 'Foo'; }
	}
}

namespace org\phockito\tests\bar {
	class PhockitoNamespaceTest_Type {
		/* NOP */
	}
}

namespace org\phockito\tests {
	class PhockitoNamespaceTest_HasGloballyResolvedTypedArguments {
		public function Foo( \org\phockito\tests\bar\PhockitoNamespaceTest_Type $a) { return 'Foo'; }
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

		function testCanMockNamespacedClassWithLocallyTypedArgument() {
			$mock = Phockito::mock('\org\phockito\tests\PhockitoNamespaceTest_HasLocallyTypedArguments');
			$arg = new \org\phockito\tests\PhockitoNamespaceTest_Type();

			$this->assertNull($mock->Foo($arg));

			Phockito::when($mock->Foo($arg))->return('Bar');
			$this->assertEquals($mock->Foo($arg), 'Bar');
		}

		function testCanMockNamespacedClassWithUseResolvedTypedArgument() {
			$mock = Phockito::mock('\org\phockito\tests\PhockitoNamespaceTest_HasUseResolvedTypedArguments');
			$arg = new \org\phockito\tests\foo\PhockitoNamespaceTest_Type();

			$this->assertNull($mock->Foo($arg));

			Phockito::when($mock->Foo($arg))->return('Bar');
			$this->assertEquals($mock->Foo($arg), 'Bar');
		}

		function testCanMockNamespacedClassWithGloballyResolvedTypedArgument() {
			$mock = Phockito::mock('\org\phockito\tests\PhockitoNamespaceTest_HasGloballyResolvedTypedArguments');
			$arg = new \org\phockito\tests\bar\PhockitoNamespaceTest_Type();

			$this->assertNull($mock->Foo($arg));

			Phockito::when($mock->Foo($arg))->return('Bar');
			$this->assertEquals($mock->Foo($arg), 'Bar');
		}

		function testCanBridgeNamespacedClass() {
			$mockMatcher = new \Hamcrest_Core_IsInstanceOf('\org\phockito\tests\PhockitoNamespaceTest_MockMe');

			$typeBridge = \HamcrestTypeBridge::argOfTypeThat(
				'\org\phockito\tests\PhockitoNamespaceTest_MockMe',
				$mockMatcher);

			$this->assertThat($typeBridge, $this->isInstanceOf('\org\phockito\tests\PhockitoNamespaceTest_MockMe'));
		}

	}
}
