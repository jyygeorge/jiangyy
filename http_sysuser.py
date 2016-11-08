#!/usr/bin/env python3
# -*- coding: utf-8 -*-


import sys
import urllib.request
import argparse
import ssl


class bcolors:
	BLACK = '\033[30m'
	RED = '\033[31m'
	GREEN = '\033[32m'
	YELLOW = '\033[33m'
	BLUE = '\033[34m'
	PURPLE = '\033[35m'
	LIGHT_BLUE = '\033[36m'
	GREY = '\033[37m'
	WHITE = '\033[38m'
	LIGHT_GREEN = '\033[92m'
	LIGHT_YELLOW = '\033[93m'
	NC = '\033[0m'


def test(url,ctx):
	r = -1
	
	try:
		res = urllib.request.urlopen(url, None, 2, context=ctx)
		data = str(res.read())
		print(bcolors.GREEN + url + ' ' + str(res.status) + ' ' + str(res.reason) + ' ' + str(sys.getsizeof(data)) + bcolors.NC)
		r = 0
	except urllib.error.HTTPError:
		print(bcolors.RED + url + bcolors.NC)
		r = 1
	except urllib.error.URLError:
		print(bcolors.GREY + url + bcolors.NC)
		r = 2
	
	return r


def main():
	parse = argparse.ArgumentParser()
	parse.add_argument('-s', action='store', dest='server_file', help='Server list')
	
	t_user = ["pomliukjytjhtgbyhnresuipgvn","root"]
	args = parse.parse_args()
	
	if args.server_file == None:
		parse.print_help()
		print("\n")
		exit(1)
	
	if args.server_file != None:
		try:
			hostFile = open(args.server_file, 'r')
		except IOError:
			parse.print_help()
			print(bcolors.FAIL + "\n[-] The file '"'%s'"' doesn't exist." % (args.server_file) + "\n" + bcolors.ENDC)
			exit(1)

	t_url = []
	for line in hostFile.readlines():
		line = line.split("\n")
		t_url.append(str(line[0]))
	
	ctx = ssl.create_default_context()
	ctx.check_hostname = False
	ctx.verify_mode = ssl.CERT_NONE
	
	for s in t_url:
		for u in t_user:
			url = "http://"+s+"/~"+u
			r = test(url,ctx)
			
			if r == 2:
				url = "https://"+s+"/~"+u
				r = test(url,ctx)
		print ("\n")


if __name__ == "__main__":
    main()
