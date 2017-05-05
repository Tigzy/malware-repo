#!/usr/bin/env python
import os, sys
from subprocess import call
from subprocess import Popen, PIPE

# use doc: https://python-ssdeep.readthedocs.io/en/latest/installation.html#id9
# https://pypi.python.org/pypi/ssdeep
ssdeep_python = True

# condition import of ssdeep
try:
    import ssdeep
except ImportError:
    ssdeep_python = False

def parse_args():
    global pathspec
    if (len(sys.argv) == 1) | (len(sys.argv) > 7):
        usage()
        quit(1)
    
    ignore_next = False
    for arg in sys.argv[1:]:
        if ignore_next:
            ignore_next = False
            continue
        
        if arg == '-?' or arg == '/?':
            usage()
            exit(1)
            
        # some unknown input
        elif arg[0] == '-':
            print '################################\n'
            print '{0} is not a valid argument!\n'.format(arg)
            print '################################\n'
            usage()
            exit(1) 
        else:
            if pathspec == None:
                pathspec = os.path.normpath(arg)

def usage():
    print """ssdeepparse: parse ssdeep output for a file, extract metadata
    
usage: ssdeepparse <filespec> [-?]
    
where:
    <pathspec>    file to scan.
    -?            Show this usage screen"""

def ParseOutput(output):
   try:
       splitted = output.splitlines()
       splitted = filter(None, splitted)
       if (len(splitted) == 2):           
           hash = splitted[1]
           hash_splitted = hash.split(',')
           if (len(hash_splitted) == 2):           
               return hash_splitted[0]
   except Exception:
       pass
   return ""

def ProcessFile(path):
    if not(os.path.isfile(path)):
        print '{0} not a file!'.format(path)
        return 2

    output = ""
    try:
        if ssdeep_python:
            hash = ssdeep.hash_from_file(path)
            print hash
        else:
            p = Popen(["ssdeep", "-b", path], stdout=PIPE, stderr=PIPE)
            output, err = p.communicate()
            rc = p.returncode
            print ParseOutput(output)
    except Exception as ex:
        return 1
        
    return 0

#--------------------------------------------------------------------------------------------------
#--------------------------------------------------------------------------------------------------
#--------------------------------------------------------------------------------------------------
cwd = os.path.dirname(os.path.realpath(__file__))
pathspec = None

parse_args()

# validate path input
if (pathspec == None):
    print('A path specification is required')
    exit(2)

# convert relative path to absolute path
if len(os.path.splitdrive(pathspec)[0]) == 0:
    pathspec = os.path.normpath(os.path.join(cwd, pathspec))

if os.path.isdir(pathspec):
    print 'please specify a file arg'
    exit(2)
else:
    exit(ProcessFile(pathspec))

