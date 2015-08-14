<?php

namespace Spatie\SignedUrl;

use DateTime;
use League\Url\Components\QueryInterface;
use League\Url\UrlImmutable;
use Spatie\SignedUrl\Exceptions\InvalidExpiration;

class SignedUrl
{
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
     */
    public function __construct($signatureKey, $expiresParameter = 'expires', $signatureParameter = 'signature')
    {
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
     * @param string $expiration
     * @param string $signature
     *
     * @return \League\Url\UrlImmutable
     */
    protected function signUrl(UrlImmutable $url, $expiration, $signature)
    {
        $query = $url->getQuery();

        $query->modify([
            $this->expiresParameter => $expiration,
            $this->signatureParameter => $signature,
        ]);

        $signedUrl = $url->setQuery($query);

        return $signedUrl;
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
        $providedSignature = $query[$this->signatureParameter];

        if ($this->isExpired($expiration)) {
            return false;
        }

        $intendedUrl = $this->getIntendedUrl($url);

        $validSignature = $this->createSignature($intendedUrl, $expiration);

        return $providedSignature === $validSignature;
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
     * Check if an expiration date is expired.
     *
     * @param int $expiration
     *
     * @return bool
     */
    protected function isExpired($expiration)
    {
        return (int) $expiration < (new DateTime())->getTimestamp();
    }

    /**
     * Retrieve the intended URL by stripping off the SignedUrl specific parameters.
     *
     * @param \League\Url\UrlImmutable $url
     *
     * @return \League\Url\UrlImmutable
     */
    protected function getIntendedUrl(UrlImmutable $url)
    {
        $intendedQuery = $url->getQuery();
        $intendedQuery->modify([
            $this->expiresParameter => null,
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
     * @return string
     *
     * @throws \Spatie\SignedUrl\Exceptions\InvalidExpiration
     */
    protected function getExpirationTimestamp($expiration)
    {
        if ($expiration instanceof DateTime) {
            if ($expiration->getTimestamp() - (new DateTime())->getTimestamp() <= 0) {
                throw new InvalidExpiration('Expiration date must be in the future');
            }

            return (string) $expiration->getTimestamp();
        }

        if (is_int($expiration)) {
            if ($expiration <= 0) {
                throw new InvalidExpiration('Expiration date must be in the future');
            }

            return (string) (new DateTime())
                ->modify((int) $expiration.' days')
                ->getTimestamp();
        }

        throw new InvalidExpiration('Expiration date must be an instance of DateTime or an integer');
    }

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

        return md5("{$url}::{$expiration}::{$this->signatureKey}");
    }
}
