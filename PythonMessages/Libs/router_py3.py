#! /usr/bin/env python3
#-*- coding: UTF-8 -*-

try:
	import sys, json
except (ImportError, ValueError):
	sys.exit(0)

__all__ = ['loadIP']

def loadIP():
	with open('/home/www/PythonMessages/Libs/ip.json', 'r') as f:
		data = json.load(f)
	return data

if __name__ == '__main__':
	print( loadIP()['daij@innosilicon.com.cn'] )

