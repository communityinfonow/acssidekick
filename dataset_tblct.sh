#!/bin/bash

find /var/lib/mysql/2* -type f -name "*_*.frm" | awk -F"/" '{ 
	print $(NF-1)" "$NF
}' | sed 's/.frm$//g' | awk -F"_" '{
	print $1"_"$2
}' | sort | uniq -c
