<?php

namespace Spatie\SignedUrl;

use DateTime;
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
    public function generate($url, $expiration)
    {
        $url = UrlImmutable::createFromUrl($url);

        $expiration = $this->getExpirationTimestamp($expiration);
        $signature = $this->createSignature((string) $url, $expiration);

        $query = $url->getQuery();
        $query->modify([
            $this->expiresParameter => $expiration,
            $this->signatureParameter => $signature,
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

        if (
            !isset($query[$this->expiresParameter]) ||
            !isset($query[$this->signatureParameter])
        ) {
            return false;
        }

        $expiration = $query[$this->expiresParameter];
        $signature = $query[$this->signatureParameter];

        $intendedQuery = $url->getQuery();
        $intendedQuery->modify([
            $this->expiresParameter => null,
            $this->signatureParameter => null,
        ]);
        $intendedUrl = $url->setQuery($intendedQuery);

        $match = $this->createSignature((string) $intendedUrl, $expiration);

        return $signature === $match;
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

        throw new InvalidExpiration("Expiration date must be an instance of \DateTime or an integer");
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
