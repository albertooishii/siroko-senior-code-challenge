<?php

declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

class Money
{
    private readonly string $amount;
    private readonly string $currency;

    public function __construct(string $amount, string $currency = 'EUR')
    {
        if (!is_numeric($amount) || (float) $amount < 0) {
            throw new InvalidArgumentException('La cantidad debe ser un número positivo');
        }

        if (empty($currency) || strlen($currency) !== 3) {
            throw new InvalidArgumentException('La moneda debe ser un código ISO de 3 letras válido');
        }

        $this->amount = number_format((float) $amount, 2, '.', '');
        $this->currency = strtoupper($currency);
    }

    public static function fromString(string $amount, string $currency = 'EUR'): self
    {
        return new self($amount, $currency);
    }

    public static function zero(string $currency = 'EUR'): self
    {
        return new self('0.00', $currency);
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function getAmountAsFloat(): float
    {
        return (float) $this->amount;
    }

    public function add(Money $other): self
    {
        $this->ensureSameCurrency($other);
        $newAmount = $this->getAmountAsFloat() + $other->getAmountAsFloat();

        return new self((string) $newAmount, $this->currency);
    }

    public function subtract(Money $other): self
    {
        $this->ensureSameCurrency($other);
        $newAmount = $this->getAmountAsFloat() - $other->getAmountAsFloat();

        if ($newAmount < 0) {
            throw new InvalidArgumentException('La resta resultaría en una cantidad negativa');
        }

        return new self((string) $newAmount, $this->currency);
    }

    public function multiply(int $multiplier): self
    {
        if ($multiplier < 0) {
            throw new InvalidArgumentException('El multiplicador debe ser positivo');
        }

        $newAmount = $this->getAmountAsFloat() * $multiplier;

        return new self((string) $newAmount, $this->currency);
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }

    public function greaterThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->getAmountAsFloat() > $other->getAmountAsFloat();
    }

    public function lessThan(Money $other): bool
    {
        $this->ensureSameCurrency($other);

        return $this->getAmountAsFloat() < $other->getAmountAsFloat();
    }

    public function isZero(): bool
    {
        return $this->getAmountAsFloat() === 0.0;
    }

    public function toString(): string
    {
        return $this->amount . ' ' . $this->currency;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    private function ensureSameCurrency(Money $other): void
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(
                sprintf('Diferencia de moneda: %s vs %s', $this->currency, $other->currency)
            );
        }
    }
}
