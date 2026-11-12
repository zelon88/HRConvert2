#!/usr/bin/env python3
# / -----------------------------------------------------------------------------------
# / COPYRIGHT INFORMATION ...
# / HRConvert2, Copyright on 10/30/2026 by Justin Grimes, www.github.com/zelon88
# /
# / LICENSE INFORMATION ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / FILE INFORMATION ...
# / v3.9.7.
# / This file writes the two documents that ship beside every pipeline. The dependency
# / approval record, & the README that introduces the pipeline & lists what it depends on.
# /
# / A pipeline is detachable, so the record of what its dependencies are & whether they have
# / been audited travels with it rather than living in one central file somebody has to be
# / told about. Each pipeline folder gets a DEPENDENCY_APPROVALS.txt. Dependencies that
# / belong to the application rather than to any one pipeline get the record in Documentation.
# /
# / THE MANIFEST IS THE ONLY SOURCE OF FACTS.
# / Every factual field in a record is read from Resources/Contract/depends.php, so a
# / record cannot drift from the manifest. Re-run this after changing the manifest.
# /
# / THIS TOOL NEVER ANSWERS AN AUDIT QUESTION.
# / It writes the questions & leaves them unanswered. An answer is a judgement a person makes
# / & records here. A refresh keeps every answer that is already written.
# /
# / A record is a document like every other one in this project, so it opens with the same
# / file header & is laid out in the same sections, one per dependency.
# / An approval may carry notes or conditions, such as the configuration it was approved
# / under. That field may run over several lines. A line indented to the value column with no
# / label of its own continues the field above it, & a refresh keeps every line of it.
# /
# / Usage.
# /   python3 hrconvert2-approvals.py              Write any record that does not exist yet.
# /   python3 hrconvert2-approvals.py --refresh    Rewrite the facts & keep the answers.
# /   python3 hrconvert2-approvals.py --check      Report gaps & expiries. Exit 1 on a finding.
# /   python3 hrconvert2-approvals.py --readme     Rewrite every pipeline README.md.
# /
# / A README holds no judgement & nothing a person writes, so it is rewritten every time
# / rather than merged. An approval record is the opposite & is merged.
# / -----------------------------------------------------------------------------------

import os
import re
import sys
import datetime

TOOL_VERSION = 'v3.9.7'
# / The rule every document in Documentation is drawn with.
RULE = '-' * 160
UNANSWERED = 'NOT YET ANSWERED'
UNAUDITED = 'NOT YET AUDITED'
# / The date the approval records first shipped. It is fixed rather than today's date, so a
# / refresh that changes no fact changes no line of a record either.
COPYRIGHT_DATE = '10/30/2026'
# / Every label is padded to this width, so every value starts in the same column.
# / The longest label is question 1f, at forty four characters. The value column sat at
# / forty four until v4.0.0, so that one label ran straight into its value, & an answer
# / written beside it had no gap a refresh could read & was lost on the next refresh.
LABEL_WIDTH = 46
# / A continuation line of a field is indented to the value column.
VALUE_INDENT = ' ' * (4 + LABEL_WIDTH)

# / -----------------------------------------------------------------------------------
# / The audit questions, copied from ABOUT_DEPENDENCIES.txt so the record asks exactly what
# / the procedure asks. The code in front of each one is what the spreadsheet column is
# / called, so a cell & a line in this record are the same question.
# / -----------------------------------------------------------------------------------
QUESTIONS = (
    ('1a', 'Security design is amenable to ours'),
    ('1b', 'Recent CVEs, issues or exploits'),
    ('1c', 'Development activity & breakage risk'),
    ('1d', 'What it needs to function'),
    ('1e', 'Can it be sandboxed or isolated'),
    ('1f', 'Security options & hardened configuration'),
    ('1g', 'Could another dependency do this better'),
    ('1h', 'Origin & supply chain risk'),
    ('1i', 'Dependency traps for existing installs'),
    ('2a', 'System package versus a source build'),
    ('2b', 'Build flags, libraries & features'),
    ('2c', 'Minimum version & version edge cases'),
    ('2d', 'Licensing implications of the build'),
    ('3a', 'Distribution method'),
    ('3b', 'Download source used'),
    ('3c', 'How an installer obtains this version'),
    ('3d', 'Inspection of any bundled binary or code'),
    ('3e', 'Sanity check of the build system'),
    ('3f', 'Build script provided'))

RECORD_FIELDS = (
    ('Status', UNAUDITED),
    ('Approved on', UNAUDITED),
    ('Expires on', UNAUDITED),
    ('Audited by', UNAUDITED),
    ('Audit outcome', UNAUDITED),
    ('Notes or Conditions', UNAUDITED))

# / The labels a person writes answers against. Anything else in a record is a manifest fact
# / & is rewritten from the manifest, so it is never read back as an answer.
ANSWER_LABELS = set([label for label, default in RECORD_FIELDS] + [code + ' ' + question for code, question in QUESTIONS])
# / Longest first, so a label is never mistaken for a shorter one it begins with.
LABELS_LONGEST_FIRST = sorted(ANSWER_LABELS, key=len, reverse=True)
# / The formats a date may be written in. M/D/YYYY is how the changelog writes a date, so a
# / record may use it too. A date written with slashes is always read month first.
DATE_FORMATS = ('%Y-%m-%d', '%m/%d/%Y')
# / The fields an audited dependency must fill in, & the fields that hold a single line.
# / Audit outcome & Notes or Conditions may be left empty or run across several lines.
REQUIRED_FIELDS = ('Approved on', 'Expires on', 'Audited by')
SINGLE_LINE_FIELDS = ('Status', 'Approved on', 'Expires on', 'Audited by')
# / An expiry that begins with this is exempt from the calendar. An owned dependency uses it.
NO_EXPIRY = 'NOT APPLICABLE'

# / A subsystem that belongs to the application rather than to any single pipeline. These are
# / recorded once in Documentation rather than repeated in every pipeline folder.
APPLICATION_SUBSYSTEMS = ('Core', 'Updates', 'Resource Awareness', 'Sandbox')

# / Bubblewrap is the exception to the rule above. It is a Sandbox dependency, & every
# / pipeline that isolates a command genuinely cannot run without it, so it is recorded with
# / each of those pipelines as well as with the application.
SANDBOX_DEPENDENCY = 'Bubblewrap'


# / -----------------------------------------------------------------------------------
# / A function to read every dependency record out of the manifest.
# / Accepts the tree root. Returns a dictionary keyed by dependency name.
# / The manifest is PHP, & it is read as text rather than executed, for the same reason the
# / core reads a component version as text. A manifest broken enough to need reading is a
# / manifest nobody should execute to find out.
# / -----------------------------------------------------------------------------------
def read_manifest(root):
    path = os.path.join(root, 'Resources', 'Contract', 'depends.php')
    text = open(path, encoding='utf-8', errors='replace').read()
    starts = [match.start() for match in re.finditer(r"array\('Name'\s*=>", text)]
    entries = {}
    for index, start in enumerate(starts):
        finish = starts[index + 1] if index + 1 < len(starts) else len(text)
        block = text[start:finish]
        record = {}
        for field in ('Name', 'Binary', 'Type', 'Package', 'MinimumVersion', 'VersionCommand',
                      'Subsystem', 'License', 'Source', 'Purpose'):
            found = re.search(r"'" + field + r"'\s*=>\s*'((?:[^'\\]|\\.)*)'", block)
            record[field] = found.group(1).replace("\\'", "'") if found else ''
        required = re.search(r"'Required'\s*=>\s*(TRUE|FALSE)", block)
        record['Required'] = 'YES' if (required and required.group(1) == 'TRUE') else 'NO'
        requires = re.search(r"'Requires'\s*=>\s*array\(([^)]*)\)", block)
        record['Requires'] = ', '.join(re.findall(r"'([^']+)'", requires.group(1))) if requires else ''
        entries[record['Name']] = record
    return entries


# / -----------------------------------------------------------------------------------
# / A function to work out which dependencies a pipeline actually uses.
# / Accepts the pipeline folder & the manifest. Returns the dependency names, sorted.
# / Three sources are combined, because no single one is complete.
# / The code is the ground truth for a tool the pipeline asks for by name.
# / A pipeline that isolates a command depends on Bubblewrap whether it names it or not.
# / The subsystem catches a dependency the pipeline reaches through a shared module or an
# / interpreter rather than through verifiedToolPath, such as PyMeshLab in the Model pipeline.
# / -----------------------------------------------------------------------------------
def dependencies_for_pipeline(folder, manifest, subsystem, declared):
    found = set()
    # / A pipeline that names its own dependencies is believed before anything is inferred.
    for name in declared:
        if name in manifest:
            found.add(name)
    for walked, directories, names in os.walk(folder):
        for name in names:
            if not name.endswith('.php'):
                continue
            text = open(os.path.join(walked, name), encoding='utf-8', errors='replace').read()
            for called in re.findall(r"verifiedToolPath\('([^']+)'\)", text):
                if called in manifest:
                    found.add(called)
            if 'sandboxCommand(' in text and SANDBOX_DEPENDENCY in manifest:
                found.add(SANDBOX_DEPENDENCY)
    if subsystem and subsystem not in APPLICATION_SUBSYSTEMS:
        for name, record in manifest.items():
            if record['Subsystem'] == subsystem:
                found.add(name)
    return sorted(found)


# / -----------------------------------------------------------------------------------
# / A function to read what a pipeline declares about itself.
# / Accepts the path to a pipelineConfig.php. Returns the declarations as a dictionary.
# / The file is read as text rather than executed, for the same reason every other component
# / is. A configuration broken enough to need reading is one nobody should execute to find out.
# / -----------------------------------------------------------------------------------
def read_pipeline_config(path):
    text = open(path, encoding='utf-8', errors='replace').read()
    facts = {}
    for field in ('PipelineVersion', 'PipelineKind', 'PipelineFamily', 'PipelineDisplayName',
                  'PipelineEntryPoint', 'PipelineSubsystem'):
        found = re.search(r'\$' + field + r"\s*=\s*'([^']*)'", text)
        facts[field] = found.group(1) if found else ''
    for field in ('Input', 'Output'):
        block = re.search(r"'" + field + r"'\s*=>\s*array\((.*?)\)", text, re.S)
        facts[field] = re.findall(r"'([^']+)'", block.group(1)) if block else []
    usage = re.search(r"\$PipelineUsage\s*=\s*array\(([^)]*)\)", text)
    facts['Usage'] = re.findall(r"'([^']+)'", usage.group(1)) if usage else []
    return facts


# / -----------------------------------------------------------------------------------
# / A function to describe a pipeline in one sentence, from what it declares.
# / Accepts the declarations. Returns the sentence.
# / Nothing here is invented. A pipeline that declares formats is described by its formats,
# / & one that declares none is described by its kind, because that is all there is to say.
# / An operation is described by what makes it one, which is that it takes a selection.
# / -----------------------------------------------------------------------------------
def describe_pipeline(facts):
    kind = facts['PipelineKind']
    name = facts['PipelineDisplayName'] or facts['PipelineFamily']
    if kind == 'conversion' and facts['Input'] and facts['Output']:
        return ('The ' + name + ' pipeline converts files. It accepts ' + str(len(facts['Input']))
                + ' input format(s) & produces ' + str(len(facts['Output'])) + ' output format(s).')
    if kind == 'scanner':
        return 'The ' + name + ' pipeline examines a file rather than converting it. It reports what it finds & converts nothing.'
    if kind == 'file':
        return 'The ' + name + ' pipeline performs operations that do not depend on what is inside a file.'
    if kind == 'operation':
        return 'The ' + name + ' pipeline is an operation. It takes a selection of files at once & decides what to do with each one.'
    # / The article follows the kind's first letter, because a kind is any word a declaration gives.
    kind = kind or 'pipeline'
    return 'The ' + name + ' pipeline is ' + ('an ' if kind[0].lower() in 'aeiou' else 'a ') + kind + ' pipeline.'


# / -----------------------------------------------------------------------------------
# / A function to render a pipeline README.
# / Accepts the folder name, the declarations, the dependency names & the manifest.
# / Returns the file as text.
# / -----------------------------------------------------------------------------------
def render_readme(folder, facts, names, manifest):
    title = facts['PipelineDisplayName'] or folder
    lines = ['# The ' + title + ' pipeline',
             '',
             describe_pipeline(facts),
             '',
             'This file is written by `Documentation/Build/hrconvert2-approvals.py --readme`.',
             'It is rewritten from the pipeline\'s own declarations & from the dependency manifest,',
             'so it cannot drift from either. Do not edit it by hand.',
             '',
             '## What it declares',
             '']
    for label, value in (('Version', facts['PipelineVersion']), ('Kind', facts['PipelineKind']),
                         ('Family', facts['PipelineFamily']), ('Entry point', facts['PipelineEntryPoint']),
                         ('Subsystem', facts['PipelineSubsystem']),
                         ('Available to', ', '.join(facts['Usage']) or 'every surface')):
        lines.append('- **' + label + '** ' + (value or '-'))
    if facts['Input'] or facts['Output']:
        lines.append('')
        lines.append('## Formats')
        lines.append('')
        if facts['Input']:
            lines.append('**Accepts** ' + ', '.join(facts['Input']))
            lines.append('')
        if facts['Output']:
            lines.append('**Produces** ' + ', '.join(facts['Output']))
    lines.append('')
    lines.append('## Dependencies')
    lines.append('')
    if not names:
        lines.append('This pipeline uses no dependency outside the application itself.')
    else:
        for name in names:
            record = manifest[name]
            needed = 'required' if record['Required'] == 'YES' else 'optional'
            minimum = (', ' + record['MinimumVersion'] + ' or newer') if record['MinimumVersion'] else ''
            lines.append('- **' + name + '** (' + needed + minimum + ', ' + (record['License'] or 'license not stated') + '). '
                         + (record['Purpose'] or 'No purpose is recorded in the manifest.'))
    lines.append('')
    lines.append('## Approval')
    lines.append('')
    lines.append('`DEPENDENCY_APPROVALS.txt` beside this file is the official record for every')
    lines.append('dependency listed above. It holds the approval status, the approval & expiry dates,')
    lines.append('& the audit answers. That record is authoritative & this README is a summary of it.')
    lines.append('')
    lines.append('The procedure behind it is `Documentation/ABOUT_DEPENDENCIES.txt`.')
    lines.append('')
    return '\n'.join(lines)


# / -----------------------------------------------------------------------------------
# / A function to read the answers out of a record that already exists.
# / Accepts a path. Returns a dictionary of dependency name to field label to answer.
# / Only an answer that has been written is returned. A placeholder is not an answer & is
# / left out, so a refresh that finds nothing answered writes a fresh scaffold.
# / A manifest fact is not an answer either & is never returned. Until v4.0.0 every fact
# / line was read back as one, which did no harm only because nothing asked for it.
# / A line indented to the value column with no label continues the answer above it, so a
# / note written over several lines survives a refresh whole.
# / -----------------------------------------------------------------------------------
def read_existing_answers(path):
    answers = {}
    if not os.path.exists(path):
        return answers
    current = None
    continuing = None
    for line in open(path, encoding='utf-8', errors='replace').read().split('\n'):
        stripped = line.strip()
        # / A rule line is made only of dashes. It looks exactly like a heading & is not one.
        # / Reading it as a heading filed every answer under it & lost them on the next refresh.
        if stripped and set(stripped) == set('-'):
            continuing = None
            continue
        heading = re.match(r'^--(.+)--$', stripped)
        if heading:
            current = heading.group(1).strip()
            continuing = None
            continue
        if current is None:
            continue
        # / A continuation is told apart by its indentation alone, because the text of a
        # / note may contain anything, two spaces in a row included. It is indented deeper
        # / than any label, which is written four spaces in. A record written before v4.0.0
        # / put its values two columns further left, so the depth is not one exact column.
        indent = len(line) - len(line.lstrip(' '))
        if continuing is not None and stripped and indent >= 20:
            answers[current][continuing] += '\n' + stripped
            continue
        continuing = None
        if indent < 2 or not stripped:
            continue
        # / A label is found by its name rather than by the gap after it. A label that
        # / filled its column ran straight into its value, & an answer written there had no
        # / gap to read & was lost on every refresh. The case of a label is not judged.
        label = next((known for known in LABELS_LONGEST_FIRST if stripped.lower().startswith(known.lower())), None)
        if label is None:
            continue
        value = stripped[len(label):].strip()
        if value in ('', UNANSWERED, UNAUDITED):
            continue
        answers.setdefault(current, {})[label] = value
        continuing = label
    return answers


# / -----------------------------------------------------------------------------------
# / A function to render one labelled field, over as many lines as its value holds.
# / Accepts the label & the value. Returns the lines.
# / -----------------------------------------------------------------------------------
def render_field(label, value):
    parts = str(value).split('\n')
    lines = ['    ' + label.ljust(LABEL_WIDTH) + parts[0]]
    for part in parts[1:]:
        lines.append(VALUE_INDENT + part)
    return lines


# / -----------------------------------------------------------------------------------
# / A function to render one dependency as a section of the record.
# / Accepts the manifest record & any answers already written. Returns the lines.
# / -----------------------------------------------------------------------------------
def render_dependency(record, answers):
    lines = ['', RULE, '--' + record['Name'] + '--', '', '  Approval record']
    for label, default in RECORD_FIELDS:
        lines.extend(render_field(label, answers.get(label, default)))
    lines.append('')
    lines.append('  Manifest facts, read from Resources/Contract/depends.php')
    for label, key in (('Binary', 'Binary'), ('Install type', 'Type'), ('Package', 'Package'),
                       ('Minimum version', 'MinimumVersion'), ('Required', 'Required'),
                       ('Subsystem', 'Subsystem'), ('License', 'License'),
                       ('Depends on', 'Requires'), ('Source', 'Source'), ('Purpose', 'Purpose')):
        lines.extend(render_field(label, record.get(key) or '-'))
    lines.append('')
    lines.append('  Audit answers. Every one is a judgement a person records here.')
    for code, question in QUESTIONS:
        lines.extend(render_field(code + ' ' + question, answers.get(code + ' ' + question, UNANSWERED)))
    lines.append(RULE)
    return lines


# / -----------------------------------------------------------------------------------
# / A function to render the file header every document in this project carries.
# / Accepts what the record is for, such as the Archive pipeline. Returns the lines.
# / -----------------------------------------------------------------------------------
def render_header(subject):
    return [RULE,
            'COPYRIGHT INFORMATION ...',
            'HRConvert2, Copyright on ' + COPYRIGHT_DATE + ' by Justin Grimes, www.github.com/zelon88',
            '',
            'LICENSE INFORMATION ...',
            'This project is protected by the GNU GPLv3 Open-Source license.',
            'https://www.gnu.org/licenses/gpl-3.0.html',
            '',
            'APPLICATION INFORMATION ...',
            'This application is designed to provide a web-interface for converting file formats on a server for users of any web browser without authentication.',
            '',
            'FILE INFORMATION ...',
            TOOL_VERSION + '.',
            'This plain text file is the dependency approval record for ' + subject + '.',
            'It is written by Documentation/Build/hrconvert2-approvals.py. Every factual field is read from the dependency manifest & is rewritten by that tool. Every audit answer & every note is written by a person & is kept across a refresh.',
            'The procedure these questions come from is in Documentation/ABOUT_DEPENDENCIES.txt.',
            '',
            'HARDWARE REQUIREMENTS ...',
            'This application requires at least a Raspberry Pi Model B+ or greater.',
            'This application will run on just about any x86 or x64 computer.',
            '',
            'DEPENDENCY REQUIREMENTS ...',
            'The authoritative list of dependencies is Resources/Contract/depends.php & nothing else. Run  php convertCore.php -v  to see what this installation has.',
            '',
            '<3 Open-Source',
            RULE]


# / -----------------------------------------------------------------------------------
# / A function to render a whole record file.
# / Accepts the subject, the explanation, the dependency names, the manifest & the answers.
# / Returns the file as text.
# / The record opens with the standard header & a section saying what it covers, then holds
# / one section per dependency.
# / -----------------------------------------------------------------------------------
def render_file(subject, explanation, names, manifest, answers):
    lines = render_header(subject) + ['', RULE, '--About This Record--', '']
    # / A record with nothing in it says so instead of introducing a list that is empty.
    if names:
        lines.extend(explanation.split('\n'))
    else:
        lines.append('This pipeline uses no dependency outside the application itself.')
        lines.append('There is nothing here to audit. The dependencies the application needs to run at')
        lines.append('all are recorded in Documentation/DEPENDENCY_APPROVALS.txt.')
    lines.append(RULE)
    for name in names:
        lines.extend(render_dependency(manifest[name], answers.get(name, {})))
    return '\n'.join(lines) + '\n'


# / -----------------------------------------------------------------------------------
# / A function to work out every record this tree should hold.
# / Accepts the tree root. Returns a list of path, title, explanation & dependency names.
# / -----------------------------------------------------------------------------------
def plan_records(root):
    manifest = read_manifest(root)
    planned = []
    application = sorted(name for name, record in manifest.items()
                         if record['Subsystem'] in APPLICATION_SUBSYSTEMS)
    planned.append((os.path.join(root, 'Documentation', 'DEPENDENCY_APPROVALS.txt'),
                    'the application itself',
                    'These dependencies belong to HRConvert2 rather than to any one pipeline. Removing\n'
                    'one does not disable a conversion type. It stops the application running at all, or\n'
                    'it removes a capability every pipeline relies on, such as the sandbox.',
                    application, manifest))
    pipelines = os.path.join(root, 'Resources', 'Pipelines')
    for folder in sorted(os.listdir(pipelines)):
        full = os.path.join(pipelines, folder)
        config = os.path.join(full, 'pipelineConfig.php')
        if not os.path.isdir(full) or not os.path.exists(config):
            continue
        text = open(config, encoding='utf-8', errors='replace').read()
        subsystem = re.search(r"\$PipelineSubsystem\s*=\s*'([^']*)'", text)
        display = re.search(r"\$PipelineDisplayName\s*=\s*'([^']*)'", text)
        declaration = re.search(r"\$PipelineDependencies\s*=\s*array\(([^)]*)\)", text)
        declared = re.findall(r"'([^']+)'", declaration.group(1)) if declaration else []
        names = dependencies_for_pipeline(full, manifest, subsystem.group(1) if subsystem else '', declared)
        planned.append((os.path.join(full, 'DEPENDENCY_APPROVALS.txt'),
                        'the ' + (display.group(1) if display else folder) + ' pipeline',
                        'These are the dependencies this pipeline uses. The list is worked out from the\n'
                        'tools the pipeline asks for by name, from whether it isolates a command, & from the\n'
                        'subsystem it declares. A pipeline is detachable, so this record travels with it.',
                        names, manifest))
    return planned


# / -----------------------------------------------------------------------------------
# / A function to read a date written in any accepted format.
# / Accepts the date as written. Returns the date, or None when no accepted format reads it.
# / -----------------------------------------------------------------------------------
def read_date(written):
    for form in DATE_FORMATS:
        try:
            return datetime.datetime.strptime(written, form).date()
        except ValueError:
            continue
    return None


# / -----------------------------------------------------------------------------------
# / A function to check the approval fields of one audited dependency.
# / Accepts the record as shown, the dependency name, its answers & today's date.
# / Returns the number of findings. A single line field that runs onto a second line usually
# / means the label below it was deleted, so that value was read as a continuation.
# / -----------------------------------------------------------------------------------
def check_approval(shown, name, written, today):
    findings = 0
    for field in SINGLE_LINE_FIELDS:
        if '\n' in written.get(field, ''):
            print('  MALFORMED ' + shown + ' :: ' + name + ' writes ' + field + ' on more than one line. Check for a deleted label.')
            findings += 1
    for field in REQUIRED_FIELDS:
        if field not in written:
            print('  INCOMPLETE ' + shown + ' :: ' + name + ' has a status but no ' + field)
            findings += 1
    for field in ('Approved on', 'Expires on'):
        value = written.get(field, '')
        if not value or (field == 'Expires on' and value.upper().startswith(NO_EXPIRY)):
            continue
        when = read_date(value)
        if when is None:
            print('  UNREADABLE ' + shown + ' :: ' + name + ' ' + field.lower() + ' "' + value + '", which is neither YYYY-MM-DD nor M/D/YYYY')
            findings += 1
        elif field == 'Expires on' and when < today:
            print('  EXPIRED   ' + shown + ' :: ' + name + ' expired on ' + value)
            findings += 1
    # / An approval expires on 1 January of the year after it was given, so every dependency
    # / comes up for audit together. An expiry on any other day is outside that policy.
    approved = read_date(written.get('Approved on', ''))
    expires = read_date(written.get('Expires on', ''))
    if approved is not None and expires is not None and expires != datetime.date(approved.year + 1, 1, 1):
        print('  OFFPOLICY ' + shown + ' :: ' + name + ' expires on ' + written['Expires on'] + '. An approval given in '
              + str(approved.year) + ' expires on 01/01/' + str(approved.year + 1) + '.')
        findings += 1
    return findings


# / -----------------------------------------------------------------------------------
# / A function to report what is missing or out of date without writing anything.
# / Accepts the planned records. Returns the number of findings.
# / An expiry that has passed is a finding. So is a date that cannot be read, since a date
# / nobody can parse cannot be checked against the calendar, & so is a required field left empty.
# / -----------------------------------------------------------------------------------
def check_records(planned):
    findings = 0
    today = datetime.date.today()
    for path, title, explanation, names, manifest in planned:
        shown = os.path.relpath(path)
        if not os.path.exists(path):
            print('  MISSING   ' + shown + ' has never been written')
            findings += 1
            continue
        answers = read_existing_answers(path)
        for name in names:
            written = answers.get(name, {})
            if 'Status' not in written:
                print('  UNAUDITED ' + shown + ' :: ' + name + ' has no declared status')
                findings += 1
                continue
            findings += check_approval(shown, name, written, today)
    return findings


def main():
    root = os.path.abspath(os.path.join(os.path.dirname(__file__), '..', '..'))
    planned = plan_records(root)
    if '--check' in sys.argv:
        print('Checking ' + str(len(planned)) + ' dependency approval record(s) under ' + root + '.')
        print()
        findings = check_records(planned)
        print()
        print(('No finding. Every record exists & nothing has expired.' if findings == 0
               else str(findings) + ' finding(s). A finding is work to do, not a broken tree.'))
        return 1 if findings else 0
    if '--readme' in sys.argv:
        manifest = read_manifest(root)
        pipelines = os.path.join(root, 'Resources', 'Pipelines')
        written = 0
        for folder in sorted(os.listdir(pipelines)):
            full = os.path.join(pipelines, folder)
            config = os.path.join(full, 'pipelineConfig.php')
            if not os.path.isdir(full) or not os.path.exists(config):
                continue
            facts = read_pipeline_config(config)
            declaration = re.search(r"\$PipelineDependencies\s*=\s*array\(([^)]*)\)",
                                    open(config, encoding='utf-8', errors='replace').read())
            declared = re.findall(r"'([^']+)'", declaration.group(1)) if declaration else []
            names = dependencies_for_pipeline(full, manifest, facts['PipelineSubsystem'], declared)
            open(os.path.join(full, 'README.md'), 'w', encoding='utf-8', newline='').write(
                render_readme(folder, facts, names, manifest))
            written += 1
        print('Wrote ' + str(written) + ' pipeline README(s).')
        return 0
    refresh = '--refresh' in sys.argv
    written = 0
    kept = 0
    for path, title, explanation, names, manifest in planned:
        if os.path.exists(path) and not refresh:
            kept += 1
            continue
        answers = read_existing_answers(path) if refresh else {}
        open(path, 'w', encoding='utf-8', newline='').write(render_file(title, explanation, names, manifest, answers))
        written += 1
    print('Wrote ' + str(written) + ' record(s). Left ' + str(kept) + ' existing record(s) alone.')
    if kept and not refresh:
        print('Pass --refresh to rewrite the facts in an existing record. Answers are kept.')
    return 0


if __name__ == '__main__':
    sys.exit(main())
