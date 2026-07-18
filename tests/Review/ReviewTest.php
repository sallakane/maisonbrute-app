<?php

namespace App\Tests\Review;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReviewTest extends WebTestCase
{
    public function testAvisPageShowsModeratedReviews(): void
    {
        $client = static::createClient();
        $client->request('GET', '/avis');

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Hélène D.', $body);           // avis modéré (fixture)
        self::assertStringContainsString('0 réception', $body);
    }

    public function testDepositedReviewIsPendingAndHidden(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(ReviewRepository::class);
        $before = \count($repo->findAll());

        $crawler = $client->request('GET', '/avis');
        $form = $crawler->selectButton('Publier mon avis')->form();
        $form['review[auteur]'] = 'Testeur Patient';
        $form['review[note]'] = '5';
        $form['review[attente]'] = '1 semaine';
        $form['review[texte]'] = 'Je viens de commander, et déjà je savoure l\'attente.';
        $client->submit($form);

        $this->assertResponseRedirects('/avis');
        $client->followRedirect();
        self::assertStringContainsString('publié après modération', (string) $client->getResponse()->getContent());

        // L'avis est bien créé mais non modéré, donc pas affiché.
        $reviews = $repo->findBy(['auteur' => 'Testeur Patient']);
        self::assertCount(1, $reviews);
        self::assertFalse($reviews[0]->isModere());
        self::assertCount($before + 1, $repo->findAll());
        self::assertStringNotContainsString('Testeur Patient', (string) $client->getResponse()->getContent());
    }

    public function testProductWithReviewsHasAggregateRating(): void
    {
        $client = static::createClient();
        $client->request('GET', '/p/le-vide-contenu');

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('"@type":"AggregateRating"', $body);
        self::assertStringContainsString('"reviewCount":2', $body);
    }

    public function testProductWithoutReviewsHasNoAggregateRating(): void
    {
        $client = static::createClient();
        // « Le Silence Domestique » n'a aucun avis en fixtures.
        $client->request('GET', '/p/le-silence-domestique');

        $this->assertResponseIsSuccessful();
        self::assertStringNotContainsString('AggregateRating', (string) $client->getResponse()->getContent());
    }

    public function testProductReviewSubmissionIsPending(): void
    {
        $client = static::createClient();
        $repo = static::getContainer()->get(ReviewRepository::class);

        $crawler = $client->request('GET', '/p/le-silence-domestique');
        $form = $crawler->filter('form[action="/p/le-silence-domestique/avis"]')->form();
        $form['review[auteur]'] = 'Amateur de silence';
        $form['review[note]'] = '5';
        $form['review[texte]'] = 'Le silence est arrivé avant le colis. Parfait.';
        $client->submit($form);

        $this->assertResponseRedirects('/p/le-silence-domestique');

        /** @var Review[] $reviews */
        $reviews = $repo->findBy(['auteur' => 'Amateur de silence']);
        self::assertCount(1, $reviews);
        self::assertFalse($reviews[0]->isModere());
        self::assertNotNull($reviews[0]->getProduct());
    }
}
