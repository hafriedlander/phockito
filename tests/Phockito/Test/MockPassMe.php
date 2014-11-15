<?php

namespace Phockito\Test;


use Exception;

class MockPassMe
{
    /**
     * @param $a
     * @param $b
     * @throws Exception
     * @return null
     */
    function Foo($a, $b)
    {
        throw new Exception('Base method Foo was called');
    }

    /**
     * @param $a
     * @throws Exception
     * @return null
     */
    function Bar($a)
    {
        throw new Exception('Base method Bar was called');
    }

    /**
     * @param PassMe $a
     * @throws Exception
     * @return null
     */
    function Baz(PassMe $a)
    {
        throw new Exception('Base method Baz was called');
    }
}