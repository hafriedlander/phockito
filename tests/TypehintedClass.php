<?
namespace org\phockito\tests;
require_once 'NamespacedClass.php';
use org\phockito\tests\NamespacedClass;

interface TypehintedClass {

	/**
	 * @param array $foo
	 * @param \org\phockito\tests\NamespacedClass $bar
	 * @param \org\phockito\tests\NamespacedClass $baz
	 */
	function run(array $foo, NamespacedClass $bar, \org\phockito\tests\NamespacedClass $baz);
}
