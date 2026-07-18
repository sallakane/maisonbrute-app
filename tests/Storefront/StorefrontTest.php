<?php

namespace App\Tests\Storefront;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class StorefrontTest extends WebTestCase
{
    public function testHomeLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', '');
        self::assertStringContainsString('conjecture', (string) $client->getResponse()->getContent());
    }

    public function testCollectionsLists(): void
    {
        $client = static::createClient();
        $client->request('GET', '/collections');

        $this->assertResponseIsSuccessful();
        self::assertStringContainsString('Reliques', (string) $client->getResponse()->getContent());
    }

    public function testCategoryShowsPublishedProductsOnly(): void
    {
        $client = static::createClient();
        $client->request('GET', '/c/reliques');

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Le Vide Contenu', $body);
        // "La Patine Anticipée" est non publiée : elle ne doit pas apparaître.
        self::assertStringNotContainsString('La Patine Anticipée', $body);
    }

    public function testUnknownCategoryIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/c/nexiste-pas');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testProductPageRendersWithJsonLdAndBothDescriptions(): void
    {
        $client = static::createClient();
        $client->request('GET', '/p/le-vide-contenu');

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();

        // SEO : JSON-LD schema.org/Product
        self::assertStringContainsString('"@type":"Product"', $body);
        self::assertStringContainsString('schema.org/InStock', $body);
        self::assertSelectorExists('script[type="application/ld+json"]');

        // Le contraste satirique : les deux descriptions sont présentes.
        self::assertStringContainsString('scellé sur du vide', $body);       // marketing
        self::assertStringContainsString('restera vide', $body);             // vraie
    }

    public function testUnpublishedProductIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/p/patine-anticipee');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testUnknownProductIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/p/nexiste-pas');
        $this->assertResponseStatusCodeSame(404);
    }
}
