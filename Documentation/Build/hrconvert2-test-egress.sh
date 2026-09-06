#!/bin/bash
# / -----------------------------------------------------------------------------------
# / HRConvert2 v3.9.0. Proves the egress design end to end on THIS host.
# / Run as the web server account:  sudo -u www-data bash Documentation/Build/hrconvert2-test-egress.sh
# /
# / It starts pasta, which gives an unshared namespace an interface, then bwrap, which
# / installs the egress filter & runs curl twice: once to a pinned address, which must
# / succeed, & once to an address that was not pinned, which must be refused.
# / Both results are printed & the exit code is zero only when both are as expected.
# /
# / The filter half of this was proved in a container with no host privilege. The pasta
# / half could not be, because that container could not open a tap device. This script is
# / how the second half gets proved, & it must pass before sandboxCommand is taught any of
# / this.
# / -----------------------------------------------------------------------------------
set -u
cd "$(dirname "$0")/../.." || exit 2
for tool in pasta bwrap nft curl; do
  command -v "$tool" >/dev/null 2>&1 || { echo "  $tool is not installed"; exit 2; }
done
ALLOW=$(getent ahostsv4 github.com | awk '{print $1; exit}')
[ -n "$ALLOW" ] || { echo "  could not resolve a test address"; exit 2; }
echo "  pinned address: $ALLOW"
RESULT=$(timeout 60 pasta --config-net -4 --quiet -- \
  bwrap --unshare-ipc --unshare-pid --unshare-uts --cap-add CAP_NET_ADMIN \
    --ro-bind /usr /usr --ro-bind /lib /lib --ro-bind-try /lib64 /lib64 --ro-bind /bin /bin \
    --ro-bind /sbin /sbin --ro-bind /etc/ssl /etc/ssl --ro-bind-try /etc/nftables.conf /etc/nftables.conf \
    --ro-bind "$(pwd)/Documentation/Build/hrconvert2-egress.sh" /egress.sh \
    --proc /proc --dev /dev --tmpfs /tmp --setenv PINNED "$ALLOW" -- \
    /egress.sh sh -c "a=\$(curl -s -o /dev/null -w '%{http_code}' --resolve github.com:443:$ALLOW --max-time 10 https://github.com/); b=\$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 http://1.1.1.1/ || echo REFUSED); echo \"\$a \$b\"" 2>&1 | tail -1)
ALLOWED=$(echo "$RESULT" | awk '{print $1}'); BLOCKED=$(echo "$RESULT" | awk '{print $2}')
echo "  pinned destination answered:   $ALLOWED   (want 200 or 301)"
echo "  unpinned destination answered: $BLOCKED   (want REFUSED or 000)"
case "$ALLOWED" in 200|301|302) ok1=1 ;; *) ok1=0 ;; esac
case "$BLOCKED" in REFUSED|000) ok2=1 ;; *) ok2=0 ;; esac
if [ "$ok1" = 1 ] && [ "$ok2" = 1 ]; then echo "  EGRESS FILTER PROVED ON THIS HOST"; exit 0; fi
echo "  not proved. Read the two lines above. pasta output follows."; echo "$RESULT"; exit 1
