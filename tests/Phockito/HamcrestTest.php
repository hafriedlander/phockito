<?php

namespace Phockito;

use Phockito\Test\MockPassMe;
use Phockito\Test\PassMe;
use PHPUnit_Framework_TestCase;

class HamcrestTest extends PHPUnit_Framework_TestCase
{
    function testCanStubByType()
    {
        $mock = Phockito::mock(MockPassMe::class);

        Phockito::when($mock->Foo(intValue(), stringValue()))->return('int,string');
        Phockito::when($mock->Foo(stringValue(), stringValue()))->return('string,string');

        $this->assertNull($mock->Foo(1, 1));
        $this->assertEquals($mock->Foo(1, 'a'), 'int,string');
        $this->assertNull($mock->Foo('a', 1));
        $this->assertEquals($mock->Foo('a', 'a'), 'string,string');
    }

    function testCanVerifyByType()
    {
        $mock = Phockito::mock(MockPassMe::class);

        $mock->Bar('Pow!');
        $mock->Bar('Bam!');

        Phockito::verify($mock, 2)->Bar(stringValue());
    }

    function testCanStubTypeHintedMethodsByPassingOnlyMockIntoWhen()
    {
        $mock = Phockito::mock(MockPassMe::class);

        Phockito::when($mock)->Baz(anything())->return('PassMe');

        $this->assertEquals($mock->Baz(new PassMe()), 'PassMe');
    }
}