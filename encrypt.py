#! /usr/bin/env python3
#-*- coding: UTF-8 -*-

import gnupg
import sys

gpg = gnupg.GPG(gnupghome='/home/www/gnupg')

key_data = open(sys.argv[1]).read()
import_result = gpg.import_keys(key_data)

keyinfo = gpg.scan_keys(sys.argv[1])
uids = keyinfo[0]['uids'][0]

with open(sys.argv[2], 'rb') as f:
    status = gpg.encrypt_file(
        f, recipients=[uids],
        output=sys.argv[2]+'.asc', 
        always_trust=True)


