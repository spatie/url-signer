<?php

use Spatie\UrlSigner\Support\Str;

it('can get the string after a string', function (string $string, string $after, string $expected) {
    $actual = Str::after($string, $after);

    expect($actual)->toBe($expected);
})->with([
    ['https://spatie.be?hey', '?', 'hey'],
    ['https://spatie.be', '?', ''],
    ['https://spatie.be?', '?', ''],
    ['https://?spatie.be?', '?', 'spatie.be?'],
    ['https://?spatie.be?', '!', ''],
]);

it('can get the string before a string', function (string $string, string $after, string $expected) {
    $actual = Str::before($string, $after);

    expect($actual)->toBe($expected);
})->with([
    ['https://spatie.be?hey', '?', 'https://spatie.be'],
    ['https://spatie.be', '?', 'https://spatie.be'],
    ['https://?spatie.be?', '?', 'https://'],
    ['https://?spatie.be?', '!', 'https://?spatie.be?'],
    ['?https://spatie.be?hey', '?', ''],

]);
