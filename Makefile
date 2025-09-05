# Makefile para Siroko Cart & Checkout API
.PHONY: help up down build install test cs-fix cs-check phpstan cache-clear logs bash db-create db-migrate db-fixtures deps-only check quality fix install-hooks

# Colores para output
GREEN=\033[0;32m
YELLOW=\033[1;33m
NC=\033[0m # No Color

# Detectar si estamos en un entorno CI/hook (sin TTY)
TTY_FLAG := $(shell if [ -t 0 ]; then echo ""; else echo "-T"; fi)

help: ## Mostrar ayuda
	@echo "${GREEN}Siroko Cart & Checkout API - Comandos disponibles:${NC}"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  ${YELLOW}%-15s${NC} %s\n", $$1, $$2}'

up: ## Levantar contenedores Docker
	@echo "${GREEN}Levantando contenedores...${NC}"
	docker compose up -d

down: ## Parar contenedores Docker
	@echo "${GREEN}Parando contenedores...${NC}"
	docker compose down

build: ## Construir contenedores Docker
	@echo "${GREEN}Construyendo contenedores...${NC}"
	docker compose build --no-cache

install: up ## Setup completo del proyecto (contenedores + dependencias + BD + hooks)
	@echo "${GREEN}Instalando dependencias...${NC}"
	docker compose exec $(TTY_FLAG) app composer install
	@echo "${GREEN}Creando base de datos...${NC}"
	docker compose exec $(TTY_FLAG) app php bin/console doctrine:database:create --if-not-exists
	@echo "${GREEN}Ejecutando migraciones...${NC}"
	docker compose exec $(TTY_FLAG) app php bin/console doctrine:migrations:migrate --no-interaction
	@$(MAKE) install-hooks
	@echo "${GREEN}🎉 ¡Setup completado! Accede a http://localhost:8080${NC}"
	@echo "${GREEN}Los git hooks están instalados y se ejecutarán automáticamente en cada commit${NC}"

test: ## Ejecutar todos los tests
	@echo "${GREEN}Ejecutando todos los tests...${NC}"
	docker compose exec $(TTY_FLAG) app ./vendor/bin/phpunit

test-unit: ## Ejecutar solo tests unitarios
	@echo "${GREEN}Ejecutando tests unitarios...${NC}"
	docker compose exec $(TTY_FLAG) app ./vendor/bin/phpunit --exclude-group architecture

test-architecture: ## Ejecutar tests de arquitectura
	@echo "${GREEN}Ejecutando tests de arquitectura...${NC}"
	docker compose exec $(TTY_FLAG) app ./vendor/bin/phpunit tests/Architecture/

cs-fix: ## Corregir estilo de código
	@echo "${GREEN}Corrigiendo estilo de código...${NC}"
	docker compose exec $(TTY_FLAG) app ./vendor/bin/php-cs-fixer fix

cs-check: ## Verificar estilo de código
	@echo "${GREEN}Verificando estilo de código...${NC}"
	docker compose exec $(TTY_FLAG) app ./vendor/bin/php-cs-fixer fix --dry-run --diff

phpstan: ## Análisis estático con PHPStan
	@echo "${GREEN}Ejecutando análisis estático...${NC}"
	docker compose exec $(TTY_FLAG) app ./vendor/bin/phpstan analyse

cache-clear: ## Limpiar caché
	@echo "${GREEN}Limpiando caché...${NC}"
	docker compose exec $(TTY_FLAG) app php bin/console cache:clear

logs: ## Ver logs de contenedores
	docker compose logs -f

bash: ## Acceder al contenedor PHP
	docker compose exec app bash

db-create: ## Crear base de datos
	@echo "${GREEN}Creando base de datos...${NC}"
	docker compose exec $(TTY_FLAG) app php bin/console doctrine:database:create --if-not-exists

db-migrate: ## Ejecutar migraciones
	@echo "${GREEN}Ejecutando migraciones...${NC}"
	docker compose exec $(TTY_FLAG) app php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Cargar fixtures
	@echo "${GREEN}Cargando fixtures...${NC}"
	docker compose exec $(TTY_FLAG) app php bin/console doctrine:fixtures:load --no-interaction

deps-only: ## Solo instalar dependencias de Composer
	@echo "${GREEN}Instalando solo dependencias de Composer...${NC}"
	docker compose exec $(TTY_FLAG) app composer install

quality: cs-check phpstan test ## Verificar calidad del código completa

check: cs-check phpstan ## Verificar código sin ejecutar tests
	@echo "${GREEN}Verificación de código completada${NC}"

fix: cs-fix ## Alias para cs-fix
	@echo "${GREEN}Código formateado${NC}"

install-hooks: ## Instalar git hooks automáticamente
	@echo "${GREEN}Instalando git hooks...${NC}"
	@if [ -d .git ]; then \
		cp .githooks/pre-commit .git/hooks/pre-commit; \
		chmod +x .git/hooks/pre-commit; \
		echo "${GREEN}✅ Git hooks instalados correctamente${NC}"; \
	else \
		echo "${YELLOW}⚠️  No es un repositorio git, hooks no instalados${NC}"; \
	fi

# Default target
.DEFAULT_GOAL := help
