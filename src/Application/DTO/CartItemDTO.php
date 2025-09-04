<?php

declare(strict_types=1);

namespace App\Application\DTO;

final class CartItemDTO
{
    public function __construct(
        public readonly int $productId,
        public readonly string $productName,
        public readonly string $unitPrice,
        public readonly int $quantity,
        public readonly string $subtotal,
    ) {
    }
}
