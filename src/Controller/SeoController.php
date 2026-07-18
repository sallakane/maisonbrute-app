<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\JournalArticleRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SeoController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', methods: ['GET'])]
    public function sitemap(
        ProductRepository $products,
        CategoryRepository $categories,
        JournalArticleRepository $articles,
    ): Response {
        $urls = [];
        $add = function (string $route, array $params = [], ?\DateTimeInterface $lastmod = null, string $priority = '0.5') use (&$urls): void {
            $urls[] = [
                'loc' => $this->generateUrl($route, $params, UrlGeneratorInterface::ABSOLUTE_URL),
                'lastmod' => $lastmod?->format('Y-m-d'),
                'priority' => $priority,
            ];
        };

        $add('app_home', [], null, '1.0');
        $add('app_collections', [], null, '0.8');
        $add('app_journal', [], null, '0.8');
        $add('app_avis', [], null, '0.6');
        $add('app_apropos', [], null, '0.5');
        $add('app_cgv', [], null, '0.3');
        $add('app_confidentialite', [], null, '0.3');

        foreach ($categories->findBy([], ['nom' => 'ASC']) as $category) {
            $add('app_category', ['slug' => $category->getSlug()], null, '0.7');
        }
        foreach ($products->findBy(['publie' => true], ['createdAt' => 'DESC']) as $product) {
            $add('app_product', ['slug' => $product->getSlug()], $product->getCreatedAt(), '0.7');
        }
        foreach ($articles->findPublies() as $article) {
            $add('app_journal_article', ['slug' => $article->getSlug()], $article->getPublieLe(), '0.6');
        }

        $response = $this->render('seo/sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');

        return $response;
    }

    #[Route('/robots.txt', name: 'app_robots', methods: ['GET'])]
    public function robots(): Response
    {
        $sitemap = $this->generateUrl('app_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $body = "User-agent: *\nAllow: /\nDisallow: /admin\nDisallow: /commande\nDisallow: /panier\n\nSitemap: {$sitemap}\n";

        return new Response($body, Response::HTTP_OK, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
