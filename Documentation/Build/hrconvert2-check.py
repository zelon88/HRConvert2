#!/usr/bin/env python3
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
# / v3.8.9.
# / This file checks the source for faults a human reading it will not reliably see.
# /
# / Run it from the installation root, beside convertCore.php.
# /   python3 Documentation/Build/hrconvert2-check.py
# /
# / It exits zero when every check passes & non zero when any of them does not, so a
# / build pipeline can call it without reading the output.
# /   python3 Documentation/Build/hrconvert2-check.py --quiet
# /
# / Why this exists.
# / Every check below was written after a fault reached a running installation.
# / None of them are hypothetical & none of them were caught by reading the code.
# / A syntax error, a call to a component that has not loaded yet & a variable that was
# / renamed in one place are all invisible to careful reading & obvious to a parser.
# /
# / What it does NOT do.
# / It does not run the application & it does not test behaviour.
# / A conversion that produces the wrong file passes every check here.
# / It is a net for one class of fault rather than a test suite.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / A note on where this lives.
# / Documentation/Build is inside the web root & is served like anything else there.
# / A python file has no handler configured, so a web server hands back its source rather
# / than running it. Nothing here is secret & the project is open source, so that is untidy
# / rather than dangerous, & it is still worth the index.html every other directory in this
# / project carries. Copy one in from any neighbouring folder rather than writing a new one.
# / This whole directory is removed by --Delete Build Environment-- in config.php, which is
# / the correct answer for an installation that is not being developed on.
# /
# / Dependency Requirements ...
# / This file requires Python 3. The syntax check additionally requires the php binary &
# / is skipped with a notice when it is not installed.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------

import os
import re
import subprocess
import sys


# / -----------------------------------------------------------------------------------
# / A function to find every PHP file this project owns.
# / Accepts the installation root. Returns a sorted list of paths.
# / Only the directories this project writes are searched.
# / A pipeline somebody else installed is still checked, because it is still loaded.
def project_files(root):
    found = []
    for base, dirs, files in os.walk(root):
        # / Nothing under a version control directory or a data location is source.
        dirs[:] = [d for d in dirs if d not in ('.git', 'DATA', 'Logs', 'node_modules')]
        for name in files:
            if name.endswith('.php'):
                found.append(os.path.join(base, name))
    return sorted(found)


# / -----------------------------------------------------------------------------------
# / A function to strip comments & string literals from one line of PHP.
# / Accepts the line. Returns the line with every literal emptied.
# / A brace inside a string is not a brace & a variable name inside a regular expression
# / is not a variable. Every check below reads code rather than text because of this.
def strip_literals(line):
    out = []
    quote = None
    index = 0
    while index < len(line):
        character = line[index]
        if quote is None:
            if line.startswith('//', index) or character == '#':
                break
            if character in '"\'':
                quote = character
                out.append(character)
                out.append(character)
            else:
                out.append(character)
        else:
            # / A backslash escapes the next character, including the closing quote.
            if character == '\\':
                index += 2
                continue
            if character == quote:
                quote = None
        index += 1
    return ''.join(out)


# / -----------------------------------------------------------------------------------
# / A function to read every function in one file & where it begins & ends.
# / Accepts the lines of the file. Returns a dictionary of name to a record.
# / A function is found by a line beginning with the keyword, because this project
# / declares every function at the top level & never nests one inside another.
def read_functions(lines):
    functions = {}
    for index, line in enumerate(lines):
        if not line.startswith('function '):
            continue
        name = line.split('(')[0][9:]
        depth = 0
        end = index
        while True:
            stripped = strip_literals(lines[end])
            depth += stripped.count('{') - stripped.count('}')
            if depth == 0 and end > index:
                break
            end += 1
            if end >= len(lines):
                break
        functions[name] = {
            'start': index,
            'end': end,
            'signature': line,
            'body': lines[index:end + 1]}
    return functions


# / -----------------------------------------------------------------------------------
# / CHECK ONE. The php binary parses every file.
# / A syntax error is the cheapest fault to find & the one most often introduced by a
# / scripted edit that matched the wrong text.
# / This is skipped rather than failed when php is not installed, because a machine
# / without it can still run every other check here.
def check_syntax(files, report):
    if subprocess.call(['which', 'php'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL) != 0:
        report('SKIP', 'php is not installed, so the syntax check did not run.')
        return 0
    faults = 0
    for path in files:
        result = subprocess.run(['php', '-l', path], capture_output=True, text=True)
        if result.returncode != 0:
            report('SYNTAX', path + ': ' + result.stdout.strip().split('\n')[0])
            faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK TWO. Every delimiter closes.
# / php -l finds this too & this runs anyway, because it runs without php installed & it
# / reports which delimiter rather than only which line.
def check_balance(files, report):
    faults = 0
    for path in files:
        counts = {'{': 0, '(': 0, '[': 0}
        closing = {'}': '{', ')': '(', ']': '['}
        for line in open(path, encoding='utf-8').read().split('\n'):
            for character in strip_literals(line):
                if character in counts:
                    counts[character] += 1
                elif character in closing:
                    counts[closing[character]] -= 1
        for opener, count in counts.items():
            if count != 0:
                report('BALANCE', path + ': ' + opener + ' is out by ' + str(count))
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK THREE. Nothing calls a component before that component is loaded.
# / This is the check that matters most & the one a human reading the file cannot do.
# /
# / A component is verified & loaded at a known line in the main logic. Anything the main
# / logic calls before that line must not reach a function the component defines.
# / The reach is followed through the call graph rather than read off the call itself,
# / because the fault is never in the obvious place.
# /
# / A real example. verifyGlobals is called before the Engine loads. It calls
# / requestConvertLoc, which calls resolveDataLocation, which the Engine defines.
# / Reading the call sites showed nothing, because requestConvertLoc is DEFINED early in
# / the file. It RUNS late. That difference produced a fatal on the first request.
def check_call_order(root, report):
    core_path = os.path.join(root, 'convertCore.php')
    if not os.path.exists(core_path):
        report('SKIP', 'convertCore.php was not found, so the call order check did not run.')
        return 0
    lines = open(core_path, encoding='utf-8').read().split('\n')
    functions = read_functions(lines)

    # / Which component file defines which function.
    defined_by = {}
    for base, dirs, files in os.walk(os.path.join(root, 'Resources')):
        dirs[:] = [d for d in dirs if d not in ('.git',)]
        for name in files:
            if not name.endswith('.php'):
                continue
            body = open(os.path.join(base, name), encoding='utf-8').read()
            for match in re.finditer(r'^function (\w+)\(', body, re.M):
                defined_by[match.group(1)] = name

    # / Where the main logic loads each component. Only a top level load counts, because a
    # / load inside a function happens whenever that function is called.
    loaded_at = {}
    for index, line in enumerate(lines):
        if line.startswith('list (') and 'verifyCoreComponent(' in line:
            match = re.search(r"'([A-Za-z]+\.php)'", line)
            if match:
                loaded_at[match.group(1)] = index

    def calls_made(name):
        if name not in functions:
            return set()
        text = '\n'.join(strip_literals(x) for x in functions[name]['body'])
        return set(re.findall(r'\b([a-z][A-Za-z0-9_]*)\s*\(', text))

    # / The transitive closure of what a function reaches, memoized because the graph is
    # / large & a function is reached from many places.
    reached = {}

    def reach(name, seen=None):
        if name in reached:
            return reached[name]
        seen = seen or set()
        if name in seen or name not in functions:
            return set()
        seen = seen | {name}
        found = set()
        for called in calls_made(name):
            found.add(called)
            found |= reach(called, seen)
        reached[name] = found
        return found

    faults = 0
    for index, line in enumerate(lines):
        # / Only a top level statement is main logic. A definition & an indented line are
        # / both inside something that has not necessarily run yet.
        if line.startswith('function ') or line.startswith(' ') or line.startswith('//') or not line.strip():
            continue
        for called in re.findall(r'\b([a-z][A-Za-z0-9_]*)\s*\(', strip_literals(line)):
            for target in {called} | reach(called):
                if target in defined_by and defined_by[target] in loaded_at:
                    if loaded_at[defined_by[target]] > index:
                        report('ORDER', 'line ' + str(index + 1) + ': ' + called
                               + '() reaches ' + target + '() in ' + defined_by[target]
                               + ', which loads at line ' + str(loaded_at[defined_by[target]] + 1))
                        faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK FOUR. Every variable a function reads exists.
# / A variable that is read & is neither a parameter, nor declared global, nor assigned
# / anywhere inside the function does not exist when it is read.
# /
# / This is the shape of a parameter renamed in the body but not in the signature, which
# / is valid PHP & fatal at runtime. It is also the shape of a global somebody forgot to
# / declare, which is a warning rather than a fatal & corrupts an AJAX reply.
# /
# / Two patterns produce a finding that is not a fault & are worth knowing about.
# / A reference in a foreach, written as  foreach ($x as &$y), assigns $y by reference.
# / A catch block binds its exception to a variable that nothing appears to assign.
# / A file brought in with require may assign variables into the calling scope, which is
# / how a GUI supplies its button codes.
# / Neither is visible to a parser reading one function, so both are reported.
def check_undefined_reads(files, report):
    faults = 0
    for path in files:
        lines = open(path, encoding='utf-8').read().split('\n')
        for name, record in read_functions(lines).items():
            signature = record['signature']
            parameters = set(re.findall(r'\$(\w+)', signature[signature.index('('):]))
            declared = set()
            assigned = set()
            used = set()
            for line in [strip_literals(x) for x in record['body'][1:]]:
                match = re.match(r'\s*global ([^;]+);', line)
                if match:
                    declared |= set(re.findall(r'\$(\w+)', match.group(1)))
                for found in re.finditer(r'\$(\w+)\s*=(?!=)', line):
                    assigned.add(found.group(1))
                for found in re.finditer(r'list\s*\(([^)]*)\)\s*=', line):
                    assigned |= set(re.findall(r'\$(\w+)', found.group(1)))
                # / A foreach assigns its key & its value, by value or by reference.
                for found in re.finditer(r'as\s+&?\$(\w+)\s*(?:=>\s*&?\$(\w+))?', line):
                    assigned.add(found.group(1))
                    if found.group(2):
                        assigned.add(found.group(2))
                # / A catch block binds its exception to a variable.
                for found in re.finditer(r'catch\s*\([^)]*\$(\w+)\s*\)', line):
                    assigned.add(found.group(1))
                # / preg_match & exec fill their later arguments by reference.
                if 'preg_match' in line or 'exec(' in line:
                    for found in re.finditer(r',\s*\$(\w+)\s*[,)]', line):
                        assigned.add(found.group(1))
                used |= set(re.findall(r'\$(\w+)', line))
            superglobals = {'GLOBALS', 'this', '_SERVER', '_GET', '_POST', '_FILES', '_ENV', '_COOKIE', '_SESSION'}
            for variable in sorted(used - declared - assigned - parameters - superglobals):
                report('UNDEFINED', path + ':' + str(record['start'] + 1) + ' ' + name
                       + '() reads $' + variable + ', which is not a parameter, a declared global or assigned')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK FIVE. No comment line is entirely capitals.
# / Convention nineteen. A comment that needs shouting is a comment describing something
# / too important to be a comment. A capitalised word inside a sentence is fine & is not
# / reported, because emphasis is not shouting.
def check_comment_case(files, report):
    faults = 0
    for path in files:
        for index, line in enumerate(open(path, encoding='utf-8').read().split('\n')):
            stripped = line.strip()
            if not stripped.startswith('// /'):
                continue
            body = stripped[4:].strip()
            if len(body) < 12:
                continue
            letters = [c for c in body if c.isalpha()]
            if letters and all(c.isupper() for c in letters):
                report('CAPS', path + ':' + str(index + 1) + ' ' + body[:60])
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK SIX. Every cleanup at a return carries the comment that belongs with it.
# / The comment is how a reader knows the cleanup is deliberate rather than copied, & a
# / cleanup without it reads as noise the next person is tempted to delete.
def check_purge_comment(files, report):
    wanted = 'Manually clean up sensitive memory'
    faults = 0
    for path in files:
        lines = open(path, encoding='utf-8').read().split('\n')
        for index, line in enumerate(lines):
            if 'purgeSensitiveMemory(' not in line or line.strip().startswith('// /'):
                continue
            if index + 1 >= len(lines) or not lines[index + 1].strip().startswith('return'):
                continue
            found = False
            back = index - 1
            while back >= 0 and lines[back].strip().startswith('// /'):
                if wanted in lines[back]:
                    found = True
                back -= 1
            if not found:
                report('PURGE', path + ':' + str(index + 1) + ' a cleanup before a return carries no comment')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK SEVEN. Every function has one exit.
# / Convention. A single exit is what makes a cleanup at the return reliable, & it is what
# / made moving thirteen conversion families out of the core a mechanical operation.
# / Three functions are exempt & each is exempt for a stated reason.
def check_single_exit(files, report):
    exempt = {'quickDie', 'closeHRC2Connection', 'mergeConfigFile'}
    faults = 0
    for path in files:
        lines = open(path, encoding='utf-8').read().split('\n')
        for name, record in read_functions(lines).items():
            if name in exempt:
                continue
            returns = len([x for x in record['body'] if re.match(r'\s*return\b', x)])
            if returns != 1:
                report('EXIT', path + ':' + str(record['start'] + 1) + ' ' + name
                       + '() has ' + str(returns) + ' return statements')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / The main logic. Every check runs & the total decides the exit code.
# / Nothing stops early, because an operator wants every fault in one pass rather than the
# / first one seven times.
def main():
    quiet = '--quiet' in sys.argv
    root = os.getcwd()
    if not os.path.exists(os.path.join(root, 'convertCore.php')):
        print('Run this from the installation root, beside convertCore.php.')
        return 2
    findings = []

    def report(kind, message):
        findings.append((kind, message))
        if not quiet:
            print('  ' + kind.ljust(10) + message)

    files = project_files(root)
    if not quiet:
        print('Checking ' + str(len(files)) + ' PHP files under ' + root + '.')
        print()
    total = 0
    total += check_syntax(files, report)
    total += check_balance(files, report)
    total += check_call_order(root, report)
    total += check_undefined_reads(files, report)
    total += check_comment_case(files, report)
    total += check_purge_comment(files, report)
    total += check_single_exit(files, report)
    if not quiet:
        print()
        if total == 0:
            print('Every check passed.')
        else:
            print(str(total) + ' finding(s). A finding is not always a fault, & the header')
            print('for each check says which patterns it reports that are not.')
    return 1 if total > 0 else 0


if __name__ == '__main__':
    sys.exit(main())
