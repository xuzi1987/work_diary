#! /usr/bin/env python3
#-*- coding: UTF-8 -*-

try:
	import gi
	gi.require_version('Gtk', '3.0')
	from gi.repository import Gtk, GObject
except (ImportError, ValueError):
	sys.exit(0)

__all__ = ['Box']

class Handler:

	def onDelete(self, *args):
		Gtk.main_quit(*args)

class Box(object):

	def __init__(self):
		self.builder = Gtk.Builder()
		self.builder.add_from_file("/home/www/PythonMessages/Libs/msg_ui.glade")
		self.builder.connect_signals(Handler())
		
	def show(self, *args):
		self.builder.get_object("Message").set_text(args[0])
		self.builder.get_object("Alart_Box").show()
		Gtk.main()

if __name__ == '__main__':
	Box().show("\n \t ...... \t \n")

