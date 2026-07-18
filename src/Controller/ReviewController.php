<?php

namespace App\Controller;

use App\Entity\Review;
use App\Form\ReviewType;
use App\Repository\ProductRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class ReviewController extends AbstractController
{
    #[Route('/avis', name: 'app_avis', methods: ['GET', 'POST'])]
    public function index(Request $request, ReviewRepository $reviews, EntityManagerInterface $em): Response
    {
        $review = new Review();
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->depose($em, $review);

            return $this->redirectToRoute('app_avis');
        }

        return $this->render('review/index.html.twig', [
            'form' => $form,
            'avis' => $reviews->findModeres(60),
            'agg' => $reviews->aggregatGlobal(),
        ]);
    }

    #[Route('/p/{slug}/avis', name: 'app_product_review', methods: ['POST'])]
    public function submitForProduct(
        string $slug,
        Request $request,
        ProductRepository $products,
        EntityManagerInterface $em,
    ): Response {
        $product = $products->findOnePublishedBySlug($slug);
        if ($product === null) {
            throw new NotFoundHttpException('Objet introuvable.');
        }

        $review = new Review();
        $review->setProduct($product);
        $form = $this->createForm(ReviewType::class, $review);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->depose($em, $review);
        } else {
            $this->addFlash('error', 'Votre avis n\'a pas pu être enregistré. Vérifiez les champs.');
        }

        return $this->redirectToRoute('app_product', ['slug' => $slug]);
    }

    private function depose(EntityManagerInterface $em, Review $review): void
    {
        $review->setModere(false);
        $em->persist($review);
        $em->flush();

        $this->addFlash('success', 'Merci. Votre avis sera publié après modération — dans un délai, lui aussi, indéterminé.');
    }
}
