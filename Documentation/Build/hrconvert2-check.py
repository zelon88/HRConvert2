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
# / v3.9.0.
# / This file checks the source for faults a human reading it will not reliably see.
# /
# / Run it from the installation root, beside convertCore.php.
# /   python3 Documentation/Build/hrconvert2-check.py
# /
# / It exits zero when every check passes, one when any of them does not & two when it was
# / asked something it could not answer, so a build pipeline can call it without reading
# / the output.
# /   python3 Documentation/Build/hrconvert2-check.py --quiet
# /
# / Neither this tool nor its Bash twin ever writes to a file. They report & nothing else.
# / A change to the code is always a separate act, & this tool is what to run after it.
# /
# / --fail-on names the kinds that decide the exit code, for a hook that nobody reads.
# /   python3 Documentation/Build/hrconvert2-check.py --quiet --fail-on syntax,balance,return,config,duplicate,order,undefined
# /
# / Every check has a name & can be run on its own, which is what to do while fixing one.
# /   python3 Documentation/Build/hrconvert2-check.py --list
# /   python3 Documentation/Build/hrconvert2-check.py --only pins
# /
# / Twelve checks run. Each one is described above the function that performs it, & each
# / description says what it CANNOT see as well as what it can.
# /
# / Why this exists.
# / Every check below was written after a fault reached a running installation.
# / A convention nobody can verify is only taste. Every one of these is a convention this
# / project already had, turned into something a machine decides rather than something a
# / reader is trusted to notice.
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
# / A function to blank every comment & string literal in one whole file.
# / Accepts the file text. Returns the text with every literal emptied & every line still
# / on the line it started on, so a reported line number is still the real one.
# /
# / This reads the WHOLE FILE rather than one line at a time & the difference matters.
# / A string in PHP may run across several lines, & a per line reader treats the rest of
# / that string as code. Generated code inside a string is the worst case, because it looks
# / exactly like the thing being searched for.
# / A cache file this project writes contains the text  if (!isset($CoreLoaded))  inside a
# / string, & a per line reader reported it as a variable read that was never declared.
# /
# / Heredoc & nowdoc are not handled & this project does not use them. A file that starts
# / using one will produce noise here rather than silence, which is the safer failure.
def strip_file_literals(text):
    output = []
    quote = None
    comment = None
    index = 0
    length = len(text)
    while index < length:
        character = text[index]
        # / A newline ends a line comment & is always kept, so line numbers survive.
        if character == '\n':
            comment = None if comment == 'line' else comment
            output.append('\n')
            index += 1
            continue
        if comment == 'line':
            output.append(' ')
            index += 1
            continue
        if comment == 'block':
            if text.startswith('*/', index):
                comment = None
                output.append('  ')
                index += 2
                continue
            output.append(' ')
            index += 1
            continue
        if quote is None:
            if text.startswith('//', index) or character == '#':
                comment = 'line'
                output.append(' ')
                index += 1
                continue
            if text.startswith('/*', index):
                comment = 'block'
                output.append('  ')
                index += 2
                continue
            if character in '"\'':
                quote = character
                output.append(character)
                output.append(character)
                index += 1
                continue
            output.append(character)
            index += 1
            continue
        # / Inside a string. A backslash escapes whatever follows it, including the quote.
        if character == '\\' and index + 1 < length:
            if text[index + 1] == '\n':
                output.append('\n')
            else:
                output.append(' ')
            index += 2
            continue
        if character == quote:
            quote = None
        output.append(' ')
        index += 1
    return ''.join(output)


# / -----------------------------------------------------------------------------------
# / A function to read one file & return its lines with every literal already blanked.
# / Accepts the path. Returns the original lines & the blanked lines, in that order.
# / Every check reads through here, so a literal is stripped once per file rather than
# / once per check, & every check sees the same thing.
def read_source(path):
    text = open(path, encoding='utf-8').read()
    return text.split('\n'), strip_file_literals(text).split('\n')


# / -----------------------------------------------------------------------------------
# / A function to read every function in one file & where it begins & ends.
# / Accepts the lines of the file. Returns a dictionary of name to a record.
# / A function is found by a line beginning with the keyword, because this project
# / declares every function at the top level & never nests one inside another.
def read_functions(original, blanked):
    functions = {}
    for index, line in enumerate(blanked):
        if not line.startswith('function '):
            continue
        name = line.split('(')[0][9:]
        depth = 0
        end = index
        while True:
            depth += blanked[end].count('{') - blanked[end].count('}')
            if depth == 0 and end > index:
                break
            end += 1
            if end >= len(blanked):
                break
        # / The blanked body is what every check reasons about & the original is what a
        # / report quotes, so a message shows what an author wrote rather than a line with
        # / the strings taken out of it.
        functions[name] = {
            'start': index,
            'end': end,
            'signature': blanked[index],
            'body': blanked[index:end + 1],
            'original': original[index:end + 1]}
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
        original, blanked = read_source(path)
        for line in blanked:
            for character in line:
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
    original, lines = read_source(core_path)
    functions = read_functions(original, lines)

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
        text = '\n'.join(functions[name]['body'])
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
        for called in re.findall(r'\b([a-z][A-Za-z0-9_]*)\s*\(', line):
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
        original, blanked = read_source(path)
        for name, record in read_functions(original, blanked).items():
            signature = record['signature']
            # / Only the text between the signature's parentheses is a parameter list.
            # / Taking everything after the first parenthesis meant that a function written
            # / on one line had its whole body read as its parameters, so every variable in
            # / it counted as declared & the check could not fail on such a function.
            depth = 0
            start = signature.index('(')
            finish = start
            for position in range(start, len(signature)):
                if signature[position] == '(':
                    depth += 1
                elif signature[position] == ')':
                    depth -= 1
                    if depth == 0:
                        finish = position
                        break
            parameters = set(re.findall(r'\$(\w+)', signature[start:finish]))
            declared = set()
            assigned = set()
            used = set()
            # / A declaration may run over several lines, so it is read from the whole body
            # / rather than line by line. Reading one line at a time saw the first line of a
            # / long declaration & reported every name below it as undeclared, which once
            # / led to production code being reformatted to suit this tool rather than the
            # / tool being taught to read it.
            for found in re.finditer(r'\bglobal\s+([^;]+);', '\n'.join(record['body'])):
                declared |= set(re.findall(r'\$(\w+)', found.group(1)))
            # / The WHOLE body is read, including the signature line. Skipping it hid a
            # / function written entirely on one line, because that line was the only line
            # / & removing it left nothing at all to examine.
            # / The signature contributes only its parameters, which are subtracted below.
            for line in record['body']:
                for found in re.finditer(r'\$(\w+)\s*=(?!=)', line):
                    assigned.add(found.group(1))
                for found in re.finditer(r'list\s*\(([^)]*)\)\s*=', line):
                    assigned |= set(re.findall(r'\$(\w+)', found.group(1)))
                # / A foreach assigns its key & its value, by value or by reference.
                for found in re.finditer(r'as\s+&?\$(\w+)\s*(?:=>\s*&?\$(\w+))?', line):
                    assigned.add(found.group(1))
                    if found.group(2):
                        assigned.add(found.group(2))
                # / An anonymous function declares parameters of its own, & a use clause
                # / imports names from the enclosing scope. Both are declarations that occur
                # / in the middle of a line rather than at the top of a function.
                for found in re.finditer(r'function\s*\(([^)]*)\)(?:\s*use\s*\(([^)]*)\))?', line):
                    assigned |= set(re.findall(r'\$(\w+)', found.group(1) or ''))
                    assigned |= set(re.findall(r'\$(\w+)', found.group(2) or ''))
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
        # / This reads the ORIGINAL rather than the blanked source, because a comment is
        # / exactly what the blanked source has removed.
        original, blanked = read_source(path)
        for index, line in enumerate(original):
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
        # / The call is found in the blanked source & the comment above it in the original,
        # / because one is code & the other is not.
        original, blanked = read_source(path)
        lines = original
        for index, line in enumerate(blanked):
            if 'purgeSensitiveMemory(' not in line:
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
        original, blanked = read_source(path)
        for name, record in read_functions(original, blanked).items():
            if name in exempt:
                continue
            returns = len([x for x in record['body'] if re.match(r'\s*return\b', x)])
            if returns != 1:
                report('EXIT', path + ':' + str(record['start'] + 1) + ' ' + name
                       + '() has ' + str(returns) + ' return statements')
                faults += 1
    return faults



# / -----------------------------------------------------------------------------------
# / CHECK EIGHT. The Engine never calls application code.
# / The Engine may call the kernel, because the kernel is byte identical in every
# / application & calling it couples the Engine to none of them. It may not call anything
# / only this application has, because an Engine that does is an Engine that works for
# / exactly one application & the whole point of extracting it is gone.
# /
# / A violation is almost never deliberate. It happens when a function is moved & the
# / helpers it quietly depended on are not moved with it, which is invisible while both
# / files are loaded in the same request & fatal the moment somebody else bundles the
# / Engine on its own.
# /
# / A finding here is a job that is not finished rather than a mistake. The named function
# / either moves too or the value it produces becomes a parameter.
def check_engine_independence(root, report):
    core_path = os.path.join(root, 'convertCore.php')
    engine_dir = os.path.join(root, 'Resources', 'Engine')
    if not os.path.exists(core_path) or not os.path.isdir(engine_dir):
        report('SKIP', 'no Engine directory was found, so the independence check did not run.')
        return 0
    # / Every function only the application defines.
    application = set(re.findall(r'^function (\w+)\(', open(core_path, encoding='utf-8').read(), re.M))
    # / The kernel lives in the application file & the Engine is allowed to call it.
    # / This list is the contract & is written down in ABOUT_ENGINE_CONTRACT.txt.
    kernel = {'quickDie', 'logEntry', 'warningEntry', 'errorEntry', 'purgeSensitiveMemory',
              'redeclare', 'readComponentVersion', 'verifyCoreComponent', 'composeLogEntry',
              'emitLogEntry', 'flushLogBuffer', 'locateDependency', 'sanitize', 'getExtension'}
    engine = {}
    for base, dirs, files in os.walk(engine_dir):
        for name in files:
            if not name.endswith('.php'):
                continue
            for match in re.finditer(r'^function (\w+)\(', open(os.path.join(base, name), encoding='utf-8').read(), re.M):
                engine[match.group(1)] = name
    faults = 0
    seen = set()
    for base, dirs, files in os.walk(engine_dir):
        for name in sorted(files):
            if not name.endswith('.php'):
                continue
            original, blanked = read_source(os.path.join(base, name))
            for index, line in enumerate(blanked):
                for called in re.findall(r'\b([a-z][A-Za-z0-9_]*)\s*\(', line):
                    if called in engine or called in kernel:
                        continue
                    if called in application and (name, called) not in seen:
                        seen.add((name, called))
                        report('CONTRACT', name + ':' + str(index + 1) + ' calls ' + called
                               + '(), which only the application defines')
                        faults += 1
    return faults

# / -----------------------------------------------------------------------------------
# / CHECK NINE. No function is defined twice.
# / PHP has no function overloading, so a second definition of the same name is a fatal
# / the moment both files load. It is the normal result of moving a function & leaving the
# / original behind, which is invisible until the two files meet in one request.
def check_duplicate_functions(files, report):
    seen = {}
    faults = 0
    for path in files:
        original, blanked = read_source(path)
        for index, line in enumerate(blanked):
            if not line.startswith('function '):
                continue
            name = line.split('(')[0][9:]
            if name in seen:
                report('DUPLICATE', name + '() is defined in ' + os.path.basename(seen[name])
                       + ' and again in ' + os.path.basename(path) + ':' + str(index + 1))
                faults += 1
            else:
                seen[name] = path
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK TEN. Every version pin matches the file it points at.
# / A component declares its own version & the thing loading it declares the version it
# / requires. The two drifting apart is how a correct component gets refused at boot & how
# / a stale one gets accepted.
# / Both directions are checked. A pin naming a version nothing ships is as wrong as a file
# / shipping a version nothing asks for.
# /
# / WHAT THIS CANNOT SEE.
# / A pin whose component is not installed is skipped rather than reported, & that is a
# / limitation rather than a decision. Config, the GUI, the language pack & the install
# / secret are all pinned here & none of them is a component with a version constant, so a
# / missing match cannot be told apart from a component that was never meant to have one.
# / A component genuinely absent from the tree is therefore silent here. The call order
# / check & the application itself both report that at load time instead.
def check_version_pins(root, report):
    core_path = os.path.join(root, 'convertCore.php')
    if not os.path.exists(core_path):
        report('SKIP', 'convertCore.php was not found, so the version pin check did not run.')
        return 0
    core = open(core_path, encoding='utf-8').read()
    faults = 0
    # / A component is pinned as $RequiredNameVersion & declares $NameVersion.
    for match in re.finditer(r"\$Required(\w+?)Version\s*=\s*'v?([0-9.]+)'", core):
        component = match.group(1)
        pinned = match.group(2)
        declared = None
        where = None
        for base, dirs, files in os.walk(os.path.join(root, 'Resources')):
            for name in files:
                if not name.endswith('.php'):
                    continue
                body = open(os.path.join(base, name), encoding='utf-8').read()
                found = re.search(r"\$" + component + r"Version\s*=\s*'v?([0-9.]+)'", body)
                if found:
                    declared = found.group(1)
                    where = name
        # / A pin with nothing to point at is not reported. Config, the GUI, the language
        # / pack & the install secret are all pinned here & none of them lives in Resources
        # / as a component with a version constant of its own.
        if declared is not None and declared != pinned:
            report('PIN', component + ' is pinned at ' + pinned + ' & ' + where
                   + ' declares ' + declared)
            faults += 1
    # / A manager is pinned by the Engine rather than by the application.
    engine_path = os.path.join(root, 'Resources', 'Engine', 'engine.php')
    if os.path.exists(engine_path):
        for match in re.finditer(r"'(\w+\.php)'\s*=>\s*'v?([0-9.]+)'", open(engine_path, encoding='utf-8').read()):
            manager_path = os.path.join(root, 'Resources', 'Engine', 'Managers', match.group(1))
            if not os.path.exists(manager_path):
                report('PIN', 'the Engine pins ' + match.group(1) + ' & no such manager is installed')
                faults += 1
                continue
            found = re.search(r"\$ManagerVersion\s*=\s*'v?([0-9.]+)'", open(manager_path, encoding='utf-8').read())
            if not found:
                report('PIN', match.group(1) + ' reports no version & an unknown build cannot be cleared')
                faults += 1
            elif found.group(1) != match.group(2):
                report('PIN', match.group(1) + ' is pinned at ' + match.group(2)
                       + ' & declares ' + found.group(1))
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK ELEVEN. Every error number raised is documented.
# / Convention seventeen. An operator who reads a number in a log & finds nothing about it
# / has been handed a number instead of an answer.
# / The reverse is not reported. A documented number nothing raises any more is history
# / rather than a fault, & the numbers in this project are never reassigned.
def check_error_numbers(root, files, report):
    catalog_path = os.path.join(root, 'Documentation', 'ERROR_DESCRIPTIONS.txt')
    if not os.path.exists(catalog_path):
        report('SKIP', 'ERROR_DESCRIPTIONS.txt was not found, so the error number check did not run.')
        return 0
    catalog = open(catalog_path, encoding='utf-8', errors='replace').read()
    # / Only a real entry counts. An earlier version accepted any number appearing anywhere
    # / in the catalog, which meant a file permission written as 0700 & a year written as
    # / 2020 both counted as documented error numbers. An error numbered 2020 would have
    # / passed silently, which is the one thing this check exists to prevent.
    documented = set(re.findall(r'APPLICATION_NAME>-(\d{1,5})', catalog))
    faults = 0
    reported = set()
    for path in files:
        original, blanked = read_source(path)
        # / The whole file is searched rather than each line, because a call may be written
        # / across several lines & a per line search sees only the fragment on each one.
        # / The line number is recovered from the offset so a report still points somewhere.
        text = '\n'.join(blanked)
        for match in re.finditer(r'(?:errorEntry|quickDie)\s*\([^;]*?,\s*(\d{1,5})\s*[,)]', text):
            number = match.group(1)
            if number in documented or number in reported:
                continue
            reported.add(number)
            line_number = text.count('\n', 0, match.start()) + 1
            report('ERRNO', os.path.basename(path) + ':' + str(line_number)
                   + ' raises error ' + number + ', which ERROR_DESCRIPTIONS.txt does not document')
            faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK TWELVE. Every component refuses to be loaded directly.
# / A component reached by a web server without an application around it must not run.
# / The guard is one line & its absence is silent, which is the worst combination.
def check_component_guard(root, report):
    faults = 0
    for base, dirs, files in os.walk(os.path.join(root, 'Resources')):
        for name in sorted(files):
            if not name.endswith('.php'):
                continue
            path = os.path.join(base, name)
            original, blanked = read_source(path)
            body = '\n'.join(blanked)
            # / The BLANKED source is read rather than the file, so a comment mentioning the
            # / guard does not count as having one. An earlier version tested the raw text &
            # / a file whose only mention was in a comment passed.
            # / Both halves are required. The name alone appears in a comment or a message,
            # / & a die alone is any other refusal. Together they are the guard.
            # / A file with no functions & no logic is a manifest or a configuration, & it
            # / is guarded for the same reason, so everything under Resources is checked.
            # / The name is matched whole. A substring test passed a file whose guard read
            # / $CoreLoadedX, which is a different variable that is never set & therefore
            # / a guard that can never fire.
            if not re.search(r'\$CoreLoaded\b', body) or not re.search(r'\b(die|exit)\s*\(', body):
                report('GUARD', os.path.relpath(path, root)
                       + ' has no $CoreLoaded guard & would run if reached directly')
                faults += 1
    return faults



# / -----------------------------------------------------------------------------------
# / CHECK THIRTEEN. Every required configuration setting is declared global where the
# / configuration is loaded.
# /
# / config.php is brought in with require_once INSIDE a function, so every setting it
# / defines lands in that function's local scope rather than in $GLOBALS. A setting the
# / function has not declared global therefore exists in the file, is read correctly by
# / PHP, & is invisible to everything afterwards.
# /
# / The completeness check then reports it missing. An administrator reads that their
# / config.php is missing a setting which is plainly present in their config.php, which is
# / the least helpful true statement this application can make.
# /
# / No other check can see this. The settings are tested by name through a list at runtime,
# / so nothing reads $UserModelInputArray anywhere & a search for a read finds nothing.
# / Three places have to agree & this is the third: the file, the required list, & the
# / global declaration where the file is loaded.
def check_config_globals(root, report):
    core_path = os.path.join(root, 'convertCore.php')
    if not os.path.exists(core_path):
        report('SKIP', 'convertCore.php was not found, so the config global check did not run.')
        return 0
    original, blanked = read_source(core_path)
    text = '\n'.join(blanked)
    # / The required list is read from the ORIGINAL & the declaration from the blanked copy.
    # / The settings are quoted strings, so the blanked copy has emptied every one of them.
    # / An earlier version read both from the blanked copy, compared an empty set against the
    # / declarations & reported nothing missing whatever was removed. A check that cannot
    # / fail is worse than no check, because it is counted as having passed.
    raw = '\n'.join(original)
    if 'function verifyInstallation(' not in text or '$requiredConfigVars = array(' not in raw:
        report('SKIP', 'the configuration loader was not found, so the config global check did not run.')
        return 0
    start = raw.index('$requiredConfigVars = array(')
    end = raw.index(');', start)
    required = set(re.findall(r"'(\w+)'", raw[start:end]))
    # / Every name the loading function has declared global.
    start = text.index('function verifyInstallation(')
    start = text.index('  global ', start)
    end = text.index(';', start)
    declared = set(re.findall(r'\$(\w+)', text[start:end]))
    faults = 0
    for name in sorted(required - declared):
        report('CONFIG', name + ' is required & is not declared global where config.php is loaded, so it will read as missing')
        faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK FOURTEEN. Every lowercase local is destroyed before the function returns.
# / Convention five. A lowercase variable is initialized in the function that uses it &
# / destroyed by purgeSensitiveMemory() before that function returns, or by an explicit
# / unset. A local that is neither is a value left in memory after the code that needed it
# / has finished, which is the exposure the convention exists to close.
# /
# / This is the check that was missing when six new locals had to be found & purged by
# / hand across three functions, & it would have found all six.
# /
# / WHAT COUNTS AS A LOCAL.
# / Every lowercase-first variable the function assigns, takes as a parameter, binds in a
# / foreach, or receives by reference from preg_match or exec. Uppercase-first names are
# / globals or return values & are exempt by convention three.
# /
# / WHAT COUNTS AS DESTROYED.
# / Appearing in any purgeSensitiveMemory() call in the function, or in any unset() call.
# / The kernel functions that purgeSensitiveMemory itself depends on clean up by hand with
# / unset, because calling the purge from inside them would recurse.
# /
# / WHAT THIS CANNOT SEE.
# / A function with no purge call & no unset at all is reported once as a whole rather than
# / once per local, because it has not adopted the convention rather than missed a name.
# / A closure parameter is local to the closure & is reported here if it is not purged,
# / which is usually a false positive worth an exemption rather than a fix.
# / A static local is a cache that persists across calls by design & is not reported.
def check_locals_purged(files, report):
    # / quickDie never returns, so nothing after it runs & nothing it holds outlives it.
    # / purgeSensitiveMemory cannot call itself & cleans up by hand. quickDie never returns.
    # / redeclare's whole purpose is to shred the caller's variable through a reference &
    # / then write a new value into it. Both of those look like faults to the checks here &
    # / neither is. Obeying either instruction empties every string built through it, which
    # / is how a settings link rendered with no path at all & every change gave a 404.
    exempt = {'quickDie', 'purgeSensitiveMemory', 'redeclare'}
    faults = 0
    for path in files:
        original, blanked = read_source(path)
        for name, record in read_functions(original, blanked).items():
            if name in exempt:
                continue
            body = '\n'.join(record['body'])
            signature = record['signature']
            depth = 0
            start = signature.index('(')
            finish = start
            for position in range(start, len(signature)):
                if signature[position] == '(':
                    depth += 1
                elif signature[position] == ')':
                    depth -= 1
                    if depth == 0:
                        finish = position
                        break
            locals_found = set(re.findall(r'\$([a-z]\w*)', signature[start:finish]))
            for line in record['body'][1:]:
                for found in re.finditer(r'\$([a-z]\w*)\s*=(?!=)', line):
                    locals_found.add(found.group(1))
                for found in re.finditer(r'list\s*\(([^)]*)\)\s*=', line):
                    locals_found |= set(re.findall(r'\$([a-z]\w*)', found.group(1)))
                for found in re.finditer(r'as\s+&?\$([a-z]\w*)\s*(?:=>\s*&?\$([a-z]\w*))?', line):
                    locals_found.add(found.group(1))
                    if found.group(2):
                        locals_found.add(found.group(2))
                if 'preg_match' in line or 'exec(' in line:
                    for found in re.finditer(r',\s*\$([a-z]\w*)\s*[,)]', line):
                        locals_found.add(found.group(1))
                for found in re.finditer(r'catch\s*\([^)]*\$([a-z]\w*)\s*\)', line):
                    locals_found.add(found.group(1))
            # / A static local persists across calls by design. It is a cache rather than a
            # / value that outlived its use, & purging it would defeat the reason it exists.
            for found in re.finditer(r'\bstatic\s+\$([a-z]\w*)', body):
                locals_found.discard(found.group(1))
            # / A value the function returns must NOT be purged, whatever case it is written
            # / in. purgeSensitiveMemory takes by reference, so purging a return value returns
            # / NULL. Obeying this check without that exemption broke eighteen functions in
            # / one pass, which is why the exemption is here rather than in a reader's head.
            for found in re.finditer(r'\breturn\s+(?:array\s*\()?([^;]+);', body):
                locals_found -= set(re.findall(r'\$([a-z]\w*)', found.group(1)))
            # / A parameter taken by reference IS the caller's variable. Purging it destroys
            # / the caller's copy, so a function that purged its &$workerRegistry parameter
            # / wiped the worker registry on every call. Never demand one be purged.
            locals_found -= set(re.findall(r'&\$([a-z]\w*)', signature[start:finish]))
            # / A purge written as  if (!purgeSensitiveMemory(...))  consumes its own check &
            # / counts as a purge. A version that looked only for the bare call missed it &
            # / added a second, redundant purge beneath the one that was already there.
            destroyed = set()
            for found in re.finditer(r'purgeSensitiveMemory\s*\(([^;{]*)\)', body):
                destroyed |= set(re.findall(r'\$([a-z]\w*)', found.group(1)))
            for found in re.finditer(r'\bunset\s*\(([^;]*)\)\s*;', body):
                destroyed |= set(re.findall(r'\$([a-z]\w*)', found.group(1)))
            # / A function that purges nothing at all is a different finding from one that
            # / purges most things. The first is reported once.
            if not destroyed:
                if locals_found:
                    report('LOCALS', os.path.basename(path) + ':' + str(record['start'] + 1) + ' '
                           + name + '() holds ' + str(len(locals_found)) + ' local(s) & destroys none of them'
                           + '. Add a purgeSensitiveMemory() call naming each one before the return')
                    faults += 1
                continue
            for variable in sorted(locals_found - destroyed):
                report('LOCALS', os.path.basename(path) + ':' + str(record['start'] + 1) + ' '
                       + name + '() never destroys $' + variable
                       + '. Add it to the purgeSensitiveMemory() call before the return')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK FIFTEEN. Every comment is a complete sentence.
# / Convention twelve. A comment is written in complete sentences with proper punctuation.
# / A comment that trails off is a comment somebody stopped writing, & the next reader
# / cannot tell whether the thought was finished.
# / A line ending in a period, a question mark, an exclamation mark, a colon, or a closing
# / quote after one of those is complete. A line that is continued on the next comment line
# / is judged as part of that line rather than on its own, so a sentence may wrap.
# / A table row inside a comment, aligned with spaces, is not a sentence & is not reported.
def check_comment_sentences(files, report):
    faults = 0
    for path in files:
        original, blanked = read_source(path)
        lines = original
        # / Consecutive comment lines are one block, & the block is judged by its last line.
        # / A sentence may wrap across several lines & a wrapped line is not a fault, so
        # / judging every line reported four hundred wrapped sentences & no real one.
        # / A block whose LAST line trails off is a thought somebody stopped writing.
        index = 0
        while index < len(lines):
            stripped = lines[index].strip()
            if not stripped.startswith('// /') or stripped[4:].strip().startswith('---'):
                index += 1
                continue
            block_start = index
            while index < len(lines) and lines[index].strip().startswith('// /') and not lines[index].strip()[4:].strip().startswith('---'):
                index += 1
            block = [lines[k].strip()[4:].strip() for k in range(block_start, index)]
            block = [b for b in block if b != '']
            if not block:
                continue
            last = block[-1]
            # / A header line, a URL, a table row & a bare label are not prose.
            if re.search(r'https?://|Copyright|<3 Open|\S {2,}\S', last) or re.match(r'^[A-Z][A-Za-z ]+\.\.\.', last):
                continue
            if re.search(r'[.!?:]["\')]*$', last):
                continue
            report('SENTENCE', os.path.basename(path) + ':' + str(block_start + 1 + len(block) - 1)
                   + ' this comment block trails off. End its last line with a period: ' + last[:45])
            faults += 1
    return faults

# / -----------------------------------------------------------------------------------
# / CHECK SIXTEEN. No function parameter or return has a generic name.
# / Convention fourteen. A name like $a or $f says nothing to a reader & a signature is the
# / first thing a reader sees. Two characters or fewer, or a bare type word, is reported.
def check_generic_names(files, report):
    generic = {'a', 'b', 'c', 'd', 'e', 'f', 'g', 'i', 'j', 'k', 'n', 'p', 'q', 'r', 's', 't', 'v', 'x', 'y', 'z',
               'arr', 'array', 'str', 'string', 'int', 'num', 'val', 'value', 'var', 'obj', 'data', 'tmp', 'temp', 'foo', 'bar'}
    faults = 0
    for path in files:
        original, blanked = read_source(path)
        for name, record in read_functions(original, blanked).items():
            signature = record['signature']
            inside = signature[signature.index('(') + 1:signature.rindex(')')] if ')' in signature else ''
            for parameter in re.findall(r'\$(\w+)', inside):
                if parameter.lower() in generic or len(parameter) <= 2:
                    report('NAME', os.path.basename(path) + ':' + str(record['start'] + 1) + ' ' + name
                           + '() takes $' + parameter + '. Rename it to say what it holds')
                    faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK SEVENTEEN. No function destroys a value it is about to return.
# / purgeSensitiveMemory takes its arguments by reference & sets each to NULL, so a name
# / that appears in the purge call & then in the return is returned as nothing.
# / This is the fault the locals check can cause when it is obeyed without thought. It said
# / a lowercase parameter was never destroyed, the parameter was added to the purge, & the
# / parameter was also the return value. Eighteen functions were broken in one pass,
# / including every conversion pipeline, & the first symptom was a fatal three functions
# / away from any of them.
# / A return value is exempt from convention five BECAUSE it is returned, & the locals check
# / now knows that. This check is the backstop for a purge added by hand.
def check_return_not_purged(files, report):
    faults = 0
    for path in files:
        original, blanked = read_source(path)
        text = '\n'.join(blanked)
        for found in re.finditer(r'purgeSensitiveMemory\s*\(([^;]*)\);\s*\n\s*return\s+(?:array\s*\()?([^;]+)', text):
            purged = set(re.findall(r'\$(\w+)', found.group(1)))
            returned = set(re.findall(r'\$(\w+)', found.group(2)))
            line = text.count('\n', 0, found.start(2)) + 1
            for name in sorted(purged & returned):
                report('RETURN', os.path.basename(path) + ':' + str(line) + ' $' + name
                       + ' is purged & then returned, so it is returned as NULL. Remove it from the purge call')
                faults += 1
        # / A by-reference parameter purged anywhere in its function destroys the caller's
        # / variable, whether or not it is returned.
        for name, record in read_functions(original, blanked).items():
            # / redeclare shreds the caller's variable on purpose. See the locals check.
            if name == 'redeclare':
                continue
            signature = record['signature']
            body = '\n'.join(record['body'])
            # / A value that is purged & then ASSIGNED to something is the same fault as one
            # / that is purged & then returned. purgeSensitiveMemory writes NULL through a
            # / reference, so whatever it touched is NULL by the time it is stored.
            # / redeclare() purged its $newValue & then assigned it to the caller's variable,
            # / which silently emptied every string built through it. The interface that used
            # / it rendered links with no path at all & every settings change gave a 404.
            for found in re.finditer(r'purgeSensitiveMemory\s*\(([^;]*)\);\s*\n(?:\s*//[^\n]*\n)*\s*\$(\w+)\s*=\s*\$(\w+)\s*;', body):
                if found.group(3) in set(re.findall(r'\$(\w+)', found.group(1))):
                    report('RETURN', os.path.basename(path) + ':' + str(record['start'] + 1) + ' ' + name
                           + '() purges $' + found.group(3) + ' & then assigns it to $' + found.group(2)
                           + ', so NULL is stored. Purge the variable being overwritten instead')
                    faults += 1
            refs = set(re.findall(r'&\$(\w+)', signature[signature.index('('):]))
            if not refs:
                continue
            for found in re.finditer(r'purgeSensitiveMemory\s*\(([^;]*)\)', '\n'.join(record['body'])):
                for hit in sorted(refs & set(re.findall(r'\$(\w+)', found.group(1)))):
                    report('RETURN', os.path.basename(path) + ':' + str(record['start'] + 1) + ' ' + name
                           + '() purges &$' + hit + ', which is the caller\'s variable. Remove it from the purge call')
                    faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / CHECK EIGHTEEN. Every call supplies the arguments its function requires.
# / PHP raises an ArgumentCountError at runtime & not before, so a call left behind when a
# / signature grew is invisible until somebody exercises that exact path.
# /
# / This found two. cleanFiles() gained a second argument naming the roots it may clean
# / under, & two pipelines still called it with one. The missing argument made the call
# / refuse silently, the temporary sources were never removed, & the pipeline then reported
# / a failure on a conversion that had already succeeded.
# / Both were in pipelines that were not present when the signature changed, which is the
# / normal way this happens.
# /
# / A variadic parameter written as ...$name absorbs everything after it, so a function
# / declaring one has no maximum & its required count stops at the parameter before.
# / A parameter with a default is optional & is not counted as required.
def check_argument_counts(files, report):
    signatures = {}
    for path in files:
        original, blanked = read_source(path)
        for found in re.finditer(r'^function (\w+)\(([^)]*)\)', '\n'.join(blanked), re.M):
            parameters = [p for p in found.group(2).split(',') if p.strip()]
            required = 0
            for parameter in parameters:
                if '=' in parameter or '...' in parameter:
                    break
                required += 1
            signatures[found.group(1)] = (required, os.path.basename(path))
    # / A construct that looks like a call & is not one.
    language = {'array', 'isset', 'unset', 'list', 'empty', 'echo', 'print', 'return', 'if',
                'for', 'foreach', 'while', 'switch', 'catch', 'function', 'use', 'match'}
    faults = 0
    for path in files:
        original, blanked = read_source(path)
        text = '\n'.join(blanked)
        for found in re.finditer(r'\b([a-z]\w*)\s*\(([^();]*)\)', text):
            name = found.group(1)
            if name in language or name not in signatures:
                continue
            required, where = signatures[name]
            supplied = len([a for a in found.group(2).split(',') if a.strip()])
            if supplied < required:
                line = text.count('\n', 0, found.start()) + 1
                report('ARGS', os.path.basename(path) + ':' + str(line) + ' ' + name
                       + '() is called with ' + str(supplied) + ' argument(s) & requires '
                       + str(required) + '. Its signature is in ' + where)
                faults += 1
    return faults

# / -----------------------------------------------------------------------------------
# / The main logic. Every check runs & the total decides the exit code.
# / Nothing stops early, because an operator wants every fault in one pass rather than the
# / first one seven times.
def main():
    quiet = '--quiet' in sys.argv
    # / --fail-on names the kinds that decide the exit code. Every kind is still reported, but
    # / only a named one fails the run. A pre-commit hook uses this to block on the kinds that
    # / break execution while a tracked debt like CONTRACT is allowed through.
    # / With nothing named, every finding fails the run, which is the right default for a
    # / person reading the output & the wrong one for a hook nobody reads.
    fail_on = set()
    if '--fail-on' in sys.argv:
        position = sys.argv.index('--fail-on')
        if position + 1 < len(sys.argv):
            fail_on = set(sys.argv[position + 1].upper().split(','))
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
    # / Every check, in the order they run, keyed by the name --only accepts.
    # / A check is a name, a one line description & something to call.
    # / Adding one here is the only place a new check has to be registered.
    checks = (
        ('syntax', 'the php binary parses every file', lambda: check_syntax(files, report)),
        ('balance', 'every delimiter closes', lambda: check_balance(files, report)),
        ('order', 'nothing calls a component before it loads', lambda: check_call_order(root, report)),
        ('undefined', 'every variable a function reads exists', lambda: check_undefined_reads(files, report)),
        ('duplicate', 'no function is defined twice', lambda: check_duplicate_functions(files, report)),
        ('pins', 'every version pin matches the file it points at', lambda: check_version_pins(root, report)),
        ('errno', 'every error number raised is documented', lambda: check_error_numbers(root, files, report)),
        ('guard', 'every component refuses to load directly', lambda: check_component_guard(root, report)),
        ('contract', 'the Engine never calls application code', lambda: check_engine_independence(root, report)),
        ('config', 'every required setting is declared where config.php loads', lambda: check_config_globals(root, report)),
        ('caps', 'no comment line is entirely capitals', lambda: check_comment_case(files, report)),
        ('purge', 'every cleanup at a return carries its comment', lambda: check_purge_comment(files, report)),
        ('locals', 'every lowercase local is destroyed before return', lambda: check_locals_purged(files, report)),
        ('sentence', 'every comment ends a sentence', lambda: check_comment_sentences(files, report)),
        ('name', 'no parameter has a generic name', lambda: check_generic_names(files, report)),
        ('return', 'no function destroys what it returns', lambda: check_return_not_purged(files, report)),
        ('args', 'every call supplies the arguments its function requires', lambda: check_argument_counts(files, report)),
        ('exit', 'every function has one exit', lambda: check_single_exit(files, report)))
    if '--list' in sys.argv:
        print('Checks, in the order they run.')
        for name, description, runner in checks:
            print('  ' + name.ljust(12) + description)
        print()
        print('Run one with  --only <name>. Run every one by naming none.')
        return 0
    wanted = ''
    if '--only' in sys.argv:
        position = sys.argv.index('--only')
        wanted = sys.argv[position + 1] if position + 1 < len(sys.argv) else ''
        if wanted not in [name for name, description, runner in checks]:
            print('There is no check called ' + repr(wanted) + '. Run --list to see them.')
            return 2
    if not quiet:
        print('Checking ' + str(len(files)) + ' PHP files under ' + root + '.')
        print()
    total = 0
    for name, description, runner in checks:
        if wanted != '' and name != wanted:
            continue
        total += runner()
    if not quiet:
        print()
        if total == 0:
            print('Every check passed.')
        else:
            # / A count per kind, because twenty findings of one kind is one job & twenty
            # / findings of twenty kinds is twenty. The difference decides what to do next.
            counted = {}
            for kind, message in findings:
                counted[kind] = counted.get(kind, 0) + 1
            for kind in sorted(counted):
                print('  ' + str(counted[kind]).rjust(4) + '  ' + kind)
            print()
            print(str(total) + ' finding(s). A finding is not always a fault, & the header')
            print('for each check says which patterns it reports that are not.')
    if fail_on:
        blocking = [kind for kind, message in findings if kind in fail_on]
        if not quiet and total > 0:
            print(str(len(blocking)) + ' of those would block a commit under --fail-on ' + ','.join(sorted(fail_on)) + '.')
        return 1 if blocking else 0
    return 1 if total > 0 else 0


if __name__ == '__main__':
    sys.exit(main())
