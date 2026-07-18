<?php

namespace App\Tests\Content;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ContentAndSeoTest extends WebTestCase
{
    #[DataProvider('contentPages')]
    public function testContentPagesLoad(string $url, string $expected): void
    {
        $client = static::createClient();
        $client->request('GET', $url);

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString($expected, (string) $client->getResponse()->getContent());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function contentPages(): iterable
    {
        yield 'à propos' => ['/a-propos', 'Le Manifeste'];
        yield 'cgv' => ['/cgv', 'Conditions générales de vente'];
        yield 'confidentialité' => ['/confidentialite', 'cookies'];
    }

    public function testSitemapIsValidXmlWithKeyUrls(): void
    {
        $client = static::createClient();
        $client->request('GET', '/sitemap.xml');

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('application/xml', (string) $client->getResponse()->headers->get('Content-Type'));

        $body = (string) $client->getResponse()->getContent();
        $xml = new \DOMDocument();
        self::assertTrue($xml->loadXML($body), 'Le sitemap doit être un XML valide.');
        self::assertGreaterThanOrEqual(10, $xml->getElementsByTagName('url')->length);
        self::assertStringContainsString('/p/le-vide-contenu', $body);
        self::assertStringContainsString('/journal/eloge-de-l-attente', $body);
    }

    public function testRobotsTxt(): void
    {
        $client = static::createClient();
        $client->request('GET', '/robots.txt');

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('text/plain', (string) $client->getResponse()->headers->get('Content-Type'));
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Disallow: /admin', $body);
        self::assertStringContainsString('Sitemap:', $body);
    }

    public function testDefaultOpenGraphImageIsPresent(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('og:image', (string) $client->getResponse()->getContent());
    }

    public function testPlanetCounterIsAnchoredOnRealData(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        // Base absurde présente dans l'attribut de départ du compteur.
        self::assertMatchesRegularExpression('/data-planet-counter-start-value="\d{10,}"/', (string) $client->getResponse()->getContent());
    }
}
