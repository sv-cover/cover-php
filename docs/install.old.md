# Old installation instructions

## Running locally

To run the Cover site locally, you can follow the instructions in [quick_install.md](quick_install.md). This guide has been written for Ubuntu 18.04 (stand alone or on Windows Subsystem for Linux).

### Dependencies
To run the Cover site you need a webserver with PHP (at least 7.0 I guess) compiled with imagick, libgd and PostgresSQL support. You will also need a PostgresSQL database. (8.2, 9.3 and 10 all seem to work so I guess it doesn't really matter which version.)

To get all the dependencies, run composer in the root directory of your repository. There should be a file named `composer.json` in there:

```bash
composer install
```
### Configure webserver
The router requires some configuration for your webserver. Instructions for this are provided in the [Symfony Documentation](https://symfony.com/doc/current/setup/web_server_configuration.html0).

## Setting up the database
Copy the contents of the file `config/DBIds.php.default` file to a file named `config/DBIds.php` and input your own database configuration data.

Do the same for `config/config.inc.default`. Copy its contents to `config/config.inc` and adjust the values where needed.

### Set up a bare database
Run the `data/structure.sql` script on your database. This should give you the basic database structure and content necessary to run the website:

```bash
createdb --encoding=UTF8 --template=template0 webcie 
cat data/structure.sql | psql webcie
```

That should be it, the website should work now. You can log in with:  
email: `user@example.com`  
password: `password`

### Use barbone database
Running the `data/webcie-minimal.sql` script on your database will install a barebone database with some data but no personal data. It contains a user with the email address `test@svcover.nl` and ID 1. You can set a password with the `bin\set-password.php` script.

```bash
createdb --encoding=UTF8 --template=template0 webcie 
psql webcie < data/webcie-minimal.sql
```

### Copy the database of the live site
This is only applicable for members of the AC/DCee. You can easily clone the live database using the following command. Make sure you don't have to enter your password by setting up public key authentication first.

```bash
createdb --encoding=UTF8 --template=template0 webcie 
ssh -C webcie@svcover.nl "pg_dump webcie" | psql webcie
```

## Getting Face detection to work
Face detection makes use of OpenCV and Python and the python libraries numpy and psycopg2. Make sure those are installed. If that is done correctly, the python script in opt/facedetect should work without editing.

## Using Poedit with Twig templates

*NB: the website is currently set to use only one language, but still supports i18n to enable easy multi language setup should this be desired in the future. For now, you can probably ignore this.*

You can use Poedit to update the \*.po and \*.mo files with cover the English translation. To make Poedit scan the .twig-files as well, you'll have to add it to the list of scanners. The following settings will work (but will cause some non-fatal errors).

1. Create a Poedit project for your theme if you haven�t already, and make sure to add __ on the Sources keywords tab.
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
