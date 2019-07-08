<?php

namespace Violin\Rules;

use Violin\Contracts\RuleContract;

class RequiredRule implements RuleContract
{
    public function run($value, $input, $args)
    {
        $value = preg_replace('/^[\pZ\pC]+|[\pZ\pC]+$/u', '', $value);

        return boolval(strlen($value)); //fix 0 value;
    }

    public function error()
    {
        return '{field} is required.';
    }

    public function canSkip()
    {
        return false;
    }
}
