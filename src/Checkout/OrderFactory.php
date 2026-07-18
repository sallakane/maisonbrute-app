<?php

namespace App\Checkout;

use App\Cart\CartService;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\StatutPaiement;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Crée une commande « en attente de paiement » à partir du panier et des données du tunnel.
 * Fige (snapshot) le nom et le prix de chaque objet, et génère une référence MB-XXXXX unique.
 */
class OrderFactory
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OrderRepository $orders,
    ) {
    }

    public function createFromCart(CartService $cart, CheckoutData $data, ?User $customer): Order
    {
        $items = $cart->getItems();
        if ($items === []) {
            throw new \DomainException('Impossible de commander un panier vide.');
        }

        $order = new Order();
        $order->setReference($this->generateReference());
        $order->setCustomer($customer);
        if ($customer === null) {
            $order->setEmailInvite($data->email);
        }
        $order->setLivraisonNom((string) $data->nom);
        $order->setLivraisonLigne1((string) $data->ligne1);
        $order->setLivraisonCp((string) $data->cp);
        $order->setLivraisonVille((string) $data->ville);
        $order->setLivraisonPays($data->pays);
        $order->setTransporteur(Transporteur::nom((string) $data->transporteur));
        $order->setFraisPortCents(Transporteur::fraisCents((string) $data->transporteur));
        $order->setStatutPaiement(StatutPaiement::EnAttente);

        $total = $order->getFraisPortCents();
        foreach ($items as $row) {
            $product = $row['product'];
            $item = (new OrderItem())
                ->setProduct($product)
                ->setProductNom($product->getNom())
                ->setProductSku($product->getSku())
                ->setPrixUnitaireCents($product->getPrixCents())
                ->setQuantite($row['qty']);
            $order->addItem($item);
            $total += $item->getLigneTotalCents();
        }
        $order->setMontantCents($total);

        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }

    private function generateReference(): string
    {
        do {
            $ref = 'MB-'.strtoupper(bin2hex(random_bytes(3)));
        } while ($this->orders->findOneByReference($ref) !== null);

        return $ref;
    }
}
