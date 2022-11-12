<?php

namespace Spatie\UrlSigner\Support;

class Url
{
    public static function queryParameters(string $url): array
    {
        $baseUrl = Str::after($url, '?');

        parse_str($baseUrl, $queryParameters);

        return $queryParameters;
    }

    public static function addQueryParameters(string $url, array $newQueryParameters): string
    {
        $urlQueryParameters = self::queryParameters($url);

        $allQueryParameters = array_merge($urlQueryParameters, $newQueryParameters);

        $baseUrl = Str::before($url, '?');

        return $baseUrl.'?'.http_build_query($allQueryParameters);
    }

    public static function withoutParameters(string $url, array $unwantedParameterNames = []): string
    {
        $urlQueryParameters = self::queryParameters($url);

        foreach ($unwantedParameterNames as $name) {
            if (array_key_exists($name, $urlQueryParameters)) {
                unset($urlQueryParameters[$name]);
            }
        }

        $baseUrl = Str::before($url, '?');

        if (count($urlQueryParameters) === 0) {
            return $baseUrl;
        }

        return $baseUrl.'?'.http_build_query($urlQueryParameters);
    }
}
