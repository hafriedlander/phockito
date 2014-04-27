<?php
error_reporting( E_ALL|E_STRICT );

// Include Phockito
require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

spl_autoload_register(function ($class) {
	if (0 === strncmp($class, Phockito::MOCK_PREFIX, strlen(Phockito::MOCK_PREFIX))) {
		throw new RuntimeException('Autoload attempted on a phockito mock class');
	}
}, true);

/** Base class to mock */

class PhockitoVerifyNoMoreInteractionsTest_MockMe {
	function Foo() { throw new Exception('Base method Foo was called'); }
	function Bar() { throw new Exception('Base method Bar was called'); }
}


class PhockitoVerifyNoMoreInteractionsTest extends PHPUnit_Framework_TestCase {
	const MOCK_CLASS = 'PhockitoVerifyNoMoreInteractionsTest_MockMe';

	static function setUpBeforeClass() {
		Phockito_UnsuccessfulVerificationReporter::$exception_class = 'PhockitoTest_VerificationFailure';
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testOneUnverifiedCallFailsVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}

	function testOneCallVerifiedWithNoExplicitModePassesVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		Phockito::verify($mock)->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}

	/** @expectedException PhockitoTest_VerificationFailure
	 */
	function testOneVerifiedAndOneUnverifiedDifferentCallFailsVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		$mock->Bar();
		Phockito::verify($mock)->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}

	function testTwoCallsVerifiedWithTimesPassesVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, Phockito::times(2))->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}

	function testTwoCallsVerifiedWithAtLeastPassesVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, Phockito::atLeast(1))->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}

	function testTwoCallsVerifiedWithAtLeastOncePassesVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, Phockito::atLeastOnce())->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}

	function testTwoCallsVerifiedWithAtMostPassesVerificationAgainstNoMoreInteractions() {
		$mock = Phockito::mock(self::MOCK_CLASS);
		$mock->Foo();
		$mock->Foo();
		Phockito::verify($mock, Phockito::atMost(2))->Foo();
		Phockito::verifyNoMoreInteractions($mock);
	}
}