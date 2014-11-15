<?php

namespace Phockito\Test;


class FooReturnsByReferenceImplements implements MockInterfaceFooReturnsByReference
{
    function &Foo()
    {
        return 5;
    }
}