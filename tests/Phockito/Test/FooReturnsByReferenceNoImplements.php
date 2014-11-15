<?php

namespace Phockito\Test;


class FooReturnsByReferenceNoImplements
{
    function &Foo()
    {
        return 5;
    }
}