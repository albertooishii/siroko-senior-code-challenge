<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * End-to-End functional tests
 * Tests complete user flows from cart creation to order completion.
 */
class ShoppingFlowTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->setupTestData();
    }

    public function testCompleteShoppingFlow(): void
    {
        // 1. Create a new cart
        $this->client->request('POST', '/api/carts');
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        // Verify cart is empty
        $this->assertEmpty($cartResponse['items']);
        $this->assertEquals(0, $cartResponse['total']);

        // 2. Add first product to cart
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 2])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Verify item was added
        $this->assertCount(1, $cartResponse['items']);
        $this->assertEquals(1, $cartResponse['items'][0]['productId']);
        $this->assertEquals(2, $cartResponse['items'][0]['quantity']);
        $this->assertEquals(2000, $cartResponse['total']); // 2 * 1000 cents

        // 3. Add second product to cart
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 2, 'quantity' => 1])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Verify both items are in cart
        $this->assertCount(2, $cartResponse['items']);
        $this->assertEquals(3500, $cartResponse['total']); // 2000 + 1500

        // 4. Update quantity of first product
        $this->client->request(
            'PUT',
            "/api/carts/{$cartId}/items/1",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['quantity' => 3])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Verify quantity was updated and total recalculated
        $this->assertEquals(3, $this->findItemByProductId($cartResponse['items'], 1)['quantity']);
        $this->assertEquals(4500, $cartResponse['total']); // 3000 + 1500

        // 5. Remove second product from cart
        $this->client->request('DELETE', "/api/carts/{$cartId}/items/2");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Verify item was removed
        $this->assertCount(1, $cartResponse['items']);
        $this->assertEquals(3000, $cartResponse['total']); // Only first product remains

        // 6. Get cart state before checkout
        $this->client->request('GET', "/api/carts/{$cartId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Verify final cart state
        $this->assertCount(1, $cartResponse['items']);
        $this->assertEquals(1, $cartResponse['items'][0]['productId']);
        $this->assertEquals(3, $cartResponse['items'][0]['quantity']);
        $this->assertEquals(3000, $cartResponse['total']);

        // 7. Checkout cart to create order
        $this->client->request('POST', "/api/carts/{$cartId}/checkout");
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $orderResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Verify order was created correctly
        $this->assertArrayHasKey('id', $orderResponse);
        $this->assertIsInt($orderResponse['id']);
        $this->assertArrayHasKey('items', $orderResponse);
        $this->assertArrayHasKey('total', $orderResponse);
        $this->assertArrayHasKey('createdAt', $orderResponse);

        // Verify order contains correct items and total
        $this->assertCount(1, $orderResponse['items']);
        $this->assertEquals(1, $orderResponse['items'][0]['productId']);
        $this->assertEquals(3, $orderResponse['items'][0]['quantity']);
        $this->assertEquals(3000, $orderResponse['total']);

        // 8. Verify cart is still accessible but order was created
        $this->client->request('GET', "/api/carts/{$cartId}");
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $finalCartResponse = json_decode($this->client->getResponse()->getContent(), true);

        // Cart should still contain the items (depending on business logic)
        $this->assertEquals($cartId, $finalCartResponse['id']);
    }

    public function testMultipleCartsFlow(): void
    {
        // Create first cart
        $this->client->request('POST', '/api/carts');
        $cart1Response = json_decode($this->client->getResponse()->getContent(), true);
        $cart1Id = $cart1Response['id'];

        // Create second cart
        $this->client->request('POST', '/api/carts');
        $cart2Response = json_decode($this->client->getResponse()->getContent(), true);
        $cart2Id = $cart2Response['id'];

        // Verify carts have different IDs
        $this->assertNotEquals($cart1Id, $cart2Id);

        // Add different items to each cart
        $this->client->request(
            'POST',
            "/api/carts/{$cart1Id}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 1])
        );

        $this->client->request(
            'POST',
            "/api/carts/{$cart2Id}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 2, 'quantity' => 2])
        );

        // Verify each cart contains only its items
        $this->client->request('GET', "/api/carts/{$cart1Id}");
        $cart1Final = json_decode($this->client->getResponse()->getContent(), true);

        $this->client->request('GET', "/api/carts/{$cart2Id}");
        $cart2Final = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount(1, $cart1Final['items']);
        $this->assertEquals(1, $cart1Final['items'][0]['productId']);
        $this->assertEquals(1000, $cart1Final['total']);

        $this->assertCount(1, $cart2Final['items']);
        $this->assertEquals(2, $cart2Final['items'][0]['productId']);
        $this->assertEquals(3000, $cart2Final['total']); // 2 * 1500
    }

    public function testErrorHandlingFlow(): void
    {
        // Create cart
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        // Try to add non-existent product
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 99999, 'quantity' => 1])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Try to add item with invalid quantity
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 0])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Try to update non-existent item
        $this->client->request(
            'PUT',
            "/api/carts/{$cartId}/items/1",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['quantity' => 5])
        );
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Try to remove non-existent item
        $this->client->request('DELETE', "/api/carts/{$cartId}/items/1");
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);

        // Try to checkout empty cart
        $this->client->request('POST', "/api/carts/{$cartId}/checkout");
        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Try to access non-existent cart
        $this->client->request('GET', '/api/carts/99999');
        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function findItemByProductId(array $items, int $productId): ?array
    {
        foreach ($items as $item) {
            if ($item['productId'] === $productId) {
                return $item;
            }
        }

        return null;
    }

    private function setupTestData(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();

        // Insert test products
        $connection->executeStatement(
            'INSERT INTO product (id, name, price, stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING',
            [1, 'Test Product 1', '10.00', 100, '2024-01-01 00:00:00', '2024-01-01 00:00:00']
        );

        $connection->executeStatement(
            'INSERT INTO product (id, name, price, stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING',
            [2, 'Test Product 2', '15.00', 100, '2024-01-01 00:00:00', '2024-01-01 00:00:00']
        );
    }
}
