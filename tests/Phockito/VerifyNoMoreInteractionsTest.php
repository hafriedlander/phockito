<?php

namespace Phockito;


use Phockito\Test\MockMe;
use Phockito\Test\VerificationFailure;
use PHPUnit_Framework_TestCase;

class VerifyNoMoreInteractionsTest extends PHPUnit_Framework_TestCase
{
    const MOCK_CLASS = MockMe::class;

    public static function setUpBeforeClass()
    {
        UnsuccessfulVerificationReporter::$exception_class = VerificationFailure::class;
    }

    /** @expectedException VerificationFailure
     */
    function testOneUnverifiedCallFailsVerificationAgainstNoMoreInteractions()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }

    function testOneCallVerifiedWithNoExplicitModePassesVerificationAgainstNoMoreInteractions()
    {
        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        Phockito::verify($mock)->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }

    /** @expectedException VerificationFailure
     */
    function testOneVerifiedAndOneUnverifiedDifferentCallFailsVerificationAgainstNoMoreInteractions()
    {
        $this->setExpectedException(VerificationFailure::class);

        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        $mock->Bar();
        Phockito::verify($mock)->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }

    function testTwoCallsVerifiedWithTimesPassesVerificationAgainstNoMoreInteractions()
    {
        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::times(2))->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }

    function testTwoCallsVerifiedWithAtLeastPassesVerificationAgainstNoMoreInteractions()
    {
        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeast(1))->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }

    function testTwoCallsVerifiedWithAtLeastOncePassesVerificationAgainstNoMoreInteractions()
    {
        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atLeastOnce())->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }

    function testTwoCallsVerifiedWithAtMostPassesVerificationAgainstNoMoreInteractions()
    {
        $mock = Phockito::mock(self::MOCK_CLASS);
        $mock->Foo();
        $mock->Foo();
        Phockito::verify($mock, Phockito::atMost(2))->Foo();
        Phockito::verifyNoMoreInteractions($mock);
    }
}