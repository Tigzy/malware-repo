#!/usr/bin/env python

# Copyright 2010 Google Inc. All Rights Reserved.
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

"""Generates reference data for fingerprinter_test.py.

   Please note that this is supposed to be run only for updating
   test data in case more data is added or the format changes.
   This is a manual operation, that needs to be followed by a
   g4 submit of the changed test data.
"""

import os
import pickle

import fingerprint


def main():
  os.chdir('test_data')
  files = os.listdir('.')
  for fnam in files:
    if not fnam.lower().endswith('.res'):
      print 'Scanning %s' % fnam
      with file(fnam, 'rb') as objf:
        fingerprinter = fingerprint.Fingerprinter(objf)
        fingerprinter.EvalPecoff()
        fingerprinter.EvalGeneric()
        results = fingerprinter.HashIt()
        with file(fnam + '.res', 'wb') as resf:
          pickle.dump(results, resf, pickle.HIGHEST_PROTOCOL)

if __name__ == '__main__':
  main()
