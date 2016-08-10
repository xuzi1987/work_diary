#! /usr/bin/env python3
#-*- coding: UTF-8 -*-

try:
	import sys
	from xmlrpc.client import ServerProxy
except (ImportError):
	sys.exit(0)

if __name__ == '__main__':
	RPCHost = sys.argv[1]
	RPCPort = 20000
	RPCaddress = "http://" + RPCHost + ":" + str(RPCPort)
	c = ServerProxy(RPCaddress, allow_none=True)
	c.msgBox(sys.argv[2])
