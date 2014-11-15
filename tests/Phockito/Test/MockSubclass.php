<?php

namespace Phockito\Test;


use Exception;

class MockSubclass extends MockMe {
    /**
     * @throws Exception
     * @return null
     */
    function Baz() { throw new Exception('Base method Baz was called'); }
}