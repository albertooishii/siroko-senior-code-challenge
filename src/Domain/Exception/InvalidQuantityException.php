<?php

namespace App\Domain\Exception;

class InvalidQuantityException extends \Exception
{
    public function __construct(int $quantity)
    {
        parent::__construct(sprintf('Cantidad inválida: %d. La cantidad debe ser positiva', $quantity));
    }
    
    public static function mustBePositive(int $quantity): self
    {
        return new self($quantity);
    }
    
    public static function exceedsStock(int $requested, int $available): self
    {
        return new self(0, sprintf('La cantidad solicitada %d excede el stock disponible %d', $requested, $available));
    }
}
