#!/bin/bash
fpc -B -Filib -FUlib -Ilib -Fulib main.pp
rm lib/*.o
rm lib/*.ppu