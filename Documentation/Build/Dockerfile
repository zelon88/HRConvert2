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
# v3.8.6.
# This file contains the Docker build file for the project.
#
# Build this from the repository root, not from this directory.
#   docker build -t hrconvert2 -f Documentation/Build/Dockerfile .
#
# Run this with...
#   docker run -d -p 8080:80 --cap-add SYS_ADMIN hrconvert2
#
# THE BUILD CONTEXT IS THE SOURCE. THIS FILE DOES NOT CLONE.
# An earlier version fetched the repository from GitHub & checked out a reference passed
# in as a build argument. That meant the image never contained the code being built. A
# pull request could not be tested at all, because the merge commit a pull request builds
# against exists only in the fork's pull refs & not in the repository a clone reaches.
# Uncommitted work was never tested either, & the build needed the network to reach
# GitHub halfway through. The context is copied instead, so the image contains exactly
# what the build was handed. Keep .dockerignore beside this file's repository root.
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

# / Everything the bootstrap needs to reach the point where it can take over.
# / ca-certificates so package fetches can verify, sudo & procps for the setup routines,
# / curl for the health check. Every conversion dependency is installed by the manifest,
# / so this list does not grow when the manifest does.
RUN apt-get update && DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
      ca-certificates sudo procps curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html/HRProprietary/HRConvert2

# / THE MANIFEST ARRIVES BEFORE THE REST OF THE SOURCE, & THAT ORDERING IS THE CACHE.
# / Installing every conversion dependency is by far the longest step in this build.
# / Copying the whole tree first would invalidate that layer whenever any source file
# / changed, so a one line comment edit would rebuild LibreOffice, Calibre & the rest.
# / The core & the manifest are enough to run a dependency install, so they are copied on
# / their own & the install is cached against them. It re-runs when depends.php changes,
# / which is exactly when it should.
COPY convertCore.php ./convertCore.php
COPY Resources/depends.php ./Resources/depends.php
COPY Resources/DependencyCore ./Resources/DependencyCore
COPY Resources/SetupCore ./Resources/SetupCore

RUN php ./convertCore.php --setup --install-depends -y \
    || echo "Dependency installation reported problems. The image will still build & -v will report what is missing."

# / The rest of the source. Everything after this layer rebuilds on any source change,
# / which is why the expensive step above is not one of them.
COPY . .

# / The data location lives outside the web root, so conversions are never served directly
# / by the web server.
RUN mkdir -p /DATA/HRConvert2

# / Hand the whole installation to the account the web server runs as, once, before setup.
RUN chown -R www-data:www-data /var/www /DATA

# / THE INSTALL IS THE SAME ONE A BARE METAL HOST RUNS.
# / --install-complete creates the data locations, corrects permissions, writes the PHP
# / drop in, writes the ImageMagick policy at whatever prefix this image actually uses, &
# / verifies the result. The dependency stage above has already run, so it finds every
# / dependency present & moves on.
# / -y answers the confirmation, because a build has nobody to ask.
# / The listener service unit is not installed. A container has no systemd & the entrypoint
# / starts the listener directly when the configuration asks for one.
RUN php ./convertCore.php --setup --install-complete -y \
    || echo "Setup reported problems. The image will still build & -v will report what is missing."

# / Report what the image actually ended up with. This output is part of the build log &
# / is the record of what this image can convert.
RUN php ./convertCore.php -v || true

# / The container serves the application from the web root it was copied into.
RUN a2enmod rewrite headers || true

# / THE VOLUME IS DECLARED LAST, & THAT POSITION MATTERS.
# / Docker discards every change a later layer makes to a declared volume path. Declaring
# / this before the chown & the setup above meant both were thrown away, so a container
# / started from the image found an empty directory owned by root & the entrypoint had to
# / redo the work on every cold start. Declared here, the layers above are kept.
VOLUME /DATA/HRConvert2

EXPOSE 80

# / A liveness probe that does NOT touch the application.
# / A request to convertCore.php issues a session on arrival, so probing it every thirty
# / seconds would leave a session directory behind every thirty seconds, forever. Opening
# / the port answers the only question a health check needs to ask.
HEALTHCHECK --interval=30s --timeout=5s --start-period=90s --retries=3 \
  CMD php -r 'exit(@fsockopen("127.0.0.1", 80, $e, $s, 3) ? 0 : 1);' || exit 1

# / Copy the entrypoint script from the repository into the root of the Docker filesystem.
COPY ./Documentation/Build/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

ENTRYPOINT ["/entrypoint.sh"]
CMD ["apache2-foreground"]