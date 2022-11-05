FILES=`find . -name '*.*' -print0 | xargs -0 grep -l tbpref 2>/dev/null`;
# FILES=`echo ./edit_words.php`

for f in $FILES; do
    echo "$f"

    for tbl in archivedtexts archtexttags feedlinks languages newsfeeds sentences settings tags tags2 temptextitems tempwords textitems2 texts texttags tts words wordtags; do
        echo "  $tbl"
        sed -i "" "s/' \. \$tbpref \. '$tbl/$tbl/g" $f
        sed -i "" "s/\" \. \$tbpref \. \"$tbl/$tbl/g" $f
        sed -i "" "s/{\$tbpref}$tbl/$tbl/g" $f
    done
done
