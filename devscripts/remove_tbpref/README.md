Get rid of bad code.

These scripts work on a Mac, probably not other *nix systems, def not windows.

Run them from root dir

# Sample sed

sed -i "" "s/' \. \$tbpref \. 'words/words/g" ./insert_word_wellknown.php

# Scripts

## Find all tbprefs

devscripts/remove_tbpref/find_tbpref.sh

## save them to a file **outside of the proj dir**

devscripts/remove_tbpref/find_tbpref.sh > ../find_tbpref_results.txt

## Get the current counts

devscripts/remove_tbpref/find_tbpref.sh | wc

## Delete

devscripts/remove_tbpref/run_seds.sh 
