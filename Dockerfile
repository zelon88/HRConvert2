# Build this with...
#   docker build -t HRConvert2

# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 HRConvert2

# Use the official PHP parent image.
FROM php:8.1-apache

# Set the working directory in the container.
WORKDIR /var/www/html/HRProprietary

# Download the latest HRConvert2 source code from the official repository.
RUN git clone https://github.com/zelon88/HRConvert2

# Run install commands.
RUN apt-get update && docker-php-ext-install gd zip all-dev

  # Install PHP extensions & other dependencies.
  apt-get install libreoffice-common && sudo apt-get install libreoffice-java-common
  apt-get install clamav && sudo apt-get install unoconv && sudo apt-get install default-jre
  apt-get install rar && sudo apt-get install unrar && sudo apt-get install p7zip-full
  apt-get install meshlab && sudo apt-get install dia && sudo apt-get install pandoc
  apt-get install poppler-utils && sudo apt-get install nodejs && sudo apt-get install gnuplot
  apt-get install libnode-dev && sudo apt-get install node-gyp && sudo apt-get install npm
  
  # Create required directories.
  mkdir /DATA
  mkdir /DATA/HRConvert2

  # Set permissions for required directories.
  chmod -R 0755 /DATA
  chown -R www-data:www-data /DATA
  chmod -R 0755 /var/www/html
  chown -R www-data:www-data /var/www/html

  # Copy required configuration files.
  cp Documentation/Build/php.ini /etc/php/apache2/8.1/php.ini

# Expose the ports Apache listens on to the host.
EXPOSE 80
EXPOSE 443

# Start Apache & Unoconv when the container runs.
CMD ["apache2-foreground"]
CMD ["unoconv --listen"]