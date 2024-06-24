#!/bin/sh

# Create volumes directories required for image to work and adapt permissions for web user
mkdir -p /var/www/html/storage/logs /var/www/html/storage/framework/sessions /var/www/html/storage/framework/cache/data /var/www/html/storage/framework/views /var/www/html/storage/framework/testing /var/www/html/storage/app/public
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/public/images
echo "Storage directories created and permissions adapted"
