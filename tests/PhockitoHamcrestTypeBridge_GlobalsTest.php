<?php

require_once(dirname(dirname(__FILE__)) . '/Phockito.php');
Phockito::include_hamcrest();


class MockMe {
    public function do_stuff(UsedByMockMe $m) {
        throw new Exception('unstubbed!');
    }
}

class UsedByMockMe {
    public $foo = null;
    function __construct($foo = null) {
        $this->foo  = $foo;
    }
}


class PhockitoHamcrestTypeBridge_GlobalsTest extends PHPUnit_Framework_TestCase { 

    function testArgOfTypeThat() {
        $mock = Phockito::mock("MockMe");
        $a = new UsedByMockMe('a');
        $expected = 'it works';
        Phockito::when($mock)->do_stuff(argOfTypeThat('UsedByMockMe', is(equalTo($a))))->return($expected);
        $this->assertNotNull($mock->do_stuff($a));
        $this->assertEquals($expected, $mock->do_stuff($a));
        // calling do_stuff with an instance that isn't equal to $a still returns null
        $this->assertNull($mock->do_stuff(new UsedByMockMe('b')));
    }
}
