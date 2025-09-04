<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use Exception;

class CartNotFoundException extends Exception
{
    public function __construct(string $message = 'Cart not found', int $code = 0)
    {
        parent::__construct($message, $code);
    }

    public static function byId(int $id): self
    {
        return new self(sprintf('Carrito con ID %d no encontrado', $id));
    }

    public static function bySessionId(string $sessionId): self
    {
        return new self(sprintf('Carrito con session ID %s no encontrado', $sessionId));
    }
}
