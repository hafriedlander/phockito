<?php

namespace Phockito;


use Phockito\Test\MockWithoutToString;
use Phockito\Test\MockWithToString;
use PHPUnit_Framework_TestCase;

class ToStringTest extends PHPUnit_Framework_TestCase
{
    function testCanMockAndOverrideExistingToString()
    {
        $mock = Phockito::mock(MockWithToString::class);

        $this->assertEquals('', '' . $mock);

        Phockito::when($mock->__toString())->return('NewReturnValue');
        $this->assertEquals('NewReturnValue', '' . $mock);
    }

    function testCanSpyAndOverrideExistingToString()
    {
        $mock = Phockito::spy(MockWithToString::class);

        $this->assertEquals('Foo', '' . $mock);

        Phockito::when($mock->__toString())->return('NewReturnValue');
        $this->assertEquals('NewReturnValue', '' . $mock);
    }

    function testCanMockAndOverrideUndefinedToString()
    {
        $mock = Phockito::mock(MockWithoutToString::class);

        $this->assertEquals('', '' . $mock);

        Phockito::when($mock->__toString())->return('NewReturnValue');
        $this->assertEquals('NewReturnValue', '' . $mock);
    }

    function testCanSpyAndOverrideUndefinedToString()
    {
        $mock = Phockito::spy(MockWithoutToString::class);

        Phockito::when($mock)->__toString()->return('NewReturnValue');
        $this->assertEquals('NewReturnValue', '' . $mock);
    }
}