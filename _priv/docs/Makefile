TOC_FILE=toc.xml
DOC_FILES=\
	include/*.php \
	include/data/*.php \
	include/models/*.php \
	include/controllers/*.php

srcdir=../../

all: html

html:
	srcdir=$(srcdir) ./gendocs.rb --toc "$(TOC_FILE)" --html $(DOC_FILES)
	
xml:
	srcdir=$(srcdir) ./gendocs.rb --toc "$(TOC_FILE)" --xml $(DOC_FILES)

clean:
	rm -f api/html/*.html
