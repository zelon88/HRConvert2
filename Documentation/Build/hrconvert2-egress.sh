#!/bin/sh
# / -----------------------------------------------------------------------------------
# / HRConvert2 v3.9.1. Egress filter, installed INSIDE a sandbox before the tool runs.
# /
# / This script is the first thing executed in a networked namespace. It installs a
# / destination allowlist with nftables & then replaces itself with the tool, so the tool
# / never runs without the filter in front of it.
# /
# / It works because an unprivileged user namespace is root over the network namespace it
# / owns. bwrap grants CAP_NET_ADMIN over that one namespace & nothing else, so a rule set
# / installed here binds the tool & touches nothing on the host.
# / That was proved on 2026-09-05 inside a bwrap namespace with no host privilege at all.
# /
# / PINNED carries the approved addresses, comma separated, set by sandboxCommand from the
# / same records that wrote the hosts file. The two therefore cannot disagree.
# / Loopback is allowed so a tool may talk to itself. Everything else is dropped, including
# / a redirect to a literal address that no hosts file could ever have stopped.
# /
# / This script never sees user input. Every value it reads was written by the application.
# / -----------------------------------------------------------------------------------
if ! command -v nft >/dev/null 2>&1; then
  echo "hrconvert2-egress: nft is not installed, so no filter can be installed. Refusing to run the tool without one." >&2
  exit 96
fi
nft add table inet hrconvert2egress || exit 97
nft add chain inet hrconvert2egress out '{ type filter hook output priority 0; policy drop; }' || exit 97
nft add rule inet hrconvert2egress out oif lo accept || exit 97
for address in $(printf '%s' "${PINNED:-}" | tr ',' ' '); do
  case "$address" in
    *:*) nft add rule inet hrconvert2egress out ip6 daddr "$address" accept || exit 97 ;;
    *)   nft add rule inet hrconvert2egress out ip daddr "$address" accept || exit 97 ;;
  esac
done
exec "$@"
