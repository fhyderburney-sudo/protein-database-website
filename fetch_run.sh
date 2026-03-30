#!/bin/bash

QUERY="$1"
RUN_ID="$2"
MAXSEQ="$3"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
OUTDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
OUTFILE="$OUTDIR/sequences.fasta"
TMPFILE="$OUTDIR/all_sequences.fasta"

ESEARCH="/home/s2902517/edirect/esearch"
EFETCH="/home/s2902517/edirect/efetch"

if [ -f "/home/s2902517/.bash_profile" ]; then
    . /home/s2902517/.bash_profile
fi

if [ -f "/home/s2902517/.bashrc" ]; then
    . /home/s2902517/.bashrc
fi

if [ -z "$QUERY" ] || [ -z "$RUN_ID" ]; then
    echo "Usage: $0 QUERY RUN_ID MAXSEQ"
    exit 1
fi

if [ -z "$MAXSEQ" ] || [ "$MAXSEQ" -le 0 ] 2>/dev/null; then
    MAXSEQ=20
fi

mkdir -p "$OUTDIR"

echo "Running NCBI protein query:"
echo "$QUERY"
echo "Maximum sequences requested: $MAXSEQ"

"$ESEARCH" -db protein -query "$QUERY" | "$EFETCH" -format fasta > "$TMPFILE"

if [ ! -s "$TMPFILE" ]; then
    echo "No FASTA records were retrieved."
    rm -f "$TMPFILE"
    exit 1
fi

awk -v max="$MAXSEQ" '
BEGIN { n = 0 }
(/^>/) {
    n++
    if (n > max) exit
}
{ print }
' "$TMPFILE" > "$OUTFILE"

rm -f "$TMPFILE"

if [ -s "$OUTFILE" ]; then
    echo "FASTA saved to $OUTFILE"
else
    echo "No sequences were saved after filtering."
    exit 1
fi