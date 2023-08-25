<?php

namespace Spatie\UrlSigner;

class Sha256UrlSigner extends AbstractUrlSigner
{
    protected function createSignature(
        string $url,
        string $expiration,
        string $signatureKey
    ): string {
        return hash_hmac('sha256', "{$url}::{$expiration}", $signatureKey);
    }
}
