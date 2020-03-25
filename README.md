# Of Ether Converter

Docker deployment of a custom variant of HRConvert2 from https://github.com/zelon88/HRConvert2 - A self-hosted, drag-and-drop, & nosql file conversion server that supports 60+ file formats.

![Of Ether Converter](https://github.com/techyowl/Of-Ether-Converter/blob/master/HRConvert2/Screenshots/of-ether-converter-1.jpg)

## Docker Files based on the work on Docker Hub here
https://hub.docker.com/r/dwaaan/hrconvert2-docker

## Requirements

- At least 512M RAM
- At Least 1 CPU Core
- Docker installed
- Docker Compose installed
- A FQDN or local IP e.g. example.com or 192.168.0.24
- A Logo

## Tested On

- Ubuntu 18.04
  - Used `sudo apt install docker docker-compose`


## Deployment Guide

1. git clone https://github.com/techyowl/Of-Ether-Converter
2. Edit HRConvert2/config.php
3. Set Site Title & Site Logo Path at bottom.  
4. Update salts to be randomly set for your server each with high entropy.
5. (optional)Edit docker-compose.yml changing only the port 8085 to be your desired exposed port for the converter.
6. run `docker-compose up -d`
7. open browser to your FQDN or Local IP with your desired port or :8085 and have fun using your instance of Of Ether Converter.

*[OFFICIAL WEBSITE (Try Of Ether Apps!)](https://www.ofether.com)*
