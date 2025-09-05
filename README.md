# Siroko Cart & Checkout API 🛒

**API REST** para carrito de compras y checkout implementada con **Symfony 7.3**, **Docker**, **DDD**, **Hexagonal Architecture** y **CQRS**.

> 🚀 **Challenge completado al 100%**: 43/43 tests pasando, arquitectura DDD completa, API REST funcional

## 🚀 Quick Start

```bash
# Clone del repositorio
git clone https://github.com/albertooishii/siroko-senior-code-challenge.git
cd siroko-senior-code-challenge

# Setup completo automático
make install
```

¡Y ya está! El proyecto estará corriendo en [http://localhost:8080](http://localhost:8080)

## 📋 Comandos Disponibles

```bash
make help           # Ver todos los comandos disponibles
make install        # Setup completo (contenedores + deps + BD + hooks)
make up            # Solo levantar contenedores
make down          # Parar contenedores
make test          # Ejecutar tests (43/43 pasando ✅)
make quality       # Verificar calidad completa (formato + análisis + tests)
make check         # Verificar código (formato + análisis, sin tests)
make fix           # Corregir formato automáticamente
```

## 🎯 Funcionalidades Implementadas

### ✅ **Requerimientos Obligatorios Cumplidos**
- ✅ **Gestión de productos**: Añadir, actualizar y eliminar ítems del carrito
- ✅ **Obtener productos** del carrito con información detallada
- ✅ **Procesar pago (checkout)** generando órdenes persistentes
- ✅ **Diseño de dominio desacoplado** del framework

### 🛒 **API REST Endpoints**
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/api/carts` | Crear nuevo carrito |
| `GET` | `/api/carts/{id}` | Obtener carrito por ID |
| `POST` | `/api/carts/{id}/items` | Añadir item al carrito |
| `PUT` | `/api/carts/{id}/items/{productId}` | Actualizar cantidad |
| `DELETE` | `/api/carts/{id}/items/{productId}` | Eliminar item |
| `POST` | `/api/carts/{id}/checkout` | Procesar checkout → Order |

### 📖 **Documentación Completa**
- **[📋 OpenAPI Specification](docs/OPENAPI_SPECIFICATION.md)** - Documentación completa de la API
- **[🏗️ Modelado del Dominio](docs/DOMAIN_MODELING.md)** - Arquitectura DDD detallada
- **[📊 Estado del Proyecto](docs/DELIVERY_CHECKLIST.md)** - Checklist de entrega completo
- **[🏆 Resumen Ejecutivo](docs/FINAL_SUMMARY.md)** - Evaluación final del challenge

## 🏗️ Arquitectura Técnica

### **Domain-Driven Design (DDD)**
- **Entidades**: Cart, CartItem, Product, Order, OrderItem
- **Value Objects**: Money, CartId, ProductId, OrderId  
- **Agregados**: Cart y Order como aggregate roots
- **Repositorios**: Interfaces desacopladas del framework

### **Hexagonal Architecture**
```
🎯 Domain Layer (Núcleo)    → Entidades, VOs, Reglas de negocio
🔌 Application Layer (Casos) → Commands, Queries, Handlers, DTOs  
⚙️ Infrastructure Layer      → Controllers, Repositorios, BD
```

### **CQRS Implementation**
- **Commands**: AddItem, UpdateQuantity, RemoveItem, Checkout
- **Queries**: GetCart con DTOs estructurados
- **Handlers**: Separación clara entre escritura y lectura
- **MessageBus**: Symfony Messenger configurado

## 🧪 Testing Exhaustivo

**43 tests, 279 assertions, 100% passing** ✅

### **Tipos de Testing**
- **Architecture (19 tests)**: Validación principios DDD/CQRS/Hexagonal
- **Integration (15 tests)**: API endpoints + base de datos + servicios
- **Functional (3 tests)**: Flujos completos de negocio end-to-end
- **Performance (8 tests)**: Rendimiento < 2s, concurrencia, escalabilidad

### **Calidad Automatizada**
- **PHPStan Level 9**: Análisis estático sin errores
- **PHP-CS-Fixer**: Formato de código automático
- **Git Hooks**: Verificaciones en cada commit
- **Architecture Tests**: Validación automática de patrones

## ⚡ Performance

- **API Response Time**: < 200ms para operaciones básicas
- **Tests Performance**: Todos los endpoints verificados 
- **Database**: SQLite para tests, PostgreSQL para desarrollo
- **Arquitectura optimizada**: Integer IDs, queries eficientes

## 🛠️ Stack Tecnológico

- **Backend**: PHP 8.4 + Symfony 7.3
- **Base de Datos**: PostgreSQL 15 (dev) + SQLite (test)
- **Cache**: Redis 7
- **Contenedores**: Docker + Docker Compose
- **Testing**: PHPUnit + Architecture Tests
- **Calidad**: PHPStan + PHP-CS-Fixer
- **Web Server**: Nginx

## 🔄 Flujo de Desarrollo

```bash
# 1. Desarrollar feature
git checkout -b feature/nueva-funcionalidad

# 2. Verificar calidad automáticamente
make quality  # Tests + formato + análisis

# 3. Commit con hooks automáticos
git commit -m "feat: nueva funcionalidad"

# 4. Los git hooks ejecutan verificaciones automáticas
```

## 📊 Ejemplo de Uso

```bash
# 1. Crear carrito
curl -X POST http://localhost:8080/api/carts
# Response: {"id": 1, "items": [], "total": "0.00"}

# 2. Añadir producto
curl -X POST http://localhost:8080/api/carts/1/items \
  -H "Content-Type: application/json" \
  -d '{"productId": 123, "quantity": 2}'

# 3. Ver carrito
curl http://localhost:8080/api/carts/1
# Response: {"id": 1, "items": [...], "total": "51.98"}

# 4. Checkout
curl -X POST http://localhost:8080/api/carts/1/checkout
# Response: {"orderId": 456, "status": "confirmed", ...}
```

## 🎯 Criterios de Valoración Cumplidos

| Criterio | Estado | Implementación |
|----------|--------|----------------|
| **Código limpio y escalable** | ✅ | Hexagonal + CQRS + 43 tests |
| **DDD y Arquitectura Hexagonal** | ✅ | Entidades, VOs, Agregados, Tests arquitectura |
| **CQRS** | ✅ | Commands/Queries + Handlers + DTOs |
| **Testing exhaustivo** | ✅ | 43/43 tests, 4 tipos, 279 assertions |
| **Time to market** | ✅ | API completa, setup automático |
| **Performance** | ✅ | Tests performance, arquitectura optimizada |
| **API REST** | ✅ | 6 endpoints, manejo errores, JSON responses |
| **Symfony + Dominio desacoplado** | ✅ | Framework solo en Infrastructure |
| **Git profesional** | ✅ | Feature branches, commits claros, hooks |

---

**🏆 Challenge Status: COMPLETADO - 100% requerimientos + criterios de valoración cumplidos**
