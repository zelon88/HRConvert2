#!/bin/sh
# / -----------------------------------------------------------------------------------
# / HRConvert2 DATA directory protection verifier.
# /
# / A .htaccess that is not read produces no error & no log line, so the only honest way to
# / know whether these rules are in effect is to ASK THE SERVER. This does that.
# /
# / Usage:  ./verify-data-protection.sh http://your-host/path-to-hrconvert2
# /
# / It uploads nothing & needs no tokens. It writes one canary file directly into the DATA
# / tree, fetches it over HTTP, reads the headers & removes it again.
# / Run it ON the server, as a user that can write into $InstLoc/DATA.
# / -----------------------------------------------------------------------------------

BASEURL="$1"
INSTLOC="${2:-$(pwd)}"

if [ -z "$BASEURL" ]; then
  echo "Usage: $0 <base-url> [install-location]"
  echo "   eg: $0 http://localhost/HRConvert2 /var/www/html/HRProprietary/HRConvert2"
  exit 2
fi

DATADIR="$INSTLOC/DATA"
if [ ! -d "$DATADIR" ]; then
  echo "FAIL: no DATA directory at $DATADIR"
  echo "      Pass the install location as the second argument."
  exit 2
fi

CANARY="hrc2-protection-canary.svg"
CANARYPATH="$DATADIR/$CANARY"

# / An inert probe. It carries a script element so the test matches the real shape, but the
# / script does nothing at all.
cat > "$CANARYPATH" <<'SVG'
<svg xmlns="http://www.w3.org/2000/svg"><script>/* inert probe */</script></svg>
SVG

HEADERS=$(curl -s -D - -o /dev/null "$BASEURL/DATA/$CANARY" 2>/dev/null)
rm -f "$CANARYPATH"

STATUS=$(printf '%s' "$HEADERS" | head -1)
DISP=$(printf '%s' "$HEADERS" | grep -i '^content-disposition:' | tr -d '\r')
CSP=$(printf '%s' "$HEADERS" | grep -i '^content-security-policy:' | tr -d '\r')
SNIFF=$(printf '%s' "$HEADERS" | grep -i '^x-content-type-options:' | tr -d '\r')
CTYPE=$(printf '%s' "$HEADERS" | grep -i '^content-type:' | tr -d '\r')

echo "HRConvert2 DATA protection check"
echo "  URL          : $BASEURL/DATA/$CANARY"
echo "  status       : ${STATUS:-(no response)}"
echo "  content-type : ${CTYPE:-(none)}"
echo "  disposition  : ${DISP:-(none)}"
echo "  csp          : ${CSP:-(none)}"
echo "  nosniff      : ${SNIFF:-(none)}"
echo ""

if printf '%s' "$STATUS" | grep -qE '50[0-9]'; then
  echo "BROKEN: the server returned an error for the canary."
  echo "        THIS IS AN OUTAGE, NOT AN EXPOSURE. No download & no share link works."
  echo ""
  echo "        The usual cause is a directive in DATA/.htaccess that the AllowOverride"
  echo "        level for that directory does not permit. Apache REFUSES such a request"
  echo "        rather than ignoring the directive, & an <IfModule> guard does not prevent"
  echo "        it, because <IfModule> tests whether a module is loaded rather than whether"
  echo "        a directive is allowed."
  echo ""
  echo "        Only Header & RemoveHandler are legal under AllowOverride FileInfo."
  echo "        Options & php_flag need AllowOverride Options & will 500 without it."
  echo ""
  echo "        The web server error log names the offending line."
  echo "        Deleting DATA/.htaccess restores service immediately."
  exit 1
fi

if printf '%s' "$STATUS" | grep -q '404'; then
  echo "INCONCLUSIVE: the server returned 404 for the canary."
  echo "              The base URL is probably wrong, or DATA is not served at <base>/DATA/."
  exit 2
fi

PROTECTED=0
printf '%s' "$DISP" | grep -qi 'attachment' && PROTECTED=1
printf '%s' "$CSP" | grep -qi 'sandbox' && PROTECTED=1

if [ "$PROTECTED" = "1" ]; then
  echo "PASS: this DATA tree is served as inert content."
  [ -z "$DISP" ] && echo "      Note: no Content-Disposition. The CSP is carrying this alone."
  [ -z "$CSP" ] && echo "      Note: no CSP. Content-Disposition is carrying this alone."
  [ -z "$SNIFF" ] && echo "      Note: no X-Content-Type-Options. It closes nothing here on its own, but set it anyway."
  exit 0
fi

echo "FAIL: this DATA tree serves user supplied files as renderable documents."
echo "      An uploaded SVG will execute its own script in this origin."
echo ""
echo "      If you added a DATA/.htaccess, it is being ignored. Apache only reads one when"
echo "      AllowOverride is enabled for that directory, & Debian, Ubuntu & the standard"
echo "      php:*-apache container all ship AllowOverride None for /var/www. nginx never"
echo "      reads one at all."
echo ""
echo "      Apply the rules in the server configuration instead. See"
echo "      Documentation/ABOUT_DATA_DIRECTORY_PROTECTION.txt."
exit 1
