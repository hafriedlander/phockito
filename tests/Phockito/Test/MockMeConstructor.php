<?php

namespace Phockito\Test;


use Exception;

class MockMeConstructor
{
    /**
     * @param PassMe $passMe
     * @throws Exception
     * @return null
     */
    function __construct(PassMe $passMe)
    {
        throw new Exception('Base constructor was called');
    }

    /**
     * @param PassMe $a
     * @throws Exception
     * @return null
     */
    function Foo(PassMe $a)
    {
        throw new Exception('Base method Foo was called');
    }
}