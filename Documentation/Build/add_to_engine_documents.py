#!/usr/bin/env python3
# / -----------------------------------------------------------------------------------
# / Copyright Information ...
# / HRProprietary Engine, Copyright on 11/20/2026 by Justin Grimes, www.github.com/zelon88
# /
# / License Information ...
# / This project is protected by the GNU GPLv3 Open-Source license.
# / https://www.gnu.org/licenses/gpl-3.0.html
# /
# / File Information ...
# / v4.3.2.
# / It was HRProtect's, from its v0.0.7, & is the Engine's from v4.3.2.
# / Adds an application's own lines to the documents the Engine's starter kit is distributed with.
# / Usage: python3 -I Documentation/Build/add_to_engine_documents.py <kit root> <application tree> <additions file>
# / An application adds to a document of the Engine's & takes nothing from it. Each document the additions file names is
# / written from the kit's own copy with the additions put in. Running it twice writes the same files, & a later kit's
# / documents are brought up to date by running it against that kit.
# / It writes nothing where a place to add is not found exactly once, or where a line of the kit's would be missing.
# /
# / The additions file is plain text. A line beginning #### is this script's, & every other line is added as it is written.
# /   #### FILE Documentation/NAME        The document the additions below it belong to.
# /   #### AFTER the whole of one line    Adds below that line, which the kit's document must hold exactly once.
# /   #### IN --Section Title--           Adds at the end of that section, above the rule that closes it, below one blank line.
# /   #### BELOW --Section Title--        Adds below the rule that closes that section, below one blank line. For whole sections.
# /   #### AT END                         Adds at the end of the document, below one blank line. For whole sections.
# /   #### RULE                           Stands for the rule of 160 dashes a section opens & closes with.
# /   #### END                            Ends one addition.
# / A document is named from the root of the tree, as Documentation/NAME. A name that is absolute or climbs out with .. is refused
# / before anything is read, so an additions file can write nowhere but into the tree it names.
# / -----------------------------------------------------------------------------------
import os
import sys

RULE = '-' * 160


def is_subsequence(kept, whole):
    position = 0
    for line in whole:
        if position < len(kept) and line == kept[position]:
            position += 1
    return position == len(kept)


def read_additions(path):
    additions, document, place, body = [], None, None, None
    for number, line in enumerate(open(path, encoding='utf-8', newline='').read().split('\n'), 1):
        if not line.startswith('####'):
            if body is not None:
                body.append(line)
            elif line.strip():
                sys.exit('%s:%d is outside an addition.' % (path, number))
            continue
        word, _, rest = line[4:].strip().partition(' ')
        if word == 'FILE':
            document = rest.strip()
            if not document or os.path.isabs(document) or '..' in document.replace('\\', '/').split('/'):
                sys.exit('%s:%d names %s, which is not a document inside the tree.' % (path, number, document or 'nothing'))
        elif word in ('AFTER', 'IN', 'BELOW', 'AT') and body is None and document and (word != 'AT' or rest.strip() == 'END'):
            place, body = (word, rest), []
        elif word == 'RULE' and body is not None:
            body.append(RULE)
        elif word == 'END' and body is not None:
            additions.append((document, place[0], place[1], body))
            place, body = None, None
        else:
            sys.exit('%s:%d is not understood where it stands.' % (path, number))
    if body is not None:
        sys.exit('%s ends inside an addition.' % path)
    return additions


def section_end(lines, title, document):
    found = [index for index, line in enumerate(lines) if line == title]
    if len(found) != 1:
        sys.exit('%s holds the section %s %d times, & it must hold it once.' % (document, title, len(found)))
    return next(index for index in range(found[0] + 1, len(lines)) if lines[index] == RULE)


def build(kit_root, additions):
    # / Every place is found in the kit's own lines before anything is added, so one addition cannot move another's place.
    written, kit_lines, places = {}, {}, {}
    for document, word, rest, body in additions:
        if document not in kit_lines:
            # / The document is read byte for byte, so its last line ends in the tree as it ends in the kit.
            kit_lines[document] = open(os.path.join(kit_root, document), encoding='utf-8', newline='').read().split('\n')
            places[document] = []
        lines = kit_lines[document]
        if word == 'AFTER':
            found = [index for index, line in enumerate(lines) if line == rest]
            if len(found) != 1:
                sys.exit('%s holds the line "%s" %d times, & it must hold it once.' % (document, rest[:60], len(found)))
            places[document].append((found[0] + 1, body))
        elif word == 'IN':
            places[document].append((section_end(lines, rest, document), [''] + body))
        elif word == 'BELOW':
            places[document].append((section_end(lines, rest, document) + 1, [''] + body))
        else:
            # / A document that ends in a line break holds one empty line after its last, & the addition goes above that.
            places[document].append((len(lines) - (1 if lines[-1] == '' else 0), [''] + body))
    for document, lines in kit_lines.items():
        result = list(lines)
        # / Additions at one place keep the order they were written in, & the lowest place is filled last.
        for position, body in sorted(places[document], key=lambda item: item[0], reverse=True):
            same = [item for item in places[document] if item[0] == position]
            if same and same[0][1] is body:
                merged = [line for item in same for line in item[1]]
                result[position:position] = merged
        if not is_subsequence(lines, result):
            sys.exit('A line of the kit\'s %s would be missing, so nothing was written.' % document)
        written[document] = '\n'.join(result)
    return written


def main():
    if len(sys.argv) != 4:
        sys.exit('Usage: python3 -I Documentation/Build/add_to_engine_documents.py <kit root> <application tree> <additions file>')
    kit_root, tree, additions_file = sys.argv[1:4]
    written = build(kit_root, read_additions(additions_file))
    for document, text in sorted(written.items()):
        open(os.path.join(tree, document), 'w', encoding='utf-8', newline='').write(text)
        kit_count = len(open(os.path.join(kit_root, document), encoding='utf-8', newline='').read().rstrip('\n').split('\n'))
        print('%-48s %5d lines of the kit kept, %4d added.' % (document, kit_count, len(text.rstrip('\n').split('\n')) - kit_count))


if __name__ == '__main__':
    main()
