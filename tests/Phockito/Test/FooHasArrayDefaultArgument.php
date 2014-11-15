<?php

namespace Phockito\Test;


class FooHasArrayDefaultArgument
{
    /**
     * @param array $a
     * @return null
     */
    function Foo($a = array(1, 2, 3))
    {
    }
}