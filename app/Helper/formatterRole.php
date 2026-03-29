<?php

namespace App\Helper;

class FormatterRole
{
    public static function normalizeRole($role)
    {
        if (str_contains($role, ' ')) {
            return $role;
        }

        return trim(preg_replace('/([a-z])([A-Z])/', '$1 $2', $role));
    }
}
