#!/bin/bash

RUN_ID="$1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
RUNDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
INFILE="$RUNDIR/alignment.aln"
OUTBASE="$RUNDIR/conservation"

if [ -z "$RUN_ID" ]; then
    echo "Usage: $0 RUN_ID"
    exit 1
fi

if [ ! -f "$INFILE" ]; then
    echo "Alignment file not found: $INFILE"
    exit 1
fi

echo "Running plotcon conservation analysis..."

plotcon -sequences "$INFILE" -graph png -goutfile "$OUTBASE" -winsize 4
STATUS=$?

if [ $STATUS -ne 0 ]; then
    echo "Conservation analysis failed."
    exit 1
fi

if [ -f "${OUTBASE}.1.png" ] && [ -s "${OUTBASE}.1.png" ]; then
    echo "Conservation plot saved to ${OUTBASE}.1.png"
elif [ -f "${OUTBASE}.png" ] && [ -s "${OUTBASE}.png" ]; then
    echo "Conservation plot saved to ${OUTBASE}.png"
else
    echo "Conservation analysis finished, but no PNG output was found."
    exit 1
fi
