<?php

namespace App\Domain\Repository;

use App\Entity\Product;
use App\Domain\ValueObject\ProductId;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;
    
    public function findByName(string $name): ?Product;
    
    public function save(Product $product): void;
    
    public function remove(Product $product): void;
    
    /**
     * @return Product[]
     */
    public function findAll(): array;
    
    /**
     * @return Product[]
     */
    public function findByIds(array $ids): array;
    
    public function findInStock(): array;
}
