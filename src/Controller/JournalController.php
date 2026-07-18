<?php

namespace App\Controller;

use App\Repository\JournalArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class JournalController extends AbstractController
{
    #[Route('/journal', name: 'app_journal', methods: ['GET'])]
    public function index(JournalArticleRepository $articles): Response
    {
        $publies = $articles->findPublies();

        return $this->render('journal/index.html.twig', [
            'une' => $publies[0] ?? null,
            'articles' => \array_slice($publies, 1),
        ]);
    }

    #[Route('/journal/{slug}', name: 'app_journal_article', methods: ['GET'])]
    public function article(string $slug, JournalArticleRepository $articles): Response
    {
        $article = $articles->findOnePublieBySlug($slug);
        if ($article === null) {
            throw new NotFoundHttpException('Cet article n\'existe pas (ou n\'est pas encore paru).');
        }

        return $this->render('journal/article.html.twig', [
            'article' => $article,
            'recents' => \array_slice(array_values(array_filter(
                $articles->findPublies(4),
                static fn ($a) => $a->getId() !== $article->getId(),
            )), 0, 3),
        ]);
    }
}
