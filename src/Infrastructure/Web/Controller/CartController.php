<?php

declare(strict_types=1);

namespace App\Infrastructure\Web\Controller;

use App\Application\Command\AddItemToCartCommand;
use App\Application\Command\CheckoutCartCommand;
use App\Application\Command\RemoveItemFromCartCommand;
use App\Application\Command\UpdateCartItemQuantityCommand;
use App\Application\Query\GetCartQuery;
use Exception;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/carts', name: 'api_cart_')]
#[OA\Tag(name: 'Cart', description: 'Shopping cart operations')]
class CartController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/carts',
        summary: 'Create a new shopping cart',
        description: 'Creates a new empty shopping cart and returns the cart ID',
        tags: ['Cart']
    )]
    #[OA\RequestBody(
        description: 'Optional cart creation data',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'sessionId', type: 'string', description: 'Optional session ID'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Cart created successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', description: 'Cart ID'),
                        new OA\Property(property: 'sessionId', type: 'string', description: 'Session ID'),
                        new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
                        new OA\Property(property: 'total', type: 'integer', description: 'Total amount in cents'),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', description: 'Error message'),
            ]
        )
    )]
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
    #[OA\Get(
        path: '/api/carts/{id}',
        summary: 'Get cart by ID',
        description: 'Retrieves a shopping cart with all its items and total',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Cart ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Cart retrieved successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', description: 'Cart ID'),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'productId', type: 'integer'),
                                    new OA\Property(property: 'quantity', type: 'integer'),
                                    new OA\Property(property: 'unitPrice', type: 'integer', description: 'Price in cents'),
                                    new OA\Property(property: 'totalPrice', type: 'integer', description: 'Total price in cents'),
                                ]
                            )
                        ),
                        new OA\Property(property: 'total', type: 'integer', description: 'Total amount in cents'),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Cart not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', example: 'Cart not found'),
            ]
        )
    )]
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
    #[OA\Post(
        path: '/api/carts/{id}/items',
        summary: 'Add item to cart',
        description: 'Adds a product to the shopping cart with specified quantity',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Cart ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Item to add to cart',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['productId', 'quantity'],
            properties: [
                new OA\Property(property: 'productId', type: 'integer', description: 'Product ID to add'),
                new OA\Property(property: 'quantity', type: 'integer', description: 'Quantity to add', minimum: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Item added successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Item added to cart successfully'),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request - Invalid data',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', description: 'Error message'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Product not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', example: 'Product not found'),
            ]
        )
    )]
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
    #[OA\Put(
        path: '/api/carts/{id}/items/{productId}',
        summary: 'Update item quantity',
        description: 'Updates the quantity of a specific product in the cart',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Cart ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'productId',
        description: 'Product ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'New quantity for the item',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['quantity'],
            properties: [
                new OA\Property(property: 'quantity', type: 'integer', description: 'New quantity', minimum: 1),
            ]
        )
    )]
    #[OA\Response(
        response: 200,
        description: 'Item quantity updated successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Item quantity updated successfully'),
            ]
        )
    )]
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
    #[OA\Delete(
        path: '/api/carts/{id}/items/{productId}',
        summary: 'Remove item from cart',
        description: 'Removes a specific product from the shopping cart',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Cart ID',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Parameter(
        name: 'productId',
        description: 'Product ID to remove',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\Response(
        response: 200,
        description: 'Item removed successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Item removed from cart successfully'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Item not found in cart',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', example: 'Item not found in cart'),
            ]
        )
    )]
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
    #[OA\Post(
        path: '/api/carts/{id}/checkout',
        summary: 'Checkout cart',
        description: 'Processes cart checkout and creates an order',
        tags: ['Cart']
    )]
    #[OA\Parameter(
        name: 'id',
        description: 'Cart ID to checkout',
        in: 'path',
        required: true,
        schema: new OA\Schema(type: 'integer')
    )]
    #[OA\RequestBody(
        description: 'Checkout information',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'customerEmail', type: 'string', description: 'Customer email address', example: 'customer@example.com'),
            ]
        )
    )]
    #[OA\Response(
        response: 201,
        description: 'Checkout completed successfully',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Checkout completed successfully'),
                new OA\Property(
                    property: 'data',
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', description: 'Order ID'),
                        new OA\Property(property: 'total', type: 'integer', description: 'Order total in cents'),
                        new OA\Property(property: 'createdAt', type: 'string', format: 'date-time', description: 'Order creation timestamp'),
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(
                                type: 'object',
                                properties: [
                                    new OA\Property(property: 'productId', type: 'integer'),
                                    new OA\Property(property: 'quantity', type: 'integer'),
                                    new OA\Property(property: 'unitPrice', type: 'integer'),
                                    new OA\Property(property: 'totalPrice', type: 'integer'),
                                ]
                            )
                        ),
                    ]
                ),
            ]
        )
    )]
    #[OA\Response(
        response: 400,
        description: 'Bad request - Cannot checkout empty cart',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', example: 'Cannot checkout empty cart'),
            ]
        )
    )]
    #[OA\Response(
        response: 404,
        description: 'Cart not found',
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'success', type: 'boolean', example: false),
                new OA\Property(property: 'error', type: 'string', example: 'Cart not found'),
            ]
        )
    )]
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
