<?php

namespace App\Validation;


use Violin\Violin;

class OscerValidator extends Violin
{
    public function __construct()
    {

        $this->addFieldMessages([
            'userMail' => [
                'emailOrTel' => 'Not an valid email address or telNum',
            ],
        ]);
    }

    public function validate_emailOrTel($value, $input, $args)
    {

        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false || is_numeric($value);

    }

    protected function canSkipRule($ruleToCall, $value)
    {
        return false;

    }
}