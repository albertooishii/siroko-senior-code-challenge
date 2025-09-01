FROM nginx:alpine

# Copiar configuración personalizada
COPY default.conf /etc/nginx/conf.d/default.conf

# Crear directorio de trabajo
RUN mkdir -p /var/www/html

EXPOSE 80
