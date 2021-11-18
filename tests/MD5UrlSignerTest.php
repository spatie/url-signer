<?php

namespace spec\Spatie\UrlSigner;

use DateTime;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Spatie\UrlSigner\Exceptions\InvalidExpiration;
use Spatie\UrlSigner\Exceptions\InvalidSignatureKey;
use Spatie\UrlSigner\MD5UrlSigner;

class MD5UrlSignerTest extends TestCase
{
    /** @test */
    public function it_is_initialized()
    {
        $urlSigner = new MD5UrlSigner('random_monkey');

        $this->assertInstanceOf(MD5UrlSigner::class, $urlSigner);
    }

    /** @test */
    public function it_will_throw_an_exception_for_an_empty_signatureKey()
    {
        $this->expectException(InvalidSignatureKey::class);

        $urlSigner = new MD5UrlSigner('');
    }

    /** @test */
    public function it_returns_false_when_validating_a_forged_url()
    {
        $signedUrl = 'http://myapp.com/somewhereelse/?expires=4594900544&signature=41d5c3a92c6ef94e73cb70c7dcda0859';
        $urlSigner = new MD5UrlSigner('random_monkey');

        $this->assertFalse($urlSigner->validate($signedUrl));
    }

    /** @test */
    public function it_returns_false_when_validating_an_expired_url()
    {
        $signedUrl = 'http://myapp.com/?expires=1123690544&signature=93e02326d7572632dd6edfa2665f2743';
        $urlSigner = new MD5UrlSigner('random_monkey');

        $this->assertFalse($urlSigner->validate($signedUrl));
    }

    /** @test */
    public function it_returns_true_when_validating_an_non_expired_url()
    {
        $url = 'http://myapp.com';
        $expiration = 10000;
        $urlSigner = new MD5UrlSigner('random_monkey');
        $signedUrl = $urlSigner->sign($url, $expiration);

        $this->assertTrue($urlSigner->validate($signedUrl));
    }

    public function unsignedUrlProvider()
    {
        return [
            ['http://myapp.com/?expires=4594900544'],
            ['http://myapp.com/?signature=41d5c3a92c6ef94e73cb70c7dcda0859'],
        ];
    }

    /**
     * @test
     * @dataProvider unsignedUrlProvider
     */
    public function it_returns_false_when_validating_an_unsigned_url($unsignedUrl)
    {
        $urlSigner = new MD5UrlSigner('random_monkey');

        $this->assertFalse($urlSigner->validate($unsignedUrl));
    }

    /** @test */
    public function it_does_a_strict_check_on_expirations()
    {
        $url = 'http://myapp.com';
        $expiration = '30';
        $urlSigner = new MD5UrlSigner('random_monkey');

        $this->expectException(InvalidExpiration::class);

        $urlSigner->sign($url, $expiration);
    }

    public function pastExpirationProvider()
    {
        return [
            [DateTime::createFromFormat('d/m/Y H:i:s', '10/08/2005 18:15:44')],
            [-10],
        ];
    }

    /**
     * @test
     * @dataProvider pastExpirationProvider
     */
    public function it_doesnt_allow_expirations_in_the_past($pastExpiration)
    {
        $url = 'http://myapp.com';
        $urlSigner = new MD5UrlSigner('random_monkey');

        $this->expectException(InvalidExpiration::class);

        $urlSigner->sign($url, $pastExpiration);
    }

    /** @test */
    public function it_keeps_the_urls_query_parameters_intact()
    {
        $url = 'https://myapp.com/?foo=bar&baz=qux';
        $expiration = DateTime::createFromFormat(
            'd/m/Y H:i:s',
            '10/08/2115 18:15:44',
            new DateTimeZone('Europe/Brussels')
        );
        $expected = 'https://myapp.com/?foo=bar&baz=qux&expires=4594900544&signature=728971d9fd0682793d2a1e96b734d949';

        $urlSigner = new MD5UrlSigner('random_monkey');
        $signedUrl = $urlSigner->sign($url, $expiration);

        $this->assertStringContainsString('?foo=bar&baz=qux', $signedUrl);
        $this->assertTrue($urlSigner->validate($signedUrl));
    }
}
