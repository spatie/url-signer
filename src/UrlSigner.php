<?php

namespace Spatie\UrlSigner;

use DateTimeInterface;

interface UrlSigner
{
    public function sign(
        string $url,
        int|\DateTimeInterface $expiration,
        string $signatureKey = null
    ): string;

    public function validate(string $url, string $signatureKey = null): bool;
}
