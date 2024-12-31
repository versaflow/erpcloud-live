#!/bin/bash

# Ensure an XML file is provided as an argument
if [ $# -ne 1 ]; then
    echo "Usage: $0 <xml_file>"
    exit 1
fi

# Set variables
xml_file=$1
max_records=1000
original_file_name=$(basename "$xml_file" .xml)
file_prefix="split_${original_file_name}_"
file_extension=".xml"

# Temporary file to store the trimmed XML content
trimmed_file=$(mktemp)

# Trim trailing newlines from the input file
sed ':a;N;$!ba;s/\n*$//' "$xml_file" | sed '/<\/ActivatedNumbers>/d' > "$trimmed_file"


# Use awk to split the file
awk -v maxRecs="$max_records" -v prefix="$file_prefix" -v ext="$file_extension" '
BEGIN {
    recNr = 0;
    fileNr = 0;
    file_open = 0;
    insideRoot = 0;
}
function start_new_file() {
    if (file_open) {
        if (insideRoot) {
            print "</CRDBData>" >> out;
        }
        close(out);
    }
    fileNr++;
    out = prefix fileNr ext;
    print "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" > out;
    print "<CRDBData>" >> out;
    file_open = 1;
    insideRoot = 1;
}
{
    if ($0 ~ /<CRDBData>/) {
        insideRoot = 1;
    }
    if ($0 ~ /<ActivatedNumber>/) {
        if (recNr % maxRecs == 0) {
            start_new_file();
        }
        recNr++;
    }
    if (file_open) {
        print >> out;
    }
}
END {
    if (file_open) {
        # Only add the closing tag if its not already present in the last chunk
        if (!insideRoot) {
            print "</CRDBData>" >> out;
        }
        close(out);
    }
}
' "$trimmed_file"

# Clean up the temporary file
rm "$trimmed_file"

echo "Splitting completed."
