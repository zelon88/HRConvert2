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
# / Every factual field in a record is read from Resources/Engine/Contract/depends.php, so a
# / record cannot drift from the manifest. Re-run this after changing the manifest.
# /
# / THIS TOOL NEVER ANSWERS AN AUDIT QUESTION.
# / It writes the questions & leaves them unanswered. An answer is a judgement a person makes
# / & records here. A refresh keeps every answer that is already written.
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
RULE = '-' * 114
UNANSWERED = 'NOT YET ANSWERED'
UNAUDITED = 'NOT YET AUDITED'

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
    ('Audit outcome', UNAUDITED))

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
    path = os.path.join(root, 'Resources', 'Engine', 'Contract', 'depends.php')
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
    return 'The ' + name + ' pipeline is a ' + (kind or 'pipeline') + ' pipeline.'


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
# / -----------------------------------------------------------------------------------
def read_existing_answers(path):
    answers = {}
    if not os.path.exists(path):
        return answers
    current = None
    for line in open(path, encoding='utf-8', errors='replace').read().split('\n'):
        stripped = line.strip()
        # / A rule line is made only of dashes. It looks exactly like a heading & is not one.
        # / Reading it as a heading filed every answer under it & lost them on the next refresh.
        if stripped and set(stripped) == set('-'):
            continue
        heading = re.match(r'^--(.+)--$', stripped)
        if heading:
            current = heading.group(1).strip()
            continue
        if current is None:
            continue
        written = re.match(r'^\s{2,}(?:(\d[a-f])\s+)?(\S.*?)\s{2,}(\S.*)$', line)
        if not written:
            continue
        label = (written.group(1) + ' ' + written.group(2)).strip() if written.group(1) else written.group(2).strip()
        value = written.group(3).strip()
        if value in (UNANSWERED, UNAUDITED):
            continue
        answers.setdefault(current, {})[label] = value
    return answers


# / -----------------------------------------------------------------------------------
# / A function to render one dependency as a block of the record.
# / Accepts the manifest record & any answers already written. Returns the lines.
# / -----------------------------------------------------------------------------------
def render_dependency(record, answers):
    lines = []
    lines.append('--' + record['Name'] + '--')
    lines.append('')
    lines.append('  Approval record')
    for label, default in RECORD_FIELDS:
        lines.append('    ' + label.ljust(44) + answers.get(label, default))
    lines.append('')
    lines.append('  Manifest facts, read from Resources/Engine/Contract/depends.php')
    for label, key in (('Binary', 'Binary'), ('Install type', 'Type'), ('Package', 'Package'),
                       ('Minimum version', 'MinimumVersion'), ('Required', 'Required'),
                       ('Subsystem', 'Subsystem'), ('License', 'License'),
                       ('Depends on', 'Requires'), ('Source', 'Source')):
        lines.append('    ' + label.ljust(44) + (record.get(key) or '-'))
    lines.append('    ' + 'Purpose'.ljust(44) + (record.get('Purpose') or '-'))
    lines.append('')
    lines.append('  Audit answers. Every one is a judgement a person records here.')
    for code, question in QUESTIONS:
        lines.append('    ' + code + ' ' + question.ljust(41) + answers.get(code + ' ' + question, UNANSWERED))
    lines.append('')
    lines.append(RULE)
    lines.append('')
    return lines


# / -----------------------------------------------------------------------------------
# / A function to render a whole record file.
# / Accepts the title, the explanation, the dependency names, the manifest & the answers.
# / Returns the file as text.
# / -----------------------------------------------------------------------------------
def render_file(title, explanation, names, manifest, answers):
    lines = [RULE,
             'DEPENDENCY APPROVAL RECORD',
             title,
             '',
             'Written by Documentation/Build/hrconvert2-approvals.py ' + TOOL_VERSION + '.',
             'Every factual field is read from the dependency manifest & is rewritten by that tool.',
             'Every audit answer is written by a person & is kept across a refresh.',
             'The procedure these questions come from is in Documentation/ABOUT_DEPENDENCIES.txt.',
             '',
             explanation,
             RULE,
             '']
    if not names:
        lines.append('This pipeline uses no dependency outside the application itself.')
        lines.append('There is nothing here to audit. The dependencies the application needs to run at')
        lines.append('all are recorded in Documentation/DEPENDENCY_APPROVALS.txt.')
        lines.append('')
        lines.append(RULE)
        lines.append('')
        return '\n'.join(lines)
    for name in names:
        lines.extend(render_dependency(manifest[name], answers.get(name, {})))
    return '\n'.join(lines)


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
                    'The application itself.',
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
                        'The ' + (display.group(1) if display else folder) + ' pipeline.',
                        'These are the dependencies this pipeline uses. The list is worked out from the\n'
                        'tools the pipeline asks for by name, from whether it isolates a command, & from the\n'
                        'subsystem it declares. A pipeline is detachable, so this record travels with it.',
                        names, manifest))
    return planned


# / -----------------------------------------------------------------------------------
# / A function to report what is missing or out of date without writing anything.
# / Accepts the planned records. Returns the number of findings.
# / An expiry that has passed is a finding. So is an approval date that cannot be read, since
# / a date nobody can parse cannot be checked against the calendar.
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
            expires = written.get('Expires on')
            if not expires:
                continue
            try:
                when = datetime.datetime.strptime(expires, '%Y-%m-%d').date()
            except ValueError:
                print('  UNREADABLE ' + shown + ' :: ' + name + ' expires on "' + expires + '", which is not YYYY-MM-DD')
                findings += 1
                continue
            if when < today:
                print('  EXPIRED   ' + shown + ' :: ' + name + ' expired on ' + expires)
                findings += 1
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
