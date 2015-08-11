<?php

namespace Spatie\SignedUrl;

use DateTime;
use League\Url\UrlImmutable;
use Spatie\SignedUrl\Exceptions\InvalidExpiration;

class SignedUrl
{
    /**
     * The url's query parameter name for the expiration.
     * 
     * @var string
     */
    protected $expirationQueryParameter;

    /**
     * The url's query parameter name for the signature.
     * 
     * @var string
     */
    protected $signatureQueryParameter;

    /**
     * @param string $signatureKey
     * @param string $expirationQueryParameter
     * @param string $signatureQueryParameter
     */
    public function __construct($signatureKey, $expirationQueryParameter = 'e', $signatureQueryParameter = 's')
    {
        $this->signatureKey = $signatureKey;
        $this->expirationQueryParameter = $expirationQueryParameter;
        $this->signatureQueryParameter = $signatureQueryParameter;
    }

    /**
     * Get a secure URL to a controller action.
     *
     * @param string        $url
     * @param \DateTime|int $expiration
     *
     * @return string
     */
    public function generate($url, $expiration)
    {
        $url = UrlImmutable::createFromUrl($url);

        $expiration = $this->getExpirationTimestamp($expiration);
        $signature = $this->createSignature((string) $url, $expiration);

        $query = $url->getQuery();
        $query->modify([
            $this->expirationQueryParameter => $expiration,
            $this->signatureQueryParameter => $signature
        ]);

        $signedUrl = $url->setQuery($query);

        return (string) $signedUrl;
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

        if (!isset($query['e']) || !isset($query['s'])) {
            return false;
        }

        $expiration = $query['e'];
        $signature = $query['s'];

        $intendedQuery = $url->getQuery();
        $intendedQuery->modify(['e' => null, 's' => null]);
        $intendedUrl = $url->setQuery($intendedQuery);

        $match = $this->createSignature((string) $intendedUrl, $expiration);

        return $signature === $match;
    }

    /**
     * Retrieve the expiration timestamp for a link based on an absolute DateTime or a relative number of days.
     *
     * @param \DateTime|int $expiration The expiration date of this link.
     *                                   - DateTime: The value will be used as expiration date
     *                                   - int: The expiration time will be set to X days from now
     *
     * @return string
     *
     * @throws \Spatie\SignedUrl\Exceptions\InvalidExpiration
     */
    protected function getExpirationTimestamp($expiration)
    {
        if ($expiration instanceof DateTime) {
            return (string) $expiration->getTimestamp();
        }

        if (is_int($expiration)) {
            return (string) (new DateTime)
                ->modify((int) $expiration . ' days')
                ->getTimestamp();
        }

        throw new InvalidExpiration;
    }

    /**
     * Generate a token to identify the secure action.
     * 
     * @param string $url
     * @param string $expiration
     * 
     * @return string
     */
    protected function createSignature($url, $expiration)
    {
        return md5("{$url}::{$expiration}::{$this->signatureKey}");
    }
}
