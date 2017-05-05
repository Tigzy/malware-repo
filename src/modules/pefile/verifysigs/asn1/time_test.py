#!/usr/bin/env python

# Copyright 2012 Google Inc. All Rights Reserved.
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

"""Tests for time conversion utility."""

import time


from pyasn1.type import useful

import unittest as test

from x509_time import Time


class TimeTest(test.TestCase):

  def testConvert(self):
    utctime = useful.UTCTime('120614235959Z')
    t = Time()
    t.setComponentByName('utcTime', utctime)
    t_str = time.asctime(time.gmtime(t.ToPythonEpochTime()))
    self.assertEquals(t_str, 'Thu Jun 14 23:59:59 2012')


def main():
  test.main()

if __name__ == '__main__':
  main()

