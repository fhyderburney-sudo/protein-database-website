#!/bin/bash

# Usage:
# ./fetch_run.sh "QUERY" RUN_ID

QUERY="$1"
RUN_ID="$2"

OUTDIR="$HOME/public_html/PW/runs/run_${RUN_ID}"
OUTFILE="$OUTDIR/sequences.fasta"

mkdir -p "$OUTDIR"

echo "Running query:"
echo "$QUERY"
echo

esearch -db protein -query "$QUERY" | efetch -format fasta > "$OUTFILE"

if [ -s "$OUTFILE" ]; then
    echo "FASTA saved to $OUTFILE"
else
    echo "No sequences retrieved or retrieval failed"
fi
