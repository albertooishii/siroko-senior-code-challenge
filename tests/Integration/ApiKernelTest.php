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
        $this->assertArrayHasKey('success', $responseData);
        $this->assertTrue($responseData['success']);
        $this->assertArrayHasKey('data', $responseData);
    }

    public function testGetNonExistentCart(): void
    {
        $kernel = self::bootKernel();

        $request = Request::create('/api/carts/99999', 'GET');
        $response = $kernel->handle($request, HttpKernelInterface::MAIN_REQUEST, false);

        // Should be 404 (not found) or 400 (bad request) - both are acceptable for non-existent resource
        $this->assertContains($response->getStatusCode(), [400, 404],
            'Expected 400 or 404 for non-existent cart, got ' . $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('Content-Type'));

        $responseData = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('success', $responseData);
        $this->assertFalse($responseData['success']);
        $this->assertArrayHasKey('error', $responseData);

        // Debug: see what error message we get
        echo 'Error message: ' . $responseData['error'] . "\n";
    }
}
