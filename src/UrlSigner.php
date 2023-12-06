<?php

namespace Spatie\UrlSigner;

use DateTime;

/**
 * @deprecated Use {@see \Spatie\UrlSigner\Contracts\UrlSigner} instead
 */
interface UrlSigner
{
    public function sign(
        string $url,
        int|DateTime $expiration,
        ?string $signatureKey = null
    ): string;

    public function validate(string $url, ?string $signatureKey = null): bool;
}
