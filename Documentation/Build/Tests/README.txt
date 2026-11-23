The HRProprietary Engine's own tests, the library they share & the two runners that contain them, from v4.3.1.

None of this is part of an installation's work. Nothing here is loaded by the Engine or by an application, & the code
checkers do not lint it, because a harness is not application code. Documentation/ABOUT_QUALITY_CONTROL.txt says why,
& --About The Engine's Tests-- there says how a suite is kept from ever touching a machine that matters.


HOW TO RUN THEM.

As root, from the root of the application under test, inside bubblewrap, which is one of the Engine's own dependencies.

  cd /path/to/the/application
  sudo bash Documentation/Build/Tests/contained.sh php Documentation/Build/Tests/test_events-cli.php

Every suite is run the same way. Each tests the tree it is run from, & stops before it runs anything where that tree, or
anything else this machine has, could be written. A suite handed the path of a tree tests that tree instead, & it must be
the root it is run from, because a suite finds its library by the folder it is run in.

The kit is not an application, so assemble the Example first, from the root of the kit, & run the suites from there.

  bash Documentation/Build/Tests/assemble-example.sh /path/to/a/new/folder

test_starter-cli.php is the Engine's acceptance test, from v4.3.2. It installs the Starter, so it is run from the Starter's
assembly, & inside contained-install.sh rather than contained.sh.

  bash Documentation/Build/Tests/assemble-example.sh /path/to/another/new/folder Starter
  cd /path/to/another/new/folder
  sudo bash Documentation/Build/Tests/contained-install.sh php Documentation/Build/Tests/test_starter-cli.php

An application built on the Engine carries this folder with the rest of Documentation, so its developer runs these rather
than writing their own.


WHAT IS HERE.

  testlib-cli.php                    The library every suite loads first. It refuses to run anywhere but inside contained.sh,
                                     or contained-install.sh for a suite that installs, hands a suite every path it writes, &
                                     lifts real functions out of the tree under test.
  contained.sh                       Runs a command in bubblewrap, with everything this machine has mounted bound read only,
                                     two scratch filesystems in memory & no network. Root inside, because what is tested is root's.
  contained-install.sh               contained.sh with an installation's folders in memory as well, /DATA & /var/www/html, for
                                     a test that installs & runs a whole application. Both folders must exist on this machine,
                                     & an empty one is enough. Nothing is ever written to either.
  assemble-example.sh                Assembles one of the kit's examples, the Example or the Starter, into an application. It
                                     copies & removes nothing.
  test_events-cli.php                The Event Core, held to every rule it has. It loads the tree's own core.
  test_events_load-cli.php           What engine.php does where the Event Core is not ready, with the kernel's loader a stand-in.
  test_events_engine-cli.php         The kernel loads the real Event Core by the path engine.php names, & events are kept & read back.
  test_kernel-cli.php                An application's argument, sanitizePath(), printableText(), a worker of the application's own
                                     & a file the kernel opens. Handed an earlier Engine's tree as well, it holds sanitize(),
                                     sanitizeString() & the Workflow Core's printableWorkflowText() to that Engine's.
  test_kernel_without_engine-cli.php What the kernel says where the Engine cannot run, & where an installation is beside it.
  test_install-cli.php               -fp installing a release into Application, with a rename, a removal or a mode failing where a
                                     check needs one to. Nothing is removed from Application before the new one is in place.
  test_schedule-cli.php              A command an application puts on a schedule: what a timer may be named, the record it is
                                     held to, the two units written for it, how they are installed, & what systemd is asked.
  test_quarantine-cli.php            The Quarantine pipeline & the kernel's enterFolderWithoutFollowing(), with a race or a fault
                                     made to happen where a check needs one.
  test_starter-cli.php               The Engine's acceptance test. It installs the Starter, the smallest application the Engine
                                     runs, & runs --config --repair, -v, -h, -fp & its command, its Environment Core gone as well.
  test_identity-cli.php              How the kernel names the account running it, & what decides about root by that: the tree
                                     check, root's home, the folders root holds, the sweep, the Dependency Core's scratch folder &
                                     the loader -fp writes. A second PHP runs without posix & without /proc, & the tree's entry
                                     point is run as root that way. No whoami, id or getent may run.

The library, both runners, test_events-cli.php & test_kernel-cli.php were HRProtect's, from its v0.0.7. test_events_load-cli.php
is HRProtect's suite of that name for the Engine's copy of its functions. The rest are the Engine's own, from v4.3.1.
test_install-cli.php is from v4.3.2. So are test_schedule-cli.php, the Engine's half of HRProtect's suite of that name, &
test_quarantine-cli.php, HRProtect's suite of that name with a section of the Engine's own. test_starter-cli.php is the
Engine's own, from v4.3.2. test_identity-cli.php is the Engine's own, from v4.3.3.


WHAT HAS BEEN RUN.

Each has been run from the root of the Example, assembled from the v4.3.3 kit, inside contained.sh, with PHP 8.3.6.

  test_events-cli.php                440 checks, every one passed.
  test_events_load-cli.php           27 checks, every one passed. Four planted faults in engine.php, each failed it.
  test_events_engine-cli.php         17 checks, every one passed.
  test_kernel-cli.php                170 checks, every one passed. 172 with the v4.3.0 kit named as the earlier Engine.
  test_kernel_without_engine-cli.php 11 checks, every one passed. Two planted faults in the kernel, each failed it.
  test_install-cli.php               66 checks, every one passed. Five planted faults in the Setup Core, each failed it.
  test_schedule-cli.php              185 checks, every one passed. Four planted faults in the timer functions, each failed it.
  test_quarantine-cli.php            176 checks, every one passed. Four planted faults in the pipeline, each failed it. Three
                                     survived HRProtect's checks, & its section 21 holds what kills each.
  test_starter-cli.php               26 checks, every one passed, from the Starter's assembly inside contained-install.sh.
                                     Three planted faults, two in the Engine & one in the Starter, each failed it.
  test_identity-cli.php              50 checks, every one passed, from the Example's assembly & from the Starter's. Twenty
                                     planted faults in the kernel, engine.php & the Setup, Dependency & Environment Cores, each
                                     failed it. Its Part 9, repeated by hand on v4.3.2, found root without posix running a tree
                                     another account could change.

Run anywhere but inside contained.sh, each stops before it runs anything, & says why.


WHAT IS NOT CHECKED.

Nothing here has run under a systemd that is running, & no unit has been loaded, enabled or started by one.
Nothing here reaches a network. A test that would need one is a test this folder does not hold.
Nothing here has been run on a machine that matters.
