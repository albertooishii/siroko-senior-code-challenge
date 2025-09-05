<?php

declare(strict_types=1);

namespace App\Application\Command;

use InvalidArgumentException;

final readonly class AddItemToCartCommand
{
    public function __construct(
        public int $cartId,
        public int $productId,
        public int $quantity,
    ) {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Quantity must be positive');
        }
    }
}
