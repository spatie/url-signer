<?php

namespace Spatie\UrlSigner;

use DateTime;

interface UrlSigner
{
    public function sign(
        string $url,
        int|DateTime $expiration,
        string $signatureKey = null
    ): string;

    public function validate(string $url, string $signatureKey = null): bool;
}
