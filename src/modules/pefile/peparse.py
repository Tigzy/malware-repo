#!/usr/bin/env python
import os, sys, pefile, json, base64, struct, binascii, time, unicodedata, re
from pefile import DataContainer

class DigitalSignatureData(DataContainer):
    """Holds digital signature information.

    signed:     true if the file is signed
    verified:   true if the digital signature is verified (hash matches/date valid)
    signature:  information about the algorithm
    certificates: list of certificates
    """

def parse_args():
    global pretty_print, pathspec, text_mode
    if (len(sys.argv) == 1) | (len(sys.argv) > 7):
        usage()
        quit(1)
    
    ignore_next = False
    for arg in sys.argv[1:]:
        if ignore_next:
            ignore_next = False
            continue
        
        if arg == '-pretty':
            pretty_print = True
        elif arg == '-text':
            text_mode = True
        elif arg == '-?' or arg == '/?':
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
    print """peparse: parse PE file, extract metadata
    
usage: peparse <filespec> [-pretty] [-?]
    
where:
    <pathspec>    file to scan.
    -pretty       Format JSON output for humans.
    -text         Write output in plain text format
    -?            Show this usage screen"""
  
def ExtractPdb(pe):
    if not hasattr(pe, 'DIRECTORY_ENTRY_DEBUG'):
        return ""
    for debug in pe.DIRECTORY_ENTRY_DEBUG:               
        if debug.entry is not None and debug.entry.PdbFileName is not None:
            sanitized = debug.entry.PdbFileName.rstrip(' \t\r\n\0')
            return sanitized
    return ""
    
def ExtractStringsRaw(path):
  strings = list()
  
  chars = r"A-Za-z0-9/\-:.,_$%'()[\]<> "
  shortest_run = 8

  regexp = '[%s]{%d,}' % (chars, shortest_run)
  pattern = re.compile(regexp)
  
  with open(path, mode='rb') as file: # b is important -> binary
    fileContent = file.read()
    strings = pattern.findall(fileContent)
    
  return '\n'.join(strings)
    
def ExtractStrings(pe):
  strings = list()
  try:
    rt_string_idx = [entry.id for entry in pe.DIRECTORY_ENTRY_RESOURCE.entries].index(pefile.RESOURCE_TYPE['RT_STRING'])
  except:
    return strings

  rt_string_directory = pe.DIRECTORY_ENTRY_RESOURCE.entries[rt_string_idx]
  for entry in rt_string_directory.directory.entries:
    data_rva = entry.directory.entries[0].data.struct.OffsetToData
    size = entry.directory.entries[0].data.struct.Size
    #print 'Directory entry at RVA', hex(data_rva), 'of size', hex(size)

    data = pe.get_memory_mapped_image()[data_rva:data_rva+size]
    offset = 0
    while True:
      if offset>=size:
        break
      ustr_length = pe.get_word_from_data(data[offset:offset+2], 0)
      offset += 2

      if ustr_length==0:
        continue

      if ustr_length is None:
        break

      ustr = pe.get_string_u_at_rva(data_rva+offset, max_length=ustr_length)
      offset += ustr_length*2
      if ustr:
        strings.append(ustr)
      #print 'String of length', ustr_length, 'at offset', offset, ' : ', ustr
  return strings

def dump_icon_blob(pe, icon_id, icon_img_rva, icon_img_size):
    """Extract and dump an icon blob (in base64 encoding format) to a dictionary.
       The icon blob would contain the binary data corresponding to a .ico file."""

    dump_dict = dict()

    if icon_id in pe.icons.keys():
        group_icon_dir_entry = pe.icons[icon_id]
        if group_icon_dir_entry is not None:
            hex_str = ''
            blob_size = 0
            
            """ The icon image file starts with an icon directory structure, followed by a 
                list of icon entries which are followed by a list of image data.

                typedef struct
                {
                    WORD           idReserved;   // Reserved (must be 0)
                    WORD           idType;       // Resource Type (1 for icons)
                    WORD           idCount;      // How many images?
                    ICONDIRENTRY   idEntries[1]; // An entry for each image (idCount of 'em)
                } ICONDIR, *LPICONDIR;

                typedef struct
                {
                    BYTE        bWidth;          // Width, in pixels, of the image
                    BYTE        bHeight;         // Height, in pixels, of the image
                    BYTE        bColorCount;     // Number of colors in image (0 if >=8bpp)
                    BYTE        bReserved;       // Reserved ( must be 0)
                    WORD        wPlanes;         // Color Planes

                    WORD        wBitCount;       // Bits per pixel
                    DWORD       dwBytesInRes;    // How many bytes in this resource?
                    DWORD       dwImageOffset;   // Where in the file is this image?
                } ICONDIRENTRY, *LPICONDIRENTRY;
            """

            """ dump the icon directory """
            """ The size of each field in the structure is important. For example, we want to 
                dump 1 byte for a 'BYTE', 2 bytes for a 'WORD' and 4 bytes for a 'DWORD'.
                To-Do: Instead of printing each field individually, print the whole structure
            """
            
            byte_array = bytearray()
            blob_str = ''

            # icon_dir.wReserved = 0
            bytes = struct.unpack('2B',struct.pack('H',0)) # convert value to 2 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
        
            # icon_dir.wType = 1
            bytes = struct.unpack('2B',struct.pack('H',1)) # convert value to 2 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
  
            # icon_dir.wCount = 1
            bytes = struct.unpack('2B',struct.pack('H',1)) # convert value to 2 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    

            
            """ dump the icon entry """
            # icon_entry.bWidth = group_icon_dir_entry.bWidth
            bytes = struct.unpack('1B',struct.pack('B',group_icon_dir_entry.bWidth)) # convert value to 1 byte
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
           
            # icon_entry.bHeight = group_icon_dir_entry.bHeight
            bytes = struct.unpack('1B',struct.pack('B',group_icon_dir_entry.bHeight)) # convert value to 1 byte
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
            
            # icon_entry.bColorCount = group_icon_dir_entry.bColorCount
            bytes = struct.unpack('1B',struct.pack('B',group_icon_dir_entry.bColorCount)) # convert value to 1 byte
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
            
            # icon_entry.bReserved = 0
            bytes = struct.unpack('1B',struct.pack('B',0)) # convert value to 1 byte
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
           
            # icon_entry.wPlanes = group_icon_dir_entry.wPlanes
            bytes = struct.unpack('2B',struct.pack('H',group_icon_dir_entry.wPlanes)) # convert value to 2 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
            
            # icon_entry.wBitCount = group_icon_dir_entry.wBitCount
            bytes = struct.unpack('2B',struct.pack('H',group_icon_dir_entry.wBitCount)) # convert value to 2 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
           
            # icon_entry.dwBytesInRes = icon_img_size
            bytes = struct.unpack('4B',struct.pack('I',icon_img_size)) # convert value to 4 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    
            
            # icon_entry.dwImageOffset = image offset in the blob
            image_offset = blob_size+4  # the image starts at the end of this icon_entry
            bytes = struct.unpack('4B',struct.pack('I',image_offset)) # convert value to 4 bytes
            for i in range(len(bytes)):
                byte_array.append(bytes[i])
                blob_size += 1    


            """ dump the icon image data """
            try:
                offset = pe.get_offset_from_rva(icon_img_rva)
            except PEFormatError:
                offset = None
                
            if offset is not None and offset < len(pe.__data__) and offset+icon_img_size <= len(pe.__data__):
                byte_array.extend(pe.__data__[offset:offset+icon_img_size])
                blob_str = base64.b64encode(byte_array) # return the blob as a base64 encoded string
                blob_size += icon_img_size
            else:
                pe.__warnings.append('Unable to dump Icon blob (Id: %d, Size: %x). RVA invalid: %x' % (icon_id, icon_img_size, icon_img_rva))
                blob_str = 'Extracted icon image offset and size are out of bounds of file'
                blob_size = 0

            dump_dict['blob'] = blob_str
            dump_dict['blob_str_len'] = len(blob_str)
            dump_dict['bytes_len'] = blob_size   # also return the number of bytes that this string represents (ToDo: not sure if this is needed)
            dump_dict['width'] = group_icon_dir_entry.bWidth
            dump_dict['height'] = group_icon_dir_entry.bHeight

    return dump_dict
  
def parse_icons(pe, pe_group_icon_dir_struct):
    """ create an array of icon descriptors, each with the info needed to write 
        the icon formatted as an .ico file
    """
    __GROUP_ICON_DIR_format__ = ('PE_GROUPICONDIR',
    ('h,wReserved',
     'h,wType',          # Resource type (1 for icons)
     'h,wCount',         # number of images
     ) )

    # the C structure is pack(2) so I added alignment fields
    __GROUP_ICON_DIR_ENTRY_format__ = ('PE_GROUPICONDIRENTRY',
    ('B,bWidth',         # Width of image in pixels
     'B,bHeight',        # Height of image
     'B,bColorCount',    # number of colors in image, 0 if >= 8bpp
     'B,bReserved',      # reserved
     'H,wPlanes',        # color planes
     'H,wBitCount',      # bits per pixel
     'I,dwBytesInRes',   # bytes in resource
     'H,nID',            # ID
     ) )

    # theIcon = PeIconDescriptor()  # the ultimate descriptor created for every icon, formatted for ICO files

    if (pe_group_icon_dir_struct == None):
        return None

    icons = dict()

    # get the PE_GROUPICONDIR struct
    rvaGroupIconDir = pe_group_icon_dir_struct.OffsetToData # RVA of PE_GROUPICONDIR
    file_offset = pe.get_offset_from_rva(rvaGroupIconDir)
    group_icon_dir_struct_len = 6
    if (file_offset+group_icon_dir_struct_len) > len(pe.__data__):
        return None
    raw_data = pe.__data__[ file_offset : file_offset+group_icon_dir_struct_len ]

    group_icon_dir = pe.__unpack_data__(
        __GROUP_ICON_DIR_format__, raw_data,
        file_offset = file_offset )
    if group_icon_dir is None:
        return None

    # get the first PE_GROUPICONDIRENTRY entry
    rvaGroupIconDirEntry = rvaGroupIconDir + group_icon_dir_struct_len;        # RVA of PE_GROUPICONDIRENTRY array

    # walk the PE_GROUPICONDIRENTRY array
    file_offset = pe.get_offset_from_rva(rvaGroupIconDirEntry)
    for i in range(0, group_icon_dir.wCount):
        group_icon_dir_entry_struct_len = 14
        if (file_offset+group_icon_dir_entry_struct_len) > len(pe.__data__):
            break
        raw_data = pe.__data__[ file_offset : file_offset+group_icon_dir_entry_struct_len ]
        group_icon_dir_entry = pe.__unpack_data__(
            __GROUP_ICON_DIR_ENTRY_format__, raw_data,
            file_offset = file_offset )
        if group_icon_dir_entry is None:
            break
        icons[group_icon_dir_entry.nID] = group_icon_dir_entry
        file_offset += group_icon_dir_entry_struct_len

    return icons
  
def ExtractGroupIcons(pe):
  icon_group = dict()
  try:
    rt_gicon_idx = [entry.id for entry in pe.DIRECTORY_ENTRY_RESOURCE.entries].index(pefile.RESOURCE_TYPE['RT_GROUP_ICON'])
  except:
    return icon_group

  rt_icon_directory = pe.DIRECTORY_ENTRY_RESOURCE.entries[rt_gicon_idx]
  for entry in rt_icon_directory.directory.entries:
    struct = entry.directory.entries[0].data.struct
    #print 'Directory entry at RVA', hex(struct.OffsetToData), 'of size', hex(struct.Size)
    icon_group = parse_icons(pe, struct)
    #print icon_group
    #print '\n'
    break # we only take the first group_icon
  return icon_group
  
def ExtractIcon(pe):
  icon_blob = ""
  try:
    rt_icon_idx = [entry.id for entry in pe.DIRECTORY_ENTRY_RESOURCE.entries].index(pefile.RESOURCE_TYPE['RT_ICON'])
  except:
    return icon_blob

  rt_icon_directory = pe.DIRECTORY_ENTRY_RESOURCE.entries[rt_icon_idx]
  for entry in rt_icon_directory.directory.entries:
    data_rva = entry.directory.entries[0].data.struct.OffsetToData
    size = entry.directory.entries[0].data.struct.Size
    #print 'Directory entry at RVA', hex(data_rva), 'of size', hex(size)

    #icon_blob = pe.get_memory_mapped_image()[data_rva:data_rva+size]
    if pe.icons is not None and entry.struct.Id in pe.icons.keys():
        icon_blob = dump_icon_blob(pe, entry.struct.Id, data_rva, size)
        #print 'Icon of length', size, 'at offset', data_rva
        break # we only take the first icon
  return icon_blob

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

def ExtractDigitalSignature(path):
    from verifysigs import auth_data
    from verifysigs import fingerprint
    from verifysigs import pecoff_blob
    from verifysigs.asn1 import dn
    import ast
    
    # Init structure data
    certificate_data = DigitalSignatureData
    certificate_data.signed = False
    certificate_data.verified = False 
    certificate_data.warnings = []

    # open file and get fingerprints
    # compute hashes
    with file(path, 'rb') as objf:
        fingerprinter = fingerprint.Fingerprinter(objf)
        is_pecoff = fingerprinter.EvalPecoff()
        fingerprinter.EvalGeneric()
        results = fingerprinter.HashIt()    

    if not is_pecoff:
        certificate_data.warnings.append('Not a PE/COFF binary.')
        return certificate_data

    # get signed PE COFF
    signed_pecoffs = [x for x in results if x['name'] == 'pecoff' and 'SignedData' in x]

    # If the PE is not signed, skip
    if not signed_pecoffs:
        return certificate_data

    # startin here, we know the file is signed
    certificate_data.signed = True    
    
    signed_pecoff = signed_pecoffs[0]
    signed_datas = signed_pecoff['SignedData']
    
    # There may be multiple of these, if the windows binary was signed multiple
    # times, e.g. by different entities. Each of them adds a complete SignedData
    # blob to the binary.
    # TODO(user): Process all instances
    signed_data = signed_datas[0]
        
    # get blob of certificate
    try: 
        blob = pecoff_blob.PecoffBlob(signed_data)
    except Exception as e:
        certificate_data.verified = False    
        certificate_data.warnings.append('PeCoffBlob validation Error: (%s) %s' % (type(e).__name__, str(e)))
        return certificate_data

    # get hashes and signer
    try:
        auth = auth_data.AuthData(blob.getCertificateBlob())
    except Exception as e:
        certificate_data.verified = False    
        certificate_data.warnings.append('Digital Signature Validate Asn1 Error: (%s) %s' % (type(e).__name__, str(e)))
        return certificate_data
    
    try:     
        content_hasher_name = auth.digest_algorithm().name
        computed_content_hash = signed_pecoff[content_hasher_name]
        computed_content_hash_str = binascii.hexlify(bytearray(computed_content_hash))
    except Exception as e:
        certificate_data.verified = False    
        certificate_data.warnings.append('Digest Algorithm Error: (%s) %s' % (type(e).__name__, str(e)))
        return certificate_data  
    
    certificate_data.verified = True
    
    # try validate certificate with openSSL
    try:
        auth.ValidateAsn1()
    except auth_data.Asn1Error as e:
        certificate_data.verified = False
        if auth.openssl_error:
            certificate_data.warnings.append('Digital Signature Validate Asn1 OpenSSL Error: %s' % auth.openssl_error)                
        else:
            certificate_data.warnings.append('Digital Signature Validate Asn1 Error: %s' % str(e))
        
    try:
        auth.ValidateHashes(computed_content_hash)
    except auth_data.Asn1Error as e:
        certificate_data.verified = False
        if auth.openssl_error:
            certificate_data.warnings.append('Digital Signature Validate Hashes OpenSSL Error: %s' % auth.openssl_error)                
        else:
            certificate_data.warnings.append('Digital Signature Validate Hashes Error: %s' % str(e))
        
    try:
        auth.ValidateSignatures()
    except auth_data.Asn1Error as e:
        certificate_data.verified = False
        if auth.openssl_error:
            certificate_data.warnings.append('Digital Signature Validate Signatures OpenSSL Error: %s' % auth.openssl_error)                
        else:
            certificate_data.warnings.append('Digital Signature Validate Signatures Error: %s' % str(e))
        
    try:
        auth.ValidateCertChains(time.gmtime())
    except auth_data.Asn1Error as e:
        certificate_data.verified = False
        if auth.openssl_error:
            certificate_data.warnings.append('Digital Signature Validate Timestamp OpenSSL Error: %s' % auth.openssl_error)                
        else:
            certificate_data.warnings.append('Digital Signature Validate Timestamp Error: %s' % str(e))
            
    # some of those fields may be missing because of various reasons (catched above)
    try:
        certificate_data.signature = dict()
        certificate_data.signature['Algorithm'] = content_hasher_name
        certificate_data.signature['Hash'] = '0x' + computed_content_hash_str + 'L'
        certificate_data.signature['ProgramName'] = convert_to_printable_null_terminated(auth.program_name)     
        certificate_data.signature['ProgramUrl'] = convert_to_printable_null_terminated(auth.program_url)        
        signer_issuer, certificate_data.signature['SerialNumber'] = auth.signing_cert_id
    except:
        pass
    
    # some of those fields may be missing because of various reasons (catched above)
    try:
        certificate_data.signature['StartValidity'] = time.asctime(time.gmtime(auth.cert_chain_head[0]))
        certificate_data.signature['EndValidity'] = time.asctime(time.gmtime(auth.cert_chain_head[1]))
        
        # counter signature
        if auth.has_countersignature:
            certificate_data.signature['CounterSignatureTimestamp'] = time.asctime(time.gmtime(auth.counter_timestamp))             
    except:
        pass       
                    
    # to hex (we remove long (L) ending character
    if 'SerialNumber' in certificate_data.signature:
        certificate_data.signature['SerialNumber'] = hex(certificate_data.signature['SerialNumber'])
    
    try:
        signer_issuer_dict = ast.literal_eval(signer_issuer)   
        if 'C' in signer_issuer_dict:            
            certificate_data.signature['CountryName'] = signer_issuer_dict['C']              
        if 'CN' in signer_issuer_dict:            
            certificate_data.signature['Issuer'] = signer_issuer_dict['CN'] 
        if 'O' in signer_issuer_dict:            
            certificate_data.signature['OrganizationName'] = signer_issuer_dict['O'] 
        if 'OU' in signer_issuer_dict:            
            certificate_data.signature['OrganizationUnitName'] = signer_issuer_dict['OU'] 
    except:
        pass
    
    # get certificates information
    certificate_data.certificates = list() 
    for (issuer, serial), cert in auth.certificates.items():            
        certificate = dict()
        
        # split subject dictionary
        subject = cert[0][0]['subject']
        subject_dn = str(dn.DistinguishedName.TraverseRdn(subject[0])) 
        
        try:
            issuer_dict = ast.literal_eval(issuer)                
            if 'CN' in issuer_dict:            
                certificate['Issuer'] = issuer_dict['CN']      
        except:
            pass
             
        try:
            subject_dict = ast.literal_eval(subject_dn)                
            if 'C' in subject_dict:            
                certificate['CountryName'] = subject_dict['C']
            if 'CN' in subject_dict:
                certificate['CommonName'] = subject_dict['CN']
            if 'O' in subject_dict:
                certificate['OrganizationName'] = subject_dict['O']
            if 'OU' in subject_dict:
                certificate['OrganizationUnitName'] = subject_dict['OU']       
        except:
            pass   
            
        certificate['serial'] = hex(serial)
        
        not_before = cert[0][0]['validity']['notBefore']
        not_after = cert[0][0]['validity']['notAfter']
        not_before_time = not_before.ToPythonEpochTime()
        not_after_time = not_after.ToPythonEpochTime()            
        certificate['StartValidity'] = time.asctime(time.gmtime(not_before_time))
        certificate['EndValidity'] = time.asctime(time.gmtime(not_after_time))
                    
        certificate_data.certificates.append(certificate)
          
    return certificate_data

def ExtractSigner(digital_signature):
    signer = ''
    if 'signed' in digital_signature and digital_signature['signed']:
        if 'certificates' in digital_signature:
            for cert in digital_signature['certificates']:
                if 'OrganizationName' in cert:
                    for value in cert['OrganizationName']:
                        if signer == '':
                            signer = signer + value
                        else:
                            signer = signer + "," + value
    return signer

def ExtractAndFormatDigitalSignature(path):
    digisig = ExtractDigitalSignature(path)
    digital_signature = dict()
    digital_signature['signed'] = digisig.signed
    digital_signature['verified'] = digisig.verified
    digital_signature['warnings'] = digisig.warnings
    
    if hasattr(digisig, 'signature'):
        digital_signature['signature'] = digisig.signature
        
    if hasattr(digisig, 'certificates'):
        digital_signature['certificates'] = digisig.certificates
        
    return digital_signature

def ProcessFile(path):
    global pretty_print, text_mode
    if not(os.path.isfile(path)):
        print '{0} not a file!'.format(path)
        return 2

    filename = os.path.basename(path)
    size = os.path.getsize(path)
    if size > 31457280:
        data = {}
        data['valid'] = False
        data['error'] = 'File is too big'
        print json.dumps(data)
        return 1

    try:
        pe = pefile.PE(path)   
        pe.icons = ExtractGroupIcons(pe)
    except Exception as ex:
        if (text_mode):
            print 'Exception parsing file:', type(ex), ex
        else:
            data = {}
            data['valid'] = False
            data['error'] = str(ex)
            print json.dumps(data)
            return 1
    
    if (text_mode):
        try:
            print pe.dump_info()
        except Exception as ex:
            data = {}
            data['valid'] = False
            data['error'] = str(ex)
            print json.dumps(data)
            return 1
    else:
        try:
            data = {}
            data['valid'] = True
            data['data']  = pe.dump_dict()
            data['data']['strings'] = ExtractStrings(pe)
            data['data']['strings_raw'] = ExtractStringsRaw(path)
            data['data']['pdbpath'] = ExtractPdb(pe)
            data['data']['icon'] = ExtractIcon(pe)
            data['data']['imphash'] = pe.get_imphash()
            data['data']['digisig'] = ExtractAndFormatDigitalSignature(path)
            data['data']['signer']  = ExtractSigner(data['data']['digisig'])
            if (pretty_print):
                encoded = json.dumps(data, indent=4)
            else:
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
pretty_print = False
text_mode = False
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

