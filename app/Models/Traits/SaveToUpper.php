<?php

namespace App\Models\Traits;

trait SaveToUpper
{
    /**
     * Default params that will be saved on lowercase
     * @var array No Uppercase keys
     */
    protected $no_uppercase = [
        'password',
        'username',
        'email',
        'remember_token',
        'guard_name',
        'slug',
    ];

    public function setAttribute($key, $value)
    {
        if (is_string($value)) {
            if (!in_array($key, $this->no_uppercase)) {
                $value = trim(strtoupper($value));
            }
        }
        parent::setAttribute($key, $value);
    }
}