<?php

use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use Spatie\UrlSigner\Md5UrlSigner;
use Spatie\UrlSigner\UrlSigner;

beforeEach(function () {
    $this->urlSigner = new Md5UrlSigner('random_monkey');
});

it('can be initialized', function () {
    expect($this->urlSigner)->toBeInstanceOf(UrlSigner::class);
});

it('will throw an exception for an empty signature key', function () {
    new Md5UrlSigner('');
})->throws(InvalidSignatureKey::class);

it('returns false when validating a forged url', function () {
    $signedUrl = 'http://myapp.com/somewhereelse/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859';

    expect($this->urlSigner->validate($signedUrl))->toBeFalse();
});

it('returns false when validating an expired url', function () {
    $signedUrl = 'http://myapp.com/?expires=1123690544&signature=93e02326d7572632dd6edfa2665f2743';

    expect($this->urlSigner->validate($signedUrl))->toBeFalse();
});

it('returns true when validating a non-expired url', function () {
    $url = 'http://myapp.com';

    $expiration = 10000;
    $signedUrl = $this->urlSigner->sign($url, $expiration);

    expect($this->urlSigner->validate($signedUrl))->toBeTrue();
});

it('returns false when validating an unsigned url', function (string $unsignedUrl) {
    expect($this->urlSigner->validate($unsignedUrl))->toBeFalse();
})->with('unsignedUrls');

it('does not allow expirations in the past', function ($pastExpiration) {
    $url = 'http://myapp.com';

    $this->urlSigner->sign($url, $pastExpiration);
})->with([
    [DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2005 18:15:44')],
    [-10],
])->throws(InvalidExpiration::class);

it('will keep url query parameters intact', function () {
    $url = 'https://myapp.com/?foo=bar&baz=qux';
    $expiration = DateTime::createFromFormat(
        'd/m/Y H:i:s',
        '10/08/2115 18:15:44',
        new DateTimeZone('Europe/Brussels')
    );

    $signedUrl = $this->urlSigner->sign($url, $expiration);

    expect($signedUrl)->toContain('?foo=bar&baz=qux');
    expect($this->urlSigner->validate($signedUrl))->toBeTrue();
});

dataset('unsignedUrls', [
    ['http://myapp.com/?expires=4594900544'],
    ['http://myapp.com/?signature=41d5c3a92c6ef94e73cb70c7dcda0859'],
]);

it('using a custom key results in a different signed url', function () {
    $signedUsingRegularKey = $this->urlSigner->sign('https://spatie.be', 5);
    $signedUsingCustomKey = $this->urlSigner->sign('https://spatie.be', 5, 'custom-key');

    expect($signedUsingRegularKey)->not()->toBe($signedUsingCustomKey);
});

it('can sign and validate urls with a custom key', function () {
    $signedUsingCustomKey = $this->urlSigner->sign('https://spatie.be', 5, 'custom-key');

    expect($this->urlSigner->validate($signedUsingCustomKey, 'custom-key'))->toBeTrue();
    expect($this->urlSigner->validate($signedUsingCustomKey, 'wrong-custom-key'))->toBeFalse();
});

it('can sign url which has special characters in the query parameters', function ($url) {
    $expiration = 100;

    $signedUrl = $this->urlSigner->sign($url, $expiration);

    expect($this->urlSigner->validate($signedUrl))->toBeTrue();
})->with([
    ['https://myapp.com/?foo=bar baz'],
    ['https://myapp.com/?foo=bar%20baz'],
    ['https://myapp.com/?foo=bar@baz.com'],
]);

it('can sign url which has reserved query parameters', function ($url) {
    $expiration = 100;

    $signedUrl = $this->urlSigner->sign($url, $expiration);

    expect($this->urlSigner->validate($signedUrl))->toBeTrue();
})->with([
    ['https://myapp.com/?foo=bar&expires=100&signature=abc123'],
    ['https://myapp.com/?foo=bar&expires=100'],
    ['https://myapp.com/?foo=bar&signature=abc123'],
]);