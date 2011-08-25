<?php

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');

interface PhockitoSilverStripeTest_Interface {
}

class PhockitoSilverStripeTest_Base {
}

class PhockitoSilverStripeTest_Child extends PhockitoSilverStripeTest_Base {
}

class PhockitoSilverStripeTest_Implementor implements PhockitoSilverStripeTest_Interface {
}

global $_PSST_ALL_CLASSES;
$_PSST_ALL_CLASSES = array(
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

	static $orig_type_registrar;
	static $orig_all_classes;

	function setUp() {
		self::$orig_type_registrar = Phockito::$type_registrar;
		
		require_once(dirname(dirname(__FILE__)) . '/PhockitoSilverStripe.php');
		Phockito::$type_registrar = 'PhockitoSilverStripe';
		
		self::$orig_all_classes = PhockitoSilverStripe::$_all_classes;
		PhockitoSilverStripe::$_all_classes = '_PSST_ALL_CLASSES';
	}

	function tearDown() {
		PhockitoSilverStripe::$_all_classes = self::$orig_all_classes;
		Phockito::$type_registrar = self::$orig_type_registrar;
	}

	function testParentInjection() {
		global $_PSST_ALL_CLASSES;

		$class = Phockito::spy_class('PhockitoSilverStripeTest_Child');

		$this->assertEquals($_PSST_ALL_CLASSES['parents'][$class], array(
			'PhockitoSilverStripeTest_Base' => 'PhockitoSilverStripeTest_Base',
			'PhockitoSilverStripeTest_Child' => 'PhockitoSilverStripeTest_Child'
		));
	}

	function testImplementsInjectionOfAClassDouble() {
		global $_PSST_ALL_CLASSES;

		$class = Phockito::spy_class('PhockitoSilverStripeTest_Implementor');
		$this->assertTrue(array_key_exists($class, $_PSST_ALL_CLASSES['implementors']['PhockitoSilverStripeTest_Interface']));
	}

	function testImplementsInjectionOfAnInterfaceDouble() {
		global $_PSST_ALL_CLASSES;

		$class = Phockito::mock_class('PhockitoSilverStripeTest_Interface');
		$this->assertTrue(array_key_exists($class, $_PSST_ALL_CLASSES['implementors']['PhockitoSilverStripeTest_Interface']));
	}

}
