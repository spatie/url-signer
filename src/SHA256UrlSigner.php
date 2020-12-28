<?php

namespace Spatie\UrlSigner;

class SHA256UrlSigner extends BaseUrlSigner
{
    /**
     * Generate a token to identify the secure action.
     *
     * @param \League\Url\UrlImmutable|string $url
     * @param string                          $expiration
     *
     * @return string
     */
    protected function createSignature($url, $expiration)
    {
        $url = (string) $url;

        return hash_hmac('sha256', "{$url}::{$expiration}", $this->signatureKey);
    }
}
