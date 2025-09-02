<?php

namespace App\Domain\Repository;

use App\Entity\Order;
use App\Domain\ValueObject\OrderId;

interface OrderRepositoryInterface
{
    public function findById(int $id): ?Order;
    
    public function findByOrderNumber(string $orderNumber): ?Order;
    
    public function findByCustomerEmail(string $email): array;
    
    public function save(Order $order): void;
    
    public function remove(Order $order): void;
    
    /**
     * @return Order[]
     */
    public function findAll(): array;
    
    /**
     * @return Order[]
     */
    public function findByStatus(string $status): array;
}
