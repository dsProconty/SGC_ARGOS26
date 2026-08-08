ARG PHP_VERSION=8.2
FROM php:${PHP_VERSION}-apache

# mysqli es la única extensión que el proyecto necesita (todo el código usa
# mysqli_* / $mysqli). nusoap es PHP puro, no requiere ext-soap.
RUN docker-php-ext-install mysqli \
    && docker-php-ext-enable mysqli

# mod_rewrite: el .htaccess de la raíz lo usa para bloquear .env y .git,
# y el de api/v1 para el front controller.
RUN a2enmod rewrite

# La imagen base trae AllowOverride None, con lo que Apache IGNORA los
# .htaccess del proyecto. cPanel usa All, así que lo igualamos: si no, el
# routing de la API funcionaría en producción pero no en local (y el bloqueo
# de .env no se probaría nunca).
RUN sed -i 's!<Directory /var/www/>!<Directory /var/www/>\n\tAllowOverride All!' /etc/apache2/apache2.conf

# Xdebug para depuración paso a paso desde VS Code. Configurado en modo
# "trigger" (ver php.ini): no se activa salvo que se pida explícitamente,
# así que no penaliza el rendimiento del día a día.
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Cliente mysql dentro del contenedor web, útil para inspeccionar la BD
# sin salir del contenedor.
RUN apt-get update \
    && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html
