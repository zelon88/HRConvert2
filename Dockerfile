FROM ubuntu:18.04

MAINTAINER techyowl <techyowl@gmail.com>

RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone
EXPOSE 80

RUN apt-get -qq update && \
apt-get -qq install -y software-properties-common && \
add-apt-repository ppa:libreoffice/libreoffice-6-3 -y && \
DEBIAN_FRONTEND=noninteractive \
apt-get -qq install -y \
	apache2 libapache2-mod-php php php-mysql php-all-dev php-zip php-gd php-curl \
	clamav \ 
	gdb libreoffice libreoffice-writer ure libreoffice-java-common libreoffice-core libreoffice-common openjdk-8-jre fonts-opensymbol hyphen-fr hyphen-de hyphen-en-us hyphen-it hyphen-ru fonts-dejavu fonts-dejavu-core fonts-dejavu-extra fonts-droid-fallback fonts-dustin fonts-f500 fonts-fanwood fonts-freefont-ttf fonts-liberation fonts-lmodern fonts-lyx fonts-sil-gentium fonts-texgyre fonts-tlwg-purisa \
	unoconv \
	imagemagick \
	ffmpeg \
	swftools \
	tesseract-ocr \ 
	meshlab \
	dia \
	pandoc \
	poppler-utils \
	p7zip-full \
	unrar-free \
	zip \
	unzip \
	wget \
	curl \
	nano \
&& \
apt-get -qq remove -y libreoffice-gnome software-properties-common && \
apt-get -qq autoremove -y -q && \
apt-get -qq clean && \ 
rm -rf /var/lib/apt/lists/*


ENV APACHE_RUN_USER  www-data
ENV APACHE_RUN_GROUP www-data
ENV APACHE_LOG_DIR   /var/log/apache2
ENV APACHE_PID_FILE  /var/run/apache2/apache2.pid
ENV APACHE_RUN_DIR   /var/run/apache2
ENV APACHE_LOCK_DIR  /var/lock/apache2
ENV APACHE_LOG_DIR   /var/log/apache2

RUN mkdir -p $APACHE_RUN_DIR && \
mkdir -p $APACHE_LOCK_DIR && \
mkdir -p $APACHE_LOG_DIR


COPY uploads.ini /etc/php/7.2/apache2/conf.d/uploads.ini

RUN rm -f /var/www/html/index.html

WORKDIR /var/www/
ADD HRConvert2 /var/www/html


RUN chmod -R 0755 /var/www && \
chown -R www-data /var/www && \
chgrp -R www-data /var/www

RUN update-alternatives --install /usr/bin/python python /usr/bin/python3.6 0

# Get latest unoconv script and hack it to use python3.
# (it fixes an issue about unoconv listener failing first time with libreoffice).
# Reference: https://github.com/dagwieers/unoconv/pull/327
ADD https://raw.githubusercontent.com/dagwieers/unoconv/master/unoconv /usr/bin/unoconv

RUN \
    chmod +xr /usr/bin/unoconv && \
    sed -i 's/env python$/env python3.6/' /usr/bin/unoconv

RUN chown -R www-data:www-data /var/www
ENV HOME /var/www

ADD start.sh /
RUN chmod +x /start.sh

CMD ["/start.sh"]

