<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * API tests using KernelTestCase approach
 * Testing API endpoints by directly calling the kernel.
 */
class ApiKernelTest extends KernelTestCase
{
    public function testCreateCartEndpoint(): void
    {
        $kernel = self::bootKernel();

        $request = Request::create('/api/carts', 'POST');
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);

        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertArrayHasKey('sessionId', $responseData);
        $this->assertArrayHasKey('items', $responseData);
        $this->assertArrayHasKey('total', $responseData);
        $this->assertIsInt($responseData['id']);
        $this->assertIsArray($responseData['items']);
    }

    public function testGetNonExistentCart(): void
    {
        $kernel = self::bootKernel();

        $request = Request::create('/api/carts/99999', 'GET');
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);

        // Should be 404 for non-existent cart
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $responseData);
        $this->assertEquals('Cart not found', $responseData['error']);
    }
}
