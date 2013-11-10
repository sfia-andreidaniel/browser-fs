#!/bin/bash
cd "`dirname "$0"`"
IFS=$'\t\n'
NUM_SNAPSHOTS_DEFAULT_IF_OMITTED=3

if [ -z "$1" ]
then
    echo
    echo  ERROR - No input file specified.
    echo
    exit
fi

if [ -z "$2" ]
then
    echo
    echo ERROR - no output file specified.
    echo
    exit
fi

if [ -z "$3" ]
then
NUM_SNAPSHOTS=$NUM_SNAPSHOTS_DEFAULT_IF_OMITTED
else
NUM_SNAPSHOTS=$3
fi

INPUT=$1
OUTPUT=$2

FRAMERATE=`php calc_frames.php "$INPUT" "$NUM_SNAPSHOTS"`

ffmpeg -i $INPUT -y -an -sameq -f image2 -r "$FRAMERATE" -vframes $NUM_SNAPSHOTS "$OUTPUT.%d.jpg"
