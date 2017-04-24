# Cover website
This is the source code of the Cover website.

It is mostly undocumented and some parts are not very well written.

## License
Note that all the code in this repository is property of Cover. You are allowed to learn from it, play with it and contribute fixes and features to it. You are not allowed to use (parts of) the code or resources (e.g. documents, images) for other projects unrelated to Cover. Unless of course you contributed those parts to this project yourself in the first place.

## Security
Please, if you find a bug or security issue, please report it by making an issue on the Bitbucket repository or by notifying the AC/DCee at webcie@rug.nl

## Contribute
If you want to contribute code please fork this repository, create a new branch in which you implement your fix or feature, make sure it merges with the most up to date code in our master branch. (i.e. Just `git rebase master` when your master branch is in sync.) When the code is ready to be set live create a pull request and the WebCie will test and review your contribution.

## Running locally
```bash
docker build -t cover-webcie/cover-php .
docker run -p 5000:80 cover-webcie/cover-php
```

## Setting up the database
TODO set the proper environment variables

### Set up a bare database
Run the `include/data/structure.sql` script on your database. This should give you the basic database structure and content necessary to run the website:

```bash
createdb --encoding=UTF8 --template=template0 webcie 
cat include/data/structure.sql | psql webcie
```

### Copy the database of the live site
This is only applicable for members of the AC/DCee. You can easily clone the live database using the following command. Make sure you don't have to enter your password by setting up public key authentication first.

```bash
createdb --encoding=UTF8 --template=template0 webcie 
ssh -C webcie@svcover.nl "pg_dump webcie" | psql webcie
```

That should be it, the website should work now. You can log in with:  
email: `user@example.com`  
password: `password`

## Getting Face detection to work
Face detection makes use of OpenCV and Python and the python libraries numpy and psycopg2. Make sure those are installed. If that is done correctly, the python script in opt/facedetect should work without editing.

## Using Poedit with Twig templates
You can use Poedit to update the *.po and *.mo files with cover the English translation. To make Poedit scan the .twig-files as well, you'll have to add it to the list of scanners. The following settings will work (but will cause some non-fatal errors).

1. Create a Poedit project for your theme if you haven’t already, and make sure to add __ on the Sources keywords tab.
2. Go to Edit->Preferences.
3. On the Parsers tab, add a new parser with these settings:  
   Language: *Twig*  
   List of extensions: ``*.twig``  
   Parser command: ``xgettext --language=Python --add-comments=TRANSLATORS --force-po -o %o %C %K %F``  
   An item in keyword list: ``-k%k``  
   An item in input files list: ``%f``  
   Source code charset: ``--from-code=%c``

Save and Update!

Have fun!