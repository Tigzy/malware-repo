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

"""Test for fingerprinter."""

import os
import pickle
import StringIO



import unittest as test
import fingerprint


class FingerprinterTest(test.TestCase):

  def testRunTestData(self):
    # Walk through all data files in the test_data folder, and compare output
    # with precomputed expected output.
    data_dir = os.path.join("test_data")
    files = os.listdir(data_dir)
    for fnam in files:
      if not fnam.lower().endswith(".res"):
        with file(os.path.join(data_dir, fnam), "rb") as objf:
          fp = fingerprint.Fingerprinter(objf)
          fp.EvalGeneric()
          fp.EvalPecoff()
          results = fp.HashIt()
          with file(os.path.join(data_dir, fnam + ".res"), "rb") as resf:
            exp_results = pickle.load(resf)
            diff = (exp_results != results)
            if diff:
              print
              print fingerprint.FormatResults(resf, exp_results)
              print fingerprint.FormatResults(objf, results)
              self.fail()

  def testReasonableInterval(self):
    # Check if the limit on maximum blocksize for processing still holds.
    dummy = StringIO.StringIO("")
    fp = fingerprint.Fingerprinter(dummy)
    big_finger = fingerprint.Finger(None,
                                    [fingerprint.Range(0, 1000001)],
                                    None)
    fp.fingers.append(big_finger)
    start, stop = fp._GetNextInterval()
    self.assertEquals(0, start)
    self.assertEquals(1000000, stop)

  def testAdjustments(self):
    dummy = StringIO.StringIO("")
    fp = fingerprint.Fingerprinter(dummy)
    big_finger = fingerprint.Finger(None,
                                    [fingerprint.Range(10, 20)],
                                    None)
    fp.fingers.append(big_finger)

    # The remaining range should not yet be touched...
    fp._AdjustIntervals(9, 10)
    self.assertEquals([fingerprint.Range(10, 20)], fp.fingers[0].ranges)
    # Trying to consume into the range. Blow up.
    self.assertRaises(RuntimeError, fp._AdjustIntervals, 9, 11)
    # We forgot a byte. Blow up.
    self.assertRaises(RuntimeError, fp._AdjustIntervals, 11, 12)
    # Consume a byte
    fp._AdjustIntervals(10, 11)
    self.assertEquals([fingerprint.Range(11, 20)], fp.fingers[0].ranges)
    # Consumed too much. Blow up.
    self.assertRaises(RuntimeError, fp._AdjustIntervals, 11, 21)
    # Consume exactly.
    fp._AdjustIntervals(11, 20)
    self.assertEquals(0, len(fp.fingers[0].ranges))

  class MockHasher(object):
    def __init__(self):
      self.seen = ""

    def update(self, content):  # pylint: disable-msg=C6409
      self.seen += content

  def testHashBlock(self):
    # Does it invoke a hash function?
    dummy = "12345"
    fp = fingerprint.Fingerprinter(StringIO.StringIO(dummy))
    big_finger = fingerprint.Finger(None,
                                    [fingerprint.Range(0, len(dummy))],
                                    None)
    hasher = self.MockHasher()
    big_finger.hashers = [hasher]
    fp.fingers.append(big_finger)
    # Let's process the block
    fp._HashBlock(dummy, 0, len(dummy))
    self.assertEquals(hasher.seen, dummy)

  # TODO(user): Add more tests for the carry-over of HashIt,
  # the pecoff parsing pieces, and the parser / collector of the SignedData
  # blob.
  # Make sure Authenticode hashes are set to MD5, SHA1 by default, since so
  # far authenticode does not support other hashing functions.
  # Check that default hashers get used when no argument is provided, or
  # None is provided. Make sure 'empty iterable' actually works as intended.


def main():
  test.main()

if __name__ == "__main__":
  main()
