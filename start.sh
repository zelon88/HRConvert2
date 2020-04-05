#!/bin/bash

/usr/bin/unoconv -l &
/usr/sbin/apache2 -D FOREGROUND
