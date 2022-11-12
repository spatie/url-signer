<?php

namespace Spatie\UrlSigner\Exceptions;

use Exception;

class InvalidSignatureKey extends Exception
{
    public static function signatureEmpty(): self
    {
        return new self('The signature key is empty');
    }
}
