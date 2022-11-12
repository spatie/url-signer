<?php

namespace Spatie\UrlSigner;

use DateTime;
use League\Uri\Http;
use League\Uri\QueryString;
use Psr\Http\Message\UriInterface;
use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;

abstract class BaseUrlSigner implements UrlSigner
{
    public function __construct(
        protected string $defaultSignatureKey,
        protected string $expiresParameterName = 'expires',
        protected string $signatureParameterName = 'signature'
    ) {
        if ($this->defaultSignatureKey == '') {
            throw InvalidSignatureKey::signatureEmpty();
        }
    }

    abstract protected function createSignature(
        string $url,
        string $expiration,
        string $signatureKey,
    ): string;

    public function sign(
        string $url,
        int|DateTime $expiration,
        string $signatureKey = null,
    ): string {
        $signatureKey ??= $this->defaultSignatureKey;

        $url = Http::createFromString($url);

        $expiration = $this->getExpirationTimestamp($expiration);

        $signature = $this->createSignature((string) $url, $expiration, $signatureKey);

        return (string) $this->signUrl($url, $expiration, $signature);
    }

    /**
     * Add expiration and signature query parameters to an url.
     *
     * @param UriInterface $url
     * @param string       $expiration
     * @param string       $signature
     *
     * @return \League\Url\UrlImmutable
     */
    protected function signUrl(UriInterface $url, string $expiration, $signature)
    {
        $query = QueryString::extract($url->getQuery());

        $query[$this->expiresParameterName] = $expiration;
        $query[$this->signatureParameterName] = $signature;

        return $url->withQuery($this->buildQueryStringFromArray($query));
    }

    public function validate(string $url, string $signatureKey = null): bool
    {
        $signatureKey ??= $this->defaultSignatureKey;

        $url = Http::createFromString($url);

        $query = QueryString::extract($url->getQuery());

        if ($this->isMissingAQueryParameter($query)) {
            return false;
        }

        $expiration = $query[$this->expiresParameterName];

        if (! $this->isFuture($expiration)) {
            return false;
        }

        if (! $this->hasValidSignature($url, $signatureKey)) {
            return false;
        }

        return true;
    }



    protected function isMissingAQueryParameter(array $query): bool
    {
        if (! isset($query[$this->expiresParameterName])) {
            return true;
        }

        if (! isset($query[$this->signatureParameterName])) {
            return true;
        }

        return false;
    }

    protected function isFuture(int $timestamp): bool
    {
        return $timestamp >= (new DateTime())->getTimestamp();
    }

    protected function getIntendedUrl(UriInterface $url): UriInterface
    {
        $intendedQuery = QueryString::extract($url->getQuery());

        unset($intendedQuery[$this->expiresParameterName]);
        unset($intendedQuery[$this->signatureParameterName]);

        return $url->withQuery($this->buildQueryStringFromArray($intendedQuery) ?? '');
    }

    protected function getExpirationTimestamp(DateTime|int $expiration): string
    {
        if (is_int($expiration)) {
            $expiration = (new DateTime())->modify($expiration.' days');
        }

        if (! $expiration instanceof DateTime) {
            throw InvalidExpiration::wrongType();
        }

        if (! $this->isFuture($expiration->getTimestamp())) {
            throw InvalidExpiration::isInPast();
        }

        return (string) $expiration->getTimestamp();
    }

    protected function hasValidSignature(
        UriInterface|string $url,
        string $signatureKey
    ): bool
    {
        $query = QueryString::extract($url->getQuery());

        $expiration = $query[$this->expiresParameterName];
        $providedSignature = $query[$this->signatureParameterName];

        $intendedUrl = $this->getIntendedUrl($url);

        $validSignature = $this->createSignature($intendedUrl, $expiration, $signatureKey);

        return hash_equals($validSignature, $providedSignature);
    }

    protected function buildQueryStringFromArray(array $query): ?string
    {
        $buildQuery = [];

        foreach ($query as $key => $value) {
            $buildQuery[] = [$key, $value];
        }

        return QueryString::build($buildQuery);
    }
}
