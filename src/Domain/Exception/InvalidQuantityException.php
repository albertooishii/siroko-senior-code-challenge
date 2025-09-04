<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

class InvalidQuantityException extends Exception
{
    public function __construct(string $message = 'Invalid quantity', int $code = 0)
    {
        parent::__construct($message, $code);
    }

    public static function mustBePositive(int $quantity): self
    {
        return new self(sprintf('Cantidad inválida: %d. La cantidad debe ser positiva', $quantity));
    }

    public static function exceedsStock(int $requested, int $available): self
    {
        return new self(sprintf('La cantidad solicitada %d excede el stock disponible %d', $requested, $available));
    }
}
