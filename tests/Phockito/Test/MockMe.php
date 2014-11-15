<?php

namespace Phockito\Test;


use Exception;

class MockMe
{
    /**
     * @throws Exception
     * @return null
     */
    function Foo()
    {
        throw new Exception('Base method Foo was called');
    }

    /**
     * @throws Exception
     * @return null
     */
    function Bar()
    {
        throw new Exception('Base method Bar was called');
    }
}