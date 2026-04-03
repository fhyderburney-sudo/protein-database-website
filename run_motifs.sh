#!/bin/bash

RUN_ID="$1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
RUNDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
INFILE="$RUNDIR/sequences.fasta"
OUTFILE="$RUNDIR/motifs.txt"
TMPDIR="$RUNDIR/motif_tmp"

if [ -z "$RUN_ID" ]; then
    echo "Usage: $0 RUN_ID"
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

rm -f "$OUTFILE"
rm -rf "$TMPDIR"
mkdir -p "$TMPDIR"

echo "Running PROSITE motif scan..."

# Split multi-FASTA into one file per sequence
awk '
BEGIN {n=0}
/^>/ {
    n++;
    outfile=sprintf("'"$TMPDIR"'/seq_%03d.fasta", n);
}
{
    print > outfile
}
' "$INFILE"

count=0
hits_found=0

for fasta in "$TMPDIR"/*.fasta; do
    if [ ! -f "$fasta" ]; then
        continue
    fi

    patmatmotifs -sequence "$fasta" -outfile "$TMPDIR/result.txt" >/dev/null 2>&1

    if [ -f "$TMPDIR/result.txt" ] && [ -s "$TMPDIR/result.txt" ]; then
        cat "$TMPDIR/result.txt" >> "$OUTFILE"
        echo "" >> "$OUTFILE"
        echo "" >> "$OUTFILE"

        if grep -q "HitCount: [1-9]" "$TMPDIR/result.txt"; then
            hits_found=1
        fi
    fi

    count=$((count + 1))
done

rm -rf "$TMPDIR"

if [ ! -f "$OUTFILE" ] || [ ! -s "$OUTFILE" ]; then
    echo "Motif scan failed: no report file was produced."
    exit 1
fi

if [ "$hits_found" -eq 1 ]; then
    echo "Motif report saved to $OUTFILE"
    echo "At least one PROSITE hit was found."
else
    echo "Motif report saved to $OUTFILE"
    echo "No PROSITE hits were found in the scanned sequences."
fi

echo "Sequences scanned: $count"