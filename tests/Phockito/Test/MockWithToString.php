<?php

namespace Phockito\Test;


class MockWithToString
{
    /**
     * @return null
     */
    function Foo()
    {
    }

    function __toString()
    {
        return 'Foo';
    }
}