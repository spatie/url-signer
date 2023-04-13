<?php

namespace Spatie\UrlSigner;

use DateTime;
use JetBrains\PhpStorm\Deprecated;

interface UrlSigner
{
    #[Deprecated]
    public function sign(
        string $url,
        int|DateTime $expiration,
        string $signatureKey = null
    ): string;

    public function signWithDateTimeInterface(
        string $url,
        int|\DateTimeInterface $expiration,
        string $signatureKey = null
    ): string;

    public function validate(string $url, string $signatureKey = null): bool;
}
