<?php

declare(strict_types=1);

namespace App\Application\Query;

use App\Application\DTO\CartDTO;
use App\Application\DTO\CartItemDTO;
use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\ValueObject\CartId;

final readonly class GetCartQueryHandler
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
    ) {
    }

    public function handle(GetCartQuery $query): ?CartDTO
    {
        $cartId = new CartId($query->cartId);
        $cart = $this->cartRepository->findByCartId($cartId);

        if (!$cart) {
            return null;
        }

        $items = [];
        foreach ($cart->getCartItems() as $item) {
            $items[] = new CartItemDTO(
                productId: $item->getProduct()->getId(),
                productName: $item->getProduct()->getName(),
                unitPrice: $item->getUnitPrice(),
                quantity: $item->getQuantity(),
                subtotal: $item->getSubtotal()
            );
        }

        return new CartDTO(
            cartId: $cart->getSessionId() ?? '',
            items: $items,
            totalPrice: $cart->getTotalPrice() ?? '0.00',
            itemCount: $cart->getCartItems()->count()
        );
    }
}
