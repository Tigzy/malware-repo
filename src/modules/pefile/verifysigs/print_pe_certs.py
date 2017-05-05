#!/usr/bin/env python

# Copyright 2011 Google Inc. All Rights Reserved.
#
# Licensed under the Apache License, Version 2.0 (the "License");
# you may not use this file except in compliance with the License.
# You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.
#
# Author: caronni@google.com (Germano Caronni)

"""Wrapper to exercise fingerprinting and authenticode validation.

   Give it a full path to e.g. a windows binary.
"""

# I really want to use parens in print statements.
# pylint: disable-msg=C6003

import hashlib
import pprint
import sys
import time


from pyasn1.codec.der import encoder as der_encoder

import auth_data
import fingerprint
import pecoff_blob
from asn1 import dn


# EVIL EVIL -- Monkeypatch to extend accessor
# TODO(user): This was submitted to pyasn1. Remove when we have it back.
def F(self, idx):
  if type(idx) is int:
    return self.getComponentByPosition(idx)
  else: return self.getComponentByName(idx)
from pyasn1.type import univ  # pylint: disable-msg=C6204,C6203
univ.SequenceAndSetBase.__getitem__ = F
del F, univ
# EVIL EVIL


def main():
  data_file = sys.argv[1]

  with file(data_file, 'rb') as objf:
    fingerprinter = fingerprint.Fingerprinter(objf)
    is_pecoff = fingerprinter.EvalPecoff()
    fingerprinter.EvalGeneric()
    results = fingerprinter.HashIt()

  print('Generic hashes:')
  hashes = [x for x in results if x['name'] == 'generic']
  if len(hashes) > 1:
    print('More than one generic finger? Only printing first one.')
  for hname in sorted(hashes[0].keys()):
    if hname != 'name':
      print('%s: %s' % (hname, hashes[0][hname].encode('hex')))
  print

  if not is_pecoff:
    print('This is not a PE/COFF binary. Exiting.')
    return

  print('PE/COFF hashes:')
  hashes = [x for x in results if x['name'] == 'pecoff']
  if len(hashes) > 1:
    print('More than one PE/COFF finger? Only printing first one.')
  for hname in sorted(hashes[0].keys()):
    if hname != 'name' and hname != 'SignedData':
      print('%s: %s' % (hname, hashes[0][hname].encode('hex')))
  print

  signed_pecoffs = [x for x in results if x['name'] == 'pecoff' and
                    'SignedData' in x]

  if not signed_pecoffs:
    print('This PE/COFF binary has no signature. Exiting.')
    return

  signed_pecoff = signed_pecoffs[0]

  signed_datas = signed_pecoff['SignedData']
  # There may be multiple of these, if the windows binary was signed multiple
  # times, e.g. by different entities. Each of them adds a complete SignedData
  # blob to the binary.
  # TODO(user): Process all instances
  signed_data = signed_datas[0]

  blob = pecoff_blob.PecoffBlob(signed_data)

  auth = auth_data.AuthData(blob.getCertificateBlob())
  content_hasher_name = auth.digest_algorithm().name
  computed_content_hash = signed_pecoff[content_hasher_name]

  try:
    auth.ValidateAsn1()
    auth.ValidateHashes(computed_content_hash)
    auth.ValidateSignatures()
    auth.ValidateCertChains(time.gmtime())
  except auth_data.Asn1Error:
    if auth.openssl_error:
      print('OpenSSL Errors:\n%s' % auth.openssl_error)
    raise

  print('Program: %s, URL: %s' % (auth.program_name, auth.program_url))
  if auth.has_countersignature:
    print('Countersignature is present. Timestamp: %s UTC' %
          time.asctime(time.gmtime(auth.counter_timestamp)))
  else:
    print('Countersignature is not present.')

  print('Binary is signed with cert issued by:')
  pprint.pprint(auth.signing_cert_id)
  print

  print('Cert chain head issued by:')
  pprint.pprint(auth.cert_chain_head[2])
  print('  Chain not before: %s UTC' %
        (time.asctime(time.gmtime(auth.cert_chain_head[0]))))
  print('  Chain not after: %s UTC' %
        (time.asctime(time.gmtime(auth.cert_chain_head[1]))))
  print

  if auth.has_countersignature:
    print('Countersig chain head issued by:')
    pprint.pprint(auth.counter_chain_head[2])
    print('  Countersig not before: %s UTC' %
          (time.asctime(time.gmtime(auth.counter_chain_head[0]))))
    print('  Countersig not after: %s UTC' %
          (time.asctime(time.gmtime(auth.counter_chain_head[1]))))
    print

  print('Certificates')
  for (issuer, serial), cert in auth.certificates.items():
    print('  Issuer: %s' % issuer)
    print('  Serial: %s' % serial)
    subject = cert[0][0]['subject']
    subject_dn = str(dn.DistinguishedName.TraverseRdn(subject[0]))
    print('  Subject: %s' % subject_dn)
    not_before = cert[0][0]['validity']['notBefore']
    not_after = cert[0][0]['validity']['notAfter']
    not_before_time = not_before.ToPythonEpochTime()
    not_after_time = not_after.ToPythonEpochTime()
    print('  Not Before: %s UTC (%s)' %
          (time.asctime(time.gmtime(not_before_time)), not_before[0]))
    print('  Not After: %s UTC (%s)' %
          (time.asctime(time.gmtime(not_after_time)), not_after[0]))
    bin_cert = der_encoder.encode(cert)
    print('  MD5: %s' % hashlib.md5(bin_cert).hexdigest())
    print('  SHA1: %s' % hashlib.sha1(bin_cert).hexdigest())
    print

  if auth.trailing_data:
    print('Signature Blob had trailing (unvalidated) data (%d bytes): %s' %
          (len(auth.trailing_data), auth.trailing_data.encode('hex')))


if __name__ == '__main__':
  main()
