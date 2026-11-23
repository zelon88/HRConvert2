#!/bin/bash
# The HRProprietary Engine's tests. Assembles one of the kit's examples into an application the suites & the checkers can run in, from v4.3.1.
#
#   bash Documentation/Build/Tests/assemble-example.sh /path/to/a/new/folder [HRConvert2|Starter]
#
# Run it from the root of the kit. The kit is not an application: its Engine, its pipelines & each example's entry point & contract
# sit in separate folders, & a suite runs from the root of an application. This copies each where an application keeps it.
# The example is HRConvert2 where none is named. The Starter, from v4.3.2, is the smallest application the Engine runs, at app.php.
#
#   Example/<example>/<entry point>                      The root of the new folder. convertCore.php, or app.php for the Starter.
#   Example/<example>/Resources/versionInfo.php          Resources.
#   Example/<example>/Resources/Contract                 Resources/Contract.
#   Resources/Kernel, Engine, Pipelines & ScanCore       Resources, with Resources/index.html.
#   Documentation                                        Documentation, & an example's own build scripts in Documentation/Build.
#   LICENSE & README.md                                  The root of the new folder.
#
# The new folder must not exist, so nothing that was there is ever written over. This script makes it & copies into it.
# It removes nothing. It holds no rm, rmdir, unlink or mv.

set -u

if [ "$#" -lt 1 ] || [ "$#" -gt 2 ]; then
  echo "Usage: bash Documentation/Build/Tests/assemble-example.sh /path/to/a/new/folder [HRConvert2|Starter]" >&2
  exit 64
fi

target="$1"
case "$target" in
  /*) ;;
  *) echo "assemble-example.sh: name the new folder from the filesystem root, & nothing was copied." >&2; exit 64 ;;
esac

example="${2:-HRConvert2}"
case "$example" in
  HRConvert2) entry=convertCore.php ;;
  Starter) entry=app.php ;;
  *) echo "assemble-example.sh: name HRConvert2 or Starter as the example, & nothing was copied." >&2; exit 64 ;;
esac

if [ ! -f "Example/$example/$entry" ] || [ ! -f Resources/Kernel/kernel.php ] || [ ! -f Resources/Engine/engine.php ]; then
  echo "assemble-example.sh: run this from the root of the kit, which holds Example/ & Resources/, & nothing was copied." >&2
  exit 64
fi

if [ -e "$target" ] || [ -L "$target" ]; then
  echo "assemble-example.sh: $target already exists, & nothing was copied. Name a folder that does not exist yet." >&2
  exit 73
fi

mkdir -p "$target/Resources" \
  && cp -a "Example/$example/$entry" "$target/" \
  && cp -a "Example/$example/Resources/versionInfo.php" "Example/$example/Resources/Contract" "$target/Resources/" \
  && cp -a Resources/Kernel Resources/Engine Resources/Pipelines Resources/ScanCore Resources/index.html "$target/Resources/" \
  && cp -a Documentation "$target/" \
  && { [ ! -d "Example/$example/Documentation/Build" ] || cp -a "Example/$example/Documentation/Build/." "$target/Documentation/Build/"; } \
  && cp -a LICENSE README.md "$target/" \
  || { echo "assemble-example.sh: a copy failed, so $target holds part of the $example example. Name another folder." >&2; exit 74; }

echo "The $example example is assembled at $target. Run the suites & the checkers from there."
