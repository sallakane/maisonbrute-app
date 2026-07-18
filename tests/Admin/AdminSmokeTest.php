<?php

namespace App\Tests\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AdminSmokeTest extends WebTestCase
{
    public function testAdminRequiresAuthentication(): void
    {
        $client = static::createClient();
        $client->request('GET', '/admin');

        $this->assertResponseRedirects();
        self::assertStringContainsString('/login', (string) $client->getResponse()->headers->get('Location'));
    }

    public function testAdminDashboardLoadsForAdmin(): void
    {
        $client = static::createClient();
        $admin = $this->getAdmin();
        $client->loginUser($admin);

        $client->request('GET', '/admin');
        $this->assertResponseIsSuccessful();
    }

    public function testProductCrudListsSeededProducts(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());

        $crawler = $client->request('GET', '/admin/product');
        $this->assertResponseIsSuccessful();

        $body = $client->getResponse()->getContent();
        self::assertStringContainsString('Le Vide Contenu', (string) $body);
        self::assertStringContainsString('Le Silence Domestique', (string) $body);
    }

    public function testProductNewFormRenders(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());

        $client->request('GET', '/admin/product/new');
        $this->assertResponseIsSuccessful();

        $body = (string) $client->getResponse()->getContent();
        self::assertStringContainsString('Prix', $body);
        self::assertStringContainsString('Description (vraie)', $body);
    }

    public function testAllCrudIndexesLoad(): void
    {
        $client = static::createClient();
        $client->loginUser($this->getAdmin());

        foreach (['category', 'maison', 'user', 'order', 'review'] as $entity) {
            $client->request('GET', '/admin/'.$entity);
            $this->assertResponseIsSuccessful(sprintf('CRUD "%s" doit répondre 200.', $entity));
        }
    }

    private function getAdmin(): User
    {
        /** @var UserRepository $repo */
        $repo = static::getContainer()->get(UserRepository::class);
        $admin = $repo->findOneBy(['email' => 'admin@maisonbrute.fr']);
        self::assertNotNull($admin, 'Le compte admin de démo doit exister (charger les fixtures).');

        return $admin;
    }
}
