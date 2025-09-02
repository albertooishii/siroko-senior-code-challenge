<?php

namespace App\Tests\Architecture;

use PHPUnit\Framework\TestCase;

class ArchitectureValidationTest extends TestCase
{
    public function testDomainValueObjectsAreImmutable(): void
    {
        // Test Money Value Object
        $money = new \App\Domain\ValueObject\Money('10.50');
        $this->assertEquals('10.50', $money->getAmount());
        $this->assertEquals('EUR', $money->getCurrency());
        
        // Verificar que tiene método equals
        $this->assertTrue(method_exists($money, 'equals'));
        
        // Verificar inmutabilidad - las operaciones devuelven nuevas instancias
        $newMoney = $money->add(new \App\Domain\ValueObject\Money('5.25'));
        $this->assertNotSame($money, $newMoney);
        $this->assertEquals('10.50', $money->getAmount()); // Original no cambió
        $this->assertEquals('15.75', $newMoney->getAmount()); // Nueva instancia
    }
    
    public function testValueObjectsHaveProperValidation(): void
    {
        // Test ProductId
        $this->expectException(\InvalidArgumentException::class);
        new \App\Domain\ValueObject\ProductId(0); // Debe fallar con 0 o negativo
    }
    
    public function testEntitiesHaveBusinessLogic(): void
    {
        // Test Cart entity
        $cart = new \App\Entity\Cart();
        
        // Debe tener lógica de negocio, no solo getters/setters
        $this->assertTrue(method_exists($cart, 'calculateTotalPrice'));
        $this->assertTrue(method_exists($cart, 'addCartItem'));
        $this->assertTrue(method_exists($cart, 'removeCartItem'));
        
        // Test Product entity
        $product = new \App\Entity\Product();
        $this->assertTrue(method_exists($product, 'getId'));
        $this->assertTrue(method_exists($product, 'getName'));
        $this->assertTrue(method_exists($product, 'getPrice'));
    }
    
    public function testRepositoryInterfacesExist(): void
    {
        // Verificar que existen las interfaces de repositorio
        $this->assertTrue(interface_exists('App\Domain\Repository\CartRepositoryInterface'));
        $this->assertTrue(interface_exists('App\Domain\Repository\ProductRepositoryInterface'));
        $this->assertTrue(interface_exists('App\Domain\Repository\OrderRepositoryInterface'));
        
        // Verificar que tienen los métodos esperados
        $cartRepoInterface = new \ReflectionClass('App\Domain\Repository\CartRepositoryInterface');
        $this->assertTrue($cartRepoInterface->hasMethod('save'));
        $this->assertTrue($cartRepoInterface->hasMethod('findById'));
        $this->assertTrue($cartRepoInterface->hasMethod('remove'));
    }
    
    public function testDomainExceptionsAreProperlyDefined(): void
    {
        // Verificar que las excepciones existen y heredan de Exception
        $this->assertTrue(class_exists('App\Domain\Exception\CartNotFoundException'));
        $this->assertTrue(class_exists('App\Domain\Exception\ProductNotFoundException'));
        $this->assertTrue(class_exists('App\Domain\Exception\InvalidQuantityException'));
        
        // Verificar herencia
        $cartNotFoundException = new \ReflectionClass('App\Domain\Exception\CartNotFoundException');
        $this->assertTrue($cartNotFoundException->isSubclassOf(\Exception::class));
        
        // Verificar factory methods
        $this->assertTrue($cartNotFoundException->hasMethod('byId'));
    }
    
    public function testEntitiesHaveProperEncapsulation(): void
    {
        $cartClass = new \ReflectionClass('App\Entity\Cart');
        
        // Todas las propiedades deben ser privadas
        foreach ($cartClass->getProperties() as $property) {
            $this->assertTrue(
                $property->isPrivate(),
                sprintf('Propiedad %s debe ser privada', $property->getName())
            );
        }
    }
    
    public function testAggregateRootBehavior(): void
    {
        // Cart es nuestro aggregate root
        $cart = new \App\Entity\Cart();
        $product = new \App\Entity\Product();
        $product->setName('Test Product');
        $product->setPrice('19.99');
        
        $cartItem = new \App\Entity\CartItem();
        $cartItem->setProduct($product);
        $cartItem->setQuantity(2);
        $cartItem->setUnitPrice('19.99');
        
        // Cart debe manejar la adición de items
        $cart->addCartItem($cartItem);
        
        // Y calcular totales automáticamente
        $cart->calculateTotalPrice();
        
        $this->assertGreaterThan('0.00', $cart->getTotalPrice());
    }
    
    public function testDomainLayerPurity(): void
    {
        // Los Value Objects no deben depender de Doctrine/Symfony
        $moneyClass = new \ReflectionClass('App\Domain\ValueObject\Money');
        $filename = $moneyClass->getFileName();
        
        if ($filename) {
            $content = file_get_contents($filename);
            $this->assertStringNotContainsString('Doctrine', $content, 'Money no debe depender de Doctrine');
            $this->assertStringNotContainsString('Symfony', $content, 'Money no debe depender de Symfony');
        }
    }
    
    public function testValueObjectEquality(): void
    {
        $money1 = new \App\Domain\ValueObject\Money('10.50');
        $money2 = new \App\Domain\ValueObject\Money('10.50');
        $money3 = new \App\Domain\ValueObject\Money('15.75');
        
        $this->assertTrue($money1->equals($money2), 'Money con mismo valor debe ser igual');
        $this->assertFalse($money1->equals($money3), 'Money con diferente valor debe ser diferente');
        
        $productId1 = new \App\Domain\ValueObject\ProductId(1);
        $productId2 = new \App\Domain\ValueObject\ProductId(1);
        $productId3 = new \App\Domain\ValueObject\ProductId(2);
        
        $this->assertTrue($productId1->equals($productId2), 'ProductId con mismo valor debe ser igual');
        $this->assertFalse($productId1->equals($productId3), 'ProductId con diferente valor debe ser diferente');
    }
    
    public function testEntityConsistency(): void
    {
        // Verificar que las entidades tienen timestamps
        $cart = new \App\Entity\Cart();
        $this->assertInstanceOf(\DateTimeImmutable::class, $cart->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $cart->getUpdatedAt());
        
        $product = new \App\Entity\Product();
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $product->getUpdatedAt());
        
        $order = new \App\Entity\Order();
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $order->getUpdatedAt());
    }
}
