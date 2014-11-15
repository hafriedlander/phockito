<?php

namespace Phockito\Test;


class HasGloballyResolvedTypedArguments
{
    public function Foo(\Phockito\Test\Type $a)
    {
        return 'Foo';
    }
}