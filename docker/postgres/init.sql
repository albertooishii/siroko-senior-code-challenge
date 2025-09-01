-- Inicialización de la base de datos Siroko Cart
-- Este script se ejecuta automáticamente cuando se crea el contenedor

-- Crear extensiones necesarias
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Crear usuario adicional si es necesario (opcional)
-- CREATE USER siroko_app WITH PASSWORD 'app_password';
-- GRANT ALL PRIVILEGES ON DATABASE siroko_cart TO siroko_app;

-- Configurar timezone
SET timezone = 'Europe/Madrid';

-- Log de inicialización
SELECT 'Database siroko_cart initialized successfully' AS status;
