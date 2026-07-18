<?php

namespace App\Cart;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Panier stocké en session (invité ou connecté) : une simple carte productId => quantité.
 * Les entités Product sont réhydratées à la lecture ; les objets non publiés ou disparus
 * sont ignorés silencieusement.
 */
class CartService
{
    private const SESSION_KEY = 'cart';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly ProductRepository $products,
    ) {
    }

    public function add(int $productId, int $qty = 1): void
    {
        $cart = $this->raw();
        $cart[$productId] = max(1, ($cart[$productId] ?? 0) + $qty);
        $this->save($cart);
    }

    public function setQuantity(int $productId, int $qty): void
    {
        $cart = $this->raw();
        if ($qty <= 0) {
            unset($cart[$productId]);
        } else {
            $cart[$productId] = $qty;
        }
        $this->save($cart);
    }

    public function remove(int $productId): void
    {
        $cart = $this->raw();
        unset($cart[$productId]);
        $this->save($cart);
    }

    public function clear(): void
    {
        $this->save([]);
    }

    public function isEmpty(): bool
    {
        return $this->raw() === [];
    }

    /** Nombre total d'articles (somme des quantités). */
    public function count(): int
    {
        return array_sum($this->raw());
    }

    /**
     * @return list<array{product: Product, qty: int, ligneCents: int}>
     */
    public function getItems(): array
    {
        $cart = $this->raw();
        if ($cart === []) {
            return [];
        }

        $items = [];
        foreach ($this->products->findBy(['id' => array_keys($cart), 'publie' => true]) as $product) {
            $qty = (int) $cart[$product->getId()];
            $items[] = [
                'product' => $product,
                'qty' => $qty,
                'ligneCents' => $product->getPrixCents() * $qty,
            ];
        }

        return $items;
    }

    public function getTotalCents(): int
    {
        $total = 0;
        foreach ($this->getItems() as $item) {
            $total += $item['ligneCents'];
        }

        return $total;
    }

    /**
     * @return array<int, int>
     */
    private function raw(): array
    {
        /** @var array<int, int> $cart */
        $cart = $this->requestStack->getSession()->get(self::SESSION_KEY, []);

        return $cart;
    }

    /**
     * @param array<int, int> $cart
     */
    private function save(array $cart): void
    {
        $this->requestStack->getSession()->set(self::SESSION_KEY, $cart);
    }
}
