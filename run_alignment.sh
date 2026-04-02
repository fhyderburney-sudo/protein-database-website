#!/bin/bash

RUN_ID="$1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
RUNDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
INFILE="$RUNDIR/sequences.fasta"
OUTFILE="$RUNDIR/alignment.aln"

if [ -z "$RUN_ID" ]; then
    echo "Usage: $0 RUN_ID"
    exit 1
fi

if [ ! -d "$RUNDIR" ]; then
    echo "Run directory not found: $RUNDIR"
    exit 1
fi

if [ ! -f "$INFILE" ]; then
    echo "FASTA file not found: $INFILE"
    exit 1
fi

if [ ! -s "$INFILE" ]; then
    echo "FASTA file is empty: $INFILE"
    exit 1
fi

# Remove old alignment file if present
rm -f "$OUTFILE"

# Make directory writable
chmod u+rwx "$RUNDIR" 2>/dev/null
chmod u+rw "$INFILE" 2>/dev/null

echo "Running Clustal Omega alignment..."
clustalo -i "$INFILE" -o "$OUTFILE" --force 2>&1
STATUS=$?

if [ $STATUS -ne 0 ]; then
    echo "Clustal Omega failed."
    exit 1
fi

if [ -s "$OUTFILE" ]; then
    chmod u+rw "$OUTFILE" 2>/dev/null
    echo "Alignment saved to $OUTFILE"
else
    echo "Alignment failed: output file was not created."
    exit 1
fi