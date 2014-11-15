<?php

namespace Phockito;


use PHPUnit_Framework_TestCase;
use SoapClient;

class BuiltinsTest extends PHPUnit_Framework_TestCase
{
    function testCanCreateBasicMockClassOfBuiltin()
    {
        $mock = Phockito::mock(SoapClient::class);

        $this->assertInstanceOf(SoapClient::class, $mock);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Bar());
    }
}