<?php

use Spatie\UrlSigner\Support\Url;

it('can get the query parameters of a URL', function (string $url, array $expectedParameters) {
    $actualParameters = Url::queryParameters($url);

    expect($expectedParameters)->toBe($actualParameters);
})->with([
    ['spatie.be?a=1&b=2', ['a' => '1', 'b' => '2']],
    //['spatie.be', []],
]);

it('can add query parameters to a URL', function (string $url, array $add, string $expectedUrl) {
    $actualUrl = Url::addQueryParameters($url, $add);

    expect($expectedUrl)->toBe($actualUrl);
})->with([
    ['spatie.be', ['a' => 1, 'b' => 2], 'spatie.be?a=1&b=2'],
]);
