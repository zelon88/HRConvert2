# -----------------------------------------------------------------------------------
# COPYRIGHT INFORMATION ...
# HRConvert2, Copyright on 8/25/2026 by Justin Grimes, www.github.com/zelon88
#
# LICENSE INFORMATION ...
# This project is protected by the GNU GPLv3 Open-Source license.
# https://www.gnu.org/licenses/gpl-3.0.html
#
# APPLICATION INFORMATION ...
# This application is designed to provide a web-interface for converting file formats
# on a server for users of any web browser without authentication.
#
# FILE INFORMATION ...
# v3.7.9.
# This file contains the Docker build file for the project.
#
# Build this with...
#   docker build -t hrconvert2 .
#
# Run this with...
#   docker run -d -p 8080:80 --cap-add SYS_ADMIN hrconvert2
#
# SYS_ADMIN IS REQUIRED FOR THE SANDBOX.
# Every conversion runs inside a bubblewrap namespace with no network. A container cannot
# create that namespace without the capability. Without it the sandbox is unavailable &
# every conversion is refused, unless --Require Sandbox On Docker-- is FALSE in config.php,
# which runs conversions unprotected & is not recommended for anything public facing.
# An alternative to the capability is  --security-opt seccomp=unconfined  on some hosts.
#
# DEPENDENCY REQUIREMENTS ...
# Nothing is listed here on purpose. Resources/depends.php is the manifest & this file
# installs from it, so a dependency added there arrives here without editing this file.
#
# <3 Open-Source
# -----------------------------------------------------------------------------------

FROM php:8.5-apache-bookworm

# / The reference to build. A tag, a branch, or a commit.
ARG HRCONVERT2_REF=master

# / Everything the bootstrap needs to reach the point where it can take over.
# / git to fetch the source, ca-certificates so it can, sudo & procps for the setup
# / routines. Every conversion dependency is installed by --install-complete from the
# / manifest, so this list does not grow when the manifest does.
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
      git ca-certificates sudo procps \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/HRProprietary

RUN git clone https://github.com/zelon88/HRConvert2 \
    && cd HRConvert2 \
    && git checkout "$HRCONVERT2_REF"

# / The data location lives outside the web root & is a volume, so conversions survive a
# / container restart & are never served directly by the web server.
RUN mkdir -p /DATA/HRConvert2
VOLUME /DATA/HRConvert2

# / Hand the whole installation to the account the web server runs as, once, before setup.
RUN chown -R www-data:www-data /var/www /DATA

# / THE INSTALL IS THE SAME ONE A BARE METAL HOST RUNS.
# / --install-complete installs every dependency in the manifest, creates the data
# / locations, corrects permissions, writes the PHP drop in, writes the ImageMagick policy
# / at whatever prefix this image actually uses, & verifies the result.
# / -y answers the confirmation, because a build has nobody to ask.
# / The listener service unit is not installed. A container has no systemd & the entrypoint
# / starts the listener directly when the configuration asks for one.
RUN php /var/www/html/HRProprietary/HRConvert2/convertCore.php --setup --install-complete -y \
    || echo "Setup reported problems. The image will still build & -v will report what is missing."

# / Report what the image actually ended up with. This output is part of the build log &
# / is the record of what this image can convert.
RUN php /var/www/html/HRProprietary/HRConvert2/convertCore.php -v || true

# / The container serves the application from the web root it was cloned into.
RUN a2enmod rewrite headers || true
EXPOSE 80

COPY Documentation/entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh
ENTRYPOINT ["/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
