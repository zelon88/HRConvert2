#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 8/21/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / Application Information ...
# / This application is designed to provide a web-interface for converting file formats
# / on a server for users of any web browser without authentication.
# /
# / File Information ...
# / v4.0.0.
# / This file is a bubblewrap bisect harness for LibreOffice, & can be adapted for other
# / dependencies. It finds the least permissive bubblewrap configuration a dependency will
# / still work in, which comes in handy when developing a new Conversion Pipeline.
# / Usage.  sudo bash bisect.sh /path/to/any.docx
# / Set the script up to call the dependency the way your Conversion Pipeline does. Give
# / it an input file to operate on, & an output file in a format the dependency can write.
# / When run, it tests that conversion against a series of bubblewrap environments, & the
# / report at the end says exactly which settings break the dependency. To put that
# / another way, it tells you what your dependency requires from bubblewrap to function.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / Dependency Requirements ...
# / This file requires bash, bubblewrap & LibreOffice, & is run as root.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------

set -u
APACHE_USER="${APACHE_USER:-www-data}"
INPUT="${1:-}"
BWRAP="$(command -v bwrap || echo /usr/bin/bwrap)"
SOFFICE="$(command -v soffice || command -v libreoffice || echo /usr/lib/libreoffice/program/soffice)"

if [ -z "$INPUT" ] || [ ! -f "$INPUT" ]; then
  echo "Usage. sudo bash bisectSandbox2.sh /path/to/any.docx"
  exit 1
fi

echo "== What the installation actually looks like =="
for p in /etc/libreoffice /var/lib/libreoffice /usr/lib/libreoffice /usr/share/libreoffice; do
  if [ -e "$p" ]; then echo "  PRESENT  $p"; else echo "  absent   $p"; fi
done
echo "  sofficerc:"
ls -l /usr/lib/libreoffice/program/sofficerc 2>/dev/null | sed 's/^/    /'
ls -l /usr/lib/libreoffice/program/bootstraprc 2>/dev/null | sed 's/^/    /'
ls -l /usr/lib/libreoffice/program/fundamentalrc 2>/dev/null | sed 's/^/    /'
echo "  dangling symlinks under /usr/lib/libreoffice:"
find /usr/lib/libreoffice -maxdepth 3 -xtype l 2>/dev/null | head -20 | sed 's/^/    /'
echo

WORK="$(mktemp -d /tmp/hrc2-bisect2-XXXXXX)"
cp "$INPUT" "$WORK/input.docx"
chown -R "$APACHE_USER":"$APACHE_USER" "$WORK"
chmod 0777 "$WORK"
echo "workdir  $WORK"
echo

BASE="--unshare-all --die-with-parent --new-session \
 --ro-bind /usr /usr \
 --ro-bind-try /lib /lib --ro-bind-try /lib64 /lib64 \
 --ro-bind-try /bin /bin --ro-bind-try /sbin /sbin \
 --ro-bind-try /etc/alternatives /etc/alternatives \
 --ro-bind-try /etc/fonts /etc/fonts \
 --ro-bind-try /etc/ld.so.cache /etc/ld.so.cache \
 --ro-bind-try /etc/passwd /etc/passwd --ro-bind-try /etc/group /etc/group \
 --proc /proc --dev /dev --tmpfs /tmp --tmpfs /run --tmpfs /dev/shm \
 --setenv HOME /tmp --setenv XDG_RUNTIME_DIR /tmp \
 --setenv XDG_CONFIG_HOME /tmp/.config --setenv XDG_CACHE_HOME /tmp/.cache"

NAME=(
  "0  base only"
  "1  + WHOLE /etc read only        (diagnostic, over permissive)"
  "2  + WHOLE /var read only        (diagnostic, over permissive)"
  "3  + /etc/libreoffice only"
  "4  + /var/lib/libreoffice only"
  "5  + /etc/libreoffice AND /var/lib/libreoffice"
  "6  + stage 5 plus svp & no OpenCL"
  "7  NO SANDBOX, control"
)
ARGS=(
  ""
  "--ro-bind /etc /etc"
  "--ro-bind /var /var"
  "--ro-bind-try /etc/libreoffice /etc/libreoffice"
  "--ro-bind-try /var/lib/libreoffice /var/lib/libreoffice"
  "--ro-bind-try /etc/libreoffice /etc/libreoffice --ro-bind-try /var/lib/libreoffice /var/lib/libreoffice"
  "--ro-bind-try /etc/libreoffice /etc/libreoffice --ro-bind-try /var/lib/libreoffice /var/lib/libreoffice --setenv SAL_USE_VCLPLUGIN svp --setenv SAL_DISABLE_OPENCL 1"
  "SKIP"
)

PASSED=""
for i in "${!NAME[@]}"; do
  rm -f "$WORK"/input.pdf
  echo "=============================================================="
  echo "STAGE ${NAME[$i]}"
  if [ "${ARGS[$i]}" = "SKIP" ]; then
    OUT=$(su -s /bin/sh "$APACHE_USER" -c \
      "cd '$WORK' && LANG=C.UTF-8 LC_ALL=C.UTF-8 HOME='$WORK' '$SOFFICE' --headless --norestore --invisible --nolockcheck --nodefault --nofirststartwizard --nologo -env:UserInstallation=file://$WORK/lo --convert-to pdf --outdir . '$WORK/input.docx' 2>&1")
  else
    OUT=$(su -s /bin/sh "$APACHE_USER" -c \
      "LANG=C.UTF-8 LC_ALL=C.UTF-8 '$BWRAP' $BASE ${ARGS[$i]} --bind '$WORK' /work --chdir /work '$SOFFICE' --headless --norestore --invisible --nolockcheck --nodefault --nofirststartwizard --nologo -env:UserInstallation=file:///tmp/hrc2-libreoffice --convert-to pdf --outdir . /work/input.docx 2>&1")
  fi
  if [ -f "$WORK/input.pdf" ]; then
    echo "  RESULT   PASS, $(stat -c%s "$WORK/input.pdf") byte PDF"
    if [ -z "$PASSED" ]; then PASSED="${NAME[$i]}"; fi
  else
    echo "  RESULT   FAIL"
    echo "$OUT" | grep -viE '^ *(/usr|/lib|/opt)' | head -6 | sed 's/^/           /'
  fi
done

echo "=============================================================="
echo "FIRST PASS.  ${PASSED:-NONE}"
echo
echo "If stage 1 passed but 3, 4 & 5 did not, something else in /etc is required."
echo "If stage 2 passed but 4 & 5 did not, something else in /var is required."
echo "In either case re-run with that stage & add:  --strace  is not available, so instead"
echo "run the failing stage by hand with  strace -f -e trace=file  to see the missing path."
echo
echo "rm -rf $WORK   when finished."
