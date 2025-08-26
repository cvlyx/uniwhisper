FROM php:8.1-apache
COPY . /var/www/html
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && a2enmod rewrite \
    && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername
EXPOSE 80
CMD ["apache2-foreground"]