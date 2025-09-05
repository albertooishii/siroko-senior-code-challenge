<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\CartId;
use App\Domain\ValueObject\ProductId;
use App\Infrastructure\Exception\CartItemNotFoundException;
use App\Infrastructure\Exception\ProductNotFoundException;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(method: 'handle')]
final readonly class UpdateCartItemQuantityCommandHandler
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private ProductRepositoryInterface $productRepository,
    ) {
    }

    public function handle(UpdateCartItemQuantityCommand $command): void
    {
        $cartId = new CartId($command->cartId);
        $productId = ProductId::fromInt($command->productId);

        $cart = $this->cartRepository->findByCartId($cartId);
        $product = $this->productRepository->findByProductId($productId);

        if (!$cart) {
            throw new InvalidArgumentException('Cart not found');
        }

        if (!$product) {
            throw new ProductNotFoundException('Product not found');
        }

        // Verify stock for new quantity
        if (!$product->hasStock($command->quantity)) {
            throw new InvalidArgumentException('Insufficient stock');
        }

        // Find and update the item
        foreach ($cart->getCartItems() as $item) {
            if ($item->getProduct()->getId() === $product->getId()) {
                $item->updateQuantity($command->quantity);
                $cart->calculateTotalPrice();
                $this->cartRepository->save($cart);

                return;
            }
        }

        throw new CartItemNotFoundException('Item not found in cart');
    }
}
