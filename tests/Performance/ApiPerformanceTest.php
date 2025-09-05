<?php

declare(strict_types=1);

namespace App\Tests\Performance;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * Performance tests to verify API response times < 200ms.
 */
class ApiPerformanceTest extends WebTestCase
{
    private $client;
    private const MAX_RESPONSE_TIME_MS = 200; // 200ms requirement

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->setupTestData();
    }

    public function testCreateCartPerformance(): void
    {
        $startTime = microtime(true);

        $this->client->request('POST', '/api/carts');

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Create cart took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );
    }

    public function testGetCartPerformance(): void
    {
        // Create cart first
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        $startTime = microtime(true);

        $this->client->request('GET', "/api/carts/{$cartId}");

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Get cart took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );
    }

    public function testAddItemPerformance(): void
    {
        // Create cart first
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        $startTime = microtime(true);

        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 1])
        );

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Add item took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );
    }

    public function testUpdateItemPerformance(): void
    {
        // Setup: Create cart and add item
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 1])
        );

        $startTime = microtime(true);

        $this->client->request(
            'PUT',
            "/api/carts/{$cartId}/items/1",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['quantity' => 3])
        );

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Update item took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );
    }

    public function testRemoveItemPerformance(): void
    {
        // Setup: Create cart and add item
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 1])
        );

        $startTime = microtime(true);

        $this->client->request('DELETE', "/api/carts/{$cartId}/items/1");

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Remove item took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );
    }

    public function testCheckoutPerformance(): void
    {
        // Setup: Create cart and add item
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        $this->client->request(
            'POST',
            "/api/carts/{$cartId}/items",
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['productId' => 1, 'quantity' => 2])
        );

        $startTime = microtime(true);

        $this->client->request('POST', "/api/carts/{$cartId}/checkout");

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Checkout took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );
    }

    public function testConcurrentRequestsPerformance(): void
    {
        // Simulate concurrent requests by making multiple sequential requests quickly
        $startTime = microtime(true);

        $responses = [];
        for ($i = 0; $i < 5; ++$i) {
            $this->client->request('POST', '/api/carts');
            $responses[] = $this->client->getResponse()->getStatusCode();
        }

        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;

        // Verify all requests succeeded
        foreach ($responses as $statusCode) {
            $this->assertEquals(201, $statusCode);
        }

        // Should handle 5 requests reasonably quickly (less than 2 seconds)
        $this->assertLessThan(2.0, $totalTime, 'Multiple cart creation requests took too long');
    }

    public function testCartWithManyItemsPerformance(): void
    {
        // Create cart
        $this->client->request('POST', '/api/carts');
        $cartResponse = json_decode($this->client->getResponse()->getContent(), true);
        $cartId = $cartResponse['id'];

        // Add multiple items to test performance with larger carts
        for ($i = 1; $i <= 5; ++$i) {
            $this->client->request(
                'POST',
                "/api/carts/{$cartId}/items",
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode(['productId' => $i, 'quantity' => $i])
            );
        }

        $startTime = microtime(true);

        $this->client->request('GET', "/api/carts/{$cartId}");

        $endTime = microtime(true);
        $responseTimeMs = ($endTime - $startTime) * 1000;

        $this->assertResponseStatusCodeSame(Response::HTTP_OK);
        $this->assertLessThan(
            self::MAX_RESPONSE_TIME_MS,
            $responseTimeMs,
            "Get cart with many items took {$responseTimeMs}ms, should be < " . self::MAX_RESPONSE_TIME_MS . 'ms'
        );

        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertCount(5, $responseData['items']);
    }

    private function setupTestData(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $connection = $entityManager->getConnection();

        // Insert test products for performance testing
        for ($i = 1; $i <= 10; ++$i) {
            $connection->executeStatement(
                'INSERT INTO product (id, name, price, stock, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING',
                [$i, "Performance Test Product {$i}", $i * 1.00, 100, '2024-01-01 00:00:00', '2024-01-01 00:00:00']
            );
        }
    }
}
