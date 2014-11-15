<?php

namespace Phockito;


use Phockito\Test\OverloadedCall;
use PHPUnit_Framework_TestCase;

class OverloadedCallTest extends PHPUnit_Framework_TestCase
{
    function testMockingCall()
    {
        $mock = Phockito::mock(OverloadedCall::class);

        $this->assertNull($mock->Foo());

        Phockito::when($mock)->Foo()->return(1);
        $this->assertEquals($mock->Foo(), 1);

        Phockito::verify($mock, 2)->Foo();
    }

    function testSpyingCall()
    {
        $spy = Phockito::spy(OverloadedCall::class);

        $this->assertEquals($spy->Foo(), 'Foo');

        Phockito::when($spy)->Foo()->return(1);
        $this->assertEquals($spy->Foo(), 1);

        Phockito::verify($spy, 2)->Foo();
    }
}