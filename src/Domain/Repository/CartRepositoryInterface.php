<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\ValueObject\CartId;
use App\Entity\Cart;

interface CartRepositoryInterface
{
    public function findById(int $id): ?Cart;

    public function findByCartId(CartId $cartId): ?Cart;

    public function findBySessionId(string $sessionId): ?Cart;

    public function save(Cart $cart): void;

    public function remove(Cart $cart): void;

    /**
     * @return Cart[]
     */
    public function findAll(): array;
}
