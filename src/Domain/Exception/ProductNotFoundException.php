<?php

namespace App\Domain\Exception;

class ProductNotFoundException extends \Exception
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
