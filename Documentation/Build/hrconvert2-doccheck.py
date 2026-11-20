#!/usr/bin/env python3
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRConvert2, Copyright on 11/5/2026 by Justin Grimes, www.github.com/zelon88
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
# / v4.3.1.
# / This file checks the documentation the way hrconvert2-check.py checks the code.
# /
# / Run it from the installation root, beside the application's entry point, convertCore.php for HRConvert2.
# / The entry point is read from the contract, Resources/Contract/app-engine-config.php, as the code checkers read it.
# /   python3 Documentation/Build/hrconvert2-doccheck.py
# /   python3 Documentation/Build/hrconvert2-doccheck.py --list
# /   python3 Documentation/Build/hrconvert2-doccheck.py --only entries
# /   python3 Documentation/Build/hrconvert2-doccheck.py --quiet
# /   python3 Documentation/Build/hrconvert2-doccheck.py --fix
# /
# / It exits zero when every check passes, one when any of them does not & two when it was
# / asked something it could not answer. --quiet prints nothing & leaves the exit code.
# /
# / Without --fix it never writes to a file.
# / --fix makes the repairs that cannot change what a document says, & nothing else. It
# / strips trailing whitespace & leaves exactly one blank line between a closing rule & the
# / next opening rule. Every other finding is a sentence somebody has to rewrite, so it is
# / reported & left alone. Run the tool again afterwards & read what is left.
# / The changelog is the project's record. This tool judges its header & never its entries,
# / & --fix never writes to it.
# /
# / Why this exists.
# / The manual pass at v4.0.0 found a header split in two by sections pasted into it, twice,
# / sections with no rules, a section with no title, entries documented twice with different
# / answers, five entries whose FATAL field said the opposite of the code, a list of contents
# / naming a file that does not exist, & dozens of names of settings & functions that had
# / been renamed or removed. None of it changes what the application does, which is exactly
# / why nothing caught it. v4.3.0 found twenty messages the code raised that their entries did
# / not document, which the messages check has caught since v4.3.1.
# / Each check below says what it CANNOT see as well as what it can.
# /
# / Hardware Requirements ...
# / This application requires at least a Raspberry Pi Model B+ or greater.
# / This application will run on just about any x86 or x64 computer.
# /
# / Dependency Requirements ...
# / This file requires Python 3 & nothing else.
# /
# / <3 Open-Source
# / -----------------------------------------------------------------------------------

import glob
import os
import re
import sys

RULE = '-' * 160
OLD_RULE = '-' * 83
LABELS = ('COPYRIGHT INFORMATION ...', 'LICENSE INFORMATION ...', 'APPLICATION INFORMATION ...',
          'FILE INFORMATION ...', 'HARDWARE REQUIREMENTS ...', 'DEPENDENCY REQUIREMENTS ...')
OPTIONAL_LABELS = ('ADDITIONAL INFORMATION ...',)
CLOSER = '<3 Open-Source'
FIELDS = ['ERROR MESSAGE', 'FILE', 'FATAL', 'LOGGED', 'ERROR DESCRIPTION']
# / What stands for a part of a message the code fills in, or a placeholder in a documented one. No message holds it.
WILDCARD = '\x00'
# / Names a document cites on purpose that no code defines. Each one says why it is cited.
# / A name earns a place here only when the sentence around it already explains it.
HISTORICAL_NAMES = {
    'inspectStreamIP': 'the old name of inspectContentForIPs, cited to explain the rename',
    'inspectStreamDomain': 'the old name of inspectContentForDomains, cited to explain the rename',
    'runPipeline': 'a fixed entry point name, cited to explain why entry points are unique',
    'verifyArchiveVersions': 'a version check the manifest retired, cited as history',
    'setupConfigModel': 'the name the absorption contract proposed, cited beside what it became',
    'encodeURIComponent': 'a JavaScript builtin rather than a function of this project',
    'fixManagedPermissions': 'HRConvert2\'s repair until v4.2.4, cited beside repairInstallation, which it became',
    'showVersionInfo': 'HRConvert2\'s -v until v4.2.4, cited beside showVersionReport, which it became',
    'verifyConfigVersion': 'the list of required settings until v4.2.4, cited beside the templates that replaced it',
    'applicationEnvironmentFindings': 'HRConvert2\'s environment provider until v4.2.4, cited beside $EnginePolicyChecks',
    'askOperator': 'HRConvert2\'s terminal prompt until v4.2.4, cited beside promptOperator, which took it over',
    'runApplicationRepair': 'the Engine\'s call to an application\'s repair until v4.2.4, cited beside repairInstallation'}
# / Labels a document writes on purpose that no configuration file declares.
EXAMPLE_LABELS = {
    'About The Subject': 'the example section title in ABOUT_DOCUMENTATION.txt',
    'Title': 'the example section title in ABOUT_DOCUMENTATION.md'}


# / -----------------------------------------------------------------------------------
# / Two functions to recognize the two lines the layout is built from.
# / Each accepts one line & returns TRUE when it is a rule or a title respectively.
# / A rule is a line of dashes & nothing else. Its width is judged by whoever calls this.
# / A title is a line between two pairs of dashes. A line of dashes is not a title, so the
# / character after the opening pair must be something else. A title may end in an
# / argument name, which is why the character before the closing pair may be a dash.
def is_rule(line):
    return re.fullmatch(r'-{20,}', line) is not None


def is_title(line):
    return re.fullmatch(r'--[^-\s].*\S--', line) is not None


# / -----------------------------------------------------------------------------------
# / A function to decide whether a title names a section or a subheading.
# / Accepts the title line. Returns TRUE for a section title.
# / A section title is written in title case & a subheading in sentence case, which is the
# / only difference between them a reader can see. A word of four letters or more that
# / begins in lower case marks a subheading. An argument such as --config inside a title is
# / a name rather than a word & is not judged.
def names_a_section(line):
    words = re.findall(r"[A-Za-z][A-Za-z']*", re.sub(r'(?<!\S)--?[a-z][\w-]*', '', line[2:-2]))
    return not any(len(word) >= 4 and word[0].islower() for word in words)


# / -----------------------------------------------------------------------------------
# / A function to find the documents this tool judges.
# / Accepts the installation root. Returns the plain text documents & the markdown ones.
# / The pipeline approval records are generated, & are judged because they ship.
def documents(root):
    text = sorted(glob.glob(os.path.join(root, 'Documentation', '*.txt')))
    text += sorted(glob.glob(os.path.join(root, 'Resources', 'Pipelines', '*', 'DEPENDENCY_APPROVALS.txt')))
    markdown = sorted(glob.glob(os.path.join(root, 'Documentation', '*.md')))
    return text, markdown


# / -----------------------------------------------------------------------------------
# / A function to tell the project's record from the documents that describe it.
# / Accepts a path. Returns TRUE for the changelog.
# / The changelog's entries are never changed, not even for whitespace. A fault in one is
# / part of the record rather than something to repair, so only its header is judged, &
# / --fix never writes to it. A COMMIT header that is factually wrong is the one exception,
# / & that is a correction for a person to make.
def is_record(path):
    return os.path.basename(path) == 'CHANGELOG.txt'


# / -----------------------------------------------------------------------------------
# / A function to read a document as a list of lines.
# / Accepts a path. Returns its lines without their line endings.
# / A file that ends in a newline gives an empty last line, & every check expects that.
def read_lines(path):
    return open(path, encoding='utf-8').read().split('\n')


# / -----------------------------------------------------------------------------------
# / A function to find the PHP files whose names & error numbers a document may cite.
# / Accepts the installation root. Returns the application's files & the build tools'.
# / They are returned apart because a build tool defines stubs under the names of real
# / functions. A stub's name is still a name somebody may cite. Its error numbers are not
# / the application's & must never make a FILE field look right.
def php_sources(root):
    application, tools = [], []
    for base, dirs, files in os.walk(root):
        # / Nothing under a version control directory or a data location is source.
        dirs[:] = [d for d in dirs if d not in ('.git', 'DATA', 'Logs', 'node_modules')]
        for name in files:
            if name.endswith('.php'):
                path = os.path.join(base, name)
                (tools if os.path.join('Documentation', 'Build') in path else application).append(path)
    return sorted(application), sorted(tools)


# / -----------------------------------------------------------------------------------
# / A function to find where a document's header ends.
# / Accepts the document's lines. Returns the index of the closing rule, or None.
# / The closing rule is the first rule as wide as the opening one that sits directly
# / under <3 Open-Source.
def header_end(lines):
    return next((i for i in range(2, len(lines)) if lines[i] == lines[0] and lines[i - 1] == CLOSER), None)


# / -----------------------------------------------------------------------------------
# / THE HEADER CHECK. Every plain text file opens with the standard header & nothing else.
# / The six blocks appear in order between the opening rule & the closing rule, each label
# / after one blank line, & the last line before the closing rule is <3 Open-Source.
# / A rule or a title anywhere inside the header is a section pasted into it. That is the
# / fault this check exists for, & both files it was found in read as finished.
# / The older files drawn with 83 dashes are held to the same blocks & not to the width.
# / It cannot tell whether what a block says is true. It reads that the block is there.
def check_header(paths, report):
    faults = 0
    for path in paths:
        lines = read_lines(path)
        name = os.path.relpath(path)
        if lines[0] not in (RULE, OLD_RULE):
            report('HEADER', name + ':1 does not open with a rule of 160 dashes')
            faults += 1
            continue
        closing = header_end(lines)
        if closing is None:
            report('HEADER', name + ' has no ' + CLOSER + ' line directly above a rule as wide as its first')
            faults += 1
            continue
        header = [line.rstrip() for line in lines[1:closing]]
        for number, line in enumerate(header, start=2):
            if is_rule(line) or is_title(line):
                report('HEADER', name + ':' + str(number) + ' a rule or a title sits inside the header. Move what '
                       + 'follows it below the closing rule, as a section of its own')
                faults += 1
                break
        present = [line for line in header if line in LABELS]
        if present != list(LABELS):
            missing = [label for label in LABELS if label not in present]
            report('HEADER', name + ' header blocks are ' + (('missing ' + ', '.join(missing)) if missing else 'out of order')
                   + '. The order is ' + ', '.join(label.split(' ')[0] for label in LABELS))
            faults += 1
        for number, line in enumerate(header, start=2):
            if line in LABELS[1:] + OPTIONAL_LABELS and lines[number - 2].strip() != '':
                report('HEADER', name + ':' + str(number) + ' ' + line + ' needs one blank line above it')
                faults += 1
        if lines[closing - 2].strip() != '':
            report('HEADER', name + ':' + str(closing) + ' ' + CLOSER + ' needs one blank line above it')
            faults += 1
        if LABELS[3] in header:
            position = header.index(LABELS[3])
            if position + 1 >= len(header) or not re.fullmatch(r'v\d+\.\d+\.\d+\.', header[position + 1].strip()):
                report('HEADER', name + ':' + str(position + 3) + ' FILE INFORMATION does not begin with a version line such as v4.0.0.')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / THE SECTION CHECK. Every section is a rule, a title, a blank line, a body & a rule.
# / The file is read as it is written, one line at a time, either inside a section or
# / between two of them. Between two sections there is exactly one blank line & nothing
# / else. A rule found there opens a section & is followed by a title & a blank line.
# / A rule found inside a section closes it, & the last section is closed too.
# / A title in title case inside a body is a section that lost its rules. One in sentence
# / case is a subheading & belongs there. That is a judgement about case, so a section
# / somebody titled in sentence case is read as a subheading.
# / The older files drawn with 83 dashes use another layout & are skipped here.
# / ERROR_DESCRIPTIONS.txt is a list of entries rather than of sections & has its own check.
def check_sections(paths, report):
    faults = 0
    for path in paths:
        lines = read_lines(path)
        name = os.path.relpath(path)
        closing = header_end(lines) if lines[0] == RULE else None
        if closing is None or os.path.basename(path) == 'ERROR_DESCRIPTIONS.txt':
            continue
        inside = False
        stray = False
        blanks = 0
        for index in range(closing + 1, len(lines)):
            line = lines[index]
            where = name + ':' + str(index + 1)
            if is_rule(line):
                if line != RULE:
                    report('SECTION', where + ' a rule of ' + str(len(line)) + ' dashes. Use 160')
                    faults += 1
                stray = False
                if inside:
                    inside = False
                    blanks = 0
                    continue
                if blanks != 1:
                    report('SECTION', where + ' leave exactly one blank line between two sections')
                    faults += 1
                following = lines[index + 1] if index + 1 < len(lines) else ''
                if not is_title(following):
                    report('SECTION', where + ' a section opens here with no title. Give it one')
                    faults += 1
                elif index + 2 < len(lines) and lines[index + 2] != '':
                    report('SECTION', name + ':' + str(index + 2) + ' leave one blank line under a title')
                    faults += 1
                inside = True
                continue
            if inside:
                if is_title(line) and names_a_section(line) and not is_rule(lines[index - 1]):
                    report('SECTION', where + ' ' + line + ' has no opening rule. Close the section above it with '
                           + 'a rule, leave one blank line & open this one with a rule')
                    faults += 1
                continue
            if line.strip() == '':
                blanks += 1
                stray = False
                continue
            if stray:
                # / The rest of a paragraph already reported.
                continue
            if is_title(line):
                report('SECTION', where + ' ' + line + ' has no opening rule. Leave one blank line above it & '
                       + 'open it with a rule')
            else:
                report('SECTION', where + ' text between two sections belongs to neither. Give it a rule & a title, '
                       + 'or move it into the section above')
            faults += 1
            inside = lost_its_rules(lines, index)
            stray = not inside
            blanks = 0
        if inside:
            report('SECTION', name + ' the last section has no closing rule')
            faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / A function to decide what text found between two sections is.
# / Accepts a document's lines & the index of the text. Returns TRUE when the text is the
# / body of a section that lost its opening rule & title, & FALSE when it is a stray
# / paragraph.
# / The next rule decides. A rule followed by a title opens the next section, so the text
# / before it stood alone. Any other rule closes the section the text belongs to.
# / Reading it this way reports one fault once, rather than again at every line after it.
def lost_its_rules(lines, index):
    following = next((i for i in range(index + 1, len(lines)) if is_rule(lines[i])), None)
    return following is not None and not (following + 1 < len(lines) and is_title(lines[following + 1]))


# / -----------------------------------------------------------------------------------
# / THE WHITESPACE CHECK. No line ends in whitespace & no three blank lines sit together.
# / Neither changes what a document says, which is why --fix repairs the first.
# / Markdown reads two spaces at the end of a line of text as a line break, & that one form
# / is left alone in a markdown file.
# / Three blank lines are judged in the files drawn with 160 dashes, except
# / ERROR_DESCRIPTIONS.txt, whose entries check counts the blank lines between entries.
def check_whitespace(paths, markdown, report):
    faults = 0
    for path in paths + markdown:
        if is_record(path):
            continue
        lines = read_lines(path)
        name = os.path.relpath(path)
        for number, line in enumerate(lines, start=1):
            if line != line.rstrip() and not (path in markdown and is_line_break(line)):
                report('SPACE', name + ':' + str(number) + ' ends in whitespace')
                faults += 1
        if path in markdown or lines[0] != RULE or os.path.basename(path) == 'ERROR_DESCRIPTIONS.txt':
            continue
        run = 0
        for number, line in enumerate(lines, start=1):
            run = run + 1 if line.strip() == '' else 0
            if run == 3:
                report('SPACE', name + ':' + str(number) + ' three blank lines in a row')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / A function to recognize a markdown line break.
# / Accepts one line. Returns TRUE when it is text followed by exactly two spaces.
def is_line_break(line):
    return line.strip() != '' and line.endswith('  ') and not line.endswith('   ')


# / -----------------------------------------------------------------------------------
# / A function to learn which files raise which error numbers, & whether each is fatal.
# / Accepts the application's PHP files & the installation root. Returns two maps keyed by
# / error number, one of the files that raise it & one of the fatal arguments it is raised with.
# / errorEntry() is read when its number is written as a literal, & so is its fatal argument
# / when that is TRUE or FALSE. quickDie() always halts. A number held in a variable is
# / found where the variable is assigned, which gives its file & nothing about whether it
# / is fatal.
def raised_errors(sources, root):
    files, fatal = {}, {}
    for source in sources:
        text = open(source, encoding='utf-8', errors='replace').read()
        where = os.path.relpath(source, root)
        for found in re.finditer(r'errorEntry\s*\((?:[^;]*?),\s*(\d+)\s*,\s*(TRUE|FALSE|\$\w+)\s*\)', text):
            files.setdefault(int(found.group(1)), set()).add(where)
            if found.group(2) in ('TRUE', 'FALSE'):
                fatal.setdefault(int(found.group(1)), set()).add(found.group(2))
        for found in re.finditer(r'quickDie\s*\((?:[^;]*?),\s*(\d+)\s*\)', text):
            files.setdefault(int(found.group(1)), set()).add(where)
            fatal.setdefault(int(found.group(1)), set()).add('TRUE')
        for found in re.finditer(r'\$[a-zA-Z]*[Ee]rror[A-Za-z]*\s*=\s*(\d{3,6})\s*;', text):
            files.setdefault(int(found.group(1)), set()).add(where)
    return files, fatal


# / -----------------------------------------------------------------------------------
# / THE ENTRIES CHECK. ERROR_DESCRIPTIONS.txt holds one entry per number, in order.
# / The example comes first. Every entry sits between two rules of 160 dashes, two blank
# / lines separate entries, the numbers run in ascending order & no number is documented
# / twice under the same label. Kernel-35000, HRConvert2-35000 & Engine-35000 are three
# / entries on purpose, because one number names a family of guards.
# / Every entry has the five fields of the example, in its order, & FATAL & LOGGED say YES
# / or NO.
# / FILE names a file that raises the number, & FATAL says what the code does, wherever
# / the code writes both as literals & raises the number one way only.
# / A reservation names no number it calls unused that an entry documents, & where it
# / says which of its numbers are raised, that range holds every one documented in its
# / block, from v4.3.1. v4.3.1 documented 41009 & left its reservation calling it unused.
# / It cannot tell whether a description is still true. Reading that is a person's job.
def check_entries(root, report):
    path = os.path.join(root, 'Documentation', 'ERROR_DESCRIPTIONS.txt')
    if not os.path.exists(path):
        return 0
    lines = read_lines(path)
    name = os.path.relpath(path)
    faults = 0
    raised, fatal = raised_errors(php_sources(root)[0], root)
    if '~~START OF EXAMPLE~~' not in lines:
        report('ENTRIES', name + ' has no ~~START OF EXAMPLE~~, & the header says the example comes first')
        faults += 1
    example = lines.index('~~START OF EXAMPLE~~') if '~~START OF EXAMPLE~~' in lines else 0
    previous = -1
    seen = {}
    for index, line in enumerate(lines):
        if is_rule(line) and line != RULE:
            report('ENTRIES', name + ':' + str(index + 1) + ' a rule of ' + str(len(line)) + ' dashes. Use 160')
            faults += 1
        if line != 'ERROR MESSAGE':
            continue
        end = next((i for i in range(index + 1, len(lines)) if lines[i] == RULE), len(lines))
        fields = [lines[i] for i in range(index, end) if re.fullmatch(r'[A-Z][A-Z ]*[A-Z]', lines[i])]
        if fields != FIELDS:
            report('ENTRIES', name + ':' + str(index + 1) + ' the fields are ' + ', '.join(fields) + '. Use '
                   + ', '.join(FIELDS) + ', in that order')
            faults += 1
        found = re.search(r'(<APPLICATION_NAME>|[A-Za-z0-9]+)-(\d+)[,:]', lines[index + 1])
        if not found:
            # / The example explains each field rather than answering it, & has no number.
            continue
        for field in ('FATAL', 'LOGGED'):
            if field in fields:
                position = lines.index(field, index)
                if lines[position + 1].strip() not in ('YES', 'NO'):
                    report('ENTRIES', name + ':' + str(position + 2) + ' ' + field + ' says ' + repr(lines[position + 1].strip())
                           + '. It says YES or NO')
                    faults += 1
        number = int(found.group(2))
        label = found.group(1) + '-' + str(number)
        if index < example:
            report('ENTRIES', name + ':' + str(index + 1) + ' ' + label + ' sits above the example. Move it into numeric order')
            faults += 1
        key = (found.group(1), number)
        if key in seen:
            report('ENTRIES', name + ':' + str(index + 1) + ' ' + label + ' is documented twice, here & at line '
                   + str(seen[key] + 1) + '. Merge them into one entry')
            faults += 1
        seen[key] = index
        if number < previous:
            report('ENTRIES', name + ':' + str(index + 1) + ' ' + label + ' comes after error ' + str(previous)
                   + '. Move it into numeric order')
            faults += 1
        previous = max(previous, number)
        if lines[index - 1] != RULE:
            report('ENTRIES', name + ':' + str(index + 1) + ' ' + label + ' has no opening rule')
            faults += 1
        else:
            gap = index - 2
            while gap >= 0 and lines[gap] == '':
                gap -= 1
            if gap >= 0 and lines[gap] == RULE and index - 2 - gap != 2:
                report('ENTRIES', name + ':' + str(index) + ' leave exactly two blank lines between entries')
                faults += 1
        if 'FILE' in fields:
            stated = []
            position = lines.index('FILE', index) + 1
            while position < end and lines[position].strip():
                stated.append(lines[position].strip().lstrip('/'))
                position += 1
            actual = raised.get(number, set())
            if actual and not any(source.endswith(file) or file.endswith(source) for source in actual for file in stated):
                report('ENTRIES', name + ':' + str(index + 1) + ' ' + label + ' names ' + ', '.join(stated)
                       + ' & is raised in ' + ', '.join(sorted(actual)))
                faults += 1
        if 'FATAL' in fields and len(fatal.get(number, set())) == 1:
            wanted = 'YES' if 'TRUE' in fatal[number] else 'NO'
            position = lines.index('FATAL', index) + 1
            if lines[position].strip() in ('YES', 'NO') and lines[position].strip() != wanted:
                report('ENTRIES', name + ':' + str(position + 1) + ' ' + label + ' says FATAL ' + lines[position].strip()
                       + ' & the code raises it with ' + ('TRUE' if wanted == 'YES' else 'FALSE') + '. Say ' + wanted)
                faults += 1
    # / A reservation is read as one sentence, so a range written across two lines is still found.
    # / Two phrasings call a range unused, & a block whose raised numbers are not one range says so in words, which is not judged.
    documented = set(number for (prefix, number) in seen)
    for index, line in enumerate(lines):
        if line != 'ERROR NUMBER RESERVATION':
            continue
        end = next((i for i in range(index + 1, len(lines)) if lines[i] == RULE), len(lines))
        sentence = ' '.join(lines[i].strip() for i in range(index + 1, end))
        unused = re.findall(r'(\d+) through (\d+) are currently unused', sentence)
        unused += re.findall(r'(\d+) through (\d+) are reserved for [^,&.]* and are currently unused', sentence)
        for first, last in unused:
            clash = sorted(number for number in documented if int(first) <= number <= int(last))
            if clash:
                report('ENTRIES', name + ':' + str(index + 1) + ' the reservation calls ' + first + ' through ' + last
                       + ' unused, & ' + ', '.join(str(number) for number in clash) + ' is documented. Say which are unused')
                faults += 1
        reserved = re.search(r'(\d+) through (\d+) are reserved', sentence)
        for first, last in re.findall(r'(\d+) through (\d+) are raised', sentence):
            if not reserved:
                continue
            outside = sorted(number for number in documented if int(reserved.group(1)) <= number <= int(reserved.group(2))
                             and not int(first) <= number <= int(last))
            if outside:
                report('ENTRIES', name + ':' + str(index + 1) + ' the reservation says ' + first + ' through ' + last
                       + ' are raised, & ' + ', '.join(str(number) for number in outside) + ' is documented too. Say which are raised')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / A function to read the arguments of one call, from v4.3.1.
# / Accepts the text of a PHP file & the index of the call's opening parenthesis.
# / Returns the arguments as written, split where the call itself separates them, or None
# / where the call never closes. A comma inside a string, a nested call or an array is not
# / a separator, so an argument is returned whole however it is built.
def call_arguments(text, start):
    arguments, current = [], []
    depth, index, quote = 0, start, None
    while index < len(text):
        character = text[index]
        if quote:
            current.append(character)
            if character == '\\' and index + 1 < len(text):
                current.append(text[index + 1])
                index += 2
                continue
            if character == quote:
                quote = None
        elif character in '\'"':
            quote = character
            current.append(character)
        elif character in '([{':
            depth += 1
            if depth > 1:
                current.append(character)
        elif character in ')]}':
            depth -= 1
            if depth == 0:
                arguments.append(''.join(current).strip())
                return arguments
            current.append(character)
        elif character == ',' and depth == 1:
            arguments.append(''.join(current).strip())
            current = []
        elif depth >= 1:
            current.append(character)
        index += 1
    return None


# / -----------------------------------------------------------------------------------
# / A function to turn the message a call writes into a pattern, from v4.3.1.
# / Accepts the PHP expression a message is built from. Returns the message with WILDCARD
# / in place of each part the code fills in, or None where no part of it is written out.
# / The expression is split where the code joins parts with a full stop, outside every
# / string & bracket. A single quoted string is its text, & so is a double quoted one that
# / names no variable & no escape. Everything else, such as a variable, a call or a choice
# / between two texts, is a wildcard, because it may be any text.
def message_pattern(expression):
    parts, current = [], []
    depth, index, quote = 0, 0, None
    while index < len(expression):
        character = expression[index]
        if quote:
            current.append(character)
            if character == '\\' and index + 1 < len(expression):
                current.append(expression[index + 1])
                index += 2
                continue
            if character == quote:
                quote = None
        elif character in '\'"':
            quote = character
            current.append(character)
        elif character in '([{':
            depth += 1
            current.append(character)
        elif character in ')]}':
            depth -= 1
            current.append(character)
        elif character == '.' and depth == 0:
            parts.append(''.join(current).strip())
            current = []
        else:
            current.append(character)
        index += 1
    parts.append(''.join(current).strip())
    pattern, written = '', False
    for part in parts:
        single = re.fullmatch(r"'((?:[^'\\]|\\.)*)'", part, re.S)
        double = re.fullmatch(r'"([^"\\$]*)"', part)
        if single:
            pattern += single.group(1).replace("\\'", "'").replace('\\\\', '\\')
            written = True
        elif double:
            pattern += double.group(1)
            written = True
        else:
            pattern += WILDCARD
    return squeeze(pattern) if written else None


# / -----------------------------------------------------------------------------------
# / A function to make two patterns comparable, from v4.3.1.
# / Accepts a pattern. Returns it with every run of whitespace one space & every run of
# / wildcards one wildcard, because a message wrapped in a document is read as one line.
def squeeze(pattern):
    return re.sub(WILDCARD + '+', WILDCARD, re.sub(r'\s+', ' ', pattern).strip())


# / -----------------------------------------------------------------------------------
# / A function to decide whether two patterns can describe one line of a log, from v4.3.1.
# / Accepts two patterns. Returns TRUE where some text matches both. A wildcard matches any
# / run of characters, none included, so a placeholder may stand for any part the code
# / fills in, & a part the code fills in may hold any word the document writes out.
# / It walks both patterns at once, as two machines reading the same text would.
def patterns_meet(first, second):
    seen, waiting = set(), [(0, 0)]
    while waiting:
        place = waiting.pop()
        if place in seen:
            continue
        seen.add(place)
        left, right = place
        if left == len(first) and right == len(second):
            return True
        moves = []
        if left < len(first) and first[left] == WILDCARD:
            moves.append((left + 1, right))
            if right < len(second):
                moves.append((left, right + 1))
        if right < len(second) and second[right] == WILDCARD:
            moves.append((left, right + 1))
            if left < len(first):
                moves.append((left + 1, right))
        if left < len(first) and right < len(second) and WILDCARD not in (first[left], second[right]) and first[left] == second[right]:
            moves.append((left + 1, right + 1))
        waiting.extend(move for move in moves if move not in seen)
    return False


# / -----------------------------------------------------------------------------------
# / A function to read the messages ERROR_DESCRIPTIONS.txt documents, from v4.3.1.
# / Accepts the file's lines. Returns each number's messages, as written & as patterns.
# / Each ERROR!!! line in an entry's ERROR MESSAGE field begins a message. It runs on to the
# / next line until it ends in a full stop, an exclamation mark or a question mark, & a
# / line after that is a note about it, such as the wording an older release used.
def documented_messages(lines):
    messages = {}
    index = 0
    while index < len(lines):
        if lines[index] == 'ERROR MESSAGE':
            index += 1
            current = None
            while index < len(lines) and lines[index].strip():
                found = re.match(r'\s+ERROR!!! <TIMESTAMP>, (?:<APPLICATION_NAME>|[A-Za-z0-9]+)-(\d+), <SESSION_IDENTIFIER>: (.*)$', lines[index])
                if found:
                    current = [found.group(2)]
                    messages.setdefault(int(found.group(1)), []).append(current)
                elif current is not None and not re.match(r'\s+(?:ERROR|WARNING)!!!', lines[index]) and not re.search(r'[.!?]$', current[0].rstrip()):
                    current[0] += ' ' + lines[index].strip()
                else:
                    current = None
                index += 1
        index += 1
    return dict((number, [(text[0], squeeze(re.sub(r'<[A-Z][A-Z0-9_]*>', WILDCARD, text[0]))) for text in texts]) for number, texts in messages.items())


# / -----------------------------------------------------------------------------------
# / THE MESSAGES CHECK, from v4.3.1. Each message the code raises is one ERROR_DESCRIPTIONS.txt
# / documents under its number, so an administrator who searches for a line of the log finds it.
# / Every errorEntry() & quickDie() whose number is written as a literal is read, & its message
# / is compared with each one documented under that number. The two agree where some line of a
# / log could be written by the code & read as the documented message, with each <PLACEHOLDER>
# / standing for a part the code fills in. v4.3.0 found twenty that did not agree.
# / It cannot see a message held whole in a variable, a number held in a variable, or a number
# / no entry documents, which the entries check reports where it is raised & never documented.
# / It cannot tell a placeholder that names the wrong thing, because any text may fill one.
def check_messages(root, report):
    path = os.path.join(root, 'Documentation', 'ERROR_DESCRIPTIONS.txt')
    if not os.path.exists(path):
        return 0
    documented = documented_messages(read_lines(path))
    faults = 0
    for source in php_sources(root)[0]:
        text = open(source, encoding='utf-8', errors='replace').read()
        where = os.path.relpath(source, root)
        for found in re.finditer(r'\b(errorEntry|quickDie)\s*\(', text):
            arguments = call_arguments(text, found.end() - 1)
            if not arguments or len(arguments) != (3 if found.group(1) == 'errorEntry' else 2) or not re.fullmatch(r'\d+', arguments[1]):
                continue
            number = int(arguments[1])
            pattern = message_pattern(arguments[0])
            if pattern is None or number not in documented:
                continue
            if not any(patterns_meet(pattern, written) for message, written in documented[number]):
                report('MESSAGES', where + ':' + str(text[:found.start()].count('\n') + 1) + ' raises ' + str(number) + ' as ' + repr(pattern.replace(WILDCARD, '<...>'))
                       + ' & ERROR_DESCRIPTIONS.txt documents it as ' + ' or '.join(repr(message) for message, written in documented[number])
                       + '. Document the message the code raises, with a <PLACEHOLDER> for each part it fills in')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / THE NAMES CHECK. Every function & every setting a document names still exists.
# / A function is a camel case name written with () after it. It must be defined in some PHP
# / file or Build tool, or be listed in HISTORICAL_NAMES with the reason it is cited.
# / A setting is a title case label between two pairs of dashes inside a sentence. It must
# / be a label in either half of the configuration template, engineConfig.php or the contract, or a section
# / title of some document, which the documents also write that way.
# / A configuration label that carries a typo is matched as the configuration spells it,
# / because a reader searching config.php types what the document says.
# / The changelog is not read. A stale name in it is history.
# / It cannot see a name written without (), a name with no capital letter in it, which is
# / how PHP's own functions are written, or a setting named in plain words.
def check_names(root, paths, markdown, report):
    defined = set()
    for source in sum(php_sources(root), []):
        text = open(source, encoding='utf-8', errors='replace').read()
        defined |= set(re.findall(r'^\s*function\s+([A-Za-z_]\w*)\s*\(', text, re.M))
    for source in glob.glob(os.path.join(root, 'Documentation', 'Build', '*.py')):
        defined |= set(re.findall(r'^def\s+([A-Za-z_]\w*)', open(source, encoding='utf-8').read(), re.M))
    defined_lower = set(function.lower() for function in defined)
    labels = set()
    for source in ('Resources/Contract/config-template.php', 'Resources/Engine/config-template.php', 'Resources/Engine/engineConfig.php', 'Resources/Contract/app-engine-config.php'):
        full = os.path.join(root, source)
        if os.path.exists(full):
            for found in re.finditer(r'^// /\s+--(.*?[^-])-{2,}\.?\s*$', open(full, encoding='utf-8').read(), re.M):
                labels.add(found.group(1).strip())
    titles = set(line[2:-2] for path in paths for line in read_lines(path) if is_title(line))
    faults = 0
    for path in paths + markdown:
        if is_record(path):
            continue
        name = os.path.relpath(path)
        for number, line in enumerate(read_lines(path), start=1):
            for found in re.finditer(r'\b([a-z][a-z0-9]*[A-Z][A-Za-z0-9]*)\(\)', line):
                function = found.group(1)
                if function in defined or function in HISTORICAL_NAMES:
                    continue
                hint = ' The code spells it with other capitals.' if function.lower() in defined_lower else ''
                report('NAMES', name + ':' + str(number) + ' ' + function + '() is not defined anywhere.' + hint
                       + ' Name what the code calls it now')
                faults += 1
            if is_title(line):
                continue
            for found in re.finditer(r"(?<![-\w])--([A-Z][A-Za-z0-9]*(?: [A-Za-z0-9&']+)*)--(?![-\w])", line):
                label = found.group(1)
                if label in labels or label in titles or label in EXAMPLE_LABELS:
                    continue
                report('NAMES', name + ':' + str(number) + ' --' + label + '-- is not a configuration label or a '
                       + 'section title. Name the label config.php uses')
                faults += 1
    return faults


# / -----------------------------------------------------------------------------------
# / A function to leave exactly one blank line between two sections.
# / Accepts a document's lines & the index of its header's closing rule. Returns the lines.
# / It reads the file the way the section check does, so a rule is an opening rule only
# / where the section check would call it one. Text between two sections is left where it
# / is with the blank lines around it, because moving it is a decision about what it says.
def collapse_gaps(lines, closing):
    repaired = lines[:closing + 1]
    inside = False
    blanks = 0
    for index in range(closing + 1, len(lines)):
        line = lines[index]
        if is_rule(line) and inside:
            repaired.append(line)
            inside = False
            blanks = 0
        elif is_rule(line):
            repaired.extend(['', line])
            inside = True
            blanks = 0
        elif inside:
            repaired.append(line)
        elif line.strip() == '':
            blanks += 1
        else:
            repaired.extend([''] * blanks + [line])
            inside = lost_its_rules(lines, index)
            blanks = 0
    return repaired + [''] * blanks


# / -----------------------------------------------------------------------------------
# / A function to make the repairs that cannot change what a document says.
# / Accepts the documents. Returns the number of files it rewrote.
# / Trailing whitespace goes, except a markdown line break, & a file drawn with 160 dashes
# / gets exactly one blank line between two sections. ERROR_DESCRIPTIONS.txt keeps two
# / between entries. A file's last line is left ending however it ended. The changelog is
# / never written.
def repair(paths, markdown):
    rewritten = 0
    for path in paths + markdown:
        if is_record(path):
            continue
        original = open(path, encoding='utf-8').read()
        ends_with_newline = original.endswith('\n')
        lines = original.split('\n')
        if ends_with_newline:
            lines = lines[:-1]
        lines = [line if (path in markdown and is_line_break(line)) else line.rstrip() for line in lines]
        closing = header_end(lines) if lines and lines[0] == RULE and path not in markdown else None
        if closing is not None and os.path.basename(path) != 'ERROR_DESCRIPTIONS.txt':
            lines = collapse_gaps(lines, closing)
        text = '\n'.join(lines) + ('\n' if ends_with_newline else '')
        if text != original:
            open(path, 'w', encoding='utf-8').write(text)
            rewritten += 1
    return rewritten


# / -----------------------------------------------------------------------------------
# / The main logic. Every check runs & the total decides the exit code.
def main():
    quiet = '--quiet' in sys.argv
    root = os.getcwd()
    # / The entry point the contract names, read as text as the code checkers read it, & app.php where it names none.
    contract = os.path.join(root, 'Resources', 'Contract', 'app-engine-config.php')
    declared = re.search(r"^\$EngineEntryPoint\s*=\s*'([^']+)'\s*;", open(contract, encoding='utf-8', errors='replace').read() if os.path.exists(contract) else '', re.M)
    entry_point = os.path.basename(declared.group(1).strip()) if declared else 'app.php'
    if not os.path.exists(os.path.join(root, entry_point)):
        print('Run this from the installation root, beside ' + entry_point + ', the entry point Resources/Contract/app-engine-config.php names.')
        return 2
    paths, markdown = documents(root)
    findings = []

    def report(kind, message):
        findings.append(kind)
        if not quiet:
            print('  ' + kind.ljust(9) + message)

    checks = (
        ('header', 'every plain text file opens with the standard header', lambda: check_header(paths, report)),
        ('sections', 'every section is a rule, a title, a body & a rule', lambda: check_sections(paths, report)),
        ('whitespace', 'no trailing whitespace & no three blank lines together', lambda: check_whitespace(paths, markdown, report)),
        ('entries', 'ERROR_DESCRIPTIONS.txt holds one true entry per number, in order', lambda: check_entries(root, report)),
        ('messages', 'every message the code raises is one documented under its number', lambda: check_messages(root, report)),
        ('names', 'every function & setting a document names still exists', lambda: check_names(root, paths, markdown, report)))
    if '--list' in sys.argv:
        print('Checks, in the order they run.')
        for name, description, runner in checks:
            print('  ' + name.ljust(12) + description)
        print()
        print('Run one with --only <name>. Repair whitespace & the gaps between sections with --fix.')
        return 0
    if '--fix' in sys.argv:
        print('Rewrote ' + str(repair(paths, markdown)) + ' file(s). Run again without --fix to see what is left.')
        return 0
    wanted = ''
    if '--only' in sys.argv:
        position = sys.argv.index('--only')
        wanted = sys.argv[position + 1] if position + 1 < len(sys.argv) else ''
        if wanted not in [name for name, description, runner in checks]:
            print('There is no check called ' + repr(wanted) + '. Run --list to see them.')
            return 2
    if not quiet:
        print('Checking ' + str(len(paths)) + ' plain text & ' + str(len(markdown)) + ' markdown documents under ' + root + '.')
        print()
    total = 0
    for name, description, runner in checks:
        if wanted == '' or name == wanted:
            total += runner()
    if not quiet:
        print()
        if total == 0:
            print('Every check passed.')
        else:
            for kind in sorted(set(findings)):
                print('  ' + str(findings.count(kind)).rjust(4) + '  ' + kind)
            print()
            print(str(total) + ' finding(s).')
    return 0 if total == 0 else 1


if __name__ == '__main__':
    sys.exit(main())
