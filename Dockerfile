# 🧱 Imagen base oficial con PHP + Apache
FROM php:8.2-apache

# 🔧 Copiar todos los archivos de tu proyecto dentro del contenedor
COPY . /var/www/html/

# 🔐 Habilitar el módulo mod_rewrite (para usar .htaccess y redirecciones limpias)
RUN a2enmod rewrite

# ⚙️ Establecer permisos correctos para los archivos
RUN chown -R www-data:www-data /var/www/html && chmod -R 755 /var/www/html

# 🌍 Exponer el puerto 80 (Render usará este puerto automáticamente)
EXPOSE 80

# 🚀 Comando que inicia Apache al ejecutar el contenedor
CMD ["apache2-foreground"]
