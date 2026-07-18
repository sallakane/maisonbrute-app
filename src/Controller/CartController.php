<?php

namespace App\Controller;

use App\Cart\CartService;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CartController extends AbstractController
{
    #[Route('/panier', name: 'app_cart', methods: ['GET'])]
    public function index(CartService $cart): Response
    {
        return $this->render('cart/index.html.twig', [
            'items' => $cart->getItems(),
            'total_cents' => $cart->getTotalCents(),
        ]);
    }

    #[Route('/panier/ajouter/{id}', name: 'app_cart_add', methods: ['POST'])]
    public function add(int $id, Request $request, CartService $cart, ProductRepository $products): Response
    {
        $this->assertCsrf($request, 'cart_add'.$id);

        $product = $products->find($id);
        if ($product === null || !$product->isPublie()) {
            throw new NotFoundHttpException('Objet introuvable.');
        }
        if ($product->isEpuise()) {
            $this->addFlash('error', 'Cet objet est épuisé pour toujours. Vous ne pouvez pas l\'acquérir.');

            return $this->redirectToRoute('app_product', ['slug' => $product->getSlug()]);
        }

        $qty = max(1, (int) $request->request->get('qty', 1));
        $cart->add($id, $qty);
        $this->addFlash('success', sprintf('« %s » a rejoint votre panier. La suite est incertaine.', $product->getNom()));

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/modifier/{id}', name: 'app_cart_update', methods: ['POST'])]
    public function update(int $id, Request $request, CartService $cart): Response
    {
        $this->assertCsrf($request, 'cart_update'.$id);
        $cart->setQuantity($id, (int) $request->request->get('qty', 1));

        return $this->redirectToRoute('app_cart');
    }

    #[Route('/panier/retirer/{id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $id, Request $request, CartService $cart): Response
    {
        $this->assertCsrf($request, 'cart_remove'.$id);
        $cart->remove($id);
        $this->addFlash('success', 'Objet retiré. Il ne partira pas non plus.');

        return $this->redirectToRoute('app_cart');
    }

    private function assertCsrf(Request $request, string $id): void
    {
        if (!$this->isCsrfTokenValid($id, (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
    }
}
