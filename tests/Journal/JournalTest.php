<?php

namespace App\Tests\Journal;

use App\Entity\JournalArticle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class JournalTest extends WebTestCase
{
    public function testJournalIndexListsPublishedArticles(): void
    {
        $client = static::createClient();
        $client->request('GET', '/journal');

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Le Journal', $body);
        // NB : Twig échappe l'apostrophe en HTML (&#039;), on assortit donc sur des titres sans apostrophe.
        self::assertStringContainsString('À la une', $body);
        self::assertStringContainsString('La rareté comme service après-vente', $body);
    }

    public function testArticlePageRendersBodyAndJsonLd(): void
    {
        $client = static::createClient();
        $client->request('GET', '/journal/eloge-de-l-attente');

        $this->assertResponseIsSuccessful();
        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('colis de Schrödinger', $body); // corps HTML rendu
        self::assertStringContainsString('"@type":"BlogPosting"', $body);
        self::assertSelectorExists('script[type="application/ld+json"]');
    }

    public function testUnknownArticleIs404(): void
    {
        $client = static::createClient();
        $client->request('GET', '/journal/nexiste-pas');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testDraftArticleIsNotAccessible(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->createArticle($em, 'brouillon-secret', null);

        $client->request('GET', '/journal/brouillon-secret');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testScheduledFutureArticleIsNotAccessible(): void
    {
        $client = static::createClient();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->createArticle($em, 'a-paraitre', new \DateTimeImmutable('+10 days'));

        $client->request('GET', '/journal/a-paraitre');
        $this->assertResponseStatusCodeSame(404);
    }

    private function createArticle(EntityManagerInterface $em, string $slug, ?\DateTimeImmutable $publieLe): void
    {
        $article = (new JournalArticle())
            ->setTitre('Article '.$slug)
            ->setSlug($slug)
            ->setRubrique('Test')
            ->setTempsLecture(3)
            ->setChapo('Chapô de test.')
            ->setCorps('<p>Corps de test.</p>')
            ->setPublieLe($publieLe);
        $em->persist($article);
        $em->flush();
    }
}
