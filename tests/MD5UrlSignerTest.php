<?php

use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use Spatie\UrlSigner\Md5UrlSigner;
use Spatie\UrlSigner\UrlSigner;

it('can be initialized', function () {
    $urlSigner = new Md5UrlSigner('random_monkey');

    expect($urlSigner)->toBeInstanceOf(UrlSigner::class);
});

it('will throw an exception fro an empty signature key', function () {
    new Md5UrlSigner('');
})->throws(InvalidSignatureKey::class);

it('returns false when validating a forged url', function () {
    $signedUrl = 'http://myapp.com/somewhereelse/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859';
    $urlSigner = new Md5UrlSigner('random_monkey');

    expect($urlSigner->validate($signedUrl))->toBeFalse();
});

it('returns false when validating an expired url', function () {
    $signedUrl = 'http://myapp.com/?expires=1123690544&signature=93e02326d7572632dd6edfa2665f2743';
    $urlSigner = new Md5UrlSigner('random_monkey');

    expect($urlSigner->validate($signedUrl))->toBeFalse();
});

it('returns true when validating a non-expired url', function () {
    $url = 'http://myapp.com';

    $expiration = 10000;
    $urlSigner = new Md5UrlSigner('random_monkey');
    $signedUrl = $urlSigner->sign($url, $expiration);

    expect($urlSigner->validate($signedUrl))->toBeTrue();
});

it('returns false when validating an unsigned url', function (string $unsignedUrl) {
    $urlSigner = new Md5UrlSigner('random_monkey');

    expect($urlSigner->validate($unsignedUrl))->toBeFalse();
})->with('unsignedUrls');

it('does not allow expirations in the past', function ($pastExpiration) {
    $url = 'http://myapp.com';
    $urlSigner = new Md5UrlSigner('random_monkey');

    $this->expectException(InvalidExpiration::class);

    $urlSigner->sign($url, $pastExpiration);
})->with([
    [DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2005 18:15:44')],
    [-10],
])->throws(InvalidExpiration::class);

it('keep url query parameters intact', function () {
    $url = 'https://myapp.com/?foo=bar&baz=qux';
    $expiration = DateTime::createFromFormat(
        'd/m/Y H:i:s',
        '10/08/2115 18:15:44',
        new DateTimeZone('Europe/Brussels')
    );
    $expected = 'https://myapp.com/?foo=bar&baz=qux&expires=4594900544&signature=728971d9fd0682793d2a1e96b734d949';

    $urlSigner = new Md5UrlSigner('random_monkey');
    $signedUrl = $urlSigner->sign($url, $expiration);

    expect($signedUrl)->toContain('?foo=bar&baz=qux');
    expect($urlSigner->validate($signedUrl))->toBeTrue();
});

dataset('unsignedUrls', [
    ['http://myapp.com/?expires=4594900544'],
    ['http://myapp.com/?signature=41d5c3a92c6ef94e73cb70c7dcda0859'],
]);
