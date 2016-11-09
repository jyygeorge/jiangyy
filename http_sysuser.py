#!/usr/bin/env python3


import sys
import urllib.request
import argparse
import ssl
import httplib2

from socket import error as SocketError
import errno


user_test = "pomliukjytjhtgbyhnresuipgvn"
t_user = ["root"]
verbosity = 0;

t_colors = {
	'BLACK': '\033[30m',
	'RED': '\033[31m',
	'GREEN': '\033[32m',
	'YELLOW': '\033[33m',
	'BLUE': '\033[34m',
	'PURPLE': '\033[35m',
	'LIGHT_BLUE': '\033[36m',
	'GREY': '\033[37m',
	'WHITE': '\033[38m',
	'LIGHT_GREEN': '\033[92m',
	'LIGHT_YELLOW': '\033[93m',
	'NC': '\033[0m'
}


def _print(msg,color='WHITE'):
	print(t_colors[color] + msg + t_colors['NC'] + "\n", end="", flush=True)


def testserver(server,ctx):
	base = testuser(server,user_test,ctx)
	for u in t_user:
		r = testuser(server,u,ctx)
		if r != base:
			_print(server + ' seems to be vulnerable!','GREEN')


def testuser(server,user,ctx):
	r = testurl("http://"+server+"/~"+user,ctx)
	
	if r == -2:
		r = testurl("https://"+server+"/~"+user,ctx)
	
	return r


def testurl(url,ctx):
	r = -666
	
	try:
		res = urllib.request.urlopen(url, None, 2, context=ctx)
		data = str(res.read())
		l = sys.getsizeof(data)
		if verbosity <= 1:
			_print(url + ' ' + str(res.status) + ' ' + str(res.reason) + ' ' + str(l), 'YELLOW')
		r = l
	except:
		_print(url,'GREY')
		r = -2
	
	return r


def main():
	global verbosity
	
	parse = argparse.ArgumentParser()
	parse.add_argument('-s', action='store', dest='server_file', help='Server list')
	parse.add_argument('-v', action='store', dest='verbosity', help='Verbose mode, 0=all (default), 1=only good message, 2=only vulnerable url')
	
	args = parse.parse_args()
	if args.verbosity != None:
		verbosity = int(args.verbosity)
	
	if args.server_file == None:
		parse.print_help()
		print("\n")
		exit(1)
	
	if args.server_file != None:
		try:
			hostFile = open(args.server_file, 'r')
		except IOError:
			parse.print_help()
			_print("\n[-] The file '"'%s'"' doesn't exist." % (args.server_file) + "\n",'RED')
			exit(1)

	t_url = []
	for line in hostFile.readlines():
		line = line.split("\n")
		t_url.append(str(line[0]))
	
	ctx = ssl.create_default_context()
	ctx.check_hostname = False
	ctx.verify_mode = ssl.CERT_NONE
	
	for s in t_url:
		testserver(s,ctx)
		print("")


if __name__ == "__main__":
    main()
