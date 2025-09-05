<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class RemoveItemFromCartCommand
{
    public function __construct(
        public int $cartId,
        public int $productId,
    ) {
    }
}
