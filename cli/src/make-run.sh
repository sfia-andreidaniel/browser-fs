#!/bin/bash
fpc libhistory.pp
fpc libcommand.pp
fpc libterm.pp
fpc main.pp
./main