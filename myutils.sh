#!/bin/bash


NC='\033[0m'
BLACK='0;30'
RED='0;31'
GREEN='0;32'
ORANGE='0;33'
BLUE='0;34'
PURPLE='0;35'
CYAN='0;36'
LIGHT_GRAY='0;37'
DARK_GRAY='1;30'
LIGHT_RED='1;31'
LIGHT_GREEN='1;32'
YELLOW='1;33'
LIGHT_BLUE='1;34'
LIGHT_PURPLE='1;35'
LIGHT_CYAN='1;36'
WHITE='1;37'


function _print() {
    if [ -n "$2" ] ; then
		c=$2
    else
		c='WHITE'
    fi

	color="\033[${!c}m"
    printf ${color}"$1"
    printf ${NC}
}
