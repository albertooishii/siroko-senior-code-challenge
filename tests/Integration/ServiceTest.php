<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Simple integration test to verify container and services.
 */
class ServiceTest extends KernelTestCase
{
    public function testServiceContainerWiring(): void
    {
        $kernel = self::bootKernel();

        // Test that container is working
        $this->assertInstanceOf(\Symfony\Component\DependencyInjection\ContainerInterface::class, $kernel->getContainer());

        // Test that messenger service is available
        $this->assertTrue($kernel->getContainer()->has('messenger.default_bus'));

        // Test that doctrine entity manager is available
        $this->assertTrue($kernel->getContainer()->has('doctrine.orm.entity_manager'));
    }

    public function testDatabaseConnection(): void
    {
        $kernel = self::bootKernel();
        $entityManager = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        // Test basic database connection
        $connection = $entityManager->getConnection();
        $this->assertTrue($connection->isConnected() || $connection->connect());

        // Verify tables exist
        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        // Debug: see what tables are actually available
        echo 'Available tables: ' . implode(', ', $tables) . "\n";

        // Test that we have some tables
        $this->assertNotEmpty($tables, 'Database should have some tables');
    }
}
