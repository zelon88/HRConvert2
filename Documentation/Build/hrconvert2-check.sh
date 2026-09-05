#!/bin/bash
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 9/4/2026 by Justin Grimes, www.github.com/zelon88
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
# / v3.9.0.
# / This file is a SECOND OPINION on the source & is not the authoritative one.
# / hrconvert2-check.py is authoritative. This exists so two implementations written in
# / two languages can be asked the same question, & so a disagreement between them is a
# / signal that one of them is wrong.
# /
# / Run it from the installation root, beside convertCore.php.
# /   bash Documentation/Build/hrconvert2-check.sh
# /   bash Documentation/Build/hrconvert2-check.sh --list
# /   bash Documentation/Build/hrconvert2-check.sh --only pins
# /   bash Documentation/Build/hrconvert2-check.sh --quiet
# /
# / It exits zero when every check passes, one when any of them does not & two when it was
# / asked something it could not answer, exactly as the Python does.
# / Eleven of the Python's twelve checks are here. The one that is not is named below.
# /
# / WHY TWO OF THESE EXIST.
# / A checker has bugs like anything else, & a bug in a checker is invisible, because the
# / result it produces is the same silence a clean file produces.
# / Two implementations sharing no code do not share bugs. When they agree the answer is
# / probably right & when they disagree exactly one of them is, which is worth knowing.
# / The Python parses. This one uses awk, grep & the shell. That difference is the point &
# / making this a translation of the other would remove the only value it has.
# /
# / WHERE THIS ONE IS WEAKER, STATED PLAINLY.
# / The call order check follows one level of calls rather than the whole call graph, so it
# / finds a component called directly by the main logic & not one reached through three
# / other functions. The Python follows the graph & is the one to trust.
# / The undefined read check is NOT IMPLEMENTED HERE & that is a decision rather than an
# / omission. Working out which names a function creates needs a pass per function over its
# / whole body, & in awk on a file of seven thousand lines that took longer than everything
# / else in this script put together. A check nobody waits for is a check nobody runs.
# / Eleven checks are here & the twelfth is in the Python. Run the Python for it.
# / Neither difference is a bug in this file. Both are stated here so a disagreement can be
# / read correctly rather than chased.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / Dependency Requirements ...
# / This file requires bash, awk, grep & sed. The syntax check additionally requires the
# / php binary & is skipped with a notice when it is not installed.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------

set -u

QUIET=0
ONLY=''
LIST=0
FINDINGS=0
WORKDIR=''

# / -----------------------------------------------------------------------------------
# / Report one finding. Accepts a kind & a message.
# / Every finding raises the count, so the exit code follows from what was reported rather
# / than from anything having to remember to set it.
report() {
  FINDINGS=$((FINDINGS + 1))
  if [ "$QUIET" -eq 0 ]; then
    printf '  %-10s %s\n' "$1" "$2"
  fi
}

# / -----------------------------------------------------------------------------------
# / Blank every comment & string literal in one file, keeping every line where it was.
# / Accepts a path. Writes the blanked file to standard output.
# / State is carried between lines, because a string in PHP may run across several of them
# / & a reader that starts fresh on each line treats the rest of that string as code.
# / A cache file this project writes contains generated PHP inside a string, which is the
# / case that makes a per line reader report things that are not there.
blank_file() {
  awk '
    BEGIN { q = ""; blockcomment = 0 }
    {
      line = $0
      out = ""
      i = 1
      n = length(line)
      while (i <= n) {
        c = substr(line, i, 1)
        two = substr(line, i, 2)
        if (blockcomment == 1) {
          if (two == "*/") { blockcomment = 0; out = out "  "; i = i + 2; continue }
          out = out " "; i = i + 1; continue
        }
        if (q == "") {
          if (two == "//" || c == "#") { break }
          if (two == "/*") { blockcomment = 1; out = out "  "; i = i + 2; continue }
          if (c == "\"" || c == "\047") { q = c; out = out c c; i = i + 1; continue }
          out = out c; i = i + 1; continue
        }
        if (c == "\\") { out = out "  "; i = i + 2; continue }
        if (c == q) { q = "" }
        out = out " "; i = i + 1
      }
      print out
    }' "$1"
}

# / -----------------------------------------------------------------------------------
# / Prepare a blanked copy of every PHP file once, so each check reads the same thing.
# / A blanked copy lives beside the original under a scratch directory named for it, which
# / is removed when this script exits however it exits.
prepare() {
  WORKDIR=$(mktemp -d)
  trap 'rm -rf "$WORKDIR"' EXIT
  while IFS= read -r file; do
    target="$WORKDIR/$(printf '%s' "$file" | sed 's#[/.]#_#g')"
    blank_file "$file" > "$target"
  done < <(php_files)
}

# / Every PHP file this project owns. A data location & a log directory hold no source.
php_files() {
  find . -name '*.php' -not -path './.git/*' -not -path './DATA/*' -not -path './Logs/*' \
    -not -path './node_modules/*' | sort
}

# / The blanked copy of one file.
blanked_of() {
  printf '%s' "$WORKDIR/$(printf '%s' "$1" | sed 's#[/.]#_#g')"
}

# / -----------------------------------------------------------------------------------
# / CHECK. The php binary parses every file.
check_syntax() {
  if ! command -v php > /dev/null 2>&1; then
    report 'SKIP' 'php is not installed, so the syntax check did not run.'
    return
  fi
  while IFS= read -r file; do
    if ! php -l "$file" > /dev/null 2>&1; then
      report 'SYNTAX' "$file: $(php -l "$file" 2>&1 | head -1)"
    fi
  done < <(php_files)
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every delimiter closes.
# / Counted on the blanked copy, so a brace inside a string is not a brace.
check_balance() {
  while IFS= read -r file; do
    awk -v name="$file" '
      { for (i = 1; i <= length($0); i++) {
          c = substr($0, i, 1)
          if (c == "{") b++; else if (c == "}") b--
          if (c == "(") p++; else if (c == ")") p--
          if (c == "[") s++; else if (c == "]") s-- } }
      END {
        if (b != 0) printf "BALANCE|%s: { is out by %d\n", name, b
        if (p != 0) printf "BALANCE|%s: ( is out by %d\n", name, p
        if (s != 0) printf "BALANCE|%s: [ is out by %d\n", name, s }' "$(blanked_of "$file")"
  done < <(php_files) | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. No function is defined twice.
# / A second definition is a fatal the moment both files load & is the normal result of
# / moving a function & leaving the original behind.
check_duplicate() {
  while IFS= read -r file; do
    awk -v name="$file" '/^function /{ split($0, a, "("); sub(/^function /, "", a[1]); print a[1] "|" name "|" NR }' "$(blanked_of "$file")"
  done < <(php_files) | awk -F'|' '
    { if ($1 in seen) printf "DUPLICATE|%s() is defined in %s and again in %s:%s\n", $1, seen[$1], $2, $3
      else seen[$1] = $2 }' | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every version pin matches the file it points at.
# / A pin whose component is not installed is skipped rather than reported, for the reason
# / the Python states at length. Config, the GUI, the language pack & the install secret
# / are pinned here & none of them is a component with a version constant.
check_pins() {
  grep -oE '\$Required[A-Za-z]+Version[[:space:]]*=[[:space:]]*.v?[0-9.]+.' convertCore.php 2>/dev/null \
  | sed -E "s/\\\$Required([A-Za-z]+)Version[[:space:]]*=[[:space:]]*.v?([0-9.]+)./\1 \2/" \
  | while read -r component pinned; do
      declared=$(grep -rhoE "\\\$${component}Version[[:space:]]*=[[:space:]]*.v?[0-9.]+." Resources/ 2>/dev/null \
                 | sed -E "s/.*v?([0-9]+\.[0-9]+\.[0-9]+)./\1/" | head -1)
      if [ -n "$declared" ] && [ "$declared" != "$pinned" ]; then
        report 'PIN' "$component is pinned at $pinned & Resources declares $declared"
      fi
    done
  # / A manager is pinned by the Engine rather than by the application.
  if [ -f Resources/Engine/engine.php ]; then
    grep -oE "'[A-Za-z]+\.php'[[:space:]]*=>[[:space:]]*'v?[0-9.]+'" Resources/Engine/engine.php 2>/dev/null \
    | sed -E "s/'([A-Za-z]+\.php)'[[:space:]]*=>[[:space:]]*'v?([0-9.]+)'/\1 \2/" \
    | while read -r manager pinned; do
        path="Resources/Engine/Managers/$manager"
        if [ ! -f "$path" ]; then
          report 'PIN' "the Engine pins $manager & no such manager is installed"
        else
          declared=$(grep -oE "\\\$ManagerVersion[[:space:]]*=[[:space:]]*'v?[0-9.]+'" "$path" | sed -E "s/.*'v?([0-9.]+)'/\1/")
          if [ -z "$declared" ]; then
            report 'PIN' "$manager reports no version & an unknown build cannot be cleared"
          elif [ "$declared" != "$pinned" ]; then
            report 'PIN' "$manager is pinned at $pinned & declares $declared"
          fi
        fi
      done
  fi
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every error number raised is documented.
# / Only a real entry in the catalog counts. A number appearing anywhere else in that file
# / is a permission, a year or a size rather than a documented error.
check_errno() {
  catalog='Documentation/ERROR_DESCRIPTIONS.txt'
  if [ ! -f "$catalog" ]; then
    report 'SKIP' 'ERROR_DESCRIPTIONS.txt was not found, so the error number check did not run.'
    return
  fi
  grep -oE 'APPLICATION_NAME>-[0-9]{1,5}' "$catalog" | sed 's/.*-//' | sort -u > "$WORKDIR/documented"
  while IFS= read -r file; do
    tr '\n' ' ' < "$(blanked_of "$file")" \
    | grep -oE '(errorEntry|quickDie)[[:space:]]*\([^;]*,[[:space:]]*[0-9]{1,5}[[:space:]]*[,)]' \
    | grep -oE '[0-9]{1,5}[[:space:]]*[,)]$' | grep -oE '[0-9]+' \
    | sort -u | while read -r number; do
        if ! grep -qx "$number" "$WORKDIR/documented"; then
          printf 'ERRNO|%s raises error %s, which ERROR_DESCRIPTIONS.txt does not document\n' "$file" "$number"
        fi
      done
  done < <(php_files) | sort -u | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every component refuses to be loaded directly.
# / The guard is tested on the blanked copy, so a comment mentioning it does not count as
# / having one. Both the name & a refusal are required, because either alone is something
# / else entirely.
check_guard() {
  if [ ! -d Resources ]; then
    return
  fi
  while IFS= read -r file; do
    blanked=$(blanked_of "$file")
    if ! grep -q 'CoreLoaded' "$blanked" || ! grep -qE '\b(die|exit)[[:space:]]*\(' "$blanked"; then
      report 'GUARD' "$file has no \$CoreLoaded guard & would run if reached directly"
    fi
  done < <(find ./Resources -name '*.php' | sort)
}

# / -----------------------------------------------------------------------------------
# / CHECK. No comment line is entirely capitals.
# / Convention nineteen. Read from the ORIGINAL, because a comment is what the blanked copy
# / has removed. A capitalised word inside a sentence is emphasis rather than shouting & is
# / not reported.
check_caps() {
  while IFS= read -r file; do
    awk -v name="$file" '
      { line = $0
        sub(/^[[:space:]]+/, "", line)
        if (substr(line, 1, 4) != "// /") next
        body = substr(line, 5)
        sub(/^[[:space:]]+/, "", body)
        if (length(body) < 12) next
        letters = 0; upper = 0
        for (i = 1; i <= length(body); i++) {
          c = substr(body, i, 1)
          if (c ~ /[A-Za-z]/) { letters++; if (c ~ /[A-Z]/) upper++ } }
        if (letters > 0 && letters == upper) printf "CAPS|%s:%d %s\n", name, NR, substr(body, 1, 60) }' "$file"
  done < <(php_files) | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every cleanup at a return carries the comment that belongs with it.
# / The comment is how a reader knows the cleanup is deliberate rather than copied.
check_purge() {
  while IFS= read -r file; do
    awk -v name="$file" '
      { lines[NR] = $0 }
      END {
        for (i = 1; i <= NR; i++) {
          if (index(lines[i], "purgeSensitiveMemory(") == 0) continue
          trimmed = lines[i]; sub(/^[[:space:]]+/, "", trimmed)
          if (substr(trimmed, 1, 4) == "// /") continue
          following = lines[i + 1]; sub(/^[[:space:]]+/, "", following)
          if (substr(following, 1, 6) != "return") continue
          found = 0
          for (j = i - 1; j >= 1; j--) {
            back = lines[j]; sub(/^[[:space:]]+/, "", back)
            if (substr(back, 1, 4) != "// /") break
            if (index(back, "Manually clean up sensitive memory") > 0) found = 1 }
          if (found == 0) printf "PURGE|%s:%d a cleanup before a return carries no comment\n", name, i } }' "$file"
  done < <(php_files) | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every function has one exit.
# / A single exit is what makes the cleanup at the return reliable. Three functions are
# / exempt & each is exempt for a reason the Python states.
check_exit() {
  while IFS= read -r file; do
    awk -v name="$file" '
      { lines[NR] = $0 }
      END {
        exempt["quickDie"] = 1; exempt["closeHRC2Connection"] = 1; exempt["mergeConfigFile"] = 1
        for (i = 1; i <= NR; i++) {
          if (substr(lines[i], 1, 9) != "function ") continue
          split(lines[i], parts, "(")
          fname = substr(parts[1], 10)
          if (fname in exempt) continue
          depth = 0; returns = 0; j = i
          while (j <= NR) {
            line = lines[j]
            for (k = 1; k <= length(line); k++) {
              c = substr(line, k, 1)
              if (c == "{") depth++
              else if (c == "}") depth-- }
            stripped = line; sub(/^[[:space:]]+/, "", stripped)
            if (substr(stripped, 1, 7) == "return " || stripped == "return;") returns++
            if (depth == 0 && j > i) break
            j++ }
          if (returns != 1) printf "EXIT|%s:%d %s() has %d return statements\n", name, i, fname, returns } }' "$(blanked_of "$file")"
  done < <(php_files) | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. The Engine never calls application code.
# / The Engine may call the kernel, because the kernel is identical in every application.
# / It may not call anything only this application has.
check_contract() {
  if [ ! -d Resources/Engine ] || [ ! -f convertCore.php ]; then
    return
  fi
  grep -oE '^function [A-Za-z_][A-Za-z0-9_]*' convertCore.php | sed 's/^function //' | sort -u > "$WORKDIR/appfns"
  find ./Resources/Engine -name '*.php' -exec grep -hoE '^function [A-Za-z_][A-Za-z0-9_]*' {} \; \
    | sed 's/^function //' | sort -u > "$WORKDIR/enginefns"
  # / The kernel, which the Engine is permitted to call. This list is the contract & is
  # / written down in Documentation/ABOUT_ENGINE_CONTRACT.txt.
  printf '%s\n' quickDie logEntry warningEntry errorEntry purgeSensitiveMemory redeclare \
    readComponentVersion verifyCoreComponent composeLogEntry emitLogEntry flushLogBuffer \
    locateDependency sanitize getExtension | sort -u > "$WORKDIR/kernel"
  while IFS= read -r file; do
    awk -v name="$(basename "$file")" '
      { while (match($0, /[a-z][A-Za-z0-9_]*[[:space:]]*\(/)) {
          call = substr($0, RSTART, RLENGTH)
          sub(/[[:space:]]*\($/, "", call)
          printf "%s|%s|%d\n", call, name, NR
          $0 = substr($0, RSTART + RLENGTH) } }' "$(blanked_of "$file")"
  done < <(find ./Resources/Engine -name '*.php' | sort) \
  | awk -F'|' -v app="$WORKDIR/appfns" -v eng="$WORKDIR/enginefns" -v ker="$WORKDIR/kernel" '
      BEGIN {
        while ((getline line < app) > 0) application[line] = 1
        while ((getline line < eng) > 0) engine[line] = 1
        while ((getline line < ker) > 0) kernel[line] = 1 }
      { if ($1 in engine || $1 in kernel) next
        if (!($1 in application)) next
        key = $2 "|" $1
        if (key in seen) next
        seen[key] = 1
        printf "CONTRACT|%s:%s calls %s(), which only the application defines\n", $2, $3, $1 }' \
  | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. Nothing in the main logic calls a component before that component is loaded.
# / This follows ONE level of calls rather than the whole call graph, which is where it is
# / weaker than the Python. A component called directly from the main logic is found. One
# / reached through two intervening functions is not.
# / The Python is the one to trust here & this exists to agree with it rather than to
# / replace it.
check_order() {
  if [ ! -f convertCore.php ] || [ ! -d Resources ]; then
    return
  fi
  find ./Resources -name '*.php' -exec grep -HoE '^function [A-Za-z_][A-Za-z0-9_]*' {} \; \
    | sed -E 's#^\./Resources/.*/([^/]+\.php):function #\1 #; s#^\./Resources/([^/]+\.php):function #\1 #' \
    | sort -u > "$WORKDIR/componentfns"
  grep -nE "^list \(.*verifyCoreComponent\(" convertCore.php \
    | grep -oE "^[0-9]+:.*'[A-Za-z]+\.php'" | sed -E "s/^([0-9]+):.*'([A-Za-z]+\.php)'/\2 \1/" > "$WORKDIR/loadlines"
  blanked=$(blanked_of ./convertCore.php)
  awk -v fns="$WORKDIR/componentfns" -v loads="$WORKDIR/loadlines" '
    BEGIN {
      while ((getline line < fns) > 0) { split(line, a, " "); owner[a[2]] = a[1] }
      while ((getline line < loads) > 0) { split(line, b, " "); loadline[b[1]] = b[2] } }
    /^[^ \t#\/]/ && !/^function / {
      copy = $0
      while (match(copy, /[a-z][A-Za-z0-9_]*[[:space:]]*\(/)) {
        call = substr(copy, RSTART, RLENGTH)
        sub(/[[:space:]]*\($/, "", call)
        if (call in owner) {
          file = owner[call]
          if (file in loadline && loadline[file] + 0 > NR) {
            printf "ORDER|line %d: %s() is defined in %s, which loads at line %d\n", NR, call, file, loadline[file] }
        }
        copy = substr(copy, RSTART + RLENGTH) } }' convertCore.php \
  | sort -u | while IFS='|' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / CHECK. Every required configuration setting is declared global where config.php loads.
# / config.php is required inside a function, so a setting that function has not declared
# / global lands in local scope & reads as missing afterwards. An administrator is then told
# / their configuration lacks a setting which is plainly present in it.
# / No other check sees this. The settings are tested by name through a list at runtime, so
# / nothing reads them anywhere & a search for a read finds nothing.
check_config() {
  if [ ! -f convertCore.php ]; then
    return
  fi
  # / The ORIGINAL is read rather than the blanked copy. The required settings are quoted
  # / strings & the blanked copy has emptied every one of them, so reading that copy compares
  # / an empty set against the declarations & reports nothing missing whatever is removed.
  # / A check that cannot fail is worse than no check, because it is counted as having passed.
  awk '
    /\$requiredConfigVars = array\(/ { inlist = 1 }
    inlist == 1 { line = $0
      while (match(line, /\047[A-Za-z_][A-Za-z0-9_]*\047/)) {
        print "REQ " substr(line, RSTART + 1, RLENGTH - 2)
        line = substr(line, RSTART + RLENGTH) }
      if (index($0, ");") > 0) inlist = 0 }
    /^function verifyInstallation\(/ { infn = 1 }
    infn == 1 && /global / { ingl = 1 }
    ingl == 1 { line = $0
      while (match(line, /\$[A-Za-z_][A-Za-z0-9_]*/)) {
        print "DEC " substr(line, RSTART + 1, RLENGTH - 1)
        line = substr(line, RSTART + RLENGTH) }
      if (index($0, ";") > 0) { ingl = 0; infn = 0 } }' convertCore.php \
  | awk '{ if ($1 == "DEC") declared[$2] = 1; else required[$2] = 1 }
      END { for (name in required) if (!(name in declared))
              printf "CONFIG|%s is required & is not declared global where config.php is loaded, so it will read as missing\n", name }' \
  | sort | while IFS='"'"'|'"'"' read -r kind message; do report "$kind" "$message"; done
}

# / -----------------------------------------------------------------------------------
# / The main logic. Every check runs & the total decides the exit code.
# / Nothing stops early, because an operator wants every fault in one pass rather than the
# / first one seven times.

# / Every check, in the order they run, keyed by the name --only accepts.
CHECK_NAMES='syntax balance order duplicate pins errno guard contract config caps purge exit'

describe() {
  case "$1" in
    syntax)    printf 'the php binary parses every file' ;;
    balance)   printf 'every delimiter closes' ;;
    order)     printf 'nothing calls a component before it loads, one level deep' ;;
    duplicate) printf 'no function is defined twice' ;;
    pins)      printf 'every version pin matches the file it points at' ;;
    errno)     printf 'every error number raised is documented' ;;
    guard)     printf 'every component refuses to load directly' ;;
    contract)  printf 'the Engine never calls application code' ;;
    config)    printf 'every required setting is declared where config.php loads' ;;
    caps)      printf 'no comment line is entirely capitals' ;;
    purge)     printf 'every cleanup at a return carries its comment' ;;
    exit)      printf 'every function has one exit' ;;
  esac
}

while [ "$#" -gt 0 ]; do
  case "$1" in
    --quiet) QUIET=1 ;;
    --list)  LIST=1 ;;
    --only)  shift; ONLY="${1:-}" ;;
    *) printf 'Unrecognized argument %s. Run --list to see the checks.\n' "$1"; exit 2 ;;
  esac
  shift
done

if [ "$LIST" -eq 1 ]; then
  printf 'Checks, in the order they run.\n'
  for name in $CHECK_NAMES; do
    printf '  %-12s%s\n' "$name" "$(describe "$name")"
  done
  printf '\nRun one with  --only <name>. Run every one by naming none.\n'
  exit 0
fi

if [ ! -f convertCore.php ]; then
  printf 'Run this from the installation root, beside convertCore.php.\n'
  exit 2
fi

if [ -n "$ONLY" ]; then
  found=0
  for name in $CHECK_NAMES; do
    if [ "$name" = "$ONLY" ]; then found=1; fi
  done
  if [ "$found" -eq 0 ]; then
    printf "There is no check called '%s'. Run --list to see them.\n" "$ONLY"
    exit 2
  fi
fi

prepare

if [ "$QUIET" -eq 0 ]; then
  printf 'Checking %s PHP files under %s.\n\n' "$(php_files | wc -l | tr -d ' ')" "$(pwd)"
fi

# / The count is kept in a file rather than in a variable, because every check pipes its
# / output & a pipeline runs in a subshell whose variables do not survive it.
COUNTFILE="$WORKDIR/count"
: > "$COUNTFILE"
report() {
  printf '%s\n' "$1" >> "$COUNTFILE"
  if [ "$QUIET" -eq 0 ]; then
    printf '  %-10s %s\n' "$1" "$2"
  fi
}

for name in $CHECK_NAMES; do
  if [ -n "$ONLY" ] && [ "$name" != "$ONLY" ]; then continue; fi
  "check_$name"
done

FINDINGS=$(wc -l < "$COUNTFILE" | tr -d ' ')

if [ "$QUIET" -eq 0 ]; then
  printf '\n'
  if [ "$FINDINGS" -eq 0 ]; then
    printf 'Every check passed.\n'
  else
    sort "$COUNTFILE" | uniq -c | while read -r count kind; do
      printf '  %4s  %s\n' "$count" "$kind"
    done
    printf '\n%s finding(s). A finding is not always a fault, & the header for each check\n' "$FINDINGS"
    printf 'says which patterns it reports that are not.\n'
  fi
fi

if [ "$FINDINGS" -gt 0 ]; then exit 1; fi
exit 0
