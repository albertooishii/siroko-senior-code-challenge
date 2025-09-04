# Siroko Cart & Checkout API 🛒

API REST para carrito de compras y checkout implementada con **Symfony 7.3**, **Docker**, **DDD**, **Hexagonal Architecture** y **CQRS**.

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
make test          # Ejecutar tests
make quality       # Verificar calidad completa (formato + análisis + tests)
make check         # Verificar código (formato + análisis, sin tests)
make fix           # Corregir formato automáticamente
```

## 🏗️ Arquitectura

- **Hexagonal Architecture** con separación clara de capas
- **Domain Driven Design** con Value Objects y Aggregate Roots  
- **CQRS** implementado - Commands, Queries, Handlers y DTOs
- **Tests de Arquitectura** que validan principios DDD/CQRS
- **Git Hooks automáticos** para mantener calidad en cada commit

## 🧪 Testing

- **Architecture Tests**: Validación completa de principios DDD/CQRS
- **Calidad automatizada**: PHP-CS-Fixer + PHPStan Level 9
- **Verificaciones automáticas** en cada commit vía git hooks

## ️ Stack Tecnológico

- **PHP 8.4** + **Symfony 7.3**
- **PostgreSQL 15** + **Redis 7**
- **Docker** & **Docker Compose**  
- **PHPUnit**, **PHPStan**, **PHP-CS-Fixer**
- **API Platform** para documentación automática
