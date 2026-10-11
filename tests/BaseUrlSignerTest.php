<?php

use Spatie\UrlSigner\BaseUrlSigner;

it('returns false for malformed signing parameters in the deprecated signer', function (string $parameter) {
    $signer = new class('secret') extends BaseUrlSigner
    {
        protected function createSignature(string $url, string $expiration, string $signatureKey): string
        {
            return hash_hmac('sha256', "{$url}::{$expiration}", $signatureKey);
        }
    };

    $signedUrl = $signer->sign('https://example.com/download', 3600);

    expect($signer->validate("{$signedUrl}&{$parameter}"))->toBeFalse();
})->with([
    'array expiration' => ['expires[]=9999999999'],
    'array signature' => ['signature[]=invalid'],
]);
