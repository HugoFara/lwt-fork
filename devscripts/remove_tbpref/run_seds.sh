# set -e
# set -x

FILES=`find . -name '*.php' -print0 | xargs -0 grep -l tbpref 2>/dev/null`;
# FILES=`echo ./inc/database_connect.php`
echo $FILES

for f in $FILES; do
    echo "$f"

    for tbl in archivedtexts archtexttags db_to_mecab feedlinks languages mecab merge_words newsfeeds numbers sentences settings tags tags2 temptextitems tempwords textitems textitems2 texts texttags tts words wordtags; do
        # echo "  $tbl"
        sed -i "" "s/' \. \$tbpref \. '$tbl/$tbl/g" $f
        sed -i "" "s/\" \. \$tbpref \. \"$tbl/$tbl/g" $f
        sed -i "" "s/{\$tbpref}$tbl/$tbl/g" $f
        sed -i "" "s/in_array(\$tbpref \. '$tbl', \$tables)/in_array('$tbl', \$tables)/g" $f
        sed -i "" "s/FROM ' \. \$tbpref \. \$table/FROM ' . \$table/g" $f
        sed -i "" "s/\$tbpref \. '$tbl/$tbl/g" $f
        sed -i "" "s/\$tbpref \. \"$tbl/$tbl/g" $f
    done
done
