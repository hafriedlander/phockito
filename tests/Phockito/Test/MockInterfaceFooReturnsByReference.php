<?php

namespace Phockito\Test;


interface MockInterfaceFooReturnsByReference
{
    /**
     * @return mixed
     */
    function &Foo();
}