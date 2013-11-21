#!/bin/bash
fpc -B -Filib -FUlib -Ilib -Fulib main.pas
rm lib/*.o
rm lib/*.ppu
./main