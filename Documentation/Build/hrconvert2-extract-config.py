# / -----------------------------------------------------------------------------------
# / Reads every setting block out of config.php & returns name, header, prose & value.
# / Proven against all 149 blocks. It is kept because increment 2 runs it & increment 3
# / needs the same shape to generate the file back.
# /
# / A block is a run of comment lines ending at the assignment. The FIRST header shaped
# / line in that run is the header. Taking the first rather than the nearest matters,
# / because prose legitimately contains --Other Setting-- cross references & a Docker flag
# / that begins with two dashes, & the nearest match picks those instead.
# / -----------------------------------------------------------------------------------
import re

def is_comment(s): return s.startswith('// /')
def is_separator(s): return bool(re.match(r'^// /\s*-{4,}\s*$', s))
def is_header(s): return bool(re.match(r'^// /  --[A-Za-z].*--\.?\s*$', s))

def extract(path):
    lines = open(path, encoding='utf-8').read().split('\n')
    blocks = []
    for i, line in enumerate(lines):
        m = re.match(r'^\$(\w+)\s*=', line)
        if not m:
            continue
        j = i - 1
        while j >= 0 and is_comment(lines[j]) and not is_separator(lines[j]):
            j -= 1
        header = next((k for k in range(j + 1, i) if is_header(lines[k])), None)
        if header is None:
            raise SystemExit('no header for $' + m.group(1) + ' at line ' + str(i + 1))
        prose = [lines[k][5:].rstrip() for k in range(header + 1, i)]
        blocks.append({'Name': m.group(1),
                       'Header': lines[header].strip()[5:].strip('-').strip(),
                       'Prose': prose,
                       'Value': line.split('=', 1)[1].strip().rstrip(';'),
                       'HeaderLine': header, 'ValueLine': i})
    return blocks

if __name__ == '__main__':
    b = extract('Resources/config.php')
    print('  ' + str(len(b)) + ' blocks, ' + str(sum(len(x['Prose']) for x in b)) + ' prose lines')
