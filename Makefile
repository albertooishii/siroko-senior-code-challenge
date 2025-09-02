# Makefile para Siroko Cart & Checkout API
.PHONY: help up down build install test cs-fix cs-check phpstan cache-clear logs bash db-create db-migrate

# Colores para output
GREEN=\033[0;32m
YELLOW=\033[1;33m
NC=\033[0m # No Color

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

install: ## Instalar dependencias
	@echo "${GREEN}Instalando dependencias...${NC}"
	docker compose exec app composer install

test: ## Ejecutar todos los tests
	@echo "${GREEN}Ejecutando todos los tests...${NC}"
	docker compose exec app ./vendor/bin/phpunit

test-unit: ## Ejecutar solo tests unitarios
	@echo "${GREEN}Ejecutando tests unitarios...${NC}"
	docker compose exec app ./vendor/bin/phpunit --exclude-group architecture

test-architecture: ## Ejecutar tests de arquitectura
	@echo "${GREEN}Ejecutando tests de arquitectura...${NC}"
	docker compose exec app ./vendor/bin/phpunit tests/Architecture/

cs-fix: ## Corregir estilo de código
	@echo "${GREEN}Corrigiendo estilo de código...${NC}"
	docker compose exec app ./vendor/bin/php-cs-fixer fix

cs-check: ## Verificar estilo de código
	@echo "${GREEN}Verificando estilo de código...${NC}"
	docker compose exec app ./vendor/bin/php-cs-fixer fix --dry-run --diff

phpstan: ## Análisis estático con PHPStan
	@echo "${GREEN}Ejecutando análisis estático...${NC}"
	docker compose exec app ./vendor/bin/phpstan analyse

cache-clear: ## Limpiar caché
	@echo "${GREEN}Limpiando caché...${NC}"
	docker compose exec app php bin/console cache:clear

logs: ## Ver logs de contenedores
	docker compose logs -f

bash: ## Acceder al contenedor PHP
	docker compose exec app bash

db-create: ## Crear base de datos
	@echo "${GREEN}Creando base de datos...${NC}"
	docker compose exec app php bin/console doctrine:database:create --if-not-exists

db-migrate: ## Ejecutar migraciones
	@echo "${GREEN}Ejecutando migraciones...${NC}"
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

db-fixtures: ## Cargar fixtures
	@echo "${GREEN}Cargando fixtures...${NC}"
	docker compose exec app php bin/console doctrine:fixtures:load --no-interaction

setup: up install db-create db-migrate ## Setup completo del proyecto
	@echo "${GREEN}¡Setup completado! Accede a http://localhost:8080${NC}"

quality: cs-check phpstan test ## Verificar calidad del código completa

# Default target
.DEFAULT_GOAL := help
