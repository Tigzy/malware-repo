#!/usr/bin/env python
import os, sys, json, string, argparse
from peepdf.PDFCore import PDFParser #pip install peepdf

filepath    = None
stream_id   = None

def parse_args():
    global filepath
    global stream_id
    
    argsParser = argparse.ArgumentParser(usage='Parse PDF information')
    argsParser.add_argument('-f', '--file', action='store', dest='filepath', default='', help='The PDF file that will be used', required=True)
    argsParser.add_argument('-d', '--dump', metavar='stream_id', type=int, nargs='+', help='Dump stream by ID')
    args = argsParser.parse_args()
    
    filepath = os.path.normpath(args.filepath)
    if args.dump is not None and len(args.dump) > 0:        
        stream_id = args.dump[0]

def usage():
    print """pdfparse: parse pdf output for a file, extract metadata
    
usage: pdfparse <pathspec> [-?]
    
where:
    <pathspec>    file to scan.
    -?            Show this usage screen"""

def convert_char(char):
    if char in string.ascii_letters or char in string.digits or char in string.punctuation or char in string.whitespace or char == '\r' or char == '\n':
        return char            
    else:
        return '.'
  
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

def convert_to_printable_string(s):
    str_list = []
    if s is not None:
        for c in s:
            str_list.append(convert_char(c))
    
    return ''.join(str_list)

def convert_to_printable(obj):
    if isinstance(obj, str):
        return convert_to_printable_string(obj)
    
    if isinstance(obj, int):
        return str(obj)
    
    if isinstance(obj, float):
        return str(obj)
        
    if isinstance(obj, list):
        new_obj = []
        for _, value in enumerate(obj):
            new_obj.append(convert_to_printable(value))
        return new_obj
        
    if isinstance(obj, dict):
        new_obj = {}
        for key, value in obj.iteritems():
            new_obj[key] = convert_to_printable(value)
        return new_obj
    
    return "<error: not parsed>"

def DumpStream(path, objid):
    if not(os.path.isfile(path)):
        print '{0} not a file!'.format(path)
        return 1
    
    try:   
        pdfParser   = PDFParser()
        _,pdf       = pdfParser.parse(path, True)
             
        if not pdf:
            return 2
        else: 
            # get object
            obj = pdf.getObject(objid, None)
            if not obj:
                print '{0} stream not found!'.format(objid)
                return 1
            
            if obj.getType() != 'stream':
                print '{0} is not a stream!'.format(objid)
                return 1
                
            value = obj.getStream()
            if value != -1:
                print value
                        
    except Exception as ex:
        print str(ex)
        return 1
        
    return 0

def ProcessFile(path):
    if not(os.path.isfile(path)):
        print '{0} not a file!'.format(path)
        return 2

    try:
        data = {}
        data['valid'] = True  
        pdfdata     = {}        
        pdfParser   = PDFParser()
        _,pdf       = pdfParser.parse(path, True)
             
        if not pdf:
            data['valid'] = False
        else:      
            errors  = []
            streams = []  
            
            # general info
            statsDict       = pdf.getStats()
            try:
                data['info'] = json.dumps(statsDict, indent=4, sort_keys=False)
            except Exception as e:
                data['info'] = e
            
            # enumerate errors
            if hasattr(pdf, 'errors'):
                errors.extend(pdf.errors)
            
            # enumerate streams
            statsDict = pdf.getStats()
            for versionId, statsVersion in enumerate(statsDict['Versions']):
                for objid in statsVersion['Objects'][1]:            
                    obj = pdf.getObject(objid, versionId)
                    if not obj:
                        continue
                    
                    stream = {}
                    stream['id']            = objid
                    stream['type']          = obj.getType()
                    stream['attributes']    = {}
                    stream['has_js']        = obj.containsJScode
                                        
                    if hasattr(obj, 'elements'):
                        for key in obj.elements:
                            element = obj.elements[key]
                            stream['attributes'][key] = convert_to_printable(element.value)
                                        
                    if obj.getType() == 'stream':
                        stream['data_len'] = obj.size
                        value = obj.getStream()
                        if value != -1:
                            stream['data'] = convert_to_printable(value)
                            
                    streams.append(stream)
                    
            pdfdata['streams']  = streams
            pdfdata['errors']   = errors          
                        
        data['data'] = pdfdata
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

parse_args()

# validate path input
if (filepath == None):
    print('A path specification is required')
    exit(2)

# convert relative path to absolute path
if len(os.path.splitdrive(filepath)[0]) == 0:
    pathspec = os.path.normpath(os.path.join(cwd, filepath))

if os.path.isdir(filepath):
    print 'please specify a file arg'
    exit(2)
else:
    if stream_id is not None:
        DumpStream(filepath, stream_id)
    else:
        code = ProcessFile(filepath)
        exit(code)

