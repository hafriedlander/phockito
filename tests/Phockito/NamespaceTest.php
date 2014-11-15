<?php

namespace Phockito;

use Hamcrest\Core\IsInstanceOf;
use Phockito\Test\HasGloballyResolvedTypedArguments;
use Phockito\Test\HasLocallyTypedArguments;
use Phockito\Test\HasUseResolvedTypedArguments;
use Phockito\Test\MockMe;
use Phockito\Test\Type;
use PHPUnit_Framework_TestCase;

class NamespaceTest extends PHPUnit_Framework_TestCase
{
    function testCanMockNamespacedClass()
    {
        $mock = Phockito::mock(MockMe::class);

        $this->assertNull($mock->Foo());

        Phockito::when($mock->Foo())->return('Bar');
        $this->assertEquals($mock->Foo(), 'Bar');
    }

    function testCanMockNamespacedClassWithLocallyTypedArgument()
    {
        $mock = Phockito::mock(HasLocallyTypedArguments::class);
        $arg = new Type();

        $this->assertNull($mock->Foo($arg));

        Phockito::when($mock->Foo($arg))->return('Bar');
        $this->assertEquals($mock->Foo($arg), 'Bar');
    }

    function testCanMockNamespacedClassWithUseResolvedTypedArgument()
    {
        $mock = Phockito::mock(HasUseResolvedTypedArguments::class);
        $arg = new Type();

        $this->assertNull($mock->Foo($arg));

        Phockito::when($mock->Foo($arg))->return('Bar');
        $this->assertEquals($mock->Foo($arg), 'Bar');
    }

    function testCanMockNamespacedClassWithGloballyResolvedTypedArgument()
    {
        $mock = Phockito::mock(HasGloballyResolvedTypedArguments::class);
        $arg = new Type();

        $this->assertNull($mock->Foo($arg));

        Phockito::when($mock->Foo($arg))->return('Bar');
        $this->assertEquals($mock->Foo($arg), 'Bar');
    }

    function testCanBridgeNamespacedClass()
    {
        $mockMatcher = new IsInstanceOf(MockMe::class);

        $typeBridge = HamcrestTypeBridge::argOfTypeThat(MockMe::class, $mockMatcher);

        $this->assertThat($typeBridge, $this->isInstanceOf(MockMe::class));
    }

}
