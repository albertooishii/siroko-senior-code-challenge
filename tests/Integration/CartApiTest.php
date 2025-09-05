<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Integration tests for Cart API endpoints
 * Tests all 6 REST endpoints with real HTTP requests.
 */
class CartApiTest extends WebTestCase
{
    private $client;
    private int $cartId;
    private int $productId;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        // Create a test product first
        $this->createTestProduct();
    }

    public function testCreateCart(): void
    {
        $this->client->request('POST', '/api/carts');

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertIsInt($responseData['id']);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertEmpty($responseData['items']);
        $this->assertEquals(0, $responseData['total']);

        // Store cart ID for subsequent tests
        $this->cartId = $responseData['id'];
    }

    /**
     * @depends testCreateCart
     */
    public function testGetEmptyCart(): void
    {
        // First create a cart
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Then get it
        $this->client->request('GET', "/api/carts/{$cartId}");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($cartId, $responseData['id']);
        $this->assertEmpty($responseData['items']);
        $this->assertEquals(0, $responseData['total']);
    }

    public function testGetNonExistentCart(): void
    {
        $this->client->request('GET', '/api/carts/99999');

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testAddItemToCart(): void
    {
        // Create cart first
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Add item
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'productId' => $this->productId,
                'quantity' => 2,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($cartId, $responseData['id']);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals($this->productId, $responseData['items'][0]['productId']);
        $this->assertEquals(2, $responseData['items'][0]['quantity']);
        $this->assertGreaterThan(0, $responseData['total']);
    }

    public function testUpdateItemQuantity(): void
    {
        // Create cart and add item first
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Add item
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'productId' => $this->productId,
                'quantity' => 1,
            ])
        );

        // Update quantity
        $this->client->request(
            'PUT',
            "/api/carts/{$cartId}/items/{$this->productId}",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['quantity' => 5])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($cartId, $responseData['id']);
        $this->assertCount(1, $responseData['items']);
        $this->assertEquals(5, $responseData['items'][0]['quantity']);
    }

    public function testRemoveItemFromCart(): void
    {
        // Create cart and add item first
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Add item
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'productId' => $this->productId,
                'quantity' => 3,
            ])
        );

        // Remove item
        $this->client->request('DELETE', "/api/carts/{$cartId}/items/{$this->productId}");

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertEquals($cartId, $responseData['id']);
        $this->assertEmpty($responseData['items']);
        $this->assertEquals(0, $responseData['total']);
    }

    public function testCheckoutCart(): void
    {
        // Create cart and add item first
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Add item
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'productId' => $this->productId,
                'quantity' => 2,
            ])
        );

        // Checkout
        $this->client->request('POST', "/api/carts/{$cartId}/checkout");

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertArrayHasKey('createdAt', $responseData);
        $this->assertIsInt($responseData['id']);
        $this->assertCount(1, $responseData['items']);
        $this->assertGreaterThan(0, $responseData['total']);
    }

    public function testCheckoutEmptyCart(): void
    {
        // Create empty cart
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Try to checkout empty cart
        $this->client->request('POST', "/api/carts/{$cartId}/checkout");

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        $this->assertResponseHeaderSame('content-type', 'application/json');

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
    }

    public function testAddItemWithInvalidData(): void
    {
        // Create cart first
        $this->client->request('POST', '/api/carts');
        $createResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $createResponse['id'];

        // Test invalid quantity (negative)
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'productId' => $this->productId,
                'quantity' => -1,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);

        // Test invalid product ID
        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'productId' => 99999,
                'quantity' => 1,
            ])
        );

        $this->assertResponseStatusCodeSame(Response::HTTP_NOT_FOUND);
    }

    private function createTestProduct(): void
    {
        // Insert a test product directly in database for testing
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // Use raw SQL to insert test product
        $connection = $entityManager->getConnection();
        $connection->executeStatement(
            'INSERT INTO products (id, name, price) VALUES (?, ?, ?) ON CONFLICT (id) DO NOTHING',
            [1, 'Test Product', 1000] // 10.00 EUR in cents
        );

        $this->productId = 1;
    }
}
