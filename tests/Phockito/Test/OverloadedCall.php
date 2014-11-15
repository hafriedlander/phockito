<?php

namespace Phockito\Test;


class OverloadedCall
{
    function __call($name, $args)
    {
        return $name;
    }
} 