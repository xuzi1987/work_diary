#! /usr/bin/env python
#-*- coding: UTF-8 -*-

try:
	import sys
	from threading import Thread
	import base64
	from SimpleXMLRPCServer import SimpleXMLRPCServer	
except (ImportError):
	sys.exit(0)

try:
	from Libs.msg_py2 import Box
except (ImportError):
	sys.exit(0)

HOST, PORT = "", 20000

class ServerThread(Thread):

	_rpc_methods_ = ['msgBox']
	
	def __init__(self, host):
		Thread.__init__(self)
		self.server = SimpleXMLRPCServer(host, allow_none=True)
		self.server.register_multicall_functions()
		for name in self._rpc_methods_:
			self.server.register_function(getattr(self,name))
			
	def run(self):
		self.server.serve_forever()
		
	def msgBox(self, msg):
		Box().show(base64.decodestring(msg))

def server(host, port):
	addr = (host, port)
	serv = ServerThread(addr)
	serv.start()
	
if __name__ == '__main__':
	server(HOST, PORT)
