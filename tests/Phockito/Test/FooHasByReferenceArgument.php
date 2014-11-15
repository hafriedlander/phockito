<?php

namespace Phockito\Test;


class FooHasByReferenceArgument
{
    /**
     * @param $a
     * @return null
     */
    function Foo(&$a)
    {
    }
}