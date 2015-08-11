<?php

namespace spec\Spatie\SignedUrl;

use DateTime;
use DateTimeZone;
use League\Url\UrlImmutable;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Spatie\SignedUrl\Exceptions\InvalidExpiration;
use Spatie\SignedUrl\SignedUrl;

/**
 * @mixin \Spatie\SignedUrl\SignedUrl
 */
class SignedUrlSpec extends ObjectBehavior
{
    function let()
    {
        $this->beConstructedWith('mysecretkey');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(SignedUrl::class);
    }

    function it_returns_true_when_validating_a_valid_url()
    {
        $signedUrl = 'http://myapp.com/?e=1439223344&s=2d42f65bd023362c6b61f7432705d811';

        $this->validate($signedUrl)->shouldBeTrue();
    }

    function it_returns_false_when_validating_an_invalid_url()
    {
        $signedUrl = 'http://myapp.com/?e=1439223344&s=2d42f65bd023362c6b61f7432705d811-INVALID';

        $this->validate($signedUrl)->shouldBeFalse();
    }

    function it_returns_false_when_validating_an_unsigned_url()
    {
        $this->validate('http://myapp.com/?e=1439223344')->shouldBeFalse();
        $this->validate('http://myapp.com/?s=2d42f65bd023362c6b61f7432705d811')->shouldBeFalse();
    }

    function it_can_generate_a_valid_signed_url_that_expires_at_a_certain_time()
    {
        $url = 'http://myapp.com';
        $expiration = DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2015 18:15:44', new DateTimeZone('Europe/Brussels'));

        $results = [
            'url' => 'http://myapp.com/?e=1439223344&s=2d42f65bd023362c6b61f7432705d811',
            'expiration' => '1439223344',
            'signature' => '2d42f65bd023362c6b61f7432705d811',
        ];

        $signedUrl = $this->generate($url, $expiration);

        $signedUrl->shouldBe($results['url']);
        $signedUrl->shouldHaveExpiration($results['expiration']);
        $signedUrl->shouldHaveSignature($results['signature']);

        $this->validate($signedUrl)->shouldBeTrue();
    }

    function it_does_a_strict_check_on_expirations()
    {
        $url = 'http://myapp.com';
        $expiration = "30";

        $signedUrl = $this->shouldThrow(InvalidExpiration::class)->duringGenerate($url, $expiration);
    }

    function it_keeps_the_urls_query_parameters_intact()
    {
        $url = 'http://myapp.com/?foo=bar&baz=qux';
        $expiration = DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2015 18:15:44', new DateTimeZone('Europe/Brussels'));

        $results = [
            'url' => 'http://myapp.com/?foo=bar&baz=qux&e=1439223344&s=e5276b561724934373f8273f10e63a87',
            'expiration' => '1439223344',
            'signature' => 'e5276b561724934373f8273f10e63a87',
        ];

        $signedUrl = $this->generate($url, $expiration);

        $signedUrl->shouldBe($results['url']);
        $signedUrl->shouldHaveExpiration($results['expiration']);
        $signedUrl->shouldHaveSignature($results['signature']);

        $this->validate($signedUrl)->shouldBeTrue();
    }

    function it_can_generate_a_valid_signed_url_that_expires_after_a_relative_amount_of_days()
    {
        $url = 'http://myapp.com';
        $expiration = 30;
        
        $results = [
            'expiration' => (new DateTime)->modify('30 days')->getTimestamp()
        ];

        $signedUrl = $this->generate($url, $expiration);

        $signedUrl->shouldHaveExpirationAround($results['expiration']);

        $this->validate($signedUrl)->shouldBeTrue();
    }

    function getMatchers()
    {
        return [
            'beTrue' => function($subject) {
                return $subject === true;
            },

            'beFalse' => function($subject) {
                return $subject === false;
            },
            
            'haveExpiration' => function($subject, $expiration, $expirationQueryParameter = 'e') {
                $url = UrlImmutable::createFromUrl($subject);
                return $url->getQuery()[$expirationQueryParameter] === $expiration;
            },
            
            // Since some expiration timestamps are created internally, we can't match the exact time. We can however
            // safely assume that the generated expiration is correct if it's within a 5 minute interval of the
            // expected result.
            'haveExpirationAround' => function($subject, $expiration, $expirationQueryParameter = 'e') {
                $url = UrlImmutable::createFromUrl($subject);

                return (
                    $url->getQuery()[$expirationQueryParameter] < $expiration + 60*5 &&
                    $url->getQuery()[$expirationQueryParameter] > $expiration - 60*5
                );
            },
            
            'haveSignature' => function($subject, $signature, $signatureQueryParameter = 's') {
                $url = UrlImmutable::createFromUrl($subject);
                return $url->getQuery()[$signatureQueryParameter] === $signature;
            }
        ];
    }
}
