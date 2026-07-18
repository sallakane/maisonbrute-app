<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class StorefrontController extends AbstractController
{
    #[Route('/', name: 'app_home', methods: ['GET'])]
    public function home(ProductRepository $products, CategoryRepository $categories): Response
    {
        $publies = $products->findBy(['publie' => true], ['createdAt' => 'DESC']);

        return $this->render('storefront/home.html.twig', [
            'objet_du_mois' => $publies[0] ?? null,
            'vedettes' => \array_slice($publies, 1, 4),
            'categories' => $categories->findBy([], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/collections', name: 'app_collections', methods: ['GET'])]
    public function collections(CategoryRepository $categories): Response
    {
        return $this->render('storefront/collections.html.twig', [
            'categories' => $categories->findBy(['parent' => null], ['nom' => 'ASC']),
        ]);
    }

    #[Route('/c/{slug}', name: 'app_category', methods: ['GET'])]
    public function category(string $slug, CategoryRepository $categories, ProductRepository $products): Response
    {
        $category = $categories->findOneBySlug($slug);
        if ($category === null) {
            throw new NotFoundHttpException('Cette collection n\'existe pas (ou plus).');
        }

        $items = $products->findBy(
            ['publie' => true],
            ['createdAt' => 'DESC'],
        );
        // Filtre sur la catégorie (ManyToMany) côté PHP — volumes faibles en v1.
        $items = array_values(array_filter(
            $items,
            static fn ($p) => $p->getCategories()->contains($category),
        ));

        return $this->render('storefront/category.html.twig', [
            'category' => $category,
            'produits' => $items,
        ]);
    }

    #[Route('/p/{slug}', name: 'app_product', methods: ['GET'])]
    public function product(string $slug, ProductRepository $products, ReviewRepository $reviews): Response
    {
        $product = $products->findOnePublishedBySlug($slug);
        if ($product === null) {
            throw new NotFoundHttpException('Cet objet n\'est plus à sa place.');
        }

        $similaires = array_values(array_filter(
            $products->findBy(['publie' => true], ['createdAt' => 'DESC']),
            static fn ($p) => $p->getId() !== $product->getId(),
        ));

        $reviewForm = $this->createForm(ReviewType::class, new Review(), [
            'action' => $this->generateUrl('app_product_review', ['slug' => $slug]),
        ]);

        return $this->render('storefront/product.html.twig', [
            'produit' => $product,
            'similaires' => \array_slice($similaires, 0, 4),
            'avis' => $reviews->findModeresParProduit($product),
            'agg' => $reviews->aggregatProduit($product),
            'review_form' => $reviewForm,
        ]);
    }
}
