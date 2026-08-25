#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Bubblewrap sandbox diagnostic. v3.7.9, second revision.
# / Usage.  sudo bash diagnose-bwrap.sh
# /
# / The first revision of this script was wrong in two ways & both are corrected here.
# / Piping a command into sed reports SED's exit status, so every result read as success.
# / The narrowing loop discarded stderr, so six identical failures carried no information.
# / Output is captured into a variable & the status is read before anything else touches it.
# /
# / This changes nothing.
# / -----------------------------------------------------------------------------------
APACHE_USER="${APACHE_USER:-www-data}"
BW="$(command -v bwrap || echo /usr/bin/bwrap)"
BASE="--ro-bind /usr /usr --ro-bind-try /lib /lib --ro-bind-try /lib64 /lib64 --ro-bind-try /bin /bin --proc /proc --dev /dev --tmpfs /tmp"

run_as() {
  # / $1 account, $2 description, rest the bwrap flags.
  local account="$1"; shift
  local label="$1"; shift
  local output status
  output="$(su -s /bin/sh "$account" -c "$BW $* $BASE /usr/bin/true" 2>&1)"
  status=$?
  printf '  %-34s exit %-3s %s\n' "$label" "$status" "${output:-no output}"
}

echo
echo "== Kernel =="
for f in /proc/sys/kernel/unprivileged_userns_clone \
         /proc/sys/user/max_user_namespaces \
         /proc/sys/kernel/apparmor_restrict_unprivileged_userns; do
  if [ -e "$f" ]; then printf '  %-54s %s\n' "$(basename "$f")" "$(cat "$f")"; else printf '  %-54s %s\n' "$(basename "$f")" "absent"; fi
done

echo
echo "== Bubblewrap =="
ls -l "$BW" | sed 's/^/  /'
printf '  version %s\n' "$("$BW" --version 2>&1)"

echo
echo "== AppArmor profiles attached to /usr/bin/bwrap =="
for f in /etc/apparmor.d/*; do
  [ -f "$f" ] || continue
  if grep -q '/usr/bin/bwrap' "$f" 2>/dev/null; then
    printf '  on disk   %-40s\n' "$f"
    grep -E 'profile|userns|capability|network' "$f" 2>/dev/null | sed 's/^/              /'
  fi
done
echo "  --- loaded ---"
grep -iE 'bwrap' /sys/kernel/security/apparmor/profiles 2>/dev/null | sed 's/^/  /' || echo "  none loaded or securityfs unreadable"

echo
echo "== Probes as root, which can always create a namespace =="
out="$($BW --unshare-all $BASE /usr/bin/true 2>&1)"; st=$?
printf '  %-34s exit %-3s %s\n' "--unshare-all" "$st" "${out:-no output}"

echo
echo "== Probes as $APACHE_USER. THIS is the account that converts =="
run_as "$APACHE_USER" "--unshare-all"        --unshare-all
run_as "$APACHE_USER" "--unshare-user"       --unshare-user
run_as "$APACHE_USER" "--unshare-pid"        --unshare-pid
run_as "$APACHE_USER" "--unshare-ipc"        --unshare-ipc
run_as "$APACHE_USER" "--unshare-uts"        --unshare-uts
run_as "$APACHE_USER" "--unshare-net"        --unshare-net
run_as "$APACHE_USER" "--unshare-cgroup"     --unshare-cgroup
run_as "$APACHE_USER" "user + net together"  --unshare-user --unshare-net
run_as "$APACHE_USER" "all except net"       --unshare-user --unshare-pid --unshare-ipc --unshare-uts --unshare-cgroup
run_as "$APACHE_USER" "no unshare at all"

echo
echo "== Interpretation =="
echo "  If every probe fails, the namespace itself is refused."
echo "  If only the ones including --unshare-net fail, loopback setup is the problem &"
echo "  everything except network isolation is available."
echo "  If 'all except net' passes, HRConvert2 can sandbox with --share-net as a fallback,"
echo "  which keeps filesystem isolation & loses the network protection."
echo