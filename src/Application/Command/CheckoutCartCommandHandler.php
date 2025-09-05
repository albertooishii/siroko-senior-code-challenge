<?php

declare(strict_types=1);

namespace App\Application\Command;

use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\ValueObject\CartId;
use App\Domain\ValueObject\OrderId;
use App\Entity\Order;
use App\Entity\OrderItem;
use InvalidArgumentException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(method: 'handle')]
final readonly class CheckoutCartCommandHandler
{
    public function __construct(
        private CartRepositoryInterface $cartRepository,
        private OrderRepositoryInterface $orderRepository,
    ) {
    }

    public function handle(CheckoutCartCommand $command): string
    {
        $cartId = new CartId($command->cartId);
        $cart = $this->cartRepository->findByCartId($cartId);

        if (!$cart) {
            throw new InvalidArgumentException('Cart not found');
        }

        if ($cart->getCartItems()->isEmpty()) {
            throw new InvalidArgumentException('Cannot checkout empty cart');
        }

        // Create order
        $orderId = OrderId::generate();
        $order = new Order();
        $order->setOrderId($orderId->getValue());
        $order->setCustomerEmail($command->customerEmail);
        $order->setCustomerName($command->customerName);
        $order->setTotalAmount($cart->getTotalPrice());
        $order->setStatus('pending');

        // Create order items
        foreach ($cart->getCartItems() as $cartItem) {
            $orderItem = new OrderItem();
            $orderItem->setOrder($order);
            $orderItem->setProduct($cartItem->getProduct());
            $orderItem->setProductName($cartItem->getProduct()->getName()); // Añadir nombre del producto
            $orderItem->setQuantity($cartItem->getQuantity());
            $orderItem->setUnitPrice($cartItem->getUnitPrice());
            $orderItem->setSubtotal($cartItem->getSubtotal());

            $order->addOrderItem($orderItem);
        }

        // Save order
        $this->orderRepository->save($order);

        // Clear cart
        foreach ($cart->getCartItems() as $item) {
            $cart->removeCartItem($item);
        }
        $cart->calculateTotalPrice();
        $this->cartRepository->save($cart);

        return $orderId->getValue();
    }
}
