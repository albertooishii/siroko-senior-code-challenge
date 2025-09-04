<?php

declare(strict_types=1);

namespace App\Application\Command;

final readonly class CheckoutCartCommand
{
    public function __construct(
        public string $cartId,
        public string $customerEmail,
        public ?string $customerName = null,
    ) {
    }
}
