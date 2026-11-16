<?php
// / -----------------------------------------------------------------------------------
// / Copyright Information ...
// / HRProprietary Engine, Copyright on 9/4/2026 by Justin Grimes, www.github.com/zelon88
// /
// / License Information ...
// / This project is protected by the GNU GPLv3 Open-Source license.
// / https://www.gnu.org/licenses/gpl-3.0.html
// /
// / File Information ...
// / v4.2.4.
// / THIS IS THE ENGINE'S HALF OF THE DEPENDENCY MANIFEST, & it lists what the Engine needs whatever application bundles it.
// / This file is data. It defines nothing & does nothing.
// / The Engine's verifyDependsManifest() reads it, & nothing else may.
// /
// / The dependency manifest is one list with two halves, as config.php is.
// /   This file                       What the Engine needs. PHP & the extensions the Engine calls, the web server,
// /                                   Curl, Git, Bubblewrap & AppArmor's tools.
// /   Resources/Contract/depends.php  What the application needs, which is every tool it runs.
// / The two are read as one list, this half first, so everything the Engine needs is installed before anything an
// / application runs. A name is in one half only. An application that names one the Engine's half names is told so,
// / & the Engine's entry is the one used.
// / Each entry is written as the application's half documents an entry, with every field it describes.
// / This half ships with the Engine & is replaced with it. The Engine pins it EXACTLY, in getAcceptedEngineManifest()
// / in engine.php, so an Engine laid over another release's half is caught. The application pins its own half.
// /
// / Hardware Requirements ...
// / This application requires at least a Raspberry Pi Model B+ or greater.
// / This application will run on just about any x86 or x64 computer.
// /
// / Dependency Requirements ...
// / This file & Resources/Contract/depends.php are the authoritative list of dependencies, & nothing else is.
// /
// / <3 Open-Source
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / A component may only be loaded by an application.
// / This file is the Engine's, so its guard names the Engine.
if (!isset($CoreLoaded) or $CoreLoaded !== TRUE) die('ERROR!!! Engine-35000, The Engine dependency manifest cannot be loaded directly!'.PHP_EOL);
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
// / The version of this half. The Engine reads it without executing the file, & pins it exactly.
$EngineDependsVersion = 'v4.2.4';
// / -----------------------------------------------------------------------------------


// / -----------------------------------------------------------------------------------
$EngineDependsManifest = array(

  // / ---- Core. Nothing else can be installed or run without these. ----
  // / mbstring is the Authentication Core's, which counts the characters in a password.
  array('Name' => 'PHP', 'Binary' => 'php', 'Type' => 'apt', 'Package' => 'php-cli php-mbstring',
    'MinimumVersion' => '8.0', 'VersionCommand' => 'php -v', 'VersionPattern' => '/^PHP (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net', 'Purpose' => 'Runs the application.'),
  // / A PHP extension is not proved by the PHP binary being present. It is asked for by name, from PHP itself,
  // / & is its own entry for that reason.
  array('Name' => 'PHP OpenSSL', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'openssl',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'openssl\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Resource Awareness', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.openssl.php', 'Purpose' => 'Encrypts every message on a manager socket. Resource awareness cannot work without it & fails quietly rather than loudly.'),
  // / Root hands a command to the web server account by forking & dropping to it, which takes pcntl & posix.
  // / Without either, such a command is refused rather than run as root, so -fp & the listener commands root hands over fail.
  // / The command line PHP of a distribution carries both. The official PHP container image carries posix & not pcntl.
  array('Name' => 'PHP Pcntl', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'pcntl',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'pcntl\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.pcntl.php', 'Purpose' => 'Lets root run a command as the web server account. Without it that command is refused.'),
  array('Name' => 'PHP Posix', 'Binary' => '', 'Type' => 'php-extension', 'Package' => 'posix',
    'MinimumVersion' => '', 'VersionCommand' => 'php -r "exit(extension_loaded(\'posix\') ? 0 : 1);"', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'PHP-3.01', 'Source' => 'https://www.php.net/manual/en/book.posix.php', 'Purpose' => 'Names accounts & lets root run a command as the web server account. Without it that command is refused.'),
  array('Name' => 'Apache', 'Binary' => 'apache2', 'Type' => 'apt', 'Package' => 'apache2 libapache2-mod-php',
    'MinimumVersion' => '2.4', 'VersionCommand' => 'apache2 -v', 'VersionPattern' => '/Apache\/(\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array('PHP'),
    'License' => 'Apache-2.0', 'Source' => 'https://httpd.apache.org', 'Purpose' => 'Serves the web interface.'),
  array('Name' => 'Curl', 'Binary' => 'curl', 'Type' => 'apt', 'Package' => 'curl',
    'MinimumVersion' => '7.0', 'VersionCommand' => 'curl --version', 'VersionPattern' => '/^curl (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Core', 'Requires' => array(),
    'License' => 'curl', 'Source' => 'https://curl.se', 'Purpose' => 'Fetches update packages & remote streams.'),
  array('Name' => 'Git', 'Binary' => 'git', 'Type' => 'apt', 'Package' => 'git',
    'MinimumVersion' => '2.0', 'VersionCommand' => 'git --version', 'VersionPattern' => '/git version (\d+\.\d+)/',
    'Required' => FALSE, 'Subsystem' => 'Updates', 'Requires' => array(),
    'License' => 'GPL-2.0', 'Source' => 'https://git-scm.com', 'Purpose' => 'Retrieves releases during an update.'),

  // / ---- Isolation. Installed before anything an application runs, because everything it runs runs inside it. ----
  array('Name' => 'Bubblewrap', 'Binary' => 'bwrap', 'Type' => 'apt', 'Package' => 'bubblewrap',
    'MinimumVersion' => '0.4', 'VersionCommand' => 'bwrap --version', 'VersionPattern' => '/bubblewrap (\d+\.\d+)/',
    'Required' => TRUE, 'Subsystem' => 'Sandbox', 'Requires' => array(),
    'License' => 'LGPL-2.0', 'Source' => 'https://github.com/containers/bubblewrap', 'Purpose' => 'Isolates every conversion from the host & the network.'),
  array('Name' => 'AppArmor Utils', 'Binary' => 'apparmor_parser', 'Type' => 'apt', 'Package' => 'apparmor-utils',
    'MinimumVersion' => '', 'VersionCommand' => '', 'VersionPattern' => '',
    'Required' => FALSE, 'Subsystem' => 'Sandbox', 'Requires' => array('Bubblewrap'),
    'License' => 'GPL-2.0', 'Source' => 'https://gitlab.com/apparmor/apparmor', 'Purpose' => 'Loads the profile bubblewrap needs on a restricted kernel.'));
// / -----------------------------------------------------------------------------------
?>
