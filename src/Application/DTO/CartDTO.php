<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CartDTO
{
    /**
     * @param CartItemDTO[] $items
     */
    public function __construct(
        public readonly string $cartId,
        public readonly array $items,
        public readonly string $totalPrice,
        public readonly int $itemCount,
    ) {
    }
}
