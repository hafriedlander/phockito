<?php

namespace Phockito;


use PHPUnit_Framework_TestCase;

interface PhockitoSilverStripeTest_Interface
{
}

class PhockitoSilverStripeTest_Base
{
}

class PhockitoSilverStripeTest_Child extends PhockitoSilverStripeTest_Base
{
}

class PhockitoSilverStripeTest_Implementor implements PhockitoSilverStripeTest_Interface
{
}

global $_PSST_ALL_CLASSES;
$_PSST_ALL_CLASSES = array(
    'parents' => array(
        PhockitoSilverStripeTest_Base::class => array(),
        PhockitoSilverStripeTest_Child::class => array(PhockitoSilverStripeTest_Base::class => PhockitoSilverStripeTest_Base::class),
        PhockitoSilverStripeTest_Implementor::class => array()
    ),
    'implementors' => array(
        PhockitoSilverStripeTest_Interface::class => array(PhockitoSilverStripeTest_Implementor::class => PhockitoSilverStripeTest_Implementor::class)
    )
);

class SilverStripeTest extends PHPUnit_Framework_TestCase
{
    static $orig_type_registrar;
    static $orig_all_classes;

    function setUp()
    {
        self::$orig_type_registrar = Phockito::$type_registrar;

        Phockito::$type_registrar = new SilverStripe();

        self::$orig_all_classes = SilverStripe::$_all_classes;
        SilverStripe::$_all_classes = '_PSST_ALL_CLASSES';
    }

    function tearDown()
    {
        SilverStripe::$_all_classes = self::$orig_all_classes;
        Phockito::$type_registrar = self::$orig_type_registrar;
    }

    function testParentInjection()
    {
        global $_PSST_ALL_CLASSES;

        $class = Phockito::spy_class(PhockitoSilverStripeTest_Child::class);

        $this->assertEquals($_PSST_ALL_CLASSES['parents'][$class], array(
            PhockitoSilverStripeTest_Base::class => PhockitoSilverStripeTest_Base::class,
            PhockitoSilverStripeTest_Child::class => PhockitoSilverStripeTest_Child::class
        ));
    }

    function testImplementsInjectionOfAClassDouble()
    {
        global $_PSST_ALL_CLASSES;

        $class = Phockito::spy_class(PhockitoSilverStripeTest_Implementor::class);
        $this->assertTrue(array_key_exists($class, $_PSST_ALL_CLASSES['implementors'][PhockitoSilverStripeTest_Interface::class]));
    }

    function testImplementsInjectionOfAnInterfaceDouble()
    {
        global $_PSST_ALL_CLASSES;

        $class = Phockito::mock_class(PhockitoSilverStripeTest_Interface::class);
        $this->assertTrue(array_key_exists($class, $_PSST_ALL_CLASSES['implementors'][PhockitoSilverStripeTest_Interface::class]));
    }
}