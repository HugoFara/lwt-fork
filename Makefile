# Files to generate info.html
ONE_FILE_DOC = docs/info.php docs/CHANGELOG.md docs/database.md

install: composer.phar
	php composer.phar install

# Regenerate one-file documentation
info.html: $(ONE_FILE_DOC)
	echo "Regenerating info.html"
	php docs/info.php > docs/info.html

# Regenerate all documentation
doc: $(ONE_FILE_DOC) Doxyfile
	php docs/info.php > docs/info.html
	doxygen Doxyfile
	./node_modules/.bin/jsdoc -c jsdoc.json
	php tools/phpDocumentor"

# Regenerate code documentation
code_doc: Doxyfile
	echo "Regenerating documentation"
	doxygen Doxyfile

# Regenerate minified JS & CSS (including themes)
minify: src/js/ src/css/ src/themes/
	php -r "require 'src/php/minifier.php'; minify_everything();"

# Do not minify for development version!
no-minify: src/js/ src/css/ src/themes/
	rm js/pgm.js
	cat src/js/third_party/* >> js/pgm.js
	cat src/js/*.js >> js/pgm.js
	cp -r src/css .
	cp -r src/themes .

# Clear documentation
clean-doc:
	rm docs/info.html
	rm -rf docs/html

# Clear documentation, deprecated, use clean-doc instead
clean: clean-doc
