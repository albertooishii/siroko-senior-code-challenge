<?php

declare(strict_types=1);

namespace App\Tests\Architecture;

use App\Domain\Exception\CartNotFoundException;
use App\Domain\Exception\InvalidQuantityException;
use App\Domain\Exception\ProductNotFoundException;
use App\Domain\Repository\CartRepositoryInterface;
use App\Domain\Repository\OrderRepositoryInterface;
use App\Domain\Repository\ProductRepositoryInterface;
use App\Domain\ValueObject\CartId;
use App\Domain\ValueObject\Money;
use App\Domain\ValueObject\OrderId;
use App\Domain\ValueObject\ProductId;
use App\Entity\Cart;
use App\Entity\CartItem;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use Exception;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class HexagonalArchitectureTest extends TestCase
{
    public function testValueObjectsAreImmutable(): void
    {
        $valueObjects = $this->getValueObjectClasses();

        foreach ($valueObjects as $valueObjectClass) {
            $reflectionClass = new ReflectionClass($valueObjectClass);

            foreach ($reflectionClass->getProperties() as $property) {
                $this->assertTrue(
                    $property->isReadOnly() || $property->isPrivate(),
                    sprintf('Property %s::%s must be readonly or private for immutability', $valueObjectClass, $property->getName())
                );
            }

            // No public setters
            foreach ($reflectionClass->getMethods() as $method) {
                if ($method->isPublic() && str_starts_with($method->getName(), 'set')) {
                    $this->fail(
                        sprintf('Value Object %s should not have public setters: %s', $valueObjectClass, $method->getName())
                    );
                }
            }
        }
    }

    public function testRepositoryInterfacesAreInDomainLayer(): void
    {
        $repositoryInterfaces = $this->getRepositoryInterfaceClasses();

        foreach ($repositoryInterfaces as $repositoryInterface) {
            $reflectionClass = new ReflectionClass($repositoryInterface);

            $this->assertTrue(
                $reflectionClass->isInterface(),
                sprintf('%s must be an interface', $repositoryInterface)
            );

            $this->assertStringEndsWith(
                'Interface',
                $reflectionClass->getShortName(),
                sprintf('Repository interface %s must end with "Interface"', $repositoryInterface)
            );

            $this->assertStringStartsWith(
                'App\\Domain\\Repository\\',
                $repositoryInterface,
                sprintf('Repository interface %s must be in Domain layer', $repositoryInterface)
            );
        }
    }

    public function testDomainExceptionsInheritFromStandardException(): void
    {
        $exceptionClasses = $this->getDomainExceptionClasses();

        foreach ($exceptionClasses as $exceptionClass) {
            $reflectionClass = new ReflectionClass($exceptionClass);

            $this->assertTrue(
                $reflectionClass->isSubclassOf(Exception::class),
                sprintf('Domain exception %s must inherit from Exception', $exceptionClass)
            );

            $this->assertStringEndsWith(
                'Exception',
                $reflectionClass->getShortName(),
                sprintf('Domain exception %s must end with "Exception"', $exceptionClass)
            );

            $this->assertStringStartsWith(
                'App\\Domain\\Exception\\',
                $exceptionClass,
                sprintf('Domain exception %s must be in Domain\\Exception namespace', $exceptionClass)
            );
        }
    }

    public function testEntitiesHaveProperEncapsulation(): void
    {
        $entityClasses = $this->getEntityClasses();

        foreach ($entityClasses as $entityClass) {
            $reflectionClass = new ReflectionClass($entityClass);

            foreach ($reflectionClass->getProperties() as $property) {
                $this->assertTrue(
                    $property->isPrivate(),
                    sprintf('Property %s::%s must be private for encapsulation', $entityClass, $property->getName())
                );
            }
        }
    }

    public function testAggregateRootsHaveBusinessLogic(): void
    {
        $aggregateClasses = $this->getAggregateRootClasses();

        foreach ($aggregateClasses as $entityClass) {
            $reflectionClass = new ReflectionClass($entityClass);

            $businessMethods = 0;
            foreach ($reflectionClass->getMethods() as $method) {
                if ($method->isPublic()
                    && !str_starts_with($method->getName(), 'get')
                    && !str_starts_with($method->getName(), 'set')
                    && $method->getName() !== '__construct') {
                    ++$businessMethods;
                }
            }

            $this->assertGreaterThan(
                0,
                $businessMethods,
                sprintf('Aggregate Root %s should have business logic methods beyond getters/setters', $entityClass)
            );
        }
    }

    public function testValueObjectsHaveEqualityMethods(): void
    {
        $valueObjects = [Money::class, CartId::class, ProductId::class, OrderId::class];

        foreach ($valueObjects as $valueObjectClass) {
            $reflectionClass = new ReflectionClass($valueObjectClass);

            $this->assertTrue(
                $reflectionClass->hasMethod('equals') || $reflectionClass->hasMethod('__toString'),
                sprintf('Value Object %s should have equals() or __toString() method', $valueObjectClass)
            );
        }
    }

    public function testAggregateRootsHaveBusinessMethods(): void
    {
        $aggregates = [Cart::class, Order::class];

        foreach ($aggregates as $aggregateClass) {
            $reflectionClass = new ReflectionClass($aggregateClass);

            // Verificar que tienen métodos de negocio
            $hasBusinessLogic = false;
            foreach ($reflectionClass->getMethods() as $method) {
                if ($method->isPublic()
                    && !str_starts_with($method->getName(), 'get')
                    && !str_starts_with($method->getName(), 'set')
                    && $method->getName() !== '__construct') {
                    $hasBusinessLogic = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasBusinessLogic,
                sprintf('Aggregate root %s should have business logic methods', $aggregateClass)
            );
        }
    }

    public function testDomainLayerPurity(): void
    {
        $domainClasses = [
            Money::class, CartId::class, ProductId::class, OrderId::class,
            CartRepositoryInterface::class, ProductRepositoryInterface::class, OrderRepositoryInterface::class,
            CartNotFoundException::class, ProductNotFoundException::class, InvalidQuantityException::class,
        ];

        foreach ($domainClasses as $class) {
            $reflectionClass = new ReflectionClass($class);
            $dependencies = $this->getClassDependencies($reflectionClass);

            foreach ($dependencies as $dependency) {
                $this->assertStringNotContainsString(
                    'Doctrine',
                    $dependency,
                    sprintf('Domain class %s should not depend on Doctrine: %s', $class, $dependency)
                );

                $this->assertStringNotContainsString(
                    'Symfony',
                    $dependency,
                    sprintf('Domain class %s should not depend on Symfony: %s', $class, $dependency)
                );
            }
        }
    }

    public function testConsistentNaming(): void
    {
        // Value Objects no deben terminar en VO
        $valueObjects = [Money::class, CartId::class, ProductId::class, OrderId::class];
        foreach ($valueObjects as $vo) {
            $className = (new ReflectionClass($vo))->getShortName();
            $this->assertStringNotContainsString(
                'VO',
                substr($className, -2),
                sprintf('Value Object %s should not end with "VO"', $className)
            );
        }

        // Excepciones deben terminar en Exception
        $exceptions = [CartNotFoundException::class, ProductNotFoundException::class, InvalidQuantityException::class];
        foreach ($exceptions as $exception) {
            $className = (new ReflectionClass($exception))->getShortName();
            $this->assertStringEndsWith(
                'Exception',
                $className,
                sprintf('Exception %s must end with "Exception"', $className)
            );
        }
    }

    private function getClassDependencies(ReflectionClass $class): array
    {
        $dependencies = [];

        $filename = $class->getFileName();
        if ($filename) {
            $content = file_get_contents($filename);
            preg_match_all('/^use\s+([^;]+);/m', $content, $matches);
            $dependencies = array_merge($dependencies, $matches[1]);
        }

        return $dependencies;
    }

    private function getValueObjectClasses(): array
    {
        return [
            Money::class,
            CartId::class,
            ProductId::class,
            OrderId::class,
        ];
    }

    private function getRepositoryInterfaceClasses(): array
    {
        return [
            CartRepositoryInterface::class,
            ProductRepositoryInterface::class,
            OrderRepositoryInterface::class,
        ];
    }

    private function getDomainExceptionClasses(): array
    {
        return [
            CartNotFoundException::class,
            ProductNotFoundException::class,
            InvalidQuantityException::class,
        ];
    }

    private function getEntityClasses(): array
    {
        return [
            Cart::class,
            CartItem::class,
            Product::class,
            Order::class,
            OrderItem::class,
        ];
    }

    private function getAggregateRootClasses(): array
    {
        // Aggregate roots are entities that serve as entry points to their aggregates
        return [
            Cart::class,
            Order::class,
        ];
    }
}
