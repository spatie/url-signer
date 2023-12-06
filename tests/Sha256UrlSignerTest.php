<?php

use Spatie\UrlSigner\Contracts\UrlSigner;
use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use Spatie\UrlSigner\Sha256UrlSigner;

beforeEach(function () {
    $this->urlSigner = new Sha256UrlSigner('random_monkey');
});

it('can be initialized', function () {
    expect($this->urlSigner)->toBeInstanceOf(UrlSigner::class);
});

it('will throw an exception from an empty signature key', function () {
    new Sha256UrlSigner('');
})->throws(InvalidSignatureKey::class);

it('returns false when validating a forged url', function () {
    $signedUrl = 'http://myapp.com/somewhereelse/?expires=4594900544&signature=79379e8012ebebf75a4679099477c42b16bea303e3e1cb5cb59040ab6e895f08';

    expect($this->urlSigner->validate($signedUrl))->toBeFalse();
});

it('returns false when validating an expired url', function () {
    $signedUrl = 'http://myapp.com/?expires=1123690544&signature=28a85b78db3c09bcc8194c0eff9a3db7c276371b1380296f910b77277e4f88d1';

    expect($this->urlSigner->validate($signedUrl))->toBeFalse();
});

it('returns true when validating a non-expired url', function () {
    $url = 'http://myapp.com/';

    $expiration = 10000;
    $signedUrl = $this->urlSigner->sign($url, $expiration);

    expect($this->urlSigner->validate($signedUrl))->toBeTrue();
});

it('can sign with a DateTimeImmutable instance', function () {
    $url = 'http://myapp.com/';

    $expiration = (new DateTimeImmutable())->modify('10000 seconds');
    $signedUrl = $this->urlSigner->sign($url, $expiration);

    expect($this->urlSigner->validate($signedUrl))->toBeTrue();
});

dataset('unsignedUrls', [
    ['http://myapp.com/?expires=4594900544'],
    ['http://myapp.com/?signature=79379e8012ebebf75a4679099477c42b16bea303e3e1cb5cb59040ab6e895f08'],
]);

it('returns false when validating an unsigned url', function (string $unsignedUrl) {
    expect($this->urlSigner->validate($unsignedUrl))->toBeFalse();
})->with('unsignedUrls');

it('does not allow expirations in the past', function ($pastExpiration) {
    $url = 'http://myapp.com/';

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