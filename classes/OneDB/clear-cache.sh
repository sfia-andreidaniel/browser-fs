#!/bin/sh
cd "`dirname "$0"`"
find cache/. -type f -name *.cache -exec rm -f {} \;
find cache/. -type f -name *.docsplit -exec rm -f {} \;
find public-auth/. -type f -name *.auth -exec rm -f {} \;