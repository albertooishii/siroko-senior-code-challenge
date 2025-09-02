<?php

namespace App\Domain\Exception;

class CartNotFoundException extends \Exception
{
    public function __construct(int $cartId)
    {
        parent::__construct(sprintf('Carrito con ID %d no encontrado', $cartId));
    }
    
    public static function byId(int $id): self
    {
        return new self($id);
    }
    
    public static function bySessionId(string $sessionId): self
    {
        return new self(0, sprintf('Carrito con session ID %s no encontrado', $sessionId));
    }
}
