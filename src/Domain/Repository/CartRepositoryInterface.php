<?php

namespace App\Domain\Repository;

use App\Entity\Cart;
use App\Domain\ValueObject\CartId;

interface CartRepositoryInterface
{
    public function findById(int $id): ?Cart;
    
    public function findBySessionId(string $sessionId): ?Cart;
    
    public function save(Cart $cart): void;
    
    public function remove(Cart $cart): void;
    
    /**
     * @return Cart[]
     */
    public function findAll(): array;
}
