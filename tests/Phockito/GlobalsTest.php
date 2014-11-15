<?php

namespace Phockito;

use Phockito\Test\MockMe;
use PHPUnit_Framework_TestCase;

require_once(dirname(dirname(dirname(__FILE__))) . '/src/globals.php');

class GlobalsTest extends PHPUnit_Framework_TestCase
{
    function testCanBuildMock()
    {
        $mock = mock(MockMe::class);
        $this->assertInstanceOf(MockMe::class, $mock);
        $this->assertNull($mock->Foo());
        $this->assertNull($mock->Bar());
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Base method Foo was called
     */
    function testCanBuildSpy()
    {
        $spy = spy(MockMe::class);
        $this->assertInstanceOf(MockMe::class, $spy);
        $this->assertEquals($spy->Foo(), 'Foo');
    }

    function testCanStub()
    {
        $mock = mock(MockMe::class);

        when($mock->Foo())->return(1);
        $this->assertEquals($mock->Foo(), 1);
    }

    function testCanVerify()
    {
        $mock = mock(MockMe::class);

        $mock->Foo();
        verify($mock)->Foo();
    }
}