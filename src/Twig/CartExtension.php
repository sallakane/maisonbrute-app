<?php

namespace App\Twig;

use App\Cart\CartService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CartExtension extends AbstractExtension
{
    public function __construct(private readonly CartService $cart)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cart_count', $this->cartCount(...)),
        ];
    }

    public function cartCount(): int
    {
        return $this->cart->count();
    }
}
