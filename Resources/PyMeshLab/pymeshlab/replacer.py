import re
from pathlib import Path
from shutil import copyfile
import os
from os import listdir
from os.path import isfile, join, isdir


def replace_all(text, dic):
    for i, j in dic.items():
        text = text.replace(i, j)
    return text


# replaces all the old filter names with the new ones, using the file
# keys.txt that contains the pairs {oldname, newname}
def replace_pymeshlab_filter_names(input_script):
    keys = {}
    this_dir = os.path.dirname(os.path.realpath(__file__))
    with open(this_dir + '/keys.txt') as f:
        for line in f:
            (key, val) = line.split()
            # make sure that the keys are methods applied on an object
            # (starting with '.' and ending with '(') or a parameter of a
            # function (the apply_filter function)
            keys['.' + key + '('] = '.' + val + '('
            keys['"' + key + '"'] = '"' + val + '"'
            keys["'" + key + "'"] = "'" + val + "'"

    file_list = []
    if isfile(input_script):
        file_list.append(input_script)
    elif isdir(input_script):
        file_list = [join(input_script, f) for f in listdir(input_script) if isfile(join(input_script, f)) and
                     (f.endswith('.py') or f.endswith('.ipynb'))]

    for f in file_list:
        txt = Path(f).read_text()
        txt = replace_all(txt, keys)

        with open(f, "w") as myfile:
            myfile.write(txt)

        print(f + ' filter names have been replaced and saved.')
