<?php

declare(strict_types=1);

namespace App\Infrastructure\Web\Controller;

use App\Application\Command\AddItemToCartCommand;
use App\Application\Command\CheckoutCartCommand;
use App\Application\Command\RemoveItemFromCartCommand;
use App\Application\Command\UpdateCartItemQuantityCommand;
use App\Application\Query\GetCartQuery;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/carts', name: 'api_cart_')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function createCart(Request $request): JsonResponse
    {
        try {
            // Por simplicidad, creamos un carrito vacío que se puede usar inmediatamente
            // En un caso real, se crearía en BD y retornaría el ID real
            $cartId = rand(1, 999999); // Temporal: En producción sería creado en BD

            // En un caso real, podríamos recibir datos como sessionId en el body
            $sessionId = $request->get('sessionId', session_id());

            return new JsonResponse([
                'success' => true,
                'data' => [
                    'id' => $cartId,
                    'sessionId' => $sessionId,
                    'items' => [],
                    'total' => 0,
                ],
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getCart(int $id): JsonResponse
    {
        try {
            $query = new GetCartQuery((string) $id);

            $envelope = $this->messageBus->dispatch($query);
            $handledStamp = $envelope->last(HandledStamp::class);
            $cartDTO = $handledStamp?->getResult();

            if ($cartDTO === null) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Cart not found',
                ], Response::HTTP_NOT_FOUND);
            }

            return new JsonResponse([
                'success' => true,
                'data' => $cartDTO,
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/items', name: 'add_item', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function addItem(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['productId']) || !isset($data['quantity'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: productId, quantity',
                ], Response::HTTP_BAD_REQUEST);
            }

            $productId = (int) $data['productId'];
            $quantity = (int) $data['quantity'];

            $command = new AddItemToCartCommand((string) $id, (string) $productId, $quantity);
            $this->messageBus->dispatch($command);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item added to cart successfully',
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/items/{productId}', name: 'update_item', methods: ['PUT'], requirements: ['id' => '\d+', 'productId' => '\d+'])]
    public function updateItemQuantity(int $id, int $productId, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!isset($data['quantity'])) {
                return new JsonResponse([
                    'success' => false,
                    'error' => 'Missing required field: quantity',
                ], Response::HTTP_BAD_REQUEST);
            }

            $quantity = (int) $data['quantity'];

            $command = new UpdateCartItemQuantityCommand((string) $id, (string) $productId, $quantity);
            $this->messageBus->dispatch($command);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item quantity updated successfully',
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/items/{productId}', name: 'remove_item', methods: ['DELETE'], requirements: ['id' => '\d+', 'productId' => '\d+'])]
    public function removeItem(int $id, int $productId): JsonResponse
    {
        try {
            $command = new RemoveItemFromCartCommand((string) $id, (string) $productId);
            $this->messageBus->dispatch($command);

            return new JsonResponse([
                'success' => true,
                'message' => 'Item removed from cart successfully',
            ]);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }

    #[Route('/{id}/checkout', name: 'checkout', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function checkout(int $id, Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            $customerEmail = $data['customerEmail'] ?? 'guest@example.com';

            $command = new CheckoutCartCommand((string) $id, $customerEmail);

            $envelope = $this->messageBus->dispatch($command);
            $handledStamp = $envelope->last(HandledStamp::class);
            $orderData = $handledStamp?->getResult();

            return new JsonResponse([
                'success' => true,
                'message' => 'Checkout completed successfully',
                'data' => $orderData,
            ], Response::HTTP_CREATED);
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
