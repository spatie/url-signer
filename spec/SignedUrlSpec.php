<?php

namespace spec\Spatie\SignedUrl;

use DateTime;
use DateTimeZone;
use League\Url\UrlImmutable;
use PhpSpec\ObjectBehavior;
use Spatie\SignedUrl\Exceptions\InvalidExpiration;
use Spatie\SignedUrl\SignedUrl;

/**
 * @mixin \Spatie\SignedUrl\SignedUrl
 */
class SignedUrlSpec extends ObjectBehavior
{
    public function let()
    {
        $this->beConstructedWith('mysecretkey');
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType(SignedUrl::class);
    }

    public function it_returns_true_when_validating_a_valid_url()
    {
        $signedUrl = 'http://myapp.com/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859';

        $this->validate($signedUrl)->shouldBeTrue();
    }

    public function it_returns_false_when_validating_an_invalid_url()
    {
        $signedUrl = 'http://myapp.com/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859-INVALID';

        $this->validate($signedUrl)->shouldBeFalse();
    }

    public function it_returns_false_when_validating_an_unsigned_url()
    {
        $this->validate('http://myapp.com/?expires=4594900544')->shouldBeFalse();
        $this->validate('http://myapp.com/?signature=41d5c3a92c6ef94e73cb70c7dcda0859')->shouldBeFalse();
    }

    public function it_can_generate_a_valid_signed_url_that_expires_at_a_certain_time()
    {
        $url = 'http://myapp.com';
        $expiration = DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2115 18:15:44',
            new DateTimeZone('Europe/Brussels'));

        $results = [
            'url' => 'http://myapp.com/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859',
            'expiration' => '4594900544',
            'signature' => '41d5c3a92c6ef94e73cb70c7dcda0859',
        ];

        $signedUrl = $this->generate($url, $expiration);

        $signedUrl->shouldBe($results['url']);
        $signedUrl->shouldHaveExpiration($results['expiration']);
        $signedUrl->shouldHaveSignature($results['signature']);

        $this->validate($signedUrl)->shouldBeTrue();
    }

    public function it_does_a_strict_check_on_expirations()
    {
        $url = 'http://myapp.com';
        $expiration = '30';

        $this->shouldThrow(InvalidExpiration::class)->duringGenerate($url, $expiration);
    }

    public function it_doesnt_allow_expirations_in_the_past()
    {
        $url = 'http://myapp.com';

        $this->shouldThrow(InvalidExpiration::class)
            ->duringGenerate($url, DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2005 18:15:44'));

        $this->shouldThrow(InvalidExpiration::class)
            ->duringGenerate($url, -10);
    }

    public function it_keeps_the_urls_query_parameters_intact()
    {
        $url = 'http://myapp.com/?foo=bar&baz=qux';
        $expiration = DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2115 18:15:44',
            new DateTimeZone('Europe/Brussels'));

        $results = [
            'url' => 'http://myapp.com/?foo=bar&baz=qux&expires=4594900544&signature=ba4c8221ecadb2316b796eb65059fa41',
            'expiration' => '4594900544',
            'signature' => 'ba4c8221ecadb2316b796eb65059fa41',
        ];

        $signedUrl = $this->generate($url, $expiration);

        $signedUrl->shouldBe($results['url']);
        $signedUrl->shouldHaveExpiration($results['expiration']);
        $signedUrl->shouldHaveSignature($results['signature']);

        $this->validate($signedUrl)->shouldBeTrue();
    }

    public function it_can_generate_a_valid_signed_url_that_expires_after_a_relative_amount_of_days()
    {
        $url = 'http://myapp.com';
        $expiration = 30;

        $results = [
            'expiration' => (new DateTime())->modify('30 days')->getTimestamp(),
        ];

        $signedUrl = $this->generate($url, $expiration);

        $signedUrl->shouldHaveExpirationAround($results['expiration']);

        $this->validate($signedUrl)->shouldBeTrue();
    }

    public function getMatchers()
    {
        return [
            'beTrue' => function ($subject) {
                return $subject === true;
            },

            'beFalse' => function ($subject) {
                return $subject === false;
            },

            'haveExpiration' => function ($subject, $expiration, $expirationParameter = 'expires') {
                $url = UrlImmutable::createFromUrl($subject);

                return $url->getQuery()[$expirationParameter] === $expiration;
            },

            // Since some expiration timestamps are created internally, we can't match the exact time. We can however
            // safely assume that the generated expiration is correct if it's within a 5 minute interval of the
            // expected result.
            'haveExpirationAround' => function ($subject, $expiration, $expirationParameter = 'expires') {
                $url = UrlImmutable::createFromUrl($subject);

                return (
                    $url->getQuery()[$expirationParameter] < $expiration + 60 * 5 &&
                    $url->getQuery()[$expirationParameter] > $expiration - 60 * 5
                );
            },

            'haveSignature' => function ($subject, $signature, $signatureQueryParameter = 'signature') {
                $url = UrlImmutable::createFromUrl($subject);

                return $url->getQuery()[$signatureQueryParameter] === $signature;
            },
        ];
    }
}
