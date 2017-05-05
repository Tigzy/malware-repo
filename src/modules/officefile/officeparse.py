#!/usr/bin/env python
import os, sys, json, string
from oletools.olevba import VBA_Parser, TYPE_OLE, TYPE_OpenXML, TYPE_Word2003_XML, TYPE_MHTML #pip install -U oletools

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
    print """officeparse: parse office output for a file, extract metadata
    
usage: officeparse <pathspec> [-?]
    
where:
    <pathspec>    file to scan.
    -?            Show this usage screen"""

def convert_char(char):
    if char in string.ascii_letters or char in string.digits or char in string.punctuation or char in string.whitespace:
        return char            
    else:
        return '?'
  
def convert_to_printable_null_terminated(s):
    str_list = []
    if (s is None):
        return ''.join(str_list)
    for c in s:
        if (c=='\0'):   #null byte is here to mark the end of the string
            str_list.append(c)
            break;
        else:
            str_list.append(convert_char(c))
    
    return ''.join(str_list)

def ProcessFile(path):
    if not(os.path.isfile(path)):
        print '{0} not a file!'.format(path)
        return 2

    try:
        data = {}
        data['valid'] = True  
        oledata = {}
        
        vbaparser = VBA_Parser(path)
        oledata['has_macros'] = vbaparser.detect_vba_macros()
        
        # dump macros content
        macros = []
        for (filename, stream_path, vba_filename, vba_code) in vbaparser.extract_macros():
            macro = {}
            macro['filename']   = filename
            macro['stream']     = stream_path
            macro['vba']        = vba_filename
            macro['content']    = convert_to_printable_null_terminated(vba_code)
            macros.append(macro)
        oledata['macros'] = macros
            
        # macro analysis
        macros_warnings = []
        results = vbaparser.analyze_macros()
        for kw_type, keyword, description in results:
            warning = {}
            warning['type']         = kw_type
            warning['keyword']      = keyword
            warning['description']  = description
            macros_warnings.append(warning)
        oledata['macros_warnings'] = macros_warnings
            
        # counters
        counters = {}
        counters['autoexec']           = vbaparser.nb_autoexec
        counters['suspicious']         = vbaparser.nb_suspicious
        counters['iocs']               = vbaparser.nb_iocs
        counters['hexstrings']         = vbaparser.nb_hexstrings
        counters['base64strings']      = vbaparser.nb_base64strings
        counters['dridexstrings']      = vbaparser.nb_dridexstrings
        counters['vbastrings']         = vbaparser.nb_vbastrings
        oledata['counters']            = counters    
        
        # deobfuscation    
        oledata['deobfuscated'] = convert_to_printable_null_terminated(vbaparser.reveal())
        
        # close
        vbaparser.close()
        
        data['data'] = oledata
        encoded = json.dumps(data)
        print encoded
    except Exception as ex:
        data = {}
        data['valid'] = False
        data['error'] = str(ex)
        print json.dumps(data)
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

