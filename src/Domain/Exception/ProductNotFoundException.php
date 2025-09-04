<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

class ProductNotFoundException extends Exception
{
    public function __construct(int $productId)
    {
        parent::__construct(sprintf('Producto con ID %d no encontrado', $productId));
    }

    public static function byId(int $id): self
    {
        return new self($id);
    }
}
