<?php

namespace App\Controller;

use App\Cart\CartService;
use App\Checkout\CheckoutData;
use App\Checkout\OrderFactory;
use App\Entity\User;
use App\Form\CheckoutType;
use App\Payment\OrderPaymentFinalizer;
use App\Payment\StripeCheckoutService;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class CheckoutController extends AbstractController
{
    #[Route('/commande', name: 'app_checkout', methods: ['GET', 'POST'])]
    public function checkout(
        Request $request,
        CartService $cart,
        OrderFactory $orderFactory,
        StripeCheckoutService $stripe,
    ): Response {
        if ($cart->isEmpty()) {
            $this->addFlash('error', 'Votre panier est vide : il n\'y a rien à ne pas vous livrer.');

            return $this->redirectToRoute('app_cart');
        }

        /** @var User|null $user */
        $user = $this->getUser();

        $data = new CheckoutData();
        if ($user instanceof User) {
            $data->email = $user->getEmail();
        }

        $form = $this->createForm(CheckoutType::class, $data, [
            'email_locked' => $user instanceof User,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$stripe->isConfigured()) {
                $this->addFlash('error', 'Le paiement (test) n\'est pas configuré sur cet environnement. Renseignez les clés Stripe de test.');

                return $this->redirectToRoute('app_checkout');
            }

            $order = $orderFactory->createFromCart($cart, $data, $user instanceof User ? $user : null);
            $session = $stripe->createSession($order);

            return $this->redirect($session->url, Response::HTTP_SEE_OTHER);
        }

        return $this->render('checkout/checkout.html.twig', [
            'form' => $form,
            'items' => $cart->getItems(),
            'total_cents' => $cart->getTotalCents(),
            'stripe_ready' => $stripe->isConfigured(),
        ]);
    }

    #[Route('/commande/confirmation/{reference}', name: 'app_checkout_confirmation', methods: ['GET'])]
    public function confirmation(
        string $reference,
        OrderRepository $orders,
        CartService $cart,
        StripeCheckoutService $stripe,
        OrderPaymentFinalizer $finalizer,
    ): Response {
        $order = $orders->findOneByReference($reference);
        if ($order === null) {
            throw new NotFoundHttpException('Commande introuvable.');
        }

        // Réconciliation serveur : le webhook reste la source de vérité, mais si l'événement n'est pas
        // encore arrivé (ou pas configuré en local), on re-interroge Stripe directement (autoritatif).
        if (!$order->isPaye()) {
            $paymentIntent = $stripe->fetchPaidPaymentIntent($order);
            if ($paymentIntent !== null) {
                $finalizer->markPaid($order, $paymentIntent === 'paid' ? null : $paymentIntent);
            }
        }

        // Le retour navigateur vide le panier ; il ne fait pas foi pour le paiement.
        $cart->clear();

        return $this->render('checkout/confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
