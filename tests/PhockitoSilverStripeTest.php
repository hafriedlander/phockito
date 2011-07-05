<?php

global $_ALL_CLASSES;
$_ALL_CLASSES = array();

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

interface PhockitoSilverStripeTest_Interface {
}

class PhockitoSilverStripeTest_Base {
}

class PhockitoSilverStripeTest_Child extends PhockitoSilverStripeTest_Base {
}

class PhockitoSilverStripeTest_Implementor implements PhockitoSilverStripeTest_Interface {
}

$_ALL_CLASSES = array(
	'parents' => array(
		'PhockitoSilverStripeTest_Base' => array(),
		'PhockitoSilverStripeTest_Child' => array('PhockitoSilverStripeTest_Base' => 'PhockitoSilverStripeTest_Base'),
		'PhockitoSilverStripeTest_Implementor' => array()
	),
	'implementors' => array(
		'PhockitoSilverStripeTest_Interface' => array('PhockitoSilverStripeTest_Implementor' => 'PhockitoSilverStripeTest_Implementor')
	)
);


class PhockitoSilverStripeTest extends PHPUnit_Framework_TestCase {

	function setUp() {
		require_once(dirname(dirname(__FILE__)) . '/PhockitoSilverStripe.php');
		Phockito::$type_registrar = 'PhockitoSilverStripe';
	}

	function tearDown() {
		Phockito::$type_registrar = null;
	}

	function testParentInjection() {
		global $_ALL_CLASSES;

		$class = Phockito::spy_class('PhockitoSilverStripeTest_Child');

		$this->assertEquals($_ALL_CLASSES['parents'][$class], array(
			'PhockitoSilverStripeTest_Base' => 'PhockitoSilverStripeTest_Base',
			'PhockitoSilverStripeTest_Child' => 'PhockitoSilverStripeTest_Child'
		));
	}

	function testImplementsInjectionOfAClassDouble() {
		global $_ALL_CLASSES;

		$class = Phockito::spy_class('PhockitoSilverStripeTest_Implementor');
		$this->assertTrue(array_key_exists($class, $_ALL_CLASSES['implementors']['PhockitoSilverStripeTest_Interface']));
	}

	function testImplementsInjectionOfAnInterfaceDouble() {
		global $_ALL_CLASSES;

		$class = Phockito::mock_class('PhockitoSilverStripeTest_Interface');
		$this->assertTrue(array_key_exists($class, $_ALL_CLASSES['implementors']['PhockitoSilverStripeTest_Interface']));
	}

}
