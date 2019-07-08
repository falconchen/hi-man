<?php

namespace App\Validation;

use App\Model\User;
use Violin\Violin;

class Validator extends Violin
{
    public function __construct(User $user)
    {
        $this->user = $user;

        $this->addFieldMessages([
            'email' => [
                'uniqueEmail' => 'email already in use',
            ],
            'username' => [
                'uniqueUsername' => 'username already in use',
            ],
        ]);
    }

    public function validate_uniqueEmail($value, $input, $args)
    {
        $user = $this->user->where('email', $value);

        return !(bool) $user->count();
    }

    public function validate_uniqueUsername($value, $input, $args)
    {
        return !(bool) $this->user->where('username', $value)->count();
    }

    /**
     * Method to help skip a rule if a value is empty, since we
     * don't need to validate an empty value. If the rule to
     * call specifically doesn't allowing skipping, then
     * we don't want skip the rule.
     * fix the 0 value skipRule
     *
     * @param  array $ruleToCall
     * @param  mixed $value
     *
     * @return null
     */
    protected function canSkipRule($ruleToCall, $value)
    {
        return (
            (is_array($ruleToCall) &&
                method_exists($ruleToCall[0], 'canSkip') &&
                $ruleToCall[0]->canSkip()) &&
            strlen($value) == 0 && empty($value) &&
            !is_array($value)
        );

    }
}