<?php

namespace Phockito\Test;


use Exception;

class HamcrestMe
{
    /**
     * @param PassMe $a
     * @throws Exception
     * @return null
     */
    function Foo(PassMe $a)
    {
        throw new Exception('Base method Foo was called');
    }

    /**
     * @param array $a
     * @throws Exception
     * @return null
     */
    function Bar(array $a)
    {
        throw new Exception('Base method Bar was called');
    }
}