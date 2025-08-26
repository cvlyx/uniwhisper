FROM php:8.1-apache
COPY . /var/www/html
RUN a2enmod rewrite
EXPOSE 80
CMD ["apache2-foreground"]