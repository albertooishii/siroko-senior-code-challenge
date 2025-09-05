<?php

declare(strict_types=1);

namespace App\Application\Query;

final readonly class GetCartQuery
{
    public function __construct(
        public int $cartId,
    ) {
    }
}
