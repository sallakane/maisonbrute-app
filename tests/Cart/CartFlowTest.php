<?php

namespace App\Tests\Cart;

use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CartFlowTest extends WebTestCase
{
    public function testAddToCartFromProductPage(): void
    {
        $client = static::createClient();

        // La fiche produit porte le formulaire d'ajout (avec jeton CSRF).
        $crawler = $client->request('GET', '/p/le-vide-contenu');
        $this->assertResponseIsSuccessful();

        $form = $crawler->filter('form[action="/panier/ajouter/'.$this->productId('le-vide-contenu').'"]')->form();
        $client->submit($form);

        $this->assertResponseRedirects('/panier');
        $client->followRedirect();
        self::assertSelectorTextContains('body', 'Le Vide Contenu');
        self::assertStringContainsString('rejoint votre panier', (string) $client->getResponse()->getContent());
    }

    public function testCheckoutRedirectsWhenCartEmpty(): void
    {
        $client = static::createClient();
        $client->request('GET', '/commande');
        $this->assertResponseRedirects('/panier');
    }

    private function productId(string $slug): int
    {
        /** @var ProductRepository $repo */
        $repo = static::getContainer()->get(ProductRepository::class);
        $product = $repo->findOneBy(['slug' => $slug]);
        self::assertNotNull($product);

        return (int) $product->getId();
    }
}
