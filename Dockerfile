# Build this with...
#   docker build -t hrconvert2 .

# Run this with...
#   docker run -d -p 8080:80 -p 8443:443 hrconvert2

# Use the official PHP parent image.
FROM php:8.1-apache

# Set the working directory in the container.
WORKDIR /var/www/html/HRProprietary

COPY Documentation/Build/debian.sources /etc/apt/sources.list.d/

# Run install commands.
RUN apt-get update
#RUN apt-get upgrade
RUN apt-get install -y zlib1g-dev
RUN apt-get install -y libpng-dev
RUN apt-get install -y libzip-dev
RUN docker-php-ext-install gd zip
RUN apt-get install -y libreoffice-common default-jre libreoffice-java-common poppler-utils
RUN apt-get install -y clamav unoconv p7zip-full meshlab dia pandoc
RUN apt-get install -y git python3 zip unzip rar

# Download the latest HRConvert2 source code from the official repository.
RUN git clone https://github.com/zelon88/HRConvert2

# Create required directories.
RUN mkdir /DATA && \
  mkdir /DATA/HRConvert2

# Set permissions for required directories.
RUN chmod -R 0755 /DATA && \
  chown -R www-data:www-data /DATA && \
  chmod -R 0755 /var/www/html && \
  chown -R www-data:www-data /var/www/html

# Copy required configuration files.
RUN cp HRConvert2/Documentation/Build/php.ini /usr/local/etc/php.ini

# Expose the ports Apache listens on to the host.
EXPOSE 80
EXPOSE 443

# Start Apache & Unoconv when the container runs.
CMD ["sh", "-c", "apache2-foreground && python3 /usr/bin/unoconv -l"]