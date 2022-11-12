<?php

namespace Spatie\UrlSigner\Exceptions;

use Exception;

class InvalidExpiration extends Exception
{
    public static function isInPast(): self
    {
        return new self('Expiration date must be in the future');
    }

    public static function wrongType(): self
    {
        return new self('Expiration date must be an instance of DateTime or an integer');
    }
}
