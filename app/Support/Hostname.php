<?php

namespace App\Support;

class Hostname
{
    public static function normalize(string $value): string
    {
        $v = trim($value);
        $v = preg_replace('#^https?://#i', '', $v) ?? $v;
        $v = explode('/', $v, 2)[0];
        $v = rtrim($v, '.');

        return strtolower($v);
    }
}
