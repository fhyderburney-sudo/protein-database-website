#!/bin/bash

RUN_ID="$1"

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
INDIR="$SCRIPT_DIR/runs/run_${RUN_ID}"
INFILE="$INDIR/sequences.fasta"
OUTFILE="$INDIR/proteins.tsv"

if [ ! -f "$INFILE" ]; then
    echo "Input FASTA file not found: $INFILE"
    exit 1
fi

awk '
BEGIN {
    header = ""
    seq = ""
}
function print_record() {
    if (header != "") {
        acc = header
        sub(/^>/, "", acc)
        split(acc, a, /[ \t]/)
        accession = a[1]

        organism = ""
        if (match(header, /\[[^][]+\]$/)) {
            organism = substr(header, RSTART + 1, RLENGTH - 2)
        }

        protein_name = header
        sub(/^>[^ ]+ /, "", protein_name)
        sub(/ \[[^][]+\]$/, "", protein_name)

        print accession "\t" protein_name "\t" organism "\t" header "\t" seq "\t" length(seq)
    }
}
(/^>/) {
    print_record()
    header = $0
    seq = ""
    next
}
{
    seq = seq $0
}
END {
    print_record()
}
' "$INFILE" > "$OUTFILE"

if [ -s "$OUTFILE" ]; then
    echo "Parsed TSV saved to $OUTFILE"
else
    echo "Parsing failed or produced empty TSV"
    exit 1
fi