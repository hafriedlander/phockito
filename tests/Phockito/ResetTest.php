<?php

namespace Phockito;


use Phockito\Test\MockMe;
use PHPUnit_Framework_TestCase;

class ResetTest extends PHPUnit_Framework_TestCase
{
    function testCanResetStubbedResults()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock)->Foo()->return(1);
        $this->assertEquals($mock->Foo(), 1);
        $this->assertEquals($mock->Foo(), 1);

        Phockito::reset($mock);
        $this->assertNull($mock->Foo());
    }

    function testCanResetStubbedResultsForSpecificMethod()
    {
        $mock = Phockito::mock(MockMe::class);

        Phockito::when($mock)->Foo()->return(1);
        Phockito::when($mock)->Bar()->return(2);

        $this->assertEquals($mock->Foo(), 1);
        $this->assertEquals($mock->Foo(), 1);

        $this->assertEquals($mock->Bar(), 2);
        $this->assertEquals($mock->Bar(), 2);

        Phockito::reset($mock, 'Foo');
        $this->assertNull($mock->Foo());
        $this->assertEquals($mock->Bar(), 2);
    }

    function testCanResetCallRecord()
    {
        $mock = Phockito::mock(MockMe::class);

        $mock->Foo();
        Phockito::verify($mock)->Foo();

        Phockito::reset($mock);
        Phockito::verify($mock, 0)->Foo();
    }

    function testCanResetCallRecordForSpecificMethod()
    {
        $mock = Phockito::mock(MockMe::class);

        $mock->Foo();
        $mock->Bar();
        Phockito::verify($mock)->Foo();
        Phockito::verify($mock)->Bar();

        Phockito::reset($mock, 'Foo');
        Phockito::verify($mock, 0)->Foo();
        Phockito::verify($mock)->Bar();
    }
}