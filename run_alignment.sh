#!/bin/bash

RUN_ID="$1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
INDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
INFILE="$INDIR/sequences.fasta"
OUTFILE="$INDIR/alignment.aln"

if [ ! -f "$INFILE" ]; then
    echo "FASTA file not found: $INFILE"
    exit 1
fi

echo "Running Clustal Omega alignment..."
clustalo -i "$INFILE" -o "$OUTFILE" --force

if [ -s "$OUTFILE" ]; then
    echo "Alignment saved to $OUTFILE"
else
    echo "Alignment failed"
    exit 1
fi
