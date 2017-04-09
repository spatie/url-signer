<?php

namespace Spatie\UrlSigner;

use DateTime;
use League\Url\Components\QueryInterface;
use League\Url\UrlImmutable;
use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;

abstract class BaseUrlSigner implements UrlSigner
{
    /**
     * The key that is used to generate secure signatures.
     *
     * @var string
     */
    protected $signatureKey;

    /**
     * The URL's query parameter name for the expiration.
     *
     * @var string
     */
    protected $expiresParameter;

    /**
     * The URL's query parameter name for the signature.
     *
     * @var string
     */
    protected $signatureParameter;

    /**
     * @param string $signatureKey
     * @param string $expiresParameter
     * @param string $signatureParameter
     *
     * @throws InvalidSignatureKey
     */
    public function __construct($signatureKey, $expiresParameter = 'expires', $signatureParameter = 'signature')
    {
        if ($signatureKey == '') {
            throw new InvalidSignatureKey('The signature key is empty');
        }

        $this->signatureKey = $signatureKey;
        $this->expiresParameter = $expiresParameter;
        $this->signatureParameter = $signatureParameter;
    }

    /**
     * Get a secure URL to a controller action.
     *
     * @param string        $url
     * @param \DateTime|int $expiration
     *
     * @return string
     */
    public function sign($url, $expiration)
    {
        $url = UrlImmutable::createFromUrl($url);

        $expiration = $this->getExpirationTimestamp($expiration);
        $signature = $this->createSignature((string) $url, $expiration);

        return (string) $this->signUrl($url, $expiration, $signature);
    }

    /**
     * Add expiration and signature query parameters to an url.
     *
     * @param \League\Url\UrlImmutable $url
     * @param string                   $expiration
     * @param string                   $signature
     *
     * @return \League\Url\UrlImmutable
     */
    protected function signUrl(UrlImmutable $url, $expiration, $signature)
    {
        $query = $url->getQuery();

        $query->modify([
            $this->expiresParameter   => $expiration,
            $this->signatureParameter => $signature,
        ]);

        $urlSigner = $url->setQuery($query);

        return $urlSigner;
    }

    /**
     * Validate a signed url.
     *
     * @param string $url
     *
     * @return bool
     */
    public function validate($url)
    {
        $url = UrlImmutable::createFromUrl($url);

        $query = $url->getQuery();

        if ($this->isMissingAQueryParameter($query)) {
            return false;
        }

        $expiration = $query[$this->expiresParameter];

        if (!$this->isFuture($expiration)) {
            return false;
        }

        if (!$this->hasValidSignature($url)) {
            return false;
        }

        return true;
    }

    /**
     * Check if a query is missing a necessary parameter.
     *
     * @param \League\Url\Components\QueryInterface $query
     *
     * @return bool
     */
    protected function isMissingAQueryParameter(QueryInterface $query)
    {
        if (!isset($query[$this->expiresParameter])) {
            return true;
        }

        if (!isset($query[$this->signatureParameter])) {
            return true;
        }

        return false;
    }

    /**
     * Check if a timestamp is in the future.
     *
     * @param int $timestamp
     *
     * @return bool
     */
    protected function isFuture($timestamp)
    {
        return ((int) $timestamp) >= (new DateTime())->getTimestamp();
    }

    /**
     * Retrieve the intended URL by stripping off the UrlSigner specific parameters.
     *
     * @param \League\Url\UrlImmutable $url
     *
     * @return \League\Url\UrlImmutable
     */
    protected function getIntendedUrl(UrlImmutable $url)
    {
        $intendedQuery = $url->getQuery();

        $intendedQuery->modify([
            $this->expiresParameter   => null,
            $this->signatureParameter => null,
        ]);

        $intendedUrl = $url->setQuery($intendedQuery);

        return $intendedUrl;
    }

    /**
     * Retrieve the expiration timestamp for a link based on an absolute DateTime or a relative number of days.
     *
     * @param \DateTime|int $expiration The expiration date of this link.
     *                                  - DateTime: The value will be used as expiration date
     *                                  - int: The expiration time will be set to X days from now
     *
     * @throws \Spatie\UrlSigner\Exceptions\InvalidExpiration
     *
     * @return string
     */
    protected function getExpirationTimestamp($expiration)
    {
        if (is_int($expiration)) {
            $expiration = (new DateTime())->modify((int) $expiration.' days');
        }

        if (!$expiration instanceof DateTime) {
            throw new InvalidExpiration('Expiration date must be an instance of DateTime or an integer');
        }

        if (!$this->isFuture($expiration->getTimestamp())) {
            throw new InvalidExpiration('Expiration date must be in the future');
        }

        return (string) $expiration->getTimestamp();
    }

    /**
     * Determine if the url has a forged signature.
     *
     * @param \League\Url\UrlImmutable $url
     *
     * @return bool
     */
    protected function hasValidSignature(UrlImmutable $url)
    {
        $query = $url->getQuery();

        $expiration = $query[$this->expiresParameter];
        $providedSignature = $query[$this->signatureParameter];

        $intendedUrl = $this->getIntendedUrl($url);

        $validSignature = $this->createSignature($intendedUrl, $expiration);

        return hash_equals($validSignature, $providedSignature);
    }
}
