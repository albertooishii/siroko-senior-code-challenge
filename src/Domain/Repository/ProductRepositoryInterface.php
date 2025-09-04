<?php

declare(strict_types=1);

namespace App\Domain\Repository;

use App\Domain\ValueObject\ProductId;
use App\Entity\Product;

interface ProductRepositoryInterface
{
    public function findById(int $id): ?Product;

    public function findByProductId(ProductId $productId): ?Product;

    public function findByName(string $name): ?Product;

    public function save(Product $product): void;

    public function remove(Product $product): void;

    /**
     * @return Product[]
     */
    public function findAll(): array;

    /**
     * @param int[] $ids
     *
     * @return Product[]
     */
    public function findByIds(array $ids): array;

    /**
     * @return Product[]
     */
    public function findInStock(): array;
}
