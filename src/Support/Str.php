<?php

namespace Spatie\UrlSigner\Support;

class Str
{
    public static function before(string $fullString, string $character): string
    {
        if (! str_contains($fullString, $character)) {
            return $fullString;
        }

        return substr($fullString, 0, strpos($fullString, $character));
    }

    public static function after(string $fullString, string $character): string
    {
        if (! str_contains($fullString, $character)) {
            return '';
        }

        return substr($fullString, strpos($fullString, $character) + 1);
    }
}
