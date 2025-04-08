# Use a imagem oficial do PHP com Apache como base
FROM php:8.1-apache

# Instala dependências do sistema para IMAP
RUN apt-get update && apt-get install -y \
    libc-client-dev \
    libkrb5-dev \
    && rm -rf /var/lib/apt/lists/*

# Instalar extensões necessárias para conexão com MySQL
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install pdo pdo_mysql mysqli imap

# Habilitar exibição de erros no PHP
RUN echo "display_errors=On\n" >> /usr/local/etc/php/conf.d/docker-php.ini \
#    && echo "error_reporting=${PHP_ERROR_REPORTING}\n" >> /usr/local/etc/php/conf.d/docker-php.ini
    && echo "error_reporting=E_ALL\n" >> /usr/local/etc/php/conf.d/docker-php.ini

# Copiar os arquivos para o servidor web
COPY ./src /var/www/html

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html

# Expor a porta 80
EXPOSE 80
