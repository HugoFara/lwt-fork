#!/bin/bash

# Find files with tbpref but where the line either matches
#  * @global string $tbpref Database table prefix
#  global $tbpref;
#  global $tbpref, $debug;
# if the line is anything different, the file can't be updated, otherwise it can.

# Obligatory "I hate bash" note here.

# FILES=`find . -name '*.php' -print0 | xargs -0 grep -l tbpref 2>/dev/null`;
# echo $FILES
FILES=`echo ./edit_texts.php`


for f in $FILES; do
    # echo "$f"

    # echo "initial grep"
    # grep "\$tbpref" $f
    # echo "----------------"
    LINES=$(grep "\$tbpref" $f | sed -Ee 's/^[[:space:]]*//g' | sed -Ee 's/[[:space:]]*$//g' | sort | uniq)

    HASBADLINE=0
    BADLINE=""
    while IFS= read -r line; do
        # echo "=> $line"
        OK=0
        if [[ "$line" =~ "@global string $tbpref" ]]; then
            OK=1
        fi
        if [[ "$line" =~ "global $tbpref" ]]; then
            OK=1
        fi
        if [[ "$line" =~ "global $tbpref, $debug" ]]; then
            OK=1
        fi

        if [[ $OK -eq 0 ]]; then
            BADLINE="$line"
            HASBADLINE=1
        fi
    done <<< "$LINES"

    if [[ $HASBADLINE -eq 0 ]]; then
        echo "Cleaning ${f}"
        sed -i "" "s/^[[:space:]]*global \$tbpref;[[:space:]]*$//g" $f
    else
        echo "Skipping ${f}, has line ${BADLINE}"
    fi
done
