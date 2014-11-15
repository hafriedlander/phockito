<?php

namespace Phockito;

use Phockito\Test\SpyMe;
use PHPUnit_Framework_TestCase;

class SpiesTest extends PHPUnit_Framework_TestCase
{
    function testCanPartiallyStub()
    {
        $spy = Phockito::spy(SpyMe::class);
        Phockito::when($spy)->Foo()->return(1);

        $this->assertEquals($spy->Foo(), 1);
        $this->assertEquals($spy->Bar(), 1);
    }

    function testStubMethodWithArgumentNamedResponse()
    {
        $spy = Phockito::spy(SpyMe::class);
        $this->assertEquals($spy->Baz(1), 1);
    }

    /** Test constructor calling */

    function testConstructorCalledByDefault()
    {
        $spy = Phockito::spy(SpyMe::class);
        $this->assertTrue($spy->constructor_arg);
    }

    function testConstructorCalledWhenArgumentsPassed()
    {
        $spy = Phockito::spy(SpyMe::class, 'Bang!');
        $this->assertEquals($spy->constructor_arg, 'Bang!');
    }

    function testConstructorSupressedWhenDesired()
    {
        $spy = Phockito::spy(SpyMe::class, Phockito::DONT_CALL_CONSTRUCTOR);
        $this->assertFalse($spy->constructor_arg);
    }
}