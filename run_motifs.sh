#!/bin/bash

RUN_ID="$1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
RUNDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
INFILE="$RUNDIR/sequences.fasta"
OUTFILE="$RUNDIR/motifs.txt"

if [ -z "$RUN_ID" ]; then
    echo "Usage: $0 RUN_ID"
    exit 1
fi

if [ ! -f "$INFILE" ]; then
    echo "FASTA file not found: $INFILE"
    exit 1
fi

echo "Running PROSITE motif scan with patmatmotifs..."

patmatmotifs -sequence "$INFILE" -outfile "$OUTFILE"
STATUS=$?

if [ $STATUS -ne 0 ]; then
    echo "Motif scan failed."
    exit 1
fi

if [ -f "$OUTFILE" ] && [ -s "$OUTFILE" ]; then
    echo "Motif report saved to $OUTFILE"
else
    echo "Motif scan finished, but no output file was found."
    exit 1
fi